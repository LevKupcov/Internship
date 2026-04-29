<?php

declare(strict_types=1);

final class BitrixRestClient
{
    public function call(string $portalDomain, string $authId, string $method, array $params = []): array
    {
        $baseUrl = sprintf('https://%s/rest/%s.json?auth=%s', $portalDomain, $method, urlencode($authId));

        $ch = curl_init($baseUrl);
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_TIMEOUT => 15,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Bitrix24 REST request failed: ' . $curlError);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid REST response: ' . $raw);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('Bitrix24 REST HTTP error: ' . $httpCode);
        }

        if (isset($decoded['error'])) {
            throw new RuntimeException('Bitrix24 REST error: ' . $decoded['error']);
        }

        return $decoded;
    }
}
