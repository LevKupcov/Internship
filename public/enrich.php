<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/CompanyEnricher.php';

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '{}', true);

$domain = trim((string)($payload['domain'] ?? ''));
if ($domain === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Domain is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $enricher = new CompanyEnricher();
    $result = $enricher->enrichByDomain($domain);

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
