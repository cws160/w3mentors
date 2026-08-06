<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class WalletListingService
{
    /**
     * @param  array{keyword?: string, date_from?: string, date_to?: string, page?: int, per_page?: int}  $filters
     * @return array{balance: float, transactions: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(int $userId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $balance = round((float) (DB::table('tbl_user_settings')
            ->where('user_id', $userId)
            ->value('user_wallet_balance') ?? 0), 2);

        $query = DB::table('tbl_user_transactions')
            ->where('usrtxn_user_id', $userId);

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $parts = explode('-', $keyword);
            $needle = ltrim(trim($parts[1] ?? $parts[0]), '0');
            if ($needle !== '') {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $needle);
                $query->where(function ($q) use ($escaped) {
                    $q->where('usrtxn_id', 'like', '%'.$escaped.'%')
                        ->orWhere('usrtxn_comment', 'like', '%'.$escaped.'%');
                });
            }
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $query->where('usrtxn_datetime', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $query->where('usrtxn_datetime', '<=', $dateTo.' 23:59:59');
        }

        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('usrtxn_id')
            ->forPage($page, $perPage)
            ->get([
                'usrtxn_id',
                'usrtxn_amount',
                'usrtxn_type',
                'usrtxn_comment',
                'usrtxn_datetime',
            ]);

        $transactions = $rows->map(fn ($row) => [
            'id' => (int) $row->usrtxn_id,
            'txn_id_formatted' => $this->formatTxnId((int) $row->usrtxn_id),
            'amount' => (float) ($row->usrtxn_amount ?? 0),
            'type' => (int) ($row->usrtxn_type ?? 0),
            'type_label' => $this->typeLabel((int) ($row->usrtxn_type ?? 0)),
            'comment' => (string) ($row->usrtxn_comment ?? ''),
            'created_at' => $row->usrtxn_datetime ? (string) $row->usrtxn_datetime : null,
        ])->all();

        return [
            'balance' => $balance,
            'transactions' => $transactions,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    public function formatTxnId(int $txnId): string
    {
        return 'TXN-'.str_pad((string) $txnId, 7, '0', STR_PAD_LEFT);
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Lesson ordered',
            2 => 'Recurring lessons ordered',
            3 => 'Group class ordered',
            4 => 'Package ordered',
            5 => 'Course ordered',
            6 => 'Wallet recharge',
            7 => 'Gift card ordered',
            8 => 'Learner refund',
            9 => 'Teacher payment',
            10 => 'Money withdraw',
            11 => 'Money deposit',
            12 => 'Gift card redeem',
            13 => 'Support debit',
            14 => 'Support credit',
            15 => 'Reward points redeemed',
            16 => 'Referral order commission',
            17 => 'Referral signup commission',
            18 => 'Subscription plan ordered',
            19 => 'Subscription plan refund',
            default => 'N/A',
        };
    }
}
