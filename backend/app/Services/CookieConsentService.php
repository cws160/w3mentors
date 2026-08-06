<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie;

class CookieConsentService
{
    public const NECESSARY = 'necessary';

    public const PREFERENCES = 'preferences';

    public const STATISTICS = 'statistics';

    public const COOKIE_NAME = 'CONF_SITE_CONSENTS';

    public function cookiesEnabled(): bool
    {
        return (int) Configuration::getValue('CONF_ENABLE_COOKIES', 1) === 1;
    }

    /**
     * @return array<string, int>
     */
    public function defaultConsent(): array
    {
        return [
            self::NECESSARY => 1,
            self::PREFERENCES => 1,
            self::STATISTICS => 1,
        ];
    }

    /**
     * @return array{enabled: bool, settings: array<string, int>}
     */
    public function show(int $userId, Request $request): array
    {
        if (! $this->cookiesEnabled()) {
            return [
                'enabled' => false,
                'settings' => $this->defaultConsent(),
            ];
        }

        return [
            'enabled' => true,
            'settings' => $this->resolveSettings($userId, $request),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, int>
     */
    public function save(int $userId, array $input): array
    {
        if (! $this->cookiesEnabled()) {
            throw new \InvalidArgumentException('Cookie consent is not enabled on this site.');
        }

        $settings = array_merge($this->defaultConsent(), [
            self::NECESSARY => 1,
            self::PREFERENCES => $this->boolToFlag($input[self::PREFERENCES] ?? false),
            self::STATISTICS => $this->boolToFlag($input[self::STATISTICS] ?? false),
        ]);

        $now = now()->format('Y-m-d H:i:s');
        DB::table('tbl_user_cookie_consent')->updateOrInsert(
            ['usercc_user_id' => $userId],
            [
                'usercc_settings' => json_encode($settings),
                'usercc_added_on' => $now,
            ]
        );

        return $settings;
    }

    /**
     * @return array<string, int>
     */
    public function acceptAll(int $userId): array
    {
        return $this->save($userId, $this->defaultConsent());
    }

    public function makeConsentCookie(array $settings, bool $secure): Cookie
    {
        return Cookie::create(self::COOKIE_NAME)
            ->withValue(json_encode($settings))
            ->withExpires(now()->addSeconds(604800))
            ->withPath('/')
            ->withSecure($secure)
            ->withHttpOnly(true)
            ->withSameSite($secure ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_LAX);
    }

    /**
     * @return array<string, int>
     */
    private function resolveSettings(int $userId, Request $request): array
    {
        $row = DB::table('tbl_user_cookie_consent')
            ->where('usercc_user_id', $userId)
            ->value('usercc_settings');

        if (is_string($row) && $row !== '') {
            $decoded = json_decode($row, true);

            return $this->normalizeSettings(is_array($decoded) ? $decoded : []);
        }

        $cookie = $request->cookie(self::COOKIE_NAME);
        if (is_string($cookie) && $cookie !== '') {
            $decoded = json_decode($cookie, true);

            return $this->normalizeSettings(is_array($decoded) ? $decoded : []);
        }

        return [
            self::NECESSARY => 1,
            self::PREFERENCES => 0,
            self::STATISTICS => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, int>
     */
    private function normalizeSettings(array $raw): array
    {
        return [
            self::NECESSARY => 1,
            self::PREFERENCES => (int) ($raw[self::PREFERENCES] ?? 0) === 1 ? 1 : 0,
            self::STATISTICS => (int) ($raw[self::STATISTICS] ?? 0) === 1 ? 1 : 0,
        ];
    }

    private function boolToFlag(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return (int) $value === 1 ? 1 : 0;
    }
}
