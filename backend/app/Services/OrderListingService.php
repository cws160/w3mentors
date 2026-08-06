<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderListingService
{
    public const TYPE_LESSON = 1;

    public const TYPE_SUBSCR = 2;

    public const TYPE_GCLASS = 3;

    public const TYPE_PACKGE = 4;

    public const TYPE_COURSE = 5;

    public const TYPE_WALLET = 6;

    public const TYPE_GFTCRD = 7;

    public const TYPE_SUBPLAN = 18;

    public const STATUS_INPROCESS = 1;

    public const STATUS_COMPLETED = 2;

    public const STATUS_CANCELLED = 3;

    public const PAYMENT_UNPAID = 0;

    public const PAYMENT_PAID = 1;

    public const PAYIN_TYPE = 1;

    /**
     * @param  array{keyword?: string, order_type?: int, order_pmethod_id?: int, date_from?: string, date_to?: string, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>, filters: array<string, mixed>}
     */
    public function list(int $userId, bool $isTeacher, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $idQuery = $this->baseIdQuery($userId, $isTeacher, $filters);
        $total = DB::query()
            ->fromSub($idQuery, 'order_ids')
            ->count();

        $orderIds = (clone $idQuery)
            ->forPage($page, $perPage)
            ->pluck('order_id')
            ->all();

        $items = [];
        if ($orderIds !== []) {
            $rows = DB::table('tbl_orders as orders')
                ->leftJoin('tbl_payment_methods as pmethod', 'pmethod.pmethod_id', '=', 'orders.order_pmethod_id')
                ->whereIn('orders.order_id', $orderIds)
                ->orderByDesc('orders.order_addedon')
                ->get([
                    'orders.order_id',
                    'orders.order_net_amount',
                    'orders.order_type',
                    'orders.order_status',
                    'orders.order_item_count',
                    'orders.order_pmethod_id',
                    'orders.order_payment_status',
                    'orders.order_addedon',
                    'pmethod.pmethod_code',
                ]);

            foreach ($rows as $row) {
                $items[] = $this->formatRow($row);
            }
        }

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
            'filters' => [
                'order_types' => $this->orderTypeOptions($isTeacher),
                'payment_methods' => $this->paymentMethodOptions(),
            ],
        ];
    }

    /**
     * @param  array{keyword?: string, order_type?: int, order_pmethod_id?: int, date_from?: string, date_to?: string}  $filters
     */
    private function baseIdQuery(int $userId, bool $isTeacher, array $filters)
    {
        $query = DB::table('tbl_orders as orders')
            ->leftJoin('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->select('orders.order_id')
            ->groupBy('orders.order_id')
            ->orderByDesc('orders.order_id');

        if ($isTeacher) {
            $query
                ->leftJoin('tbl_order_lessons as ordles', function ($join) {
                    $join->on('orders.order_type', '=', DB::raw((string) self::TYPE_LESSON))
                        ->on('orders.order_id', '=', 'ordles.ordles_order_id');
                })
                ->leftJoin('tbl_order_classes as ordcls', function ($join) {
                    $join->on('orders.order_type', '=', DB::raw((string) self::TYPE_GCLASS))
                        ->on('orders.order_id', '=', 'ordcls.ordcls_order_id');
                })
                ->leftJoin('tbl_group_classes as grpcls', function ($join) {
                    $join->on('orders.order_type', '=', DB::raw((string) self::TYPE_GCLASS))
                        ->on('ordcls.ordcls_grpcls_id', '=', 'grpcls.grpcls_id');
                })
                ->leftJoin('tbl_order_courses as ordcrs', function ($join) {
                    $join->on('orders.order_type', '=', DB::raw((string) self::TYPE_COURSE))
                        ->on('ordcrs.ordcrs_order_id', '=', 'orders.order_id');
                })
                ->leftJoin('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
                ->where(function ($q) use ($userId) {
                    $q->where('ordles.ordles_teacher_id', $userId)
                        ->orWhere('grpcls.grpcls_teacher_id', $userId)
                        ->orWhere('course.course_user_id', $userId);
                })
                ->whereIn('orders.order_type', [
                    self::TYPE_LESSON,
                    self::TYPE_GCLASS,
                    self::TYPE_COURSE,
                ]);
        } else {
            $query->where('orders.order_user_id', $userId);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $orderId = (int) preg_replace('/^O0*/i', '', $keyword);
            $query->where(function ($q) use ($keyword, $orderId) {
                $q->whereRaw('CONCAT(learner.user_first_name, " ", learner.user_last_name) LIKE ?', ['%'.$keyword.'%']);
                if ($orderId > 0) {
                    $q->orWhere('orders.order_id', $orderId);
                }
            });
        }

        if (! empty($filters['order_type'])) {
            $query->where('orders.order_type', (int) $filters['order_type']);
        }

        if (! empty($filters['order_pmethod_id'])) {
            $query->where('orders.order_pmethod_id', (int) $filters['order_pmethod_id']);
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $query->where('orders.order_addedon', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $query->where('orders.order_addedon', '<=', $dateTo.' 23:59:59');
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRow(object $row): array
    {
        $type = (int) $row->order_type;
        $status = (int) $row->order_status;
        $paymentStatus = (int) $row->order_payment_status;
        $pmethodCode = (string) ($row->pmethod_code ?? '');

        return [
            'id' => (int) $row->order_id,
            'order_id_formatted' => $this->formatOrderId((int) $row->order_id),
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'item_count' => (int) ($row->order_item_count ?? 0),
            'amount' => (float) ($row->order_net_amount ?? 0),
            'payment_method_id' => (int) ($row->order_pmethod_id ?? 0),
            'payment_method_code' => $pmethodCode,
            'payment_method_label' => $this->paymentMethodLabel($pmethodCode),
            'payment_status' => $paymentStatus,
            'payment_status_label' => $this->paymentStatusLabel($paymentStatus),
            'is_paid' => $paymentStatus === self::PAYMENT_PAID,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'created_at' => $row->order_addedon ? (string) $row->order_addedon : null,
        ];
    }

    public function formatOrderId(int $orderId): string
    {
        return 'O'.str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function orderTypeOptions(bool $isTeacher): array
    {
        $types = $isTeacher
            ? [self::TYPE_LESSON, self::TYPE_GCLASS, self::TYPE_COURSE]
            : [
                self::TYPE_LESSON,
                self::TYPE_SUBSCR,
                self::TYPE_GCLASS,
                self::TYPE_PACKGE,
                self::TYPE_COURSE,
                self::TYPE_WALLET,
                self::TYPE_GFTCRD,
                self::TYPE_SUBPLAN,
            ];

        return array_map(fn (int $type) => [
            'value' => $type,
            'label' => $this->typeLabel($type),
        ], $types);
    }

    /**
     * @return array<int, array{value: int, code: string, label: string}>
     */
    private function paymentMethodOptions(): array
    {
        return DB::table('tbl_payment_methods')
            ->where('pmethod_type', self::PAYIN_TYPE)
            ->where('pmethod_active', 1)
            ->orderBy('pmethod_order')
            ->get(['pmethod_id', 'pmethod_code'])
            ->map(fn ($row) => [
                'value' => (int) $row->pmethod_id,
                'code' => (string) $row->pmethod_code,
                'label' => $this->paymentMethodLabel((string) $row->pmethod_code),
            ])
            ->all();
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_LESSON => 'Lesson',
            self::TYPE_SUBSCR => 'Recurring lessons',
            self::TYPE_GCLASS => 'Group classes',
            self::TYPE_PACKGE => 'Class packages',
            self::TYPE_COURSE => 'Course purchased',
            self::TYPE_WALLET => 'Wallet recharge',
            self::TYPE_GFTCRD => 'Giftcard purchased',
            self::TYPE_SUBPLAN => 'Subscription plan',
            default => 'N/A',
        };
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_INPROCESS => 'In process',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'N/A',
        };
    }

    private function paymentStatusLabel(int $status): string
    {
        return match ($status) {
            self::PAYMENT_UNPAID => 'Unpaid',
            self::PAYMENT_PAID => 'Paid',
            default => 'N/A',
        };
    }

    private function paymentMethodLabel(string $code): string
    {
        if ($code === '') {
            return 'N/A';
        }

        return match ($code) {
            'StripePay' => 'Stripe',
            'PaypalPay' => 'PayPal',
            'BankTransferPay' => 'Bank transfer',
            'WalletPay' => 'Wallet',
            'AuthorizePay' => 'Authorize.net',
            default => preg_replace('/Pay$/', '', $code) ?: $code,
        };
    }
}
