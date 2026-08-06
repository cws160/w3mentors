<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

class UserProfileService
{
    private const GENDER_LABELS = [
        1 => 'LBL_MALE',
        2 => 'LBL_FEMALE',
        3 => 'LBL_NON_BINARY',
        4 => 'LBL_PREFER_NOT_TO_SAY',
    ];

    public function getGeneralForm(int $userId, int $langId, bool $isTeacher): array
    {
        $row = DB::table('tbl_users as user')
            ->leftJoin('tbl_user_settings as uset', 'uset.user_id', '=', 'user.user_id')
            ->where('user.user_id', $userId)
            ->whereNull('user.user_deleted')
            ->select([
                'user.*',
                'uset.user_phone_number',
                'uset.user_phone_code',
                'uset.user_book_before',
                'uset.user_trial_enabled',
                'uset.user_google_token',
            ])
            ->first();

        if (! $row) {
            throw new \RuntimeException('User not found.');
        }

        $profileLanguages = [];
        if ($isTeacher) {
            $profileLanguages = DB::table('tbl_languages')
                ->where('language_active', 1)
                ->orderBy('language_id')
                ->get(['language_id as id', 'language_name as name'])
                ->map(fn ($l) => ['id' => (int) $l->id, 'name' => (string) $l->name])
                ->all();
        }

        return [
            'values' => [
                'username' => (string) ($row->user_username ?? ''),
                'first_name' => (string) ($row->user_first_name ?? ''),
                'last_name' => (string) ($row->user_last_name ?? ''),
                'gender' => (int) ($row->user_gender ?? 0),
                'country_id' => (int) ($row->user_country_id ?? 0),
                'phone_code' => (int) ($row->user_phone_code ?? 0),
                'phone_number' => (string) ($row->user_phone_number ?? ''),
                'timezone' => (string) ($row->user_timezone ?? ''),
                'lang_id' => (int) ($row->user_lang_id ?? 0),
                'book_before' => (int) ($row->user_book_before ?? 0),
                'offline_sessions' => (int) ($row->user_offline_sessions ?? 0) === 1,
                'trial_enabled' => (int) ($row->user_trial_enabled ?? 0) === 1,
            ],
            'options' => [
                'genders' => $this->genderOptions(),
                'countries' => $this->countryOptions($langId),
                'timezones' => $this->timezoneOptions(),
                'notification_languages' => $this->notificationLanguageOptions(),
                'book_before' => $this->bookBeforeOptions(),
            ],
            'meta' => [
                'is_teacher' => $isTeacher,
                'profile_languages' => $profileLanguages,
                'offline_sessions_enabled' => (int) Configuration::getValue('CONF_ENABLE_OFFLINE_SESSIONS', 0) === 1,
                'free_trial_enabled' => (int) Configuration::getValue('CONF_ENABLE_FREE_TRIAL', 0) === 1,
                'google_calendar_configured' => ! empty(Configuration::getValue('CONF_GOOGLE_CLIENT_JSON')),
                'google_calendar_auth_ready' => $this->isGoogleCalendarAuthReady(),
                'google_calendar_synced' => ! empty($row->user_google_token ?? null),
                'google_calendar_authorize_url' => $this->googleCalendarAuthorizeUrl(),
                'teacher_profile_url' => $isTeacher && $row->user_username
                    ? $this->teacherPublicUrl((string) $row->user_username)
                    : null,
            ],
        ];
    }

    public function updateGeneral(int $userId, array $data, bool $isTeacher): void
    {
        $user = User::query()->findOrFail($userId);
        $settings = DB::table('tbl_user_settings')->where('user_id', $userId)->first();

        if ($isTeacher && isset($data['username'])) {
            $username = $this->normalizeUsername((string) $data['username']);
            $exists = DB::table('tbl_users')
                ->where('user_username', $username)
                ->where('user_id', '!=', $userId)
                ->exists();
            if ($exists) {
                throw new \InvalidArgumentException('Username is already taken.');
            }
            $user->user_username = $username;
        }

        if (isset($data['first_name'])) {
            $user->user_first_name = (string) $data['first_name'];
        }
        if (array_key_exists('last_name', $data)) {
            $user->user_last_name = $data['last_name'];
        }
        if (isset($data['gender'])) {
            $user->user_gender = (int) $data['gender'];
        }
        if (isset($data['country_id'])) {
            $this->assertActiveCountry((int) $data['country_id']);
            $user->user_country_id = (int) $data['country_id'];
        }
        if (isset($data['timezone'])) {
            $user->user_timezone = (string) $data['timezone'];
        }
        if (isset($data['lang_id'])) {
            $user->user_lang_id = (int) $data['lang_id'];
        }
        if ($isTeacher && array_key_exists('offline_sessions', $data)) {
            $user->user_offline_sessions = $data['offline_sessions'] ? 1 : 0;
        }

        $user->save();

        $settingsPayload = [];
        if (isset($data['phone_code'])) {
            $this->assertActiveCountry((int) $data['phone_code']);
            $settingsPayload['user_phone_code'] = (int) $data['phone_code'];
        }
        if (isset($data['phone_number'])) {
            $settingsPayload['user_phone_number'] = (string) $data['phone_number'];
        }
        if ($isTeacher && isset($data['book_before'])) {
            $settingsPayload['user_book_before'] = (int) $data['book_before'];
        }
        if ($isTeacher && array_key_exists('trial_enabled', $data)) {
            $settingsPayload['user_trial_enabled'] = $data['trial_enabled'] ? 1 : 0;
        }

        if ($settingsPayload !== []) {
            DB::table('tbl_user_settings')->updateOrInsert(
                ['user_id' => $userId],
                array_merge($settingsPayload, ['user_id' => $userId])
            );
        }
    }

