<?php

declare(strict_types=1);

/**
 * REST для сохранения/загрузки маппинга UF-полей (серверный JSON на портал).
 * Вызывается из public/app.js (GET/POST).
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
$storagePath = ENRICHER_STORAGE . '/mapping/mapping-store.json';

function readStore(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function writeStore(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents(
        $path,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

function buildPortalKey(?string $explicitPortal = null): string
{
    $explicit = trim((string)($explicitPortal ?? $_GET['portal'] ?? $_POST['portal'] ?? ''));
    if ($explicit !== '') {
        return mb_strtolower($explicit);
    }

    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin !== '') {
        $host = parse_url($origin, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return mb_strtolower($host);
        }
    }

    return 'default';
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $portalKey = buildPortalKey();
    $store = readStore($storagePath);
    echo json_encode([
        'ok' => true,
        'portal' => $portalKey,
        'mapping' => (array)($store[$portalKey] ?? []),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput ?: '{}', true);
    $mapping = $payload['mapping'] ?? null;
    $portalKey = buildPortalKey((string)($payload['portal'] ?? ''));

    if (!is_array($mapping)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Mapping must be object'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cleaned = [];
    foreach ($mapping as $fieldCode => $sourceKey) {
        if (!is_string($fieldCode) || !preg_match('/^UF_CRM_/i', $fieldCode)) {
            continue;
        }
        if (!is_string($sourceKey)) {
            continue;
        }

        $sourceKey = trim($sourceKey);
        if ($sourceKey !== '') {
            $cleaned[$fieldCode] = $sourceKey;
        }
    }

    $store = readStore($storagePath);
    $store[$portalKey] = $cleaned;
    writeStore($storagePath, $store);

    echo json_encode([
        'ok' => true,
        'portal' => $portalKey,
        'mapping' => $cleaned,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
