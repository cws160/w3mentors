<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\LegacyPasswordHasher;
use App\Services\WalletListingService;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminUserService
{
    private const USER_LEARNER = 1;

    private const USER_TEACHER = 2;

    private const USER_AFFILIATE = 5;

    private const TXN_SUPPORT_CREDIT = 14;

    private const TXN_SUPPORT_DEBIT = 13;

    private const CREDIT_TYPE = 1;

    private const DEBIT_TYPE = 2;

    private const PASSWORD_REGEX = '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%-_]{8,15}$/';

    private const PHONE_REGEX = '/^[0-9(\)\-{} +]{4,16}$/';

    public function __construct(private WalletListingService $wallet)
    {
    }

    /** @return array<string, mixed> */
    public function view(int $userId, int $langId = 1): array
    {
        $row = $this->baseQuery($langId)
            ->where('user.user_id', $userId)
            ->first([
                DB::raw('CONCAT(user.user_first_name, " ", COALESCE(user.user_last_name, "")) AS full_name'),
                'user.user_email as email',
                'user.user_username as username',
                'user.user_timezone as timezone',
                'user.user_created as created_at',
                'user.user_lastseen as last_seen_at',
                'user.user_verified as verified_at',
                'user.user_active as active',
                'user.user_featured as featured',
                'user.user_is_teacher as is_teacher',
                'user.user_is_affiliate as is_affiliate',
                'uset.user_registered_as as registered_as',
                'uset.user_phone_code as phone_code',
                'uset.user_phone_number as phone_number',
                DB::raw('IFNULL(countrylang.country_name, country.country_identifier) as country_name'),
                DB::raw('IFNULL(userlang.user_biography, "") as biography'),
            ]);

        if (! $row) {
            throw new \RuntimeException('User not found', 404);
        }

        $dialCode = '';
        if ($row->phone_code) {
            $dialCode = (string) (DB::table('tbl_countries')
                ->where('country_id', (int) $row->phone_code)
                ->value('country_dial_code') ?? '');
        }

        return [
            'id' => $userId,
            'full_name' => trim((string) $row->full_name),
            'email' => (string) $row->email,
            'username' => (string) ($row->username ?? ''),
            'timezone' => (string) ($row->timezone ?? ''),
            'created_at' => (string) $row->created_at,
            'last_seen_at' => (string) ($row->last_seen_at ?? ''),
            'phone_dial_code' => $dialCode,
            'phone_number' => (string) ($row->phone_number ?? ''),
            'phone_display' => trim($dialCode.' '.($row->phone_number ?? '')),
            'country_name' => (string) ($row->country_name ?? ''),
            'biography' => (string) ($row->biography ?? ''),
            'verified' => $row->verified_at !== null,
            'active' => (bool) $row->active,
            'featured' => (bool) $row->featured,
            'is_teacher' => (bool) $row->is_teacher,
            'is_affiliate' => (bool) $row->is_affiliate,
            'registered_as' => $row->registered_as !== null ? (int) $row->registered_as : null,
        ];
    }

    /** @return array<string, mixed> */
    public function editForm(int $userId, int $langId = 1): array
    {
        $row = DB::table('tbl_users as user')
            ->leftJoin('tbl_user_settings as uset', 'uset.user_id', '=', 'user.user_id')
            ->whereNull('user.user_deleted')
            ->where('user.user_id', $userId)
            ->first([
                'user.user_id',
                'user.user_first_name',
                'user.user_last_name',
                'user.user_email',
                'user.user_username',
                'user.user_country_id',
                'user.user_is_teacher',
                'user.user_is_affiliate',
                'user.user_featured',
                'user.user_timezone',
                'uset.user_phone_code',
                'uset.user_phone_number',
                'uset.user_registered_as',
            ]);

        if (! $row) {
            throw new \RuntimeException('User not found', 404);
        }

        $userType = self::USER_LEARNER;
        if ((bool) $row->user_is_affiliate) {
            $userType = self::USER_AFFILIATE;
        } elseif ((bool) $row->user_is_teacher) {
            $userType = self::USER_TEACHER;
        } elseif ($row->user_registered_as) {
            $userType = (int) $row->user_registered_as;
        }

        $timezone = (string) ($row->user_timezone ?? '');

        return [
            'user' => [
                'id' => (int) $row->user_id,
                'first_name' => (string) $row->user_first_name,
                'last_name' => (string) ($row->user_last_name ?? ''),
                'email' => (string) $row->user_email,
                'username' => (string) ($row->user_username ?? ''),
                'email_username' => (string) $row->user_email,
                'country_id' => (int) ($row->user_country_id ?? 0),
                'phone_code' => (int) ($row->user_phone_code ?? 0),
                'phone_number' => (string) ($row->user_phone_number ?? ''),
                'featured' => (bool) $row->user_featured,
                'is_teacher' => (bool) $row->user_is_teacher,
                'user_type' => $userType,
                'timezone' => $timezone,
                'timezone_locked' => $timezone !== '',
                'is_parent_account' => false,
            ],
            'countries' => $this->countries($langId),
            'timezones' => $this->timezoneOptions(),
            'user_types' => [
                ['id' => self::USER_LEARNER, 'label_key' => 'LBL_Learner'],
                ['id' => self::USER_TEACHER, 'label_key' => 'LBL_Teacher'],
                ['id' => self::USER_AFFILIATE, 'label_key' => 'LBL_Affiliate'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function createForm(int $langId = 1): array
    {
        $defaultCountryId = (int) (DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_COUNTRY')
            ->value('conf_val') ?: 1);
        $defaultTimezone = (string) (DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_TIMEZONE')
            ->value('conf_val') ?: 'UTC');

        return [
            'default_country_id' => $defaultCountryId,
            'default_timezone' => $defaultTimezone,
            'countries' => $this->countries($langId),
            'timezones' => $this->timezoneOptions(),
            'user_types' => [
                ['id' => self::USER_LEARNER, 'label_key' => 'LBL_Learner'],
                ['id' => self::USER_TEACHER, 'label_key' => 'LBL_Teacher'],
                ['id' => self::USER_AFFILIATE, 'label_key' => 'LBL_Affiliate'],
            ],
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): int
    {
        $userType = (int) ($data['user_type'] ?? 0);
        $emailUsername = trim((string) ($data['email_username'] ?? $data['email'] ?? ''));
        $teacherUsername = trim((string) ($data['username'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phoneCode = (int) ($data['phone_code'] ?? 0);
        $phoneNumber = trim((string) ($data['phone_number'] ?? ''));
        $countryId = (int) ($data['country_id'] ?? 0);
        $timezone = trim((string) ($data['timezone'] ?? ''));
        $featured = ! empty($data['featured']);

        if (! in_array($userType, [self::USER_LEARNER, self::USER_TEACHER, self::USER_AFFILIATE], true)) {
            throw new \InvalidArgumentException('Please select a user type.');
        }
        if ($emailUsername === '') {
            throw new \InvalidArgumentException('Please enter email or username.');
        }
        if ($firstName === '') {
            throw new \InvalidArgumentException('First name is required.');
        }
        if ($phoneCode < 1) {
            throw new \InvalidArgumentException('Phone code is required.');
        }
        if ($phoneNumber === '' || ! preg_match(self::PHONE_REGEX, $phoneNumber)) {
            throw new \InvalidArgumentException('Please enter a valid phone number.');
        }
        if ($countryId < 1) {
            throw new \InvalidArgumentException('Country is required.');
        }
        if ($timezone === '' || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new \InvalidArgumentException('Please select a timezone.');
        }
        if (preg_match('/<[^>]+>/', $firstName.$lastName)) {
            throw new \InvalidArgumentException('Script tags are not allowed in name fields.');
        }

        [$email, $username] = $this->resolveEmailUsername($emailUsername);
        if ($userType === self::USER_TEACHER) {
            if ($teacherUsername === '') {
                throw new \InvalidArgumentException('Username is required.');
            }
            if (! preg_match('/^[a-zA-Z0-9_]{3,50}$/', $teacherUsername)) {
                throw new \InvalidArgumentException('Please enter a valid username.');
            }
            $username = $teacherUsername;
        }

        if (DB::table('tbl_users')->where('user_email', $email)->whereNull('user_deleted')->exists()) {
            throw new \InvalidArgumentException('Email address is already in use.');
        }
        if (DB::table('tbl_users')->where('user_username', $username)->whereNull('user_deleted')->exists()) {
            throw new \InvalidArgumentException('Username is already in use.');
        }

        $langId = (int) (DB::table('tbl_configurations')->where('conf_name', 'CONF_DEFAULT_LANG')->value('conf_val') ?: 1);
        $currencyId = (int) (DB::table('tbl_configurations')->where('conf_name', 'CONF_DEFAULT_CURRENCY')->value('conf_val') ?: 1);
        $password = $this->generatePassword();
        $now = now()->format('Y-m-d H:i:s');

        $isTeacher = $userType === self::USER_TEACHER ? 1 : 0;
        $isAffiliate = $userType === self::USER_AFFILIATE ? 1 : 0;
        $isFeatured = $isTeacher && $featured ? 1 : 0;
        $dashboard = $userType;

        return (int) DB::transaction(function () use (
            $firstName,
            $lastName,
            $email,
            $username,
            $password,
            $timezone,
            $countryId,
            $langId,
            $currencyId,
            $phoneCode,
            $phoneNumber,
            $now,
            $isTeacher,
            $isAffiliate,
            $isFeatured,
            $dashboard,
            $userType,
        ) {
            $userId = (int) DB::table('tbl_users')->insertGetId([
                'user_first_name' => $firstName,
                'user_last_name' => $lastName,
                'user_email' => $email,
                'user_username' => $username,
                'user_password' => LegacyPasswordHasher::hash($password),
                'user_timezone' => $timezone,
                'user_lang_id' => $langId,
                'user_currency_id' => $currencyId,
                'user_country_id' => $countryId,
                'user_is_teacher' => $isTeacher,
                'user_is_affiliate' => $isAffiliate,
                'user_featured' => $isFeatured,
                'user_active' => 1,
                'user_verified' => $now,
                'user_created' => $now,
            ]);

            DB::table('tbl_user_settings')->insert([
                'user_id' => $userId,
                'user_dashboard' => $dashboard,
                'user_registered_as' => $userType,
                'user_trial_enabled' => 0,
                'user_book_before' => 0,
                'user_phone_code' => $phoneCode,
                'user_phone_number' => $phoneNumber,
                'user_wallet_balance' => 0,
                'user_apple_id' => '',
                'user_apple_token' => '',
                'user_device_token' => '',
                'user_zoom_status' => 0,
                'user_autorenew_subscription' => 0,
                'user_reward_points' => 0,
                'user_referral_code' => uniqid(),
            ]);

            return $userId;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $userId, array $data): void
    {
        $existing = DB::table('tbl_users')
            ->where('user_id', $userId)
            ->whereNull('user_deleted')
            ->first();

        if (! $existing) {
            throw new \RuntimeException('User not found', 404);
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phoneCode = (int) ($data['phone_code'] ?? 0);
        $phoneNumber = trim((string) ($data['phone_number'] ?? ''));
        $countryId = (int) ($data['country_id'] ?? 0);
        $userType = (int) ($data['user_type'] ?? 0);

        if ($firstName === '') {
            throw new \InvalidArgumentException('First name is required.');
        }
        if ($lastName === '') {
            throw new \InvalidArgumentException('Last name is required.');
        }
        if ($phoneCode < 1) {
            throw new \InvalidArgumentException('Phone code is required.');
        }
        if ($phoneNumber === '' || ! preg_match(self::PHONE_REGEX, $phoneNumber)) {
            throw new \InvalidArgumentException('Please enter a valid phone number.');
        }
        if ($countryId < 1) {
            throw new \InvalidArgumentException('Country is required.');
        }
        if ($userType > 0 && ! in_array($userType, [self::USER_LEARNER, self::USER_TEACHER, self::USER_AFFILIATE], true)) {
            throw new \InvalidArgumentException('Please select a valid user type.');
        }

        if (preg_match('/<[^>]+>/', $firstName.$lastName)) {
            throw new \InvalidArgumentException('Script tags are not allowed in name fields.');
        }

        $isTeacher = $userType > 0 && $userType === self::USER_TEACHER ? 1 : (int) $existing->user_is_teacher;
        $isAffiliate = $userType > 0 && $userType === self::USER_AFFILIATE ? 1 : (int) $existing->user_is_affiliate;
        if ($userType === self::USER_LEARNER) {
            $isTeacher = 0;
            $isAffiliate = 0;
        } elseif ($userType === self::USER_TEACHER) {
            $isTeacher = 1;
            $isAffiliate = 0;
        } elseif ($userType === self::USER_AFFILIATE) {
            $isTeacher = 0;
            $isAffiliate = 1;
        }

        DB::transaction(function () use ($userId, $firstName, $lastName, $phoneCode, $phoneNumber, $countryId, $existing, $data, $isTeacher, $isAffiliate, $userType) {
            $userUpdate = [
                'user_first_name' => $firstName,
                'user_last_name' => $lastName,
                'user_country_id' => $countryId,
                'user_is_teacher' => $isTeacher,
                'user_is_affiliate' => $isAffiliate,
            ];

            if ((bool) $existing->user_is_teacher || $isTeacher) {
                $userUpdate['user_featured'] = ! empty($data['featured']) ? 1 : 0;
            }

            DB::table('tbl_users')->where('user_id', $userId)->update($userUpdate);

            $settingsUpdate = [
                'user_phone_code' => $phoneCode,
                'user_phone_number' => $phoneNumber,
            ];

            if ($userType > 0) {
                $settingsUpdate['user_registered_as'] = $userType;
                $settingsUpdate['user_dashboard'] = $userType;
            }

            DB::table('tbl_user_settings')->updateOrInsert(
                ['user_id' => $userId],
                array_merge($settingsUpdate, ['user_id' => $userId])
            );
        });
    }

    /** @return array{token: string, redirect_url: string} */
    public function loginAsUser(int $userId): array
    {
        $user = User::query()
            ->where('user_id', $userId)
            ->whereNull('user_deleted')
            ->where('user_active', 1)
            ->first();

        if (! $user) {
            throw new \RuntimeException('User not found', 404);
        }

        $user->tokens()->where('name', 'admin-impersonation')->delete();
        $token = $user->createToken('admin-impersonation')->plainTextToken;

        $redirect = $user->user_is_teacher ? '/dashboard/teacher' : '/dashboard/learner';

        return [
            'token' => $token,
            'redirect_url' => $redirect,
        ];
    }

    /** @return array{url: string} */
    public function createDashboardBridgeUrl(int $userId, int $courseId): array
    {
        $user = User::query()
            ->where('user_id', $userId)
            ->whereNull('user_deleted')
            ->where('user_active', 1)
            ->first();

        if (! $user) {
            throw new \RuntimeException('User not found', 404);
        }

        if ($courseId < 1) {
            throw new \RuntimeException('Invalid course', 422);
        }

        $expires = time() + 120;
        $key = (string) config('legacy.dashboard_bridge_key');
        $payload = "{$userId}:{$courseId}:{$expires}";
        $signature = hash_hmac('sha256', $payload, $key);
        $query = http_build_query([
            'user_id' => $userId,
            'course_id' => $courseId,
            'exp' => $expires,
            'sig' => $signature,
        ]);

        // Relative URL keeps the preview on the React dev origin (Vite proxies bridge + dashboard).
        return [
            'url' => "/admin-dashboard-bridge.php?{$query}",
        ];
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function transactions(int $userId, Request $request): array
    {
        if (! $this->userExists($userId)) {
            throw new \RuntimeException('User not found', 404);
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ADMIN_PAGESIZE')
            ->value('conf_val') ?: 10;
        $perPage = min(50, max(1, $perPage));

        $query = DB::table('tbl_user_transactions')->where('usrtxn_user_id', $userId);
        $total = (clone $query)->count();

        $rows = $query
            ->orderByDesc('usrtxn_id')
            ->forPage($page, $perPage)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => (int) $row->usrtxn_id,
            'txn_id_formatted' => $this->wallet->formatTxnId((int) $row->usrtxn_id),
            'amount' => (float) $row->usrtxn_amount,
            'amount_formatted' => number_format(abs((float) $row->usrtxn_amount), 2),
            'comment' => strip_tags((string) ($row->usrtxn_comment ?? '')),
            'created_at' => (string) $row->usrtxn_datetime,
        ])->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function createTransaction(int $userId, array $data): void
    {
        if (! $this->userExists($userId)) {
            throw new \RuntimeException('User not found', 404);
        }

        $type = (int) ($data['type'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);
        $description = trim(strip_tags((string) ($data['description'] ?? '')));

        if (! in_array($type, [self::CREDIT_TYPE, self::DEBIT_TYPE], true)) {
            throw new \InvalidArgumentException('Transaction type is required.');
        }
        if ($amount < 1 || $amount > 9999999999) {
            throw new \InvalidArgumentException('Amount must be between 1 and 9999999999.');
        }
        if ($description === '') {
            throw new \InvalidArgumentException('Description is required.');
        }

        $txnType = $type === self::CREDIT_TYPE ? self::TXN_SUPPORT_CREDIT : self::TXN_SUPPORT_DEBIT;
        $signedAmount = $type === self::CREDIT_TYPE ? abs($amount) : -abs($amount);

        DB::transaction(function () use ($userId, $txnType, $signedAmount, $description) {
            DB::table('tbl_user_transactions')->insert([
                'usrtxn_type' => $txnType,
                'usrtxn_user_id' => $userId,
                'usrtxn_amount' => $signedAmount,
                'usrtxn_comment' => $description,
                'usrtxn_datetime' => now()->format('Y-m-d H:i:s'),
            ]);

            DB::table('tbl_user_settings')
                ->where('user_id', $userId)
                ->update([
                    'user_wallet_balance' => DB::raw('user_wallet_balance + '.$signedAmount),
                ]);
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function addresses(int $userId, int $langId = 1): array
    {
        if (! $this->userExists($userId)) {
            throw new \RuntimeException('User not found', 404);
        }

        $rows = DB::table('tbl_user_addresses as usradd')
            ->join('tbl_states as st', 'st.state_id', '=', 'usradd.usradd_state_id')
            ->leftJoin('tbl_states_lang as stlang', function ($join) use ($langId) {
                $join->on('stlang.stlang_state_id', '=', 'st.state_id')
                    ->where('stlang.stlang_lang_id', '=', $langId);
            })
            ->join('tbl_countries as c', 'c.country_id', '=', 'usradd.usradd_country_id')
            ->leftJoin('tbl_countries_lang as clang', function ($join) use ($langId) {
                $join->on('clang.countrylang_country_id', '=', 'c.country_id')
                    ->where('clang.countrylang_lang_id', '=', $langId);
            })
            ->where('usradd.usradd_user_id', $userId)
            ->whereNull('usradd.usradd_deleted')
            ->orderByDesc('usradd.usradd_default')
            ->orderBy('usradd.usradd_id')
            ->get([
                'usradd.usradd_id',
                'usradd.usradd_address',
                'usradd.usradd_city',
                'usradd.usradd_zipcode',
                'usradd.usradd_default',
                DB::raw('IFNULL(stlang.state_name, st.state_identifier) AS state_name'),
                DB::raw('IFNULL(clang.country_name, c.country_identifier) AS country_name'),
            ]);

        return $rows->map(fn ($row) => [
            'id' => (int) $row->usradd_id,
            'formatted' => implode(', ', array_filter([
                (string) $row->usradd_address,
                (string) $row->usradd_city,
                (string) $row->state_name,
                (string) $row->usradd_zipcode,
                (string) $row->country_name,
            ])),
            'is_default' => (int) $row->usradd_default === 1,
        ])->all();
    }

    /** @param  array<string, mixed>  $data */
    public function changePassword(int $userId, array $data): void
    {
        $user = User::query()
            ->where('user_id', $userId)
            ->whereNull('user_deleted')
            ->first();

        if (! $user) {
            throw new \RuntimeException('User not found', 404);
        }

        $password = (string) ($data['new_password'] ?? '');
        $confirm = (string) ($data['conf_new_password'] ?? '');

        if ($password === '' || ! preg_match(self::PASSWORD_REGEX, $password)) {
            throw new \InvalidArgumentException('Password must be 8–15 characters and include letters and numbers.');
        }
        if ($password !== $confirm) {
            throw new \InvalidArgumentException('Password confirmation does not match.');
        }

        $user->user_password = LegacyPasswordHasher::hash($password);
        $user->save();

        DB::table('tbl_users')->where('user_id', $userId)->update(['user_password_updated' => 1]);
    }

    /** @return array<int, array<string, mixed>> */
    private function countries(int $langId): array
    {
        return DB::table('tbl_countries as country')
            ->leftJoin('tbl_countries_lang as lang', function ($join) use ($langId) {
                $join->on('lang.countrylang_country_id', '=', 'country.country_id')
                    ->where('lang.countrylang_lang_id', '=', $langId);
            })
            ->where('country.country_active', 1)
            ->orderByRaw('IFNULL(lang.country_name, country.country_identifier) ASC')
            ->get([
                'country.country_id as id',
                DB::raw('IFNULL(lang.country_name, country.country_identifier) AS name'),
                'country.country_dial_code as dial_code',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'dial_code' => (string) $row->dial_code,
                'phone_label' => $row->name.' ('.$row->dial_code.')',
            ])
            ->all();
    }

    private function userExists(int $userId): bool
    {
        return DB::table('tbl_users')
            ->where('user_id', $userId)
            ->whereNull('user_deleted')
            ->exists();
    }

    /** @return array{0: string, 1: string} */
    private function resolveEmailUsername(string $value): array
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($value);
            $username = $this->uniqueUsername((string) strstr($email, '@', true));

            return [$email, $username];
        }

        if (! preg_match('/^[a-zA-Z0-9_]{3,50}$/', $value)) {
            throw new \InvalidArgumentException('Please enter a valid email or username.');
        }

        $username = $value;
        $siteEmail = (string) (DB::table('tbl_configurations')->where('conf_name', 'CONF_SITE_EMAIL')->value('conf_val') ?: '');
        $domain = str_contains($siteEmail, '@') ? substr(strstr($siteEmail, '@'), 1) : 'localhost';
        $email = strtolower($username).'@'.$domain;

        return [$email, $username];
    }

    private function uniqueUsername(string $base): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($base)) ?: 'user';
        $candidate = $base;
        $suffix = 0;

        while (DB::table('tbl_users')->where('user_username', $candidate)->whereNull('user_deleted')->exists()) {
            $candidate = $base.'_'.(++$suffix);
        }

        return $candidate;
    }

    private function generatePassword(): string
    {
        do {
            $password = Str::password(12, letters: true, numbers: true, symbols: false);
        } while (! preg_match(self::PASSWORD_REGEX, $password));

        return $password;
    }

    /** @return array<int, array{id: string, label: string}> */
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

    private function baseQuery(int $langId)
    {
        return DB::table('tbl_users as user')
            ->leftJoin('tbl_user_settings as uset', 'uset.user_id', '=', 'user.user_id')
            ->leftJoin('tbl_users_lang as userlang', function ($join) use ($langId) {
                $join->on('userlang.userlang_user_id', '=', 'user.user_id')
                    ->where('userlang.userlang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries as country', 'country.country_id', '=', 'user.user_country_id')
            ->leftJoin('tbl_countries_lang as countrylang', function ($join) use ($langId) {
                $join->on('countrylang.countrylang_country_id', '=', 'country.country_id')
                    ->where('countrylang.countrylang_lang_id', '=', $langId);
            })
            ->whereNull('user.user_deleted');
    }
}
