<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/BitrixRestClient.php';

$portalDomain = trim((string)($_REQUEST['DOMAIN'] ?? ''));
$authId = trim((string)($_REQUEST['AUTH_ID'] ?? ''));

if ($portalDomain === '' || $authId === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'error' => 'DOMAIN and AUTH_ID are required in install payload.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$appUrl = sprintf('%s://%s/work/b24-company-enricher/public/index.php', $scheme, $_SERVER['HTTP_HOST'] ?? 'localhost');

try {
    $client = new BitrixRestClient();

    $bindResult = $client->call(
        $portalDomain,
        $authId,
        'placement.bind',
        [
            'PLACEMENT' => 'CRM_COMPANY_DETAIL_TAB',
            'HANDLER' => $appUrl,
            'TITLE' => 'Обогатить',
            'DESCRIPTION' => 'AI-обогащение карточки компании',
            'GROUP_NAME' => 'AI tools',
        ]
    );

    echo json_encode([
        'ok' => true,
        'message' => 'Placement bound successfully',
        'appUrl' => $appUrl,
        'placementResult' => $bindResult,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Install failed',
        'details' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
