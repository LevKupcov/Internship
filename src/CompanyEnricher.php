<?php

declare(strict_types=1);

final class CompanyEnricher
{
    public function enrichByDomain(string $domain): array
    {
        $normalizedDomain = $this->normalizeDomain($domain);
        $baseUrl = 'https://' . $normalizedDomain;
        $discovery = $this->discoverContactsPage($baseUrl);
        $contactsData = $this->extractContactsData($discovery['html'], $discovery['url']);
        $comments = $this->buildComments($contactsData, $discovery['url']);

        return [
            'TITLE' => $contactsData['fields']['company_name']['value'] ?? $this->guessCompanyName($normalizedDomain),
            'WEB' => $baseUrl,
            'EMAIL' => $contactsData['fields']['email']['value'] ?? '',
            'PHONE' => $contactsData['fields']['phone']['value'] ?? '',
            'INDUSTRY' => 'Не определено',
            'ADDRESS_CITY' => $this->extractCity($contactsData['fields']['address']['value'] ?? ''),
            'COMMENTS' => $comments,
            '_verification' => [
                'sourceUrl' => $discovery['url'],
                'contactsPageFound' => $discovery['contacts_page_found'],
                'fields' => $contactsData['fields'],
                'conflicts' => $contactsData['conflicts'],
                'needsReview' => !empty($contactsData['conflicts']),
            ],
        ];
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#i', '', trim($domain)) ?? '';
        $domain = trim($domain, "/ \t\n\r\0\x0B");

        if ($domain === '') {
            throw new InvalidArgumentException('Invalid domain');
        }

        return mb_strtolower($domain);
    }

    /**
     * @return array{url:string, html:string, contacts_page_found:bool}
     */
    private function discoverContactsPage(string $baseUrl): array
    {
        $homeHtml = $this->fetchUrl($baseUrl);
        $homeLinks = $this->extractLinks($homeHtml, $baseUrl);

        $candidates = [];
        foreach ($homeLinks as $link) {
            $normalized = mb_strtolower($link['text'] . ' ' . $link['href']);
            if (
                str_contains($normalized, 'контакт')
                || str_contains($normalized, 'contact')
                || str_contains($normalized, 'kontakt')
            ) {
                $candidates[] = $link['absolute'];
            }
        }

        $fallbackPaths = [
            '/contacts',
            '/contact',
            '/kontakty',
            '/contacts/',
            '/about/contacts',
        ];
        foreach ($fallbackPaths as $path) {
            $candidates[] = rtrim($baseUrl, '/') . $path;
        }

        $visited = [];
        foreach ($candidates as $candidate) {
            if (isset($visited[$candidate])) {
                continue;
            }
            $visited[$candidate] = true;

            try {
                $html = $this->fetchUrl($candidate);
                if ($this->looksLikeContactPage($html)) {
                    return [
                        'url' => $candidate,
                        'html' => $html,
                        'contacts_page_found' => true,
                    ];
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return [
            'url' => $baseUrl,
            'html' => $homeHtml,
            'contacts_page_found' => false,
        ];
    }

    private function fetchUrl(string $url): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Cannot initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'B24CompanyEnricher/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $httpCode >= 400) {
            throw new RuntimeException('Failed to fetch URL: ' . $url . ' (' . $error . ')');
        }

        return (string)$body;
    }

    /**
     * @return array<int, array{text:string, href:string, absolute:string}>
     */
    private function extractLinks(string $html, string $baseUrl): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $links = [];
        foreach ($dom->getElementsByTagName('a') as $node) {
            $href = trim((string)$node->getAttribute('href'));
            if ($href === '' || str_starts_with($href, 'javascript:') || str_starts_with($href, '#')) {
                continue;
            }

            $text = trim((string)$node->textContent);
            $absolute = $this->toAbsoluteUrl($href, $baseUrl);

            if ($absolute === '') {
                continue;
            }

            $links[] = [
                'text' => $text,
                'href' => $href,
                'absolute' => $absolute,
            ];
        }

        return $links;
    }

