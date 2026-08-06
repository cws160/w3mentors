<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GroupClassListingService
{
    public const TYPE_REGULAR = 1;

    public const TYPE_PACKAGE = 2;

    public const ORDER_PAID = 1;

    public const STATUS_SCHEDULED = 1;

    public const STATUS_COMPLETED = 2;

    public const STATUS_CANCELLED = 3;

    public const QUIZ_TYPE_GCLASS = 2;

    /**
     * @param  array{keyword?: string, status?: int, page?: int, per_page?: int, offline?: mixed, start_date?: string, end_date?: string}  $filters
     * @return array{items: array<int, array<string, mixed>>, groups: array<int, array{key: string, classes: array<int, array<string, mixed>>}>, meta: array<string, int>}
     */
    public function list(int $userId, bool $isTeacher, int $classType, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        if ($isTeacher) {
            $query = DB::table('tbl_group_classes as grpcls')
                ->where('grpcls.grpcls_teacher_id', $userId)
                ->where('grpcls.grpcls_type', $classType);
            if ($classType === self::TYPE_PACKAGE) {
                $query->where('grpcls.grpcls_parent', 0);
            }
        } else {
            $query = DB::table('tbl_order_classes as ordcls')
                ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
                ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
                ->where('orders.order_user_id', $userId)
                ->where('orders.order_payment_status', self::ORDER_PAID)
                ->where('grpcls.grpcls_type', $classType);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where('grpcls.grpcls_title', 'like', '%'.$keyword.'%');
        }

        if (isset($filters['offline']) && $filters['offline'] !== '' && $filters['offline'] !== null) {
            $query->where('grpcls.grpcls_offline', (int) $filters['offline']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('grpcls.grpcls_start_datetime', '>=', $filters['start_date'].' 00:00:00');
        }
        if (! empty($filters['end_date'])) {
            $query->where('grpcls.grpcls_end_datetime', '<=', $filters['end_date'].' 23:59:59');
        }

        $defaultStatus = $isTeacher && $classType === self::TYPE_REGULAR
            ? self::STATUS_SCHEDULED
            : -1;
        $status = array_key_exists('status', $filters) && $filters['status'] !== null
            ? (int) $filters['status']
            : $defaultStatus;
        if ($status >= 0) {
            if ($isTeacher) {
                $query->where('grpcls.grpcls_status', $status);
            } else {
                $query->where('ordcls.ordcls_status', $status);
            }
        }

        if ($classType === self::TYPE_PACKAGE && $isTeacher) {
            $query->orderByDesc('grpcls.grpcls_id');
        } else {
            $query->orderBy('grpcls.grpcls_start_datetime');
        }

        $countQuery = clone $query;
        if ($isTeacher) {
            $total = $countQuery->count('grpcls.grpcls_id');
        } else {
            $total = $countQuery->count('ordcls.ordcls_id');
        }

        $select = [
            'grpcls.grpcls_id',
            'grpcls.grpcls_title',
            'grpcls.grpcls_start_datetime',
            'grpcls.grpcls_end_datetime',
            'grpcls.grpcls_offline',
            'grpcls.grpcls_status',
            'grpcls.grpcls_entry_fee',
            'grpcls.grpcls_booked_seats',
            'grpcls.grpcls_total_seats',
            'grpcls.grpcls_parent',
        ];
        if (! $isTeacher) {
            $select[] = 'ordcls.ordcls_id';
            $select[] = 'ordcls.ordcls_status';
        }

        $rows = $query->forPage($page, $perPage)->get($select);
        $classIds = $rows->pluck('grpcls_id')->map(fn ($id) => (int) $id)->all();
        $plans = $this->loadClassPlans($classIds);
        $quizCounts = $this->loadQuizCounts($classIds);

        $items = $rows
            ->map(fn ($row) => $this->formatRow($row, $isTeacher, $plans, $quizCounts))
            ->all();
        $groups = $this->groupByDate(collect($items));

        return [
            'items' => $items,
            'groups' => $groups,
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /**
     * @param  array<int>  $classIds
     * @return array<int, object>
     */
    private function loadClassPlans(array $classIds): array
    {
        if ($classIds === []) {
            return [];
        }

        $rows = DB::table('tbl_plan_classes as plancls')
            ->join('tbl_plans as plan', 'plan.plan_id', '=', 'plancls.plancls_plan_id')
            ->whereIn('plancls.plancls_grpcls_id', $classIds)
            ->get([
                'plancls.plancls_grpcls_id',
                'plan.plan_id',
                'plan.plan_title',
                'plancls.plancls_id',
            ]);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->plancls_grpcls_id] = $row;
        }

        return $map;
    }

    /**
     * @param  array<int>  $classIds
     * @return array<int, int>
     */
    private function loadQuizCounts(array $classIds): array
    {
        if ($classIds === []) {
            return [];
        }

        return DB::table('tbl_quiz_linked as quilin')
            ->whereIn('quilin.quilin_record_id', $classIds)
            ->where('quilin.quilin_record_type', self::QUIZ_TYPE_GCLASS)
            ->whereNull('quilin.quilin_deleted')
            ->groupBy('quilin.quilin_record_id')
            ->pluck(DB::raw('COUNT(*) as quiz_count'), 'quilin.quilin_record_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @param  array<int, object>  $plans
     * @param  array<int, int>  $quizCounts
     * @return array<string, mixed>
     */
    private function formatRow(object $row, bool $isTeacher, array $plans, array $quizCounts): array
    {
        $grpclsId = (int) $row->grpcls_id;
        $id = $isTeacher ? $grpclsId : (int) $row->ordcls_id;
        $status = $isTeacher ? (int) $row->grpcls_status : (int) $row->ordcls_status;
        $bookedSeats = (int) ($row->grpcls_booked_seats ?? 0);
        $parentId = (int) ($row->grpcls_parent ?? 0);
        $offline = (int) $row->grpcls_offline === 1;

        $now = time();
        $startUnix = $row->grpcls_start_datetime ? strtotime((string) $row->grpcls_start_datetime) : 0;
        $endUnix = $row->grpcls_end_datetime ? strtotime((string) $row->grpcls_end_datetime) : 0;

        $isScheduled = $status === self::STATUS_SCHEDULED;
        $isCancelled = $status === self::STATUS_CANCELLED;

        $classTimeInfo = '';
        if ($now > $endUnix && $endUnix > 0 && ! $offline) {
            $classTimeInfo = 'passed';
        } elseif ($now > $startUnix && $startUnix > 0 && $now <= $endUnix) {
            $classTimeInfo = 'ongoing';
        }

        $showNoBooking = $isScheduled && $bookedSeats === 0 && $startUnix > 0 && $startUnix < $now;
        $showStartTimer = $isScheduled && $startUnix > 0 && $startUnix >= $now;

        $planRow = $plans[$grpclsId] ?? null;
        $plan = [
            'plan_id' => $planRow ? (int) $planRow->plan_id : 0,
            'plan_title' => $planRow ? (string) $planRow->plan_title : '',
            'plancls_id' => $planRow ? (int) $planRow->plancls_id : 0,
        ];

        $statusLabels = [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Canceled',
        ];

        return [
            'id' => $id,
            'grpcls_id' => $grpclsId,
            'title' => (string) $row->grpcls_title,
            'start_time' => $row->grpcls_start_datetime ? (string) $row->grpcls_start_datetime : null,
            'end_time' => $row->grpcls_end_datetime ? (string) $row->grpcls_end_datetime : null,
            'start_time_unix' => $startUnix,
            'end_time_unix' => $endUnix,
            'offline' => $offline,
            'status' => $status,
            'status_label' => $statusLabels[$status] ?? (string) $status,
            'entry_fee' => (float) ($row->grpcls_entry_fee ?? 0),
            'booked_seats' => $bookedSeats,
            'total_seats' => (int) ($row->grpcls_total_seats ?? 0),
            'is_package_class' => $parentId > 0,
            'is_scheduled' => $isScheduled,
            'is_cancelled' => $isCancelled,
            'show_no_booking' => $showNoBooking,
            'show_start_timer' => $showStartTimer,
            'class_time_info' => $classTimeInfo,
            'plan' => $plan,
            'quiz_count' => $quizCounts[$grpclsId] ?? 0,
            'can_edit' => $isTeacher && $isScheduled && $bookedSeats === 0 && $parentId === 0
                && $startUnix > $now,
            'can_cancel' => $isTeacher && $isScheduled && $bookedSeats === 0 && $parentId === 0
                && $startUnix > $now,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array{key: string, classes: array<int, array<string, mixed>>}>
     */
    private function groupByDate(Collection $items): array
    {
        $grouped = [];
        foreach ($items as $class) {
            $key = 'Scheduled';
            if (! empty($class['start_time_unix'])) {
                $key = date('Y-m-d', (int) $class['start_time_unix']);
            } elseif (! empty($class['start_time'])) {
                $key = date('Y-m-d', strtotime((string) $class['start_time']));
            }
            if (! isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $class;
        }

        $out = [];
        foreach ($grouped as $key => $classes) {
            $out[] = ['key' => (string) $key, 'classes' => $classes];
        }

        return $out;
    }
}
