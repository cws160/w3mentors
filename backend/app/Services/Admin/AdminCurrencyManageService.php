<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminCurrencyManageService
{
    private const ACTIVE = 1;

    /** @return array<string, mixed> */
    public function form(int $currencyId): array
    {
        $currency = $currencyId > 0
            ? DB::table('tbl_currencies')->where('currency_id', $currencyId)->first()
            : null;

        if ($currencyId > 0 && ! $currency) {
            throw new \InvalidArgumentException('Currency not found');
        }

        $symbol = $currency ? (string) $currency->currency_symbol : '$';
        $isDefault = $currency ? (int) $currency->currency_is_default === 1 : false;

        return [
            'currency' => $currency ? (array) $currency : [
                'currency_id' => 0,
                'currency_code' => '',
                'currency_symbol' => '',
                'currency_positive_format' => '{currency_symbol}{currency_number}',
                'currency_negative_format' => '-{currency_symbol}{currency_number}',
                'currency_decimal_symbol' => '.',
                'currency_grouping_symbol' => ',',
                'currency_value' => 1,
                'currency_active' => 1,
            ],
            'is_default' => $isDefault,
            'options' => $this->formOptions($symbol),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $currencyId, int $langId): ?array
    {
        if ($currencyId < 1 || $langId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        if (! DB::table('tbl_currencies')->where('currency_id', $currencyId)->exists()) {
            return null;
        }

        $row = DB::table('tbl_currencies_lang')
            ->where('currencylang_currency_id', $currencyId)
            ->where('currencylang_lang_id', $langId)
            ->first(['currency_name']);

        return [
            'currency_id' => $currencyId,
            'lang_id' => $langId,
            'currency_name' => (string) ($row->currency_name ?? ''),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function setup(array $payload): int
    {
        $currencyId = (int) ($payload['currency_id'] ?? 0);
        $existing = $currencyId > 0
            ? DB::table('tbl_currencies')->where('currency_id', $currencyId)->first()
            : null;

        if ($currencyId > 0 && ! $existing) {
            throw new \InvalidArgumentException('Currency not found');
        }

        $isDefault = $existing && (int) $existing->currency_is_default === 1;

        $values = [
            'currency_symbol' => trim((string) ($payload['currency_symbol'] ?? '')),
            'currency_positive_format' => (string) ($payload['currency_positive_format'] ?? ''),
            'currency_negative_format' => (string) ($payload['currency_negative_format'] ?? ''),
            'currency_decimal_symbol' => (string) ($payload['currency_decimal_symbol'] ?? ''),
            'currency_grouping_symbol' => (string) ($payload['currency_grouping_symbol'] ?? ''),
            'currency_updated' => now()->format('Y-m-d H:i:s'),
        ];

        if (! $isDefault) {
            $values['currency_code'] = trim((string) ($payload['currency_code'] ?? ''));
            $values['currency_value'] = (float) ($payload['currency_value'] ?? 0);
            $values['currency_active'] = (int) ($payload['currency_active'] ?? 1);
        }

        if ($values['currency_symbol'] === '') {
            throw new \InvalidArgumentException('Currency symbol is required');
        }

        if (! $isDefault && $values['currency_code'] === '') {
            throw new \InvalidArgumentException('Currency code is required');
        }

        if (
            isset($values['currency_decimal_symbol'], $values['currency_grouping_symbol'])
            && $values['currency_decimal_symbol'] === $values['currency_grouping_symbol']
        ) {
            throw new \InvalidArgumentException('Decimal and grouping symbols must be different');
        }

        if (! $isDefault && $values['currency_value'] > 9999999999.99999999) {
            throw new \InvalidArgumentException('Currency conversion value is out of range');
        }

        if ($currencyId > 0) {
            if (
                ! $isDefault
                && (int) ($values['currency_active'] ?? 1) === 0
                && $this->siteCurrencyId() === $currencyId
            ) {
                throw new \InvalidArgumentException('Cannot change status of currency already in use');
            }

            DB::table('tbl_currencies')->where('currency_id', $currencyId)->update($values);

            return $currencyId;
        }

        $maxOrder = (int) DB::table('tbl_currencies')->max('currency_order');
        $values['currency_code'] = trim((string) ($payload['currency_code'] ?? ''));
        $values['currency_value'] = (float) ($payload['currency_value'] ?? 1);
        $values['currency_active'] = (int) ($payload['currency_active'] ?? 1);
        $values['currency_is_default'] = 0;
        $values['currency_order'] = $maxOrder + 1;

        return (int) DB::table('tbl_currencies')->insertGetId($values);
    }

    /** @param array<string, mixed> $payload */
    public function langSetup(array $payload): int
    {
        $currencyId = (int) ($payload['currency_id'] ?? 0);
        $langId = (int) ($payload['lang_id'] ?? 0);
        $name = trim((string) ($payload['currency_name'] ?? ''));

        if ($currencyId < 1 || $langId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        if ($name === '') {
            throw new \InvalidArgumentException('Currency name is required');
        }

        if (! DB::table('tbl_currencies')->where('currency_id', $currencyId)->exists()) {
            throw new \InvalidArgumentException('Currency not found');
        }

        $exists = DB::table('tbl_currencies_lang')
            ->where('currencylang_currency_id', $currencyId)
            ->where('currencylang_lang_id', $langId)
            ->exists();

        if ($exists) {
            DB::table('tbl_currencies_lang')
                ->where('currencylang_currency_id', $currencyId)
                ->where('currencylang_lang_id', $langId)
                ->update(['currency_name' => $name]);
        } else {
            DB::table('tbl_currencies_lang')->insert([
                'currencylang_currency_id' => $currencyId,
                'currencylang_lang_id' => $langId,
                'currency_name' => $name,
            ]);
        }

        return $currencyId;
    }

    public function changeStatus(int $currencyId, int $status): void
    {
        if ($currencyId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        if ($status === 0 && $this->siteCurrencyId() === $currencyId) {
            throw new \InvalidArgumentException('Cannot change status of currency already in use');
        }

        if (! DB::table('tbl_currencies')->where('currency_id', $currencyId)->exists()) {
            throw new \InvalidArgumentException('Currency not found');
        }

        DB::table('tbl_currencies')
            ->where('currency_id', $currencyId)
            ->update(['currency_active' => $status]);
    }

    /** @param array<int, int|string> $ids */
    public function updateOrder(array $ids): void
    {
        foreach (array_values($ids) as $order => $id) {
            $currencyId = (int) $id;
            if ($currencyId < 1) {
                continue;
            }
            DB::table('tbl_currencies')
                ->where('currency_id', $currencyId)
                ->update(['currency_order' => $order + 1]);
        }
    }

    /** @return array<string, mixed> */
    public function fixerConfig(): array
    {
        $config = $this->readFixerConfig();

        return [
            'api_key' => (string) ($config['api_key'] ?? ''),
            'status' => (int) (! empty($config['status']) ? 1 : 0),
            'info' => (string) ($config['info'] ?? ''),
            'last_synced' => (string) ($config['last_synced'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function setupFixerConfig(array $payload): array
    {
        $config = $this->readFixerConfig();
        $config['api_key'] = trim((string) ($payload['api_key'] ?? ''));
        $config['status'] = (int) ($payload['status'] ?? 0) === 1 ? 1 : 0;

        if ($config['status'] === 1 && $config['api_key'] === '') {
            throw new \InvalidArgumentException('Fixer API key is required');
        }

        DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_FIXER')
            ->update(['conf_val' => json_encode($config)]);

        if ($config['status'] === 1 && $config['api_key'] !== '') {
            $this->syncRates();
            $config = $this->readFixerConfig();
        }

        return $this->fixerConfig();
    }

    /** @return array<string, mixed> */
    public function syncRates(): array
    {
        $config = $this->readFixerConfig();
        if (empty($config['api_key'])) {
            throw new \InvalidArgumentException('Fixer is not configured');
        }
        if (empty($config['status'])) {
            throw new \InvalidArgumentException('Fixer is not active');
        }

        $baseCurrency = (string) DB::table('tbl_currencies')
            ->where('currency_is_default', 1)
            ->value('currency_code');

        if ($baseCurrency === '') {
            $baseCurrency = 'USD';
        }

        $codes = DB::table('tbl_currencies')->pluck('currency_code')->all();
        $response = Http::get('https://data.fixer.io/api/latest', [
            'access_key' => $config['api_key'],
            'base' => $baseCurrency,
            'symbols' => implode(',', $codes),
        ]);

        if (! $response->ok()) {
            throw new \InvalidArgumentException('Unable to sync currency rates');
        }

        $body = $response->json();
        if (! empty($body['error'])) {
            $message = is_array($body['error']) ? ($body['error']['info'] ?? 'Fixer API error') : (string) $body['error'];
            throw new \InvalidArgumentException($message);
        }

        $rates = $body['rates'] ?? null;
        if (! is_array($rates) || $rates === []) {
            throw new \InvalidArgumentException('Not able to sync currencies');
        }

        foreach ($rates as $code => $rate) {
            DB::table('tbl_currencies')
                ->where('currency_code', $code)
                ->update([
                    'currency_value' => $rate,
                    'currency_updated' => now()->format('Y-m-d H:i:s'),
                ]);
        }

        $config['last_synced'] = now()->format('Y-m-d H:i:s');
        DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_FIXER')
            ->update(['conf_val' => json_encode($config)]);

        return $this->fixerConfig();
    }

    /** @return array<int, array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', self::ACTIVE)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

  private function siteCurrencyId(): int
    {
        return (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_SITE_CURRENCY')
            ->value('conf_val');
    }

    /** @return array<string, mixed> */
    private function readFixerConfig(): array
    {
        $raw = DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_FIXER')
            ->value('conf_val');

        $config = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];

        return is_array($config) ? $config : [];
    }

    /** @return array<string, mixed> */
    private function formOptions(string $symbol): array
    {
        $sample = '6.66';

        return [
            'currency_codes' => $this->currencyCodes(),
            'positive_formats' => $this->formatOptions($this->positiveFormats(), $symbol, $sample),
            'negative_formats' => $this->formatOptions($this->negativeFormats(), $symbol, $sample),
            'decimal_symbols' => ['.' => '.', ',' => ','],
            'grouping_symbols' => ['.' => '.', ',' => ','],
            'status_options' => [
                ['value' => 1, 'label' => 'Active'],
                ['value' => 0, 'label' => 'Inactive'],
            ],
        ];
    }

    /** @param array<string, string> $formats */
    private function formatOptions(array $formats, string $symbol, string $number): array
    {
        $options = [];
        foreach ($formats as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => str_replace(['{currency_symbol}', '{currency_number}'], [$symbol, $number], $label),
            ];
        }

        return $options;
    }

    /** @return array<string, string> */
    private function positiveFormats(): array
    {
        return [
            '{currency_symbol}{currency_number}' => '{currency_symbol}{currency_number}',
            '{currency_number}{currency_symbol}' => '{currency_number}{currency_symbol}',
            '{currency_symbol} {currency_number}' => '{currency_symbol} {currency_number}',
            '{currency_number} {currency_symbol}' => '{currency_number} {currency_symbol}',
        ];
    }

    /** @return array<string, string> */
    private function negativeFormats(): array
    {
        return [
            '({currency_symbol}{currency_number})' => '({currency_symbol}{currency_number})',
            '-{currency_symbol}{currency_number}' => '-{currency_symbol}{currency_number}',
            '{currency_symbol}-{currency_number}' => '{currency_symbol}-{currency_number}',
            '{currency_symbol}{currency_number}-' => '{currency_symbol}{currency_number}-',
            '({currency_number}{currency_symbol})' => '({currency_number}{currency_symbol})',
            '-{currency_number}{currency_symbol}' => '-{currency_number}{currency_symbol}',
            '{currency_number}-{currency_symbol}' => '{currency_number}-{currency_symbol}',
            '{currency_number}{currency_symbol}-' => '{currency_number}{currency_symbol}-',
        ];
    }

    /** @return array<string, string> */
    private function currencyCodes(): array
    {
        return [
            'AED' => 'AED', 'AFN' => 'AFN', 'ALL' => 'ALL', 'AMD' => 'AMD', 'ANG' => 'ANG', 'AOA' => 'AOA',
            'ARS' => 'ARS', 'AUD' => 'AUD', 'AWG' => 'AWG', 'AZN' => 'AZN', 'BAM' => 'BAM', 'BBD' => 'BBD',
            'BDT' => 'BDT', 'BGN' => 'BGN', 'BHD' => 'BHD', 'BIF' => 'BIF', 'BMD' => 'BMD', 'BND' => 'BND',
            'BOB' => 'BOB', 'BRL' => 'BRL', 'BSD' => 'BSD', 'BTC' => 'BTC', 'BTN' => 'BTN', 'BWP' => 'BWP',
            'BYN' => 'BYN', 'BYR' => 'BYR', 'BZD' => 'BZD', 'CAD' => 'CAD', 'CDF' => 'CDF', 'CHF' => 'CHF',
            'CLF' => 'CLF', 'CLP' => 'CLP', 'CNY' => 'CNY', 'COP' => 'COP', 'CRC' => 'CRC', 'CUC' => 'CUC',
            'CUP' => 'CUP', 'CVE' => 'CVE', 'CZK' => 'CZK', 'DJF' => 'DJF', 'DKK' => 'DKK', 'DOP' => 'DOP',
            'DZD' => 'DZD', 'EGP' => 'EGP', 'ERN' => 'ERN', 'ETB' => 'ETB', 'EUR' => 'EUR', 'FJD' => 'FJD',
            'FKP' => 'FKP', 'GBP' => 'GBP', 'GEL' => 'GEL', 'GGP' => 'GGP', 'GHS' => 'GHS', 'GIP' => 'GIP',
            'GMD' => 'GMD', 'GNF' => 'GNF', 'GTQ' => 'GTQ', 'GYD' => 'GYD', 'HKD' => 'HKD', 'HNL' => 'HNL',
            'HRK' => 'HRK', 'HTG' => 'HTG', 'HUF' => 'HUF', 'IDR' => 'IDR', 'ILS' => 'ILS', 'IMP' => 'IMP',
            'INR' => 'INR', 'IQD' => 'IQD', 'IRR' => 'IRR', 'ISK' => 'ISK', 'JEP' => 'JEP', 'JMD' => 'JMD',
            'JOD' => 'JOD', 'JPY' => 'JPY', 'KES' => 'KES', 'KGS' => 'KGS', 'KHR' => 'KHR', 'KMF' => 'KMF',
            'KPW' => 'KPW', 'KRW' => 'KRW', 'KWD' => 'KWD', 'KYD' => 'KYD', 'KZT' => 'KZT', 'LAK' => 'LAK',
            'LBP' => 'LBP', 'LKR' => 'LKR', 'LRD' => 'LRD', 'LSL' => 'LSL', 'LTL' => 'LTL', 'LVL' => 'LVL',
            'LYD' => 'LYD', 'MAD' => 'MAD', 'MDL' => 'MDL', 'MGA' => 'MGA', 'MKD' => 'MKD', 'MMK' => 'MMK',
            'MNT' => 'MNT', 'MOP' => 'MOP', 'MRO' => 'MRO', 'MUR' => 'MUR', 'MVR' => 'MVR', 'MWK' => 'MWK',
            'MXN' => 'MXN', 'MYR' => 'MYR', 'MZN' => 'MZN', 'NAD' => 'NAD', 'NGN' => 'NGN', 'NIO' => 'NIO',
            'NOK' => 'NOK', 'NPR' => 'NPR', 'NZD' => 'NZD', 'OMR' => 'OMR', 'PAB' => 'PAB', 'PEN' => 'PEN',
            'PGK' => 'PGK', 'PHP' => 'PHP', 'PKR' => 'PKR', 'PLN' => 'PLN', 'PYG' => 'PYG', 'QAR' => 'QAR',
            'RON' => 'RON', 'RSD' => 'RSD', 'RUB' => 'RUB', 'RWF' => 'RWF', 'SAR' => 'SAR', 'SBD' => 'SBD',
            'SCR' => 'SCR', 'SDG' => 'SDG', 'SEK' => 'SEK', 'SGD' => 'SGD', 'SHP' => 'SHP', 'SLL' => 'SLL',
            'SOS' => 'SOS', 'SRD' => 'SRD', 'STD' => 'STD', 'SVC' => 'SVC', 'SYP' => 'SYP', 'SZL' => 'SZL',
            'THB' => 'THB', 'TJS' => 'TJS', 'TMT' => 'TMT', 'TND' => 'TND', 'TOP' => 'TOP', 'TRY' => 'TRY',
            'TTD' => 'TTD', 'TWD' => 'TWD', 'TZS' => 'TZS', 'UAH' => 'UAH', 'UGX' => 'UGX', 'USD' => 'USD',
            'UYU' => 'UYU', 'UZS' => 'UZS', 'VEF' => 'VEF', 'VND' => 'VND', 'VUV' => 'VUV', 'WST' => 'WST',
            'XAF' => 'XAF', 'XAG' => 'XAG', 'XAU' => 'XAU', 'XCD' => 'XCD', 'XDR' => 'XDR', 'XOF' => 'XOF',
            'XPF' => 'XPF', 'YER' => 'YER', 'ZAR' => 'ZAR', 'ZMK' => 'ZMK', 'ZMW' => 'ZMW', 'ZWL' => 'ZWL',
        ];
    }
}
