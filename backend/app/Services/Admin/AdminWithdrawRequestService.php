<?php

namespace App\Services\Admin;

use App\Services\Admin\AdminModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminWithdrawRequestService
{
    public const STATUS_PENDING = 1;

    public const STATUS_COMPLETED = 2;

    public const STATUS_DECLINED = 3;

    public const STATUS_PAYOUT_SENT = 4;

    public const STATUS_PAYOUT_FAILED = 5;

    public const BANK_PAYOUT = 'BankPayout';

    public const PAYPAL_PAYOUT = 'PaypalPayout';

    public function formatRequestNumber(int $id): string
    {
        return '#'.str_pad((string) $id, 7, '0', STR_PAD_LEFT);
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_PAYOUT_SENT => 'Payout sent',
            self::STATUS_PAYOUT_FAILED => 'Payout failed',
            default => (string) $status,
        };
    }

    public function updateStatus(int $withdrawalId, int $status): void
    {
        if (! in_array($status, [self::STATUS_COMPLETED, self::STATUS_DECLINED], true)) {
            throw new RuntimeException('Invalid request', 422);
        }

        $record = DB::table('tbl_user_withdrawal_requests as wr')
            ->join('tbl_payment_methods as pm', 'pm.pmethod_id', '=', 'wr.withdrawal_payment_method_id')
            ->where('wr.withdrawal_id', $withdrawalId)
            ->where('wr.withdrawal_status', self::STATUS_PENDING)
            ->first([
                'wr.*',
                'pm.pmethod_code',
            ]);

        if (! $record) {
            throw new RuntimeException('Invalid request', 404);
        }

        if ($status === self::STATUS_DECLINED) {
            DB::table('tbl_user_withdrawal_requests')
                ->where('withdrawal_id', $withdrawalId)
                ->update(['withdrawal_status' => self::STATUS_DECLINED]);

            return;
        }

        $fee = (float) ($record->withdrawal_transaction_fee ?? 0);
        $amount = (float) $record->withdrawal_amount - $fee;
        if ($amount <= 0) {
            throw new RuntimeException('Amount is zero after gateway fee', 422);
        }

        $walletBalance = (float) (DB::table('tbl_user_settings')
            ->where('user_id', $record->withdrawal_user_id)
            ->value('user_wallet_balance') ?? 0);

        if ((float) $record->withdrawal_amount > $walletBalance) {
            throw new RuntimeException('Insufficient wallet funds', 422);
        }

        if ((string) $record->pmethod_code === self::PAYPAL_PAYOUT) {
            DB::table('tbl_user_withdrawal_requests')
                ->where('withdrawal_id', $withdrawalId)
                ->update([
                    'withdrawal_status' => self::STATUS_PAYOUT_SENT,
                    'withdrawal_transaction_fee' => $fee,
                ]);

            return;
        }

        DB::transaction(function () use ($record, $withdrawalId, $fee) {
            DB::table('tbl_user_withdrawal_requests')
                ->where('withdrawal_id', $withdrawalId)
                ->update([
                    'withdrawal_status' => self::STATUS_COMPLETED,
                    'withdrawal_transaction_fee' => $fee,
                ]);

            DB::table('tbl_user_transactions')->insert([
                'usrtxn_user_id' => $record->withdrawal_user_id,
                'usrtxn_type' => 10,
                'usrtxn_amount' => -abs((float) $record->withdrawal_amount),
                'usrtxn_comment' => 'Payout sent',
                'usrtxn_datetime' => now()->format('Y-m-d H:i:s'),
            ]);

            DB::table('tbl_user_settings')
                ->where('user_id', $record->withdrawal_user_id)
                ->update([
                    'user_wallet_balance' => DB::raw('user_wallet_balance - '.(float) $record->withdrawal_amount),
                ]);
        });
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function exportList(Request $request): array
    {
        $request->merge(['export' => true, 'per_page' => 5000]);

        return app(AdminModuleRegistry::class)->search('withdraw-requests', $request);
    }
}