    private function genderOptions(): array
    {
        $out = [];
        foreach (self::GENDER_LABELS as $id => $labelKey) {
            $out[] = ['id' => $id, 'label_key' => $labelKey];
        }

        return $out;
    }

    private function bookBeforeOptions(): array
    {
        return [
            ['id' => 0, 'label_key' => 'LBL_IMMEDIATE'],
            ['id' => 12, 'label_key' => 'LBL_12_HOURS'],
            ['id' => 24, 'label_key' => 'LBL_24_HOURS'],
        ];
    }

    private function countryOptions(int $langId): array
    {
        return DB::table('tbl_countries as c')
            ->leftJoin('tbl_countries_lang as cl', function ($join) use ($langId) {
                $join->on('cl.countrylang_country_id', '=', 'c.country_id')
                    ->where('cl.countrylang_lang_id', '=', $langId);
            })
            ->where('c.country_active', 1)
            ->orderByRaw('IFNULL(cl.country_name, c.country_code)')
            ->get([
                'c.country_id as id',
                DB::raw('IFNULL(cl.country_name, c.country_code) as name'),
                'c.country_dial_code as dial_code',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'phone_label' => trim((string) $row->name) . ' (+' . (string) $row->dial_code . ')',
            ])
            ->all();
    }

    private function notificationLanguageOptions(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function timezoneOptions(): array
    {
        $options = [];
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $offset = (new DateTime('now', new DateTimeZone($tz)))->format('P');
            $options[] = [
                'id' => $tz,
                'label' => "UTC {$offset} {$tz}",
            ];
        }

        return $options;
    }

    private function teacherPublicUrl(string $username): string
    {
        $base = rtrim((string) config('app.url', ''), '/');

        return "{$base}/teachers/view/{$username}";
    }

    private function normalizeUsername(string $username): string
    {
        $username = strtolower(trim($username));
        $username = preg_replace('/\s+/', '', $username) ?? '';
        $username = preg_replace('/[^a-z0-9_-]/', '', $username) ?? '';

        if (strlen($username) < 6) {
            throw new \InvalidArgumentException('Username must be at least 6 characters.');
        }

        return $username;
    }

    private function assertActiveCountry(int $countryId): void
    {
        $active = DB::table('tbl_countries')
            ->where('country_id', $countryId)
            ->where('country_active', 1)
            ->exists();
        if (! $active) {
            throw new \InvalidArgumentException('Selected country is not available.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getLanguageForm(int $userId, int $langId, bool $isTeacher): array
    {
        $language = DB::table('tbl_languages')
            ->where('language_id', $langId)
            ->where('language_active', 1)
            ->first(['language_id', 'language_name', 'language_direction']);

        if (! $language) {
            throw new \InvalidArgumentException('Language is not available.');
        }

        $bio = DB::table('tbl_users_lang')
            ->where('userlang_user_id', $userId)
            ->where('userlang_lang_id', $langId)
            ->value('user_biography');

        $activeLangIds = DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->pluck('language_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $lastLangId = $activeLangIds !== [] ? (int) end($activeLangIds) : $langId;

        return [
            'values' => [
                'lang_id' => $langId,
                'biography' => (string) ($bio ?? ''),
            ],
            'meta' => [
                'language_name' => (string) $language->language_name,
                'direction' => (string) ($language->language_direction ?: 'ltr'),
                'is_last_language' => $langId === $lastLangId,
                'is_teacher' => $isTeacher,
            ],
        ];
    }

    public function updateLanguageBio(int $userId, int $langId, string $biography): void
    {
        $language = DB::table('tbl_languages')
            ->where('language_id', $langId)
            ->where('language_active', 1)
            ->exists();
        if (! $language) {
            throw new \InvalidArgumentException('Language is not available.');
        }

        $biography = trim($biography);
        if ($biography === '' || strlen($biography) > 2000) {
            throw new \InvalidArgumentException('Biography must be between 1 and 2000 characters.');
        }

        DB::table('tbl_users_lang')->updateOrInsert(
            [
                'userlang_user_id' => $userId,
                'userlang_lang_id' => $langId,
            ],
            [
                'userlang_user_id' => $userId,
                'userlang_lang_id' => $langId,
                'user_biography' => $biography,
            ]
        );
    }

    private function isGoogleCalendarAuthReady(): bool
    {
        $json = (string) Configuration::getValue('CONF_GOOGLE_CLIENT_JSON', '');
        if ($json === '') {
            return false;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return false;
        }

        $client = $decoded['web'] ?? $decoded['installed'] ?? null;

        return is_array($client) && ! empty($client['client_id']);
    }

    private function googleCalendarAuthorizeUrl(): ?string
    {
        if (! $this->isGoogleCalendarAuthReady()) {
            return null;
        }

        $base = rtrim((string) config('app.url', ''), '/');

        return "{$base}/dashboard/account/google-calendar-authorize";
    }
}
