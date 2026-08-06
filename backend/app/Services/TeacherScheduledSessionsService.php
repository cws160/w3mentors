<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TeacherScheduledSessionsService
{
    public const USER_TYPE_TEACHER = 2;

    public const LESSON_SCHEDULED = 2;

    public const CLASS_SCHEDULED = 1;

    public const ORDER_PAID = 1;

    public const ORDER_COMPLETED = 2;

    public const GROUP_CLASS_REGULAR = 1;

    /**
     * FullCalendar events for teacher dashboard sidebar (legacy TeachersController::getScheduledSessions).
     *
     * @return array<int, array{title: string, start: string, end: string, className: string, classId?: int}>
     */
    public function events(int $teacherId, string $start, string $end, int $userType = self::USER_TYPE_TEACHER): array
    {
        if ($teacherId < 1 || $start === '' || $end === '') {
            return [];
        }

        $lessons = $this->scheduledLessons($teacherId, $start, $end, $userType);
        $classes = $this->scheduledClasses($teacherId, $start, $end, $userType);

        return array_merge($lessons, $classes);
    }

    /**
     * @return array<int, array{title: string, start: string, end: string, className: string}>
     */
    private function scheduledLessons(int $teacherId, string $start, string $end, int $userType): array
    {
        $query = DB::table('tbl_order_lessons as ordles')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->where('ordles.ordles_status', self::LESSON_SCHEDULED)
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_status', self::ORDER_COMPLETED)
            ->where('ordles.ordles_lesson_starttime', '<', $end)
            ->where('ordles.ordles_lesson_endtime', '>', $start);

        if ($userType === self::USER_TYPE_TEACHER) {
            $query->where('ordles.ordles_teacher_id', $teacherId);
        } else {
            $query->where(function ($q) use ($teacherId) {
                $q->where('ordles.ordles_teacher_id', $teacherId)
                    ->orWhere('orders.order_user_id', $teacherId);
            });
        }

        return $query
            ->get(['ordles.ordles_lesson_starttime', 'ordles.ordles_lesson_endtime'])
            ->map(fn ($row) => [
                'title' => '',
                'start' => (string) $row->ordles_lesson_starttime,
                'end' => (string) $row->ordles_lesson_endtime,
                'className' => 'sch_data booked-slot',
            ])
            ->all();
    }

    /**
     * @return array<int, array{title: string, start: string, end: string, className: string, classId: int}>
     */
    private function scheduledClasses(int $teacherId, string $start, string $end, int $userType): array
    {
        if (! $this->groupClassesEnabled()) {
            return [];
        }

        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_classes as ordcls', 'ordcls.ordcls_order_id', '=', 'orders.order_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->where('grpcls.grpcls_type', self::GROUP_CLASS_REGULAR)
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_status', self::ORDER_COMPLETED)
            ->where('ordcls.ordcls_status', self::CLASS_SCHEDULED)
            ->where('grpcls.grpcls_start_datetime', '<', $end)
            ->where('grpcls.grpcls_end_datetime', '>', $start);

        if ($userType === self::USER_TYPE_TEACHER) {
            $query->where('grpcls.grpcls_teacher_id', $teacherId);
        } else {
            $query->where(function ($q) use ($teacherId) {
                $q->where('grpcls.grpcls_teacher_id', $teacherId)
                    ->orWhere('orders.order_user_id', $teacherId);
            });
        }

        return $query
            ->select([
                'grpcls.grpcls_id',
                'grpcls.grpcls_start_datetime',
                'grpcls.grpcls_end_datetime',
            ])
            ->groupBy('grpcls.grpcls_id', 'grpcls.grpcls_start_datetime', 'grpcls.grpcls_end_datetime')
            ->get()
            ->map(fn ($row) => [
                'title' => '',
                'classId' => (int) $row->grpcls_id,
                'start' => (string) $row->grpcls_start_datetime,
                'end' => (string) $row->grpcls_end_datetime,
                'className' => 'sch_data booked-slot',
            ])
            ->all();
    }

    private function groupClassesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ENABLE_GROUP_CLASSES')
            ->value('conf_val') === 1;
    }
}
