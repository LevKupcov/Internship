<?php

declare(strict_types=1);

/**
 * JSON API обогащения компании по домену сайта.
 * Вызывается из public/index.php / app.js (POST, тело: { domain, aiContext?, contactUrl? }).
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once ENRICHER_SRC . '/CompanyEnricher.php';
require_once ENRICHER_SRC . '/SiteProfileExtractor.php';
require_once ENRICHER_SRC . '/AiProfileNormalizer.php';
require_once ENRICHER_SRC . '/BitrixAiMapper.php';
require_once ENRICHER_SRC . '/BitrixRestClient.php';
require_once ENRICHER_SRC . '/EnrichmentHistoryLogger.php';

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '{}', true);

$domain = trim((string)($payload['domain'] ?? ''));
$contactUrl = trim((string)($payload['contactUrl'] ?? ''));
if ($domain === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Domain is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

function enrichByRenderedBrowserFallback(string $domain, string $contactUrl = ''): array
{
    $scriptPath = realpath(ENRICHER_ROOT . '/scripts/render-contact-data.js');
    if (!is_string($scriptPath) || $scriptPath === '') {
        return [];
    }

    $cmdParts = [
        'node',
        escapeshellarg($scriptPath),
        escapeshellarg($domain),
        escapeshellarg($contactUrl),
    ];
    $command = implode(' ', $cmdParts);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = @proc_open($command, $descriptors, $pipes, dirname($scriptPath));
    if (!is_resource($process)) {
        return [];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $start = microtime(true);
    // Keep a hard limit below PHP max_execution_time, but allow
    // heavier pages to finish and return richer contact data.
    $timeoutSec = 45.0;
    $terminated = false;

    while (true) {
        $status = proc_get_status($process);
        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';

        if (!($status['running'] ?? false)) {
            break;
        }

        if ((microtime(true) - $start) >= $timeoutSec) {
            @proc_terminate($process);
            $terminated = true;
            break;
        }

        usleep(100000);
    }

    $stdout .= stream_get_contents($pipes[1]) ?: '';
    $stderr .= stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($terminated || $exitCode !== 0 || trim($stdout) === '') {
        return [];
    }

    // Some environments can prepend warnings/logs; keep the last JSON object.
    $raw = trim($stdout);
    $lines = preg_split('/\R/u', $raw) ?: [];
    $jsonCandidate = trim((string)end($lines));
    if ($jsonCandidate === '' || ($jsonCandidate[0] ?? '') !== '{') {
        $jsonCandidate = $raw;
    }

    $decoded = json_decode($jsonCandidate, true);
    if (!is_array($decoded) || !($decoded['ok'] ?? false)) {
        return [];
    }

    return [
        'DEPARTMENT_CONTACTS' => trim((string)($decoded['departmentContacts'] ?? '')),
        'COMMENTS' => trim((string)($decoded['description'] ?? '')),
    ];
}

function isUsefulComment(string $comment): bool
{
    $comment = trim($comment);
    if ($comment === '' || mb_strlen($comment) < 60) {
        return false;
    }

    $badMarkers = [
        'расписание', 'афиша', 'кассы работают', 'previous', 'next',
        'купить билет', 'акции и скидки', 'трейлер', 'в кино',
    ];
    $lower = mb_strtolower($comment);
    $hits = 0;
    foreach ($badMarkers as $marker) {
        if (mb_strpos($lower, $marker) !== false) {
            $hits++;
        }
    }

    return $hits < 3;
}

function departmentContactsScore(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $parts = array_values(array_filter(array_map('trim', explode('|', $value))));
    if ($parts === []) {
        return 0;
    }

    $score = count($parts) * 10;
    foreach ($parts as $part) {
        if (str_contains($part, '@')) {
            $score += 8;
        }
        if (
            preg_match('/\b(акци|promo|реклам|marketing|поддерж|support|продаж|sales|касс|ticket)\b/ui', $part) === 1
        ) {
            $score += 4;
        }
    }

    return $score;
}

/**
 * @return array{promo:string,ads:string,support:string}
 */
function parseDepartmentContactsInline(string $departmentContacts): array
{
    $result = ['promo' => '', 'ads' => '', 'support' => ''];
    $parts = array_map('trim', explode('|', $departmentContacts));
    foreach ($parts as $part) {
        if ($part === '' || !str_contains($part, ':')) {
            continue;
        }
        [$labelRaw, $valueRaw] = array_map('trim', explode(':', $part, 2));
        $label = mb_strtolower($labelRaw);
        $value = trim($valueRaw);
        if ($value === '') {
            continue;
        }

        if ($result['promo'] === '' && (str_contains($label, 'акц') || str_contains($label, 'promo'))) {
            $result['promo'] = $value;
            continue;
        }
        if ($result['ads'] === '' && (str_contains($label, 'реклам') || str_contains($label, 'marketing') || str_contains($label, 'media') || $label === 'pr')) {
            $result['ads'] = $value;
            continue;
        }
        if ($result['support'] === '' && (str_contains($label, 'поддерж') || str_contains($label, 'support') || str_contains($label, 'help'))) {
            $result['support'] = $value;
        }
    }

    return $result;
}

