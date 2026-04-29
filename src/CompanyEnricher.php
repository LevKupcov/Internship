<?php

declare(strict_types=1);

final class CompanyEnricher
{
    /**
     * Возвращает базовый mock-профиль компании.
     * На следующем шаге сюда добавится:
     * - загрузка сайта;
     * - извлечение фактов;
     * - нормализация через AI Bitrix24.
     */
    public function enrichByDomain(string $domain): array
    {
        $normalizedDomain = $this->normalizeDomain($domain);
        $companyName = $this->guessCompanyName($normalizedDomain);

        return [
            'TITLE' => $companyName,
            'WEB' => "https://{$normalizedDomain}",
            'EMAIL' => "info@{$normalizedDomain}",
            'PHONE' => '',
            'INDUSTRY' => 'Не определено',
            'ADDRESS_CITY' => '',
            'COMMENTS' => 'Черновой результат обогащения. Требует подтверждения менеджером.',
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

    private function guessCompanyName(string $domain): string
    {
        $firstPart = explode('.', $domain)[0] ?? 'company';
        $firstPart = str_replace(['-', '_'], ' ', $firstPart);

        return mb_convert_case($firstPart, MB_CASE_TITLE, 'UTF-8');
    }
}
