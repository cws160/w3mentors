<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class WithdrawListingService
{
    public const STATUS_PENDING = 1;

    public const STATUS_COMPLETED = 2;

    public const STATUS_DECLINED = 3;

    public const STATUS_PAYOUT_SENT = 4;

    public const STATUS_PAYOUT_FAILED = 5;

    /**
     * @param  array{keyword?: string, date_from?: string, date_to?: string, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>, balance: float, can_withdraw: bool}
     */
    public function list(int $userId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $balance = round((float) (DB::table('tbl_user_settings')
            ->where('user_id', $userId)
            ->value('user_wallet_balance') ?? 0), 2);

        $query = DB::table('tbl_user_withdrawal_requests')
            ->where('withdrawal_user_id', $userId);

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $first = strtoupper(mb_substr($keyword, 0, 1, 'UTF-8'));
                if ($first === '#') {
                    $id = (int) ltrim(str_replace('#', '', $keyword), '0');
                    if ($id > 0) {
                        $q->where('withdrawal_id', $id);
                    }
                } else {
                    $q->where('withdrawal_id', 'like', '%'.$keyword.'%')
                        ->orWhere('withdrawal_comments', 'like', '%'.$keyword.'%');
                }
            });
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $query->where('withdrawal_request_date', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $query->where('withdrawal_request_date', '<=', $dateTo.' 23:59:59');
        }

        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('withdrawal_id')
            ->forPage($page, $perPage)
            ->get([
                'withdrawal_id',
                'withdrawal_amount',
                'withdrawal_transaction_fee',
                'withdrawal_comments',
                'withdrawal_request_date',
                'withdrawal_status',
            ]);

        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->withdrawal_id,
            'request_id_formatted' => $this->formatRequestNumber((int) $row->withdrawal_id),
            'amount' => (float) ($row->withdrawal_amount ?? 0),
            'transaction_fee' => (float) ($row->withdrawal_transaction_fee ?? 0),
            'comments' => (string) ($row->withdrawal_comments ?? ''),
            'status' => (int) ($row->withdrawal_status ?? 0),
            'status_label' => $this->statusLabel((int) ($row->withdrawal_status ?? 0)),
            'requested_at' => $row->withdrawal_request_date ? (string) $row->withdrawal_request_date : null,
        ])->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
            'balance' => $balance,
            'can_withdraw' => $this->canWithdraw($userId, $balance),
        ];
    }

    public function formatRequestNumber(int $id): string
    {
        return '#'.str_pad((string) $id, 7, '0', STR_PAD_LEFT);
    }

    private function canWithdraw(int $userId, float $balance): bool
    {
        $minimum = (float) ($this->configValue('CONF_MIN_WITHDRAW_LIMIT') ?? 0);
        if ($balance < $minimum) {
            return false;
        }

        $minInterval = (int) ($this->configValue('CONF_MIN_INTERVAL_WITHDRAW_REQUESTS') ?? 0);
        if ($minInterval <= 0) {
            return true;
        }

        $last = DB::table('tbl_user_withdrawal_requests')
            ->where('withdrawal_user_id', $userId)
            ->orderByDesc('withdrawal_id')
            ->value('withdrawal_request_date');

        if (! $last) {
            return true;
        }

        $nextAllowed = strtotime((string) $last." +{$minInterval} days");

        return $nextAllowed !== false && time() >= $nextAllowed;
    }

    private function configValue(string $key): mixed
    {
        return DB::table('tbl_configurations')->where('conf_name', $key)->value('conf_val');
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_PAYOUT_SENT => 'Payout sent',
            self::STATUS_PAYOUT_FAILED => 'Payout failed',
            default => 'N/A',
        };
    }
}