function fetchHtmlQuick(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'follow_location' => 1,
            'max_redirects' => 5,
            'header' => "User-Agent: Mozilla/5.0 (Bitrix24 Enricher Bot)\r\nAccept: text/html,*/*\r\n",
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (is_string($raw) && $raw !== '') {
        return $raw;
    }

    if (!function_exists('curl_init')) {
        return '';
    }
    $ch = curl_init($url);
    if ($ch === false) {
        return '';
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Bitrix24 Enricher Bot)',
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($body) || $body === '' || $code >= 400) {
        return '';
    }

    return $body;
}

function normalizeDomainForEmailLookup(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $raw) === 1) {
        $host = parse_url($raw, PHP_URL_HOST);

        return $host !== null && $host !== '' ? mb_strtolower((string)$host) : '';
    }
    $slash = strpos($raw, '/');
    if ($slash !== false) {
        return mb_strtolower(substr($raw, 0, $slash));
    }

    return mb_strtolower($raw);
}

function findEmailFallbackByDomain(string $domain): string
{
    $domain = mb_strtolower(trim($domain));
    if ($domain === '') {
        return '';
    }
    $hosts = [$domain];
    if (!str_starts_with($domain, 'www.')) {
        $hosts[] = 'www.' . $domain;
    }
    $paths = ['/', '/contacts', '/contacts/', '/contact', '/kontakty', '/about'];

    $candidates = [];
    foreach ($hosts as $host) {
        foreach ($paths as $path) {
            $html = fetchHtmlQuick('https://' . $host . $path);
            if ($html === '') {
                continue;
            }
            $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $decoded, $m);
            foreach (($m[0] ?? []) as $emailRaw) {
                $email = mb_strtolower(trim((string)$emailRaw));
                if ($email === '' || !str_contains($email, '@')) {
                    continue;
                }
                $emailDomain = (string)substr(strrchr($email, '@') ?: '', 1);
                if ($emailDomain !== $domain && !str_ends_with($emailDomain, '.' . $domain)) {
                    continue;
                }
                if (preg_match('/\.(png|jpg|jpeg|svg|webp|gif|css|js)$/i', $email) === 1) {
                    continue;
                }
                if (!isset($candidates[$email])) {
                    $candidates[$email] = 0;
                }
                $score = 1;
                if (preg_match('/^(sales|info|contact|support|help|mail|office|reklama|reklam)@/i', $email) === 1) {
                    $score += 4;
                }
                $candidates[$email] += $score;
            }
        }
    }

    if ($candidates === []) {
        return '';
    }
    arsort($candidates);

    return (string)array_key_first($candidates);
}

try {
    $configPath = ENRICHER_CONFIG . '/config.php';
    $config = file_exists($configPath) ? (array)require $configPath : [];

    $aiContext = [
        'portalDomain' => trim((string)($payload['aiContext']['portalDomain'] ?? '')),
        'authToken' => trim((string)($payload['aiContext']['authToken'] ?? '')),
    ];

    $enricher = new CompanyEnricher(
        new SiteProfileExtractor(),
        new AiProfileNormalizer(),
        new BitrixAiMapper(new BitrixRestClient()),
        $config
    );
    $result = $enricher->enrichByDomain($domain, $aiContext);

    // JS-rendered fallback for websites where contacts are client-side rendered.
    if (($result['DEPARTMENT_CONTACTS'] ?? '') === '' || ($result['COMMENTS'] ?? '') === '') {
        $rendered = enrichByRenderedBrowserFallback($domain, $contactUrl);
        $renderedDept = trim((string)($rendered['DEPARTMENT_CONTACTS'] ?? ''));
        if ($renderedDept !== '') {
            $currentDept = trim((string)($result['DEPARTMENT_CONTACTS'] ?? ''));
            if (departmentContactsScore($renderedDept) > departmentContactsScore($currentDept)) {
                $result['DEPARTMENT_CONTACTS'] = $renderedDept;
                $deptMap = parseDepartmentContactsInline($renderedDept);
                if ($deptMap['promo'] !== '') {
                    $result['DEPT_PROMO_CONTACT'] = $deptMap['promo'];
                }
                if ($deptMap['ads'] !== '') {
                    $result['DEPT_ADS_CONTACT'] = $deptMap['ads'];
                }
                if ($deptMap['support'] !== '') {
                    $result['DEPT_SUPPORT_CONTACT'] = $deptMap['support'];
                }
            }
        }
        // Do not auto-fill COMMENTS from browser fallback, because
        // dynamic cinema pages often produce noisy marketing text.
    }

    $result = $enricher->dedupeSuggestedContacts($result);

    if (trim((string)($result['EMAIL'] ?? '')) === '') {
        $emailHost = normalizeDomainForEmailLookup($domain);
        if ($emailHost !== '') {
            $emailFallback = findEmailFallbackByDomain($emailHost);
            if ($emailFallback !== '') {
                $result['EMAIL'] = $emailFallback;
            }
        }
    }

    $logger = new EnrichmentHistoryLogger(ENRICHER_STORAGE . '/logs/enrichment-history.jsonl');
    $logger->write([
        'ts' => date('c'),
        'domain' => $domain,
        'suggestedFields' => $result,
        'userAgent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'remoteAddr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        'hasAiContext' => $aiContext['portalDomain'] !== '' && $aiContext['authToken'] !== '',
    ]);

    echo json_encode([
        'ok' => true,
        'domain' => $domain,
        'suggestedFields' => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Enrichment failed',
        'details' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
