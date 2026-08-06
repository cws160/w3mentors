<?php

namespace Database\Seeders;

use App\Services\LegacyPasswordHasher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceUsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $verified = $now->copy()->subYear();

        $accounts = [
            array_merge(config('demo.teacher'), [
                'user_is_teacher' => 1,
                'user_featured' => 1,
                'user_offline_sessions' => 1,
                'user_gender' => 2,
                'user_country_id' => 91,
                'biography' => 'Hi everyone! I am Lydia Deckow. I teach online and love helping students learn at their own pace.',
            ]),
            array_merge(config('demo.learner'), [
                'user_is_teacher' => 0,
                'user_featured' => 0,
                'user_offline_sessions' => 0,
                'user_gender' => 2,
                'user_country_id' => 96,
                'biography' => '',
            ]),
        ];

        foreach ($accounts as $account) {
            $password = LegacyPasswordHasher::hash($account['password']);
            $email = $account['email'];

            $existing = DB::table('tbl_users')->where('user_email', $email)->first();

            $row = [
                'user_first_name' => $account['first_name'],
                'user_last_name' => $account['last_name'],
                'user_email' => $email,
                'user_username' => $account['username'],
                'user_password' => $password,
                'user_timezone' => 'UTC',
                'user_lang_id' => 1,
                'user_currency_id' => 1,
                'user_country_id' => $account['user_country_id'],
                'user_is_teacher' => $account['user_is_teacher'],
                'user_is_affiliate' => 0,
                'user_featured' => $account['user_featured'],
                'user_offline_sessions' => $account['user_offline_sessions'],
                'user_active' => 1,
                'user_verified' => $verified,
                'user_lastseen' => $now,
            ];

            if ($existing) {
                DB::table('tbl_users')->where('user_id', $existing->user_id)->update($row);
                $userId = (int) $existing->user_id;
            } else {
                $userId = (int) DB::table('tbl_users')->insertGetId(array_merge($row, [
                    'user_gender' => $account['user_gender'],
                    'user_created' => $verified,
                    'user_deleted' => null,
                ]));
            }

            if ($account['user_is_teacher'] && $account['biography'] !== '') {
                DB::table('tbl_users_lang')->updateOrInsert(
                    ['userlang_user_id' => $userId, 'userlang_lang_id' => 1],
                    ['user_biography' => $account['biography']]
                );
            }

            if ($account['user_is_teacher']) {
                $this->ensureDemoTeacherSettings($userId);
                $this->ensureDemoTeacherAddress($userId);
            }
        }
    }

    private function ensureDemoTeacherAddress(int $userId): void
    {
        $exists = DB::table('tbl_user_addresses')
            ->where('usradd_user_id', $userId)
            ->whereNull('usradd_deleted')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now()->format('Y-m-d H:i:s');
        DB::table('tbl_user_addresses')->insert([
            'usradd_user_id' => $userId,
            'usradd_phone' => '9876543210',
            'usradd_address' => '12 MG Road',
            'usradd_city' => 'Bengaluru',
            'usradd_state_id' => 148,
            'usradd_country_id' => 91,
            'usradd_zipcode' => '560001',
            'usradd_place_id' => '',
            'usradd_place_name' => '',
            'usradd_latitude' => 12.9716,
            'usradd_longitude' => 77.5946,
            'usradd_default' => 1,
            'usradd_created' => $now,
            'usradd_updated' => $now,
            'usradd_type' => 1,
            'usradd_deleted' => null,
        ]);
    }

    private function ensureDemoTeacherSettings(int $userId): void
    {
        $exists = DB::table('tbl_user_settings')->where('user_id', $userId)->exists();
        if ($exists) {
            DB::table('tbl_user_settings')->where('user_id', $userId)->update([
                'user_book_before' => 0,
                'user_slots' => '["15","30","60"]',
            ]);

            return;
        }

        DB::table('tbl_user_settings')->insert([
            'user_id' => $userId,
            'user_dashboard' => 2,
            'user_registered_as' => 1,
            'user_trial_enabled' => 1,
            'user_book_before' => 0,
            'user_wallet_balance' => 0,
            'user_apple_id' => '',
            'user_apple_token' => '',
            'user_device_token' => '',
            'user_slots' => '["15","30","60"]',
            'user_zoom_status' => 1,
            'user_autorenew_subscription' => 1,
            'user_reward_points' => 0,
        ]);
    }
}