    private function toAbsoluteUrl(string $href, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $href) === 1) {
            return $href;
        }

        $parsed = parse_url($baseUrl);
        if (!is_array($parsed) || !isset($parsed['scheme'], $parsed['host'])) {
            return '';
        }

        $scheme = $parsed['scheme'];
        $host = $parsed['host'];

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }

        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $href;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }

    private function looksLikeContactPage(string $html): bool
    {
        $text = mb_strtolower($this->htmlToText($html));

        $signals = ['контакт', 'contact', 'телефон', 'phone', 'email', 'адрес', 'address'];
        $hits = 0;
        foreach ($signals as $signal) {
            if (str_contains($text, $signal)) {
                $hits++;
            }
        }

        return $hits >= 2;
    }

    /**
     * @return array{
     *   fields: array<string, array{value:?string, confidence:float, source:string}>,
     *   conflicts: array<int, string>
     * }
     */
    private function extractContactsData(string $html, string $sourceUrl): array
    {
        $text = $this->htmlToText($html);
        $conflicts = [];

        $phones = $this->extractPhones($text);
        $emails = $this->extractEmails($text);
        $inns = $this->extractByPattern('/\b(?:ИНН|INN)\s*[:#]?\s*(\d{10,12})\b/ui', $text);
        $ogrns = $this->extractByPattern('/\b(?:ОГРН|OGRN)\s*[:#]?\s*(\d{13})\b/ui', $text);
        $kpps = $this->extractByPattern('/\b(?:КПП|KPP)\s*[:#]?\s*(\d{9})\b/ui', $text);
        $companyNames = $this->extractCompanyNames($html);
        $address = $this->extractAddress($text);

        if (count($phones) > 1) {
            $conflicts[] = 'Найдено несколько телефонов: ' . implode(', ', array_slice($phones, 0, 3));
        }
        if (count($emails) > 1) {
            $conflicts[] = 'Найдено несколько email: ' . implode(', ', array_slice($emails, 0, 3));
        }

        return [
            'fields' => [
                'company_name' => $this->buildField($companyNames[0] ?? null, $sourceUrl, count($companyNames)),
                'address' => $this->buildField($address, $sourceUrl, $address === null ? 0 : 1),
                'phone' => $this->buildField($phones[0] ?? null, $sourceUrl, count($phones)),
                'email' => $this->buildField($emails[0] ?? null, $sourceUrl, count($emails)),
                'inn' => $this->buildField($inns[0] ?? null, $sourceUrl, count($inns)),
                'ogrn' => $this->buildField($ogrns[0] ?? null, $sourceUrl, count($ogrns)),
                'kpp' => $this->buildField($kpps[0] ?? null, $sourceUrl, count($kpps)),
            ],
            'conflicts' => $conflicts,
        ];
    }

    private function htmlToText(string $html): string
    {
        $stripped = strip_tags($html);
        $decoded = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', $decoded) ?? '';

        return trim($normalized);
    }

    /**
     * @return array<int, string>
     */
    private function extractPhones(string $text): array
    {
        preg_match_all('/(?:(?:\+|8)\s?[\d\-\(\)\s]{9,16}\d)/u', $text, $matches);
        $phones = array_map(static fn (string $v): string => trim($v), $matches[0] ?? []);
        $phones = array_filter($phones, static fn (string $v): bool => preg_match('/\d{10,}/', preg_replace('/\D+/', '', $v) ?? '') === 1);

        return array_values(array_unique($phones));
    }

    /**
     * @return array<int, string>
     */
    private function extractEmails(string $text): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $matches);
        $emails = array_map(static fn (string $v): string => mb_strtolower(trim($v)), $matches[0] ?? []);

        return array_values(array_unique($emails));
    }

    /**
     * @return array<int, string>
     */
    private function extractByPattern(string $pattern, string $text): array
    {
        preg_match_all($pattern, $text, $matches);
        $values = array_map(static fn (string $v): string => trim($v), $matches[1] ?? []);

        return array_values(array_unique($values));
    }

    /**
     * @return array<int, string>
     */
    private function extractCompanyNames(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $candidates = [];
        foreach (['//h1', '//title', '//meta[@property="og:site_name"]/@content'] as $query) {
            $nodes = $xpath->query($query);
            if ($nodes === false) {
                continue;
            }
            foreach ($nodes as $node) {
                $value = trim((string)$node->nodeValue);
                if ($value !== '') {
                    $candidates[] = preg_replace('/\s+/u', ' ', $value) ?? $value;
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    private function extractAddress(string $text): ?string
    {
        if (preg_match('/(?:адрес|address)\s*[:\-]?\s*([^\.]{10,200})/ui', $text, $m) === 1) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * @return array{value:?string, confidence:float, source:string}
     */
    private function buildField(?string $value, string $sourceUrl, int $candidateCount): array
    {
        if ($value === null || $value === '') {
            return [
                'value' => null,
                'confidence' => 0.0,
                'source' => $sourceUrl,
            ];
        }

        $confidence = 0.95;
        if ($candidateCount > 1) {
            $confidence = 0.65;
        }

        return [
            'value' => $value,
            'confidence' => $confidence,
            'source' => $sourceUrl,
        ];
    }

    private function extractCity(string $address): string
    {
        if ($address === '') {
            return '';
        }

        if (preg_match('/\bг\.?\s*([А-ЯA-ZЁ][А-ЯA-ZЁа-яa-z\- ]+)/u', $address, $m) === 1) {
            return trim($m[1]);
        }

        return '';
    }

    private function buildComments(array $contactsData, string $sourceUrl): string
    {
        $parts = [];
        $parts[] = 'Проверка выполнена по странице: ' . $sourceUrl;
        $parts[] = 'Данные извлечены без генерации отсутствующих полей.';

        if (!empty($contactsData['conflicts'])) {
            $parts[] = 'Обнаружены конфликты: ' . implode(' | ', $contactsData['conflicts']);
        } else {
            $parts[] = 'Конфликты не обнаружены.';
        }

        return implode(' ', $parts);
    }

    private function guessCompanyName(string $domain): string
    {
        $firstPart = explode('.', $domain)[0] ?? 'company';
        $firstPart = str_replace(['-', '_'], ' ', $firstPart);

        return mb_convert_case($firstPart, MB_CASE_TITLE, 'UTF-8');
    }
}
