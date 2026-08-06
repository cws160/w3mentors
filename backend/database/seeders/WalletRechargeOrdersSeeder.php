<?php

namespace Database\Seeders;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Backfill wallet recharge orders from legacy wallet deposit transactions.
 *
 * The sample/old database has no tbl_orders rows with order_type = 6, but it does
 * contain admin wallet credits (tbl_user_transactions.usrtxn_type = 11).
 */
class WalletRechargeOrdersSeeder extends Seeder
{
    private const MONEY_DEPOSIT_TXN_TYPE = 11;

    private const STRIPE_PAY_METHOD_ID = 3;

    public function run(): void
    {
        $existing = (int) DB::table('tbl_orders')
            ->where('order_type', AdminOrderHelper::TYPE_WALLET)
            ->count();

        if ($existing > 0) {
            $this->command?->info("Skipping wallet recharge backfill — {$existing} wallet order(s) already exist.");

            return;
        }

        $deposits = DB::table('tbl_user_transactions')
            ->where('usrtxn_type', self::MONEY_DEPOSIT_TXN_TYPE)
            ->orderBy('usrtxn_id')
            ->get(['usrtxn_id', 'usrtxn_user_id', 'usrtxn_amount', 'usrtxn_datetime']);

        if ($deposits->isEmpty()) {
            $this->command?->warn('No wallet deposit transactions found to backfill.');

            return;
        }

        $currencyCode = (string) (DB::table('tbl_currencies')->where('currency_active', 1)->orderBy('currency_order')->value('currency_code') ?? 'USD');
        $currencyValue = (float) (DB::table('tbl_currencies')->where('currency_code', $currencyCode)->value('currency_value') ?? 1);

        $inserted = 0;

        foreach ($deposits as $deposit) {
            $userId = (int) $deposit->usrtxn_user_id;
            if ($userId < 1) {
                continue;
            }

            $userExists = DB::table('tbl_users')->where('user_id', $userId)->whereNull('user_deleted')->exists();
            if (! $userExists) {
                continue;
            }

            DB::table('tbl_orders')->insert([
                'order_type' => AdminOrderHelper::TYPE_WALLET,
                'order_user_id' => $userId,
                'order_item_count' => 1,
                'order_pmethod_id' => self::STRIPE_PAY_METHOD_ID,
                'order_discount_value' => 0,
                'order_credit_discount' => 0,
                'order_reward_value' => 0,
                'order_currency_code' => $currencyCode,
                'order_currency_value' => $currencyValue,
                'order_payment_status' => AdminOrderHelper::ISPAID,
                'order_status' => AdminOrderHelper::STATUS_COMPLETED,
                'order_total_amount' => (float) $deposit->usrtxn_amount,
                'order_net_amount' => (float) $deposit->usrtxn_amount,
                'order_addedon' => (string) $deposit->usrtxn_datetime,
                'order_related_order_id' => null,
            ]);

            $inserted++;
        }

        $this->command?->info("Backfilled {$inserted} wallet recharge order(s) from legacy wallet deposits.");
    }
}
