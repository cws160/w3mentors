<?php

namespace App\Services\Admin;

use App\Services\LegacyPasswordHasher;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminManageAdminService
{
    private const PASSWORD_REGEX = '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%-_]{8,15}$/';

    /** @return array<string, mixed> */
    public function show(int $adminId): array
    {
        $row = DB::table('tbl_admin')
            ->where('admin_id', $adminId)
            ->first([
                'admin_id as id',
                'admin_username as username',
                'admin_name as full_name',
                'admin_email as email',
                'admin_timezone as timezone',
                'admin_active as active',
            ]);

        if (! $row) {
            throw new RuntimeException('Invalid request', 404);
        }

        return (array) $row;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return [
            'default_timezone' => $this->defaultTimezone(),
            'timezones' => $this->timezoneOptions(),
            'active_options' => [
                ['id' => 1, 'label' => 'Active'],
                ['id' => 0, 'label' => 'Inactive'],
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $fullName = trim((string) ($data['full_name'] ?? $data['admin_name'] ?? ''));
        $username = trim((string) ($data['username'] ?? $data['admin_username'] ?? ''));
        $email = trim((string) ($data['email'] ?? $data['admin_email'] ?? ''));
        $timezone = trim((string) ($data['timezone'] ?? $data['admin_timezone'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');
        $active = (int) ($data['active'] ?? $data['admin_active'] ?? 1);

        $this->validateAdminFields($fullName, $username, $email, $timezone, true, $password, $confirmPassword);

        if (DB::table('tbl_admin')->where('admin_username', $username)->exists()) {
            throw new RuntimeException('Username already exists', 422);
        }
        if (DB::table('tbl_admin')->where('admin_email', $email)->exists()) {
            throw new RuntimeException('Email already exists', 422);
        }

        return (int) DB::table('tbl_admin')->insertGetId([
            'admin_username' => $username,
            'admin_password' => LegacyPasswordHasher::hash($password),
            'admin_email' => $email,
            'admin_name' => $fullName,
            'admin_timezone' => $timezone,
            'admin_active' => $active ? 1 : 0,
            'admin_password_update' => 1,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $adminId, array $data, int $loggedInAdminId): void
    {
        $row = DB::table('tbl_admin')->where('admin_id', $adminId)->first();
        if (! $row) {
            throw new RuntimeException('Invalid request', 404);
        }

        $fullName = trim((string) ($data['full_name'] ?? $data['admin_name'] ?? ''));
        $timezone = trim((string) ($data['timezone'] ?? $data['admin_timezone'] ?? ''));
        $active = (int) ($data['active'] ?? $data['admin_active'] ?? (int) $row->admin_active);

        if ($fullName === '') {
            throw new RuntimeException('Full name is required', 422);
        }
        if ($timezone === '' || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new RuntimeException('Please select a timezone', 422);
        }
        if ($adminId === $loggedInAdminId && $active === 0) {
            throw new RuntimeException('Cannot deactivate own profile', 422);
        }

        DB::table('tbl_admin')
            ->where('admin_id', $adminId)
            ->update([
                'admin_name' => $fullName,
                'admin_timezone' => $timezone,
                'admin_active' => $active ? 1 : 0,
            ]);
    }

    /** @param array<string, mixed> $data */
    public function changePassword(int $adminId, array $data): void
    {
        if ($adminId < 1) {
            throw new RuntimeException('Invalid request', 404);
        }

        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');

        if ($password === '' || ! preg_match(self::PASSWORD_REGEX, $password)) {
            throw new RuntimeException('Please enter a valid password', 422);
        }
        if ($password !== $confirmPassword) {
            throw new RuntimeException('Passwords do not match', 422);
        }
        if (! DB::table('tbl_admin')->where('admin_id', $adminId)->exists()) {
            throw new RuntimeException('Invalid request', 404);
        }

        DB::table('tbl_admin')
            ->where('admin_id', $adminId)
            ->update([
                'admin_password' => LegacyPasswordHasher::hash($password),
                'admin_password_update' => 1,
            ]);
    }

    public function updateStatus(int $adminId, int $status, int $loggedInAdminId): void
    {
        if ($adminId < 1) {
            throw new RuntimeException('Invalid request', 404);
        }
        if ($adminId === $loggedInAdminId) {
            throw new RuntimeException('Cannot update own status', 422);
        }
        if (! DB::table('tbl_admin')->where('admin_id', $adminId)->exists()) {
            throw new RuntimeException('Invalid request', 404);
        }

        DB::table('tbl_admin')
            ->where('admin_id', $adminId)
            ->update(['admin_active' => $status ? 1 : 0]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function exportList(Request $request): array
    {
        $request->merge(['export' => true, 'per_page' => 5000]);

        return app(AdminModuleRegistry::class)->search('admin-users', $request) ?? ['data' => [], 'meta' => []];
    }

    private function validateAdminFields(
        string $fullName,
        string $username,
        string $email,
        string $timezone,
        bool $isCreate,
        string $password = '',
        string $confirmPassword = '',
    ): void {
        if ($fullName === '') {
            throw new RuntimeException('Full name is required', 422);
        }
        if ($username === '' || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{5,19}$/', $username)) {
            throw new RuntimeException('Please enter a valid username', 422);
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email', 422);
        }
        if ($timezone === '' || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new RuntimeException('Please select a timezone', 422);
        }
        if ($isCreate) {
            if ($password === '' || ! preg_match(self::PASSWORD_REGEX, $password)) {
                throw new RuntimeException('Please enter a valid password', 422);
            }
            if ($password !== $confirmPassword) {
                throw new RuntimeException('Passwords do not match', 422);
            }
        }
    }

    private function defaultTimezone(): string
    {
        return (string) (DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_TIMEZONE')
            ->value('conf_val') ?: 'UTC');
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
}
