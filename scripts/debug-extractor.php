<?php

declare(strict_types=1);

/**
 * CLI: отладка SiteProfileExtractor без Bitrix.
 * Пример: php scripts/debug-extractor.php consult-info.ru
 */
require_once __DIR__ . '/../bootstrap.php';
require_once ENRICHER_SRC . '/SiteProfileExtractor.php';
$domain = $argv[1] ?? 'consult-info.ru';
$preferred = $argv[2] ?? '';
$extractor = new SiteProfileExtractor();
$result = $extractor->extract((string)$domain, (string)$preferred);

echo json_encode([
    'domain' => $domain,
    'emails' => $result['emails'] ?? [],
    'phones' => $result['phones'] ?? [],
    'department_contacts' => $result['department_contacts'] ?? '',
    'crawledUrls' => $result['crawledUrls'] ?? [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;

