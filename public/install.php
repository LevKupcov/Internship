<?php

declare(strict_types=1);

/**
 * Обработчик установки Bitrix24 (ONAPPINSTALL) и редирект на UI без события.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once ENRICHER_SRC . '/BitrixRestClient.php';

$eventName = mb_strtoupper(trim((string)($_REQUEST['event'] ?? $_REQUEST['EVENT'] ?? '')));
if ($eventName !== 'ONAPPINSTALL') {
    // When this URL is opened as app page/placement, render UI.
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/index.php';
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$portalDomain = trim((string)($_REQUEST['DOMAIN'] ?? ''));
$authId = trim((string)($_REQUEST['AUTH_ID'] ?? ''));
$accessToken = trim((string)($_REQUEST['ACCESS_TOKEN'] ?? ''));
$authToken = $authId !== '' ? $authId : $accessToken;

if ($portalDomain === '' || $authToken === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'error' => 'DOMAIN and AUTH token are required for ONAPPINSTALL.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$isLocalHost = stripos($host, 'localhost') !== false || str_starts_with($host, '127.0.0.1');
$scheme = $isLocalHost ? 'http' : 'https';
$publicBasePath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/public/install.php'))), '/');
$appUrl = sprintf('%s://%s%s/index.php', $scheme, $host, $publicBasePath);

try {
    $client = new BitrixRestClient();
    $bindParams = [
        'PLACEMENT' => 'CRM_COMPANY_DETAIL_TAB',
        'HANDLER' => $appUrl,
        'TITLE' => 'Enricher',
        'DESCRIPTION' => 'Обогащение карточки компании',
        'GROUP_NAME' => 'Company tools',
    ];

    $tokensToTry = array_values(array_unique(array_filter([$authId, $accessToken], static fn($v) => trim((string)$v) !== '')));
    $bindResult = null;
    $attemptErrors = [];

    foreach ($tokensToTry as $token) {
        try {
            // Prevent duplicated tabs on repeated installs.
            try {
                $client->call(
                    $portalDomain,
                    (string)$token,
                    'placement.unbind',
                    [
                        'PLACEMENT' => 'CRM_COMPANY_DETAIL_TAB',
                        'HANDLER' => $appUrl,
                    ]
                );
            } catch (Throwable $ignored) {
                // Ignore if placement did not exist yet.
            }

            $bindResult = $client->call(
                $portalDomain,
                (string)$token,
                'placement.bind',
                $bindParams
            );
            break;
        } catch (Throwable $inner) {
            $attemptErrors[] = $inner->getMessage();
        }
    }

    if ($bindResult === null) {
        // Do not fail install flow hard: app can still work
        // when opened from handler URL or manual placement setup.
        echo json_encode([
            'ok' => true,
            'warning' => 'Install completed with warning: placement.bind was not authorized.',
            'appUrl' => $appUrl,
            'portalDomain' => $portalDomain,
            'placementResult' => null,
            'attemptErrors' => $attemptErrors,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Placement bound successfully',
        'appUrl' => $appUrl,
        'portalDomain' => $portalDomain,
        'placementResult' => $bindResult,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    // Keep installation resilient: return warning instead of hard error.
    echo json_encode([
        'ok' => true,
        'warning' => 'Install completed with warning',
        'details' => $e->getMessage(),
        'debug' => [
            'portalDomain' => $portalDomain,
            'hasAuthId' => $authId !== '',
            'hasAccessToken' => $accessToken !== '',
            'appUrl' => $appUrl,
            'tokensTried' => array_values(array_unique(array_filter([$authId !== '' ? 'AUTH_ID' : '', $accessToken !== '' ? 'ACCESS_TOKEN' : '']))),
        ],
    ], JSON_UNESCAPED_UNICODE);
}
