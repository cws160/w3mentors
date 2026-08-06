<?php

namespace Database\Seeders;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GiftCardOrdersDemoSeeder extends Seeder
{
    private const STRIPE_PAY_METHOD_ID = 3;

    public function run(): void
    {
        $buyerIds = [
            $this->userIdByEmail(config('demo.learner.email')),
            $this->userIdByEmail(config('demo.teacher.email')),
            $this->userIdByEmail('lonie.wintheiser@dummyid.com'),
            $this->userIdByEmail('rocio.medhurst@dummyid.com'),
        ];
        $buyerIds = array_values(array_filter($buyerIds, fn (int $id): bool => $id > 0));

        if ($buyerIds === []) {
            $this->command?->warn('No users found for gift card order demo data - run ReferenceUsersSeeder first.');

            return;
        }

        $currencyCode = (string) (DB::table('tbl_currencies')
            ->where('currency_active', 1)
            ->orderBy('currency_order')
            ->value('currency_code') ?? 'USD');
        $currencyValue = (float) (DB::table('tbl_currencies')
            ->where('currency_code', $currencyCode)
            ->value('currency_value') ?? 1);

        $orders = [
            [
                'buyer_index' => 0,
                'amount' => 50.00,
                'payment_status' => AdminOrderHelper::ISPAID,
                'order_status' => AdminOrderHelper::STATUS_COMPLETED,
                'giftcard_status' => 0,
                'receiver_name' => 'Lydia Deckow',
                'receiver_email' => 'lydia.deckow@dummyid.com',
                'code' => 'GFT-JUN-001',
                'addedon' => '2025-06-16 08:10:00',
                'expiry' => '2025-08-16 08:10:00',
                'usedon' => null,
            ],
            [
                'buyer_index' => 1,
                'amount' => 75.00,
                'payment_status' => AdminOrderHelper::ISPAID,
                'order_status' => AdminOrderHelper::STATUS_COMPLETED,
                'giftcard_status' => 1,
                'receiver_name' => 'Zigepu',
                'receiver_email' => config('demo.learner.email'),
                'code' => 'GFT-MAY-024',
                'addedon' => '2025-05-31 09:11:00',
                'expiry' => '2025-07-31 09:11:00',
                'usedon' => '2025-06-05 10:42:00',
            ],
            [
                'buyer_index' => 2,
                'amount' => 100.00,
                'payment_status' => AdminOrderHelper::UNPAID,
                'order_status' => AdminOrderHelper::STATUS_INPROCESS,
                'giftcard_status' => 0,
                'receiver_name' => 'Cameron Annie',
                'receiver_email' => 'cameron.annie@dummyid.com',
                'code' => 'GFT-SEP-022',
                'addedon' => '2024-09-13 10:20:00',
                'expiry' => '2024-11-13 10:20:00',
                'usedon' => null,
            ],
            [
                'buyer_index' => 3,
                'amount' => 25.00,
                'payment_status' => AdminOrderHelper::ISPAID,
                'order_status' => AdminOrderHelper::STATUS_CANCELLED,
                'giftcard_status' => 2,
                'receiver_name' => 'Alexandria Halvorson',
                'receiver_email' => 'alexandria.halvorson@dummyid.com',
                'code' => 'GFT-AUG-019',
                'addedon' => '2024-08-18 06:20:00',
                'expiry' => '2024-10-18 06:20:00',
                'usedon' => null,
            ],
        ];

        $inserted = 0;

        foreach ($orders as $demoOrder) {
            $exists = DB::table('tbl_order_giftcards')
                ->where('ordgift_code', $demoOrder['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            $buyerId = $buyerIds[$demoOrder['buyer_index'] % count($buyerIds)];
            $orderId = (int) DB::table('tbl_orders')->insertGetId([
                'order_type' => AdminOrderHelper::TYPE_GFTCRD,
                'order_user_id' => $buyerId,
                'order_item_count' => 1,
                'order_pmethod_id' => self::STRIPE_PAY_METHOD_ID,
                'order_discount_value' => 0,
                'order_credit_discount' => 0,
                'order_reward_value' => 0,
                'order_currency_code' => $currencyCode,
                'order_currency_value' => $currencyValue,
                'order_payment_status' => $demoOrder['payment_status'],
                'order_status' => $demoOrder['order_status'],
                'order_total_amount' => $demoOrder['amount'],
                'order_net_amount' => $demoOrder['amount'],
                'order_addedon' => $demoOrder['addedon'],
                'order_related_order_id' => null,
            ]);

            DB::table('tbl_order_giftcards')->insert([
                'ordgift_order_id' => $orderId,
                'ordgift_code' => $demoOrder['code'],
                'ordgift_receiver_id' => $this->userIdByEmail((string) $demoOrder['receiver_email']),
                'ordgift_receiver_name' => $demoOrder['receiver_name'],
                'ordgift_receiver_email' => $demoOrder['receiver_email'],
                'ordgift_status' => $demoOrder['giftcard_status'],
                'ordgift_expiry' => $demoOrder['expiry'],
                'ordgift_usedon' => $demoOrder['usedon'],
            ]);

            $inserted++;
        }

        $this->command?->info("Seeded {$inserted} missing gift card order demo row(s).");
    }

    private function userIdByEmail(?string $email): int
    {
        if ($email === null || $email === '') {
            return 0;
        }

        return (int) (DB::table('tbl_users')->where('user_email', $email)->value('user_id') ?? 0);
    }
}
