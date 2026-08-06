<?php

namespace App\Services;

use App\Models\OrderLesson;
use Illuminate\Support\Facades\DB;

class TeacherDashboardService
{
    public const LESSON_SCHEDULED = 2;

    public const UPCOMING_PAGE_SIZE = 12;

    public function __construct(private LessonListingService $lessons)
    {
    }

    public const CLASS_SCHEDULED = 1;

    public const TXN_TEACHER_PAYMENT = 9;

    public function summary(int $teacherId, int $langId = 1): array
    {
        $scheduledLessons = OrderLesson::query()
            ->where('ordles_teacher_id', $teacherId)
            ->where('ordles_status', self::LESSON_SCHEDULED)
            ->count();

        $scheduledClasses = 0;
        if ($this->groupClassesEnabled()) {
            $scheduledClasses = (int) DB::table('tbl_order_classes as ordcls')
                ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
                ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
                ->where('grpcls.grpcls_teacher_id', $teacherId)
                ->where('ordcls.ordcls_status', self::CLASS_SCHEDULED)
                ->where('grpcls.grpcls_status', self::CLASS_SCHEDULED)
                ->count();
        }

        $coursesSold = 0;
        if ($this->coursesEnabled()) {
            $coursesSold = (int) DB::table('tbl_order_courses as ordcrs')
                ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
                ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
                ->where('course.course_user_id', $teacherId)
                ->count();
        }

        $totalEarnings = (float) DB::table('tbl_user_transactions')
            ->where('usrtxn_user_id', $teacherId)
            ->where('usrtxn_type', self::TXN_TEACHER_PAYMENT)
            ->sum('usrtxn_amount');

        $walletBalance = (float) (DB::table('tbl_user_settings')
            ->where('user_id', $teacherId)
            ->value('user_wallet_balance') ?? 0);

        $upcoming = $this->lessons->list($teacherId, true, $langId, [
            'status' => LessonListingService::STATUS_SCHEDULED,
            'upcoming_only' => true,
            'per_page' => self::UPCOMING_PAGE_SIZE,
            'page' => 1,
        ]);

        return [
            'scheduled_lessons' => $scheduledLessons,
            'scheduled_classes' => $scheduledClasses,
            'courses_sold' => $coursesSold,
            'total_earnings' => round($totalEarnings, 2),
            'wallet_balance' => round($walletBalance, 2),
            'upcoming_lesson_groups' => $upcoming['groups'],
            'modules' => [
                'courses' => $this->coursesEnabled(),
                'group_classes' => $this->groupClassesEnabled(),
            ],
        ];
    }

    private function coursesEnabled(): bool
    {
        return $this->confEnabled('CONF_ENABLE_COURSES');
    }

    private function groupClassesEnabled(): bool
    {
        return $this->confEnabled('CONF_ENABLE_GROUP_CLASSES');
    }

    private function confEnabled(string $key): bool
    {
        $row = DB::table('tbl_configurations')
            ->where('conf_name', $key)
            ->value('conf_val');

        return (int) $row === 1;
    }
}
