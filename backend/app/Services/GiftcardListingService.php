<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GiftcardListingService
{
    public const ORDER_TYPE_GFTCRD = 7;

    public const ORDER_STATUS_COMPLETED = 2;

    public const PAYMENT_PAID = 1;

    public const TYPE_PURCHASED = 1;

    public const TYPE_RECEIVED = 2;

    public const STATUS_UNUSED = 0;

    public const STATUS_USED = 1;

    public const STATUS_CANCELLED = 2;

    /**
     * @param  array{keyword?: string, giftcard_type?: int, giftcard_status?: int, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>, filters: array<string, mixed>}
     */
    public function list(int $userId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $giftcardType = (int) ($filters['giftcard_type'] ?? self::TYPE_PURCHASED);

        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_giftcards as ordgift', 'ordgift.ordgift_order_id', '=', 'orders.order_id')
            ->join('tbl_users as user', 'user.user_id', '=', 'orders.order_user_id')
            ->where('orders.order_type', self::ORDER_TYPE_GFTCRD)
            ->where('orders.order_payment_status', self::PAYMENT_PAID)
            ->where('orders.order_status', self::ORDER_STATUS_COMPLETED);

        if ($giftcardType === self::TYPE_RECEIVED) {
            $query->where('ordgift.ordgift_receiver_id', $userId);
        } else {
            $query->where('orders.order_user_id', $userId);
        }

        if (isset($filters['giftcard_status']) && $filters['giftcard_status'] !== '' && $filters['giftcard_status'] !== null) {
            $query->where('ordgift.ordgift_status', (int) $filters['giftcard_status']);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $orderId = (int) preg_replace('/^O/i', '', $keyword);
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);

            $query->where(function ($q) use ($giftcardType, $escaped, $orderId) {
                if ($giftcardType === self::TYPE_RECEIVED) {
                    $q->whereRaw('CONCAT(user.user_first_name, " ", user.user_last_name) LIKE ?', ['%'.$escaped.'%'])
                        ->orWhere('user.user_email', 'like', '%'.$escaped.'%')
                        ->orWhere('ordgift.ordgift_code', 'like', '%'.$escaped.'%');
                } else {
                    $q->where('ordgift.ordgift_code', 'like', '%'.$escaped.'%')
                        ->orWhere('ordgift.ordgift_receiver_name', 'like', '%'.$escaped.'%')
                        ->orWhere('ordgift.ordgift_receiver_email', 'like', '%'.$escaped.'%');
                }
                if ($orderId > 0) {
                    $q->orWhere('ordgift.ordgift_id', $orderId)
                        ->orWhere('ordgift.ordgift_order_id', $orderId);
                }
            });
        }

        $total = (clone $query)->count('ordgift.ordgift_id');
        $rows = $query
            ->orderByDesc('orders.order_id')
            ->forPage($page, $perPage)
            ->get([
                'orders.order_id',
                'orders.order_total_amount',
                'orders.order_addedon',
                'ordgift.ordgift_id',
                'ordgift.ordgift_code',
                'ordgift.ordgift_status',
                'ordgift.ordgift_receiver_id',
                'ordgift.ordgift_receiver_name',
                'ordgift.ordgift_receiver_email',
                'user.user_first_name',
                'user.user_last_name',
                'user.user_email',
            ]);

        $items = $rows->map(function ($row) use ($userId) {
            $status = (int) $row->ordgift_status;
            $receiverId = (int) ($row->ordgift_receiver_id ?? 0);
            $isReceived = $receiverId === $userId;

            return [
                'id' => (int) $row->ordgift_id,
                'order_id' => (int) $row->order_id,
                'order_id_formatted' => $this->formatOrderId((int) $row->order_id),
                'code' => (string) $row->ordgift_code,
                'amount' => (float) ($row->order_total_amount ?? 0),
                'is_received' => $isReceived,
                'sender_name' => trim((string) $row->user_first_name.' '.(string) $row->user_last_name),
                'receiver_name' => (string) ($row->ordgift_receiver_name ?? ''),
                'receiver_email' => (string) ($row->ordgift_receiver_email ?? ''),
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'status_class' => $status === self::STATUS_USED ? 'color-secondary' : 'color-primary',
                'created_at' => $row->order_addedon ? (string) $row->order_addedon : null,
            ];
        })->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
            'filters' => [
                'giftcard_type' => $giftcardType,
                'giftcard_types' => $this->typeOptions(),
                'giftcard_statuses' => $this->statusOptions(),
            ],
        ];
    }

    public function formatOrderId(int $orderId): string
    {
        return 'O'.str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function typeOptions(): array
    {
        return [
            ['value' => self::TYPE_PURCHASED, 'label' => 'Purchased'],
            ['value' => self::TYPE_RECEIVED, 'label' => 'Received'],
        ];
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => self::STATUS_UNUSED, 'label' => 'Unused'],
            ['value' => self::STATUS_USED, 'label' => 'Used'],
            ['value' => self::STATUS_CANCELLED, 'label' => 'Cancelled'],
        ];
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_USED => 'Used',
            self::STATUS_UNUSED => 'Unused',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'N/A',
        };
    }
}
