<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminConfigurationService
{
    private const ACTIVE = 1;

    private const TIME_FORMAT_12_HR = 1;

    private const TIME_FORMAT_24_HR = 2;

    /** @param  array<int, string>  $names */
    public function getMany(array $names): array
    {
        if ($names === []) {
            return [];
        }

        return DB::table('tbl_configurations')
            ->whereIn('conf_name', $names)
            ->pluck('conf_val', 'conf_name')
            ->all();
    }

    public function get(string $name, mixed $default = ''): mixed
    {
        $value = DB::table('tbl_configurations')->where('conf_name', $name)->value('conf_val');

        return $value ?? $default;
    }

    public function set(string $name, mixed $value): void
    {
        DB::table('tbl_configurations')->updateOrInsert(
            ['conf_name' => $name],
            ['conf_val' => (string) $value]
        );
    }

    /** @param  array<string, mixed>  $values */
    public function setMany(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->set((string) $name, $value);
        }
    }

    /** @return array<string, mixed> */
    public function generalSettingsForm(int $langId): array
    {
        $values = $this->getMany([
            'CONF_SITE_OWNER_EMAIL',
            'CONF_SITE_PHONE',
            'CONF_DEFAULT_LANG',
            'CONF_SITE_CURRENCY',
            'CONF_COUNTRY',
            'FRONTEND_TIME_FORMAT',
            'CONF_PRIVACY_POLICY_PAGE',
            'CONF_TERMS_AND_CONDITIONS_PAGE',
            'CONF_COOKIES_BUTTON_LINK',
            'CONF_ENABLE_COOKIES',
        ]);

        return [
            'values' => [
                'site_owner_email' => (string) ($values['CONF_SITE_OWNER_EMAIL'] ?? ''),
                'site_phone' => (string) ($values['CONF_SITE_PHONE'] ?? ''),
                'default_lang' => (string) ($values['CONF_DEFAULT_LANG'] ?? ''),
                'site_currency' => (string) ($values['CONF_SITE_CURRENCY'] ?? ''),
                'country' => (string) ($values['CONF_COUNTRY'] ?? ''),
                'frontend_time_format' => (string) ($values['FRONTEND_TIME_FORMAT'] ?? ''),
                'privacy_policy_page' => (string) ($values['CONF_PRIVACY_POLICY_PAGE'] ?? ''),
                'terms_and_conditions_page' => (string) ($values['CONF_TERMS_AND_CONDITIONS_PAGE'] ?? ''),
                'cookies_button_link' => (string) ($values['CONF_COOKIES_BUTTON_LINK'] ?? ''),
                'enable_cookies' => (string) ($values['CONF_ENABLE_COOKIES'] ?? '0') === '1',
            ],
            'options' => [
                'languages' => $this->siteLanguages(),
                'currencies' => $this->currenciesForSelect($langId),
                'countries' => $this->countriesForSelect($langId),
                'cms_pages' => $this->cmsPagesForSelect($langId),
                'time_formats' => [
                    ['value' => (string) self::TIME_FORMAT_12_HR, 'label_key' => 'LBL_12_HOUR_FORMAT', 'label_fallback' => '12 hour format'],
                    ['value' => (string) self::TIME_FORMAT_24_HR, 'label_key' => 'LBL_24_HOUR_FORMAT', 'label_fallback' => '24 hour format'],
                ],
            ],
        ];
    }

    /** @param  array<string, mixed>  $input */
    public function updateGeneralSettings(array $input): void
    {
        $email = trim((string) ($input['site_owner_email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Site owner email is invalid.');
        }

        $timeFormat = (string) ($input['frontend_time_format'] ?? '');
        $phpFormat = 'H:i:s';
        $jsFormat = 'HH:mm:ss';
        if ((int) $timeFormat === self::TIME_FORMAT_12_HR) {
            $phpFormat = 'h:i:s A';
            $jsFormat = 'hh:mm:ss A';
        }

        $this->setMany([
            'CONF_SITE_OWNER_EMAIL' => $email,
            'CONF_SITE_PHONE' => trim((string) ($input['site_phone'] ?? '')),
            'CONF_DEFAULT_LANG' => (string) ($input['default_lang'] ?? ''),
            'CONF_SITE_CURRENCY' => (string) ($input['site_currency'] ?? ''),
            'CONF_COUNTRY' => (string) ($input['country'] ?? ''),
            'FRONTEND_TIME_FORMAT' => $timeFormat,
            'FRONTEND_TIME_FORMAT_PHP' => $phpFormat,
            'FRONTEND_TIME_FORMAT_JS' => $jsFormat,
            'CONF_PRIVACY_POLICY_PAGE' => (string) ($input['privacy_policy_page'] ?? ''),
            'CONF_TERMS_AND_CONDITIONS_PAGE' => (string) ($input['terms_and_conditions_page'] ?? ''),
            'CONF_COOKIES_BUTTON_LINK' => (string) ($input['cookies_button_link'] ?? ''),
            'CONF_ENABLE_COOKIES' => ! empty($input['enable_cookies']) ? '1' : '0',
        ]);
    }

    /** @return array<string, mixed> */
    public function generalSettingsLangForm(int $formLangId): array
    {
        if ($formLangId < 1) {
            throw new \InvalidArgumentException('Invalid language.');
        }

        $values = $this->getMany([
            "CONF_WEBSITE_NAME_{$formLangId}",
            "CONF_FROM_NAME_{$formLangId}",
            "CONF_ADDRESS_{$formLangId}",
            "CONF_COOKIES_TEXT_{$formLangId}",
        ]);

        return [
            'lang_id' => $formLangId,
            'layout_direction' => $this->layoutDirection($formLangId),
            'values' => [
                'website_name' => (string) ($values["CONF_WEBSITE_NAME_{$formLangId}"] ?? ''),
                'from_name' => (string) ($values["CONF_FROM_NAME_{$formLangId}"] ?? ''),
                'address' => (string) ($values["CONF_ADDRESS_{$formLangId}"] ?? ''),
                'cookies_text' => (string) ($values["CONF_COOKIES_TEXT_{$formLangId}"] ?? ''),
            ],
        ];
    }

    /** @param  array<string, mixed>  $input */
    public function updateGeneralSettingsLangForm(int $formLangId, array $input): void
    {
        if ($formLangId < 1) {
            throw new \InvalidArgumentException('Invalid language.');
        }

        $address = trim((string) ($input['address'] ?? ''));
        $cookiesText = trim((string) ($input['cookies_text'] ?? ''));

        if ($address !== '' && (strlen($address) < 20 || strlen($address) > 250)) {
            throw new \InvalidArgumentException('Address must be between 20 and 250 characters.');
        }

        if ($cookiesText !== '' && (strlen($cookiesText) < 50 || strlen($cookiesText) > 500)) {
            throw new \InvalidArgumentException('Cookies policies text must be between 50 and 500 characters.');
        }

        $this->setMany([
            "CONF_WEBSITE_NAME_{$formLangId}" => trim((string) ($input['website_name'] ?? '')),
            "CONF_FROM_NAME_{$formLangId}" => trim((string) ($input['from_name'] ?? '')),
            "CONF_ADDRESS_{$formLangId}" => $address,
            "CONF_COOKIES_TEXT_{$formLangId}" => $cookiesText,
        ]);
    }

    /** @return array<string, mixed> */
    public function thirdPartyApiSettings(): array
    {
        $keys = [
            'CONF_FACEBOOK_APP_ID',
            'CONF_FACEBOOK_APP_SECRET',
            'CONF_APPLE_CLIENT_ID',
            'CONF_MAILCHIMP_KEY',
            'CONF_MAILCHIMP_LIST_ID',
            'CONF_MAILCHIMP_SERVER_PREFIX',
            'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_KEY',
            'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_REGION',
            'CONF_ANALYTICS_TABLE_ID',
            'CONF_GOOGLE_ANALYTICS_CLIENT_JSON',
            'CONF_RECAPTCHA_SITEKEY',
            'CONF_RECAPTCHA_SECRETKEY',
            'CONF_GOOGLE_CLIENT_JSON',
            'CONF_GOOGLE_API_KEY',
            'CONF_SERVICE_ACCOUNT_FIREBASE_JSON',
            'CONF_SHARE_THIS_PROPERTY_ID',
            'CONF_LIVE_CHAT_CODE',
            'CONF_ENABLE_LIVECHAT',
        ];

        $values = $this->getMany($keys);

        return [
            'facebook_app_id' => (string) ($values['CONF_FACEBOOK_APP_ID'] ?? ''),
            'facebook_app_secret' => (string) ($values['CONF_FACEBOOK_APP_SECRET'] ?? ''),
            'apple_client_id' => (string) ($values['CONF_APPLE_CLIENT_ID'] ?? ''),
            'mailchimp_key' => (string) ($values['CONF_MAILCHIMP_KEY'] ?? ''),
            'mailchimp_list_id' => (string) ($values['CONF_MAILCHIMP_LIST_ID'] ?? ''),
            'mailchimp_server_prefix' => (string) ($values['CONF_MAILCHIMP_SERVER_PREFIX'] ?? ''),
            'microsoft_translator_subscription_key' => (string) ($values['CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_KEY'] ?? ''),
            'microsoft_translator_subscription_region' => (string) ($values['CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_REGION'] ?? ''),
            'analytics_property_id' => (string) ($values['CONF_ANALYTICS_TABLE_ID'] ?? ''),
            'google_analytics_client_json' => '',
            'google_analytics_client_json_configured' => trim((string) ($values['CONF_GOOGLE_ANALYTICS_CLIENT_JSON'] ?? '')) !== '',
            'recaptcha_sitekey' => (string) ($values['CONF_RECAPTCHA_SITEKEY'] ?? ''),
            'recaptcha_secretkey' => (string) ($values['CONF_RECAPTCHA_SECRETKEY'] ?? ''),
            'google_client_json' => '',
            'google_client_json_configured' => trim((string) ($values['CONF_GOOGLE_CLIENT_JSON'] ?? '')) !== '',
            'google_api_key' => (string) ($values['CONF_GOOGLE_API_KEY'] ?? ''),
            'firebase_service_account_json' => '',
            'firebase_service_account_json_configured' => trim((string) ($values['CONF_SERVICE_ACCOUNT_FIREBASE_JSON'] ?? '')) !== '',
            'share_this_property_id' => (string) ($values['CONF_SHARE_THIS_PROPERTY_ID'] ?? ''),
            'live_chat_code' => '',
            'live_chat_code_configured' => trim((string) ($values['CONF_LIVE_CHAT_CODE'] ?? '')) !== '',
            'enable_live_chat' => (string) ($values['CONF_ENABLE_LIVECHAT'] ?? '0') === '1',
        ];
    }

    /** @param  array<string, mixed>  $input */
    public function updateThirdPartyApiSettings(array $input): void
    {
        $payload = [
            'CONF_FACEBOOK_APP_ID' => trim((string) ($input['facebook_app_id'] ?? '')),
            'CONF_FACEBOOK_APP_SECRET' => trim((string) ($input['facebook_app_secret'] ?? '')),
            'CONF_APPLE_CLIENT_ID' => trim((string) ($input['apple_client_id'] ?? '')),
            'CONF_MAILCHIMP_KEY' => trim((string) ($input['mailchimp_key'] ?? '')),
            'CONF_MAILCHIMP_LIST_ID' => trim((string) ($input['mailchimp_list_id'] ?? '')),
            'CONF_MAILCHIMP_SERVER_PREFIX' => trim((string) ($input['mailchimp_server_prefix'] ?? '')),
            'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_KEY' => trim((string) ($input['microsoft_translator_subscription_key'] ?? '')),
            'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_REGION' => trim((string) ($input['microsoft_translator_subscription_region'] ?? '')),
            'CONF_ANALYTICS_TABLE_ID' => trim((string) ($input['analytics_property_id'] ?? '')),
            'CONF_RECAPTCHA_SITEKEY' => trim((string) ($input['recaptcha_sitekey'] ?? '')),
            'CONF_RECAPTCHA_SECRETKEY' => trim((string) ($input['recaptcha_secretkey'] ?? '')),
            'CONF_GOOGLE_API_KEY' => trim((string) ($input['google_api_key'] ?? '')),
            'CONF_SHARE_THIS_PROPERTY_ID' => trim((string) ($input['share_this_property_id'] ?? '')),
        ];

        $payload['CONF_GOOGLE_ANALYTICS_CLIENT_JSON'] = $this->resolveJsonField(
            'google_analytics_client_json',
            'CONF_GOOGLE_ANALYTICS_CLIENT_JSON',
            $input,
            false
        );
        $payload['CONF_GOOGLE_CLIENT_JSON'] = $this->resolveJsonField(
            'google_client_json',
            'CONF_GOOGLE_CLIENT_JSON',
            $input,
            false
        );
        $payload['CONF_SERVICE_ACCOUNT_FIREBASE_JSON'] = $this->resolveJsonField(
            'firebase_service_account_json',
            'CONF_SERVICE_ACCOUNT_FIREBASE_JSON',
            $input,
            false
        );
        $payload['CONF_LIVE_CHAT_CODE'] = $this->resolveSecretField(
            'live_chat_code',
            'CONF_LIVE_CHAT_CODE',
            $input,
        );
        $payload['CONF_ENABLE_LIVECHAT'] = ! empty($input['enable_live_chat']) ? '1' : '0';

        $this->setMany($payload);
    }

    /** @return array{property_id: string, client_json: string, client_json_configured: bool}> */
    public function googleAnalyticsSettings(): array
    {
        $values = $this->getMany([
            'CONF_ANALYTICS_TABLE_ID',
            'CONF_GOOGLE_ANALYTICS_CLIENT_JSON',
        ]);

        $clientJson = (string) ($values['CONF_GOOGLE_ANALYTICS_CLIENT_JSON'] ?? '');

        return [
            'property_id' => (string) ($values['CONF_ANALYTICS_TABLE_ID'] ?? ''),
            'client_json' => $clientJson,
            'client_json_configured' => $clientJson !== '',
        ];
    }

    /**
     * @param  array{property_id?: string, client_json?: string}  $input
     */
    public function updateGoogleAnalyticsSettings(array $input): void
    {
        $propertyId = trim((string) ($input['property_id'] ?? ''));
        if ($propertyId === '') {
            throw new \InvalidArgumentException('Google Analytics property ID is required.');
        }

        $clientJson = trim((string) ($input['client_json'] ?? ''));
        if ($clientJson !== '') {
            $decoded = json_decode($clientJson, true);
            if (! is_array($decoded) || $decoded === []) {
                throw new \InvalidArgumentException('Google Analytics client JSON is invalid.');
            }
        } else {
            $existing = (string) $this->get('CONF_GOOGLE_ANALYTICS_CLIENT_JSON', '');
            if ($existing === '') {
                throw new \InvalidArgumentException('Google Analytics service account JSON is required.');
            }
            $clientJson = $existing;
        }

        $this->setMany([
            'CONF_ANALYTICS_TABLE_ID' => $propertyId,
            'CONF_GOOGLE_ANALYTICS_CLIENT_JSON' => $clientJson,
        ]);
    }

    /** @param  array<string, mixed>  $input */
    private function resolveJsonField(string $inputKey, string $configKey, array $input, bool $required): string
    {
        $value = trim((string) ($input[$inputKey] ?? ''));
        if ($value !== '') {
            $decoded = json_decode($value, true);
            if (! is_array($decoded) || $decoded === []) {
                throw new \InvalidArgumentException($configKey.' is invalid JSON.');
            }

            return $value;
        }

        $existing = (string) $this->get($configKey, '');
        if ($required && $existing === '') {
            throw new \InvalidArgumentException($configKey.' is required.');
        }

        return $existing;
    }

    /** @param  array<string, mixed>  $input */
    private function resolveSecretField(string $inputKey, string $configKey, array $input): string
    {
        $value = trim((string) ($input[$inputKey] ?? ''));
        if ($value !== '') {
            return $value;
        }

        return (string) $this->get($configKey, '');
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

    /** @return array<int, array{id: int, label: string}> */
    private function currenciesForSelect(int $langId): array
    {
        return DB::table('tbl_currencies as c')
            ->leftJoin('tbl_currencies_lang as cl', function ($join) use ($langId) {
                $join->on('cl.currencylang_currency_id', '=', 'c.currency_id')
                    ->where('cl.currencylang_lang_id', '=', $langId);
            })
            ->where('c.currency_active', self::ACTIVE)
            ->orderBy('c.currency_order')
            ->get([
                'c.currency_id as id',
                DB::raw('CONCAT(IFNULL(cl.currency_name, c.currency_code), " (", c.currency_code, ")") as label'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'label' => (string) $row->label])
            ->all();
    }

    /** @return array<int, array{id: int, name: string}> */
    private function countriesForSelect(int $langId): array
    {
        return DB::table('tbl_countries as c')
            ->leftJoin('tbl_countries_lang as cl', function ($join) use ($langId) {
                $join->on('cl.countrylang_country_id', '=', 'c.country_id')
                    ->where('cl.countrylang_lang_id', '=', $langId);
            })
            ->where('c.country_active', self::ACTIVE)
            ->orderBy('cl.country_name')
            ->get([
                'c.country_id as id',
                DB::raw('IFNULL(cl.country_name, c.country_code) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @return array<int, array{id: int, title: string}> */
    private function cmsPagesForSelect(int $langId): array
    {
        return DB::table('tbl_content_pages as p')
            ->leftJoin('tbl_content_pages_lang as pl', function ($join) use ($langId) {
                $join->on('pl.cpagelang_cpage_id', '=', 'p.cpage_id')
                    ->where('pl.cpagelang_lang_id', '=', $langId);
            })
            ->orderBy('p.cpage_id')
            ->get([
                'p.cpage_id as id',
                DB::raw('IFNULL(pl.cpage_title, p.cpage_identifier) as title'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => (string) $row->title])
            ->all();
    }

    private function layoutDirection(int $langId): string
    {
        $code = (string) DB::table('tbl_languages')->where('language_id', $langId)->value('language_code');

        return in_array(strtolower($code), ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';
    }
}
