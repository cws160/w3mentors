<?php

namespace Database\Seeders;

use App\Services\LegacyPasswordHasher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WithdrawRequestsDemoSeeder extends Seeder
{
    private const PAYPAL_PAYOUT_METHOD_ID = 4;

    public function run(): void
    {
        $lydiaId = (int) (DB::table('tbl_users')->where('user_email', config('demo.teacher.email'))->value('user_id') ?? 0);
        $zigepuId = (int) (DB::table('tbl_users')->where('user_email', config('demo.learner.email'))->value('user_id') ?? 0);
        $etId = $this->ensureEtUser();

        if ($lydiaId < 1) {
            $this->command?->warn('Teacher demo user missing — run ReferenceUsersSeeder first.');

            return;
        }

        if ($zigepuId > 0) {
            DB::table('tbl_users')->where('user_id', $zigepuId)->update([
                'user_first_name' => 'Zigepu',
                'user_last_name' => '',
            ]);
        }

        $requests = [
            [
                'withdrawal_id' => 51,
                'withdrawal_user_id' => $lydiaId,
                'withdrawal_amount' => 10.00,
                'withdrawal_transaction_fee' => 1.00,
                'withdrawal_paypal_email_id' => 'sukh017@mailinator.com',
                'withdrawal_request_date' => '2025-06-16 08:10:00',
            ],
            [
                'withdrawal_id' => 24,
                'withdrawal_user_id' => $lydiaId,
                'withdrawal_amount' => 10.00,
                'withdrawal_transaction_fee' => 1.00,
                'withdrawal_paypal_email_id' => 'lydia.deckow@dummyid.com',
                'withdrawal_request_date' => '2025-05-31 09:11:00',
            ],
            [
                'withdrawal_id' => 22,
                'withdrawal_user_id' => $lydiaId,
                'withdrawal_amount' => 400.00,
                'withdrawal_transaction_fee' => 40.00,
                'withdrawal_paypal_email_id' => 'lydia.deckow@dummyid.com',
                'withdrawal_request_date' => '2025-06-16 08:10:00',
            ],
            [
                'withdrawal_id' => 21,
                'withdrawal_user_id' => $etId,
                'withdrawal_amount' => 10.00,
                'withdrawal_transaction_fee' => 1.00,
                'withdrawal_paypal_email_id' => 'fullname',
                'withdrawal_request_date' => '2025-05-31 11:35:00',
            ],
            [
                'withdrawal_id' => 19,
                'withdrawal_user_id' => $zigepuId > 0 ? $zigepuId : $lydiaId,
                'withdrawal_amount' => 100.00,
                'withdrawal_transaction_fee' => 1.00,
                'withdrawal_paypal_email_id' => 'zigepu@mailinator.com',
                'withdrawal_request_date' => '2024-08-18 06:20:00',
            ],
        ];

        foreach ($requests as $request) {
            $id = (int) $request['withdrawal_id'];
            $row = [
                'withdrawal_user_id' => $request['withdrawal_user_id'],
                'withdrawal_amount' => $request['withdrawal_amount'],
                'withdrawal_transaction_fee' => $request['withdrawal_transaction_fee'],
                'withdrawal_payment_method_id' => self::PAYPAL_PAYOUT_METHOD_ID,
                'withdrawal_bank' => '',
                'withdrawal_account_holder_name' => '',
                'withdrawal_account_number' => '',
                'withdrawal_ifc_swift_code' => '',
                'withdrawal_bank_address' => '',
                'withdrawal_comments' => '',
                'withdrawal_request_date' => $request['withdrawal_request_date'],
                'withdrawal_status' => 1,
                'withdrawal_paypal_email_id' => $request['withdrawal_paypal_email_id'],
                'withdrawal_response' => '',
            ];

            $exists = DB::table('tbl_user_withdrawal_requests')->where('withdrawal_id', $id)->exists();
            if ($exists) {
                DB::table('tbl_user_withdrawal_requests')->where('withdrawal_id', $id)->update($row);
            } else {
                DB::table('tbl_user_withdrawal_requests')->insert(array_merge(['withdrawal_id' => $id], $row));
            }
        }

        $maxId = (int) DB::table('tbl_user_withdrawal_requests')->max('withdrawal_id');
        if ($maxId > 0) {
            DB::statement('ALTER TABLE tbl_user_withdrawal_requests AUTO_INCREMENT = '.($maxId + 1));
        }

        $this->command?->info('Seeded '.count($requests).' withdraw requests (legacy demo rows).');
    }

    private function ensureEtUser(): int
    {
        $email = 'et@mailinator.com';
        $existing = DB::table('tbl_users')->where('user_email', $email)->first();
        if ($existing) {
            DB::table('tbl_users')->where('user_id', $existing->user_id)->update([
                'user_first_name' => 'Et',
                'user_last_name' => 'Et',
                'user_active' => 1,
            ]);

            return (int) $existing->user_id;
        }

        $verified = now()->subYear();

        return (int) DB::table('tbl_users')->insertGetId([
            'user_first_name' => 'Et',
            'user_last_name' => 'Et',
            'user_email' => $email,
            'user_username' => 'et',
            'user_password' => LegacyPasswordHasher::hash('lydia@123'),
            'user_timezone' => 'UTC',
            'user_lang_id' => 1,
            'user_currency_id' => 1,
            'user_country_id' => 91,
            'user_gender' => 2,
            'user_is_teacher' => 0,
            'user_is_affiliate' => 0,
            'user_featured' => 0,
            'user_offline_sessions' => 0,
            'user_active' => 1,
            'user_verified' => $verified,
            'user_created' => $verified,
            'user_lastseen' => now(),
            'user_deleted' => null,
        ]);
    }
}
