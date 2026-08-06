<?php

namespace App\Services;

use App\Models\OrderLesson;
use Illuminate\Support\Facades\DB;

class LearnerDashboardService
{
    public const LESSON_SCHEDULED = 2;

    public function summary(int $learnerId): array
    {
        $scheduledLessons = OrderLesson::query()
            ->whereHas('order', fn ($q) => $q->where('order_user_id', $learnerId))
            ->where('ordles_status', self::LESSON_SCHEDULED)
            ->count();

        $totalLessons = OrderLesson::query()
            ->whereHas('order', fn ($q) => $q->where('order_user_id', $learnerId))
            ->count();

        $totalClasses = 0;
        if ($this->groupClassesEnabled()) {
            $totalClasses = (int) DB::table('tbl_order_classes as ordcls')
                ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
                ->where('orders.order_user_id', $learnerId)
                ->count();
        }

        $totalCourses = 0;
        if ($this->coursesEnabled()) {
            $totalCourses = (int) DB::table('tbl_order_courses as ordcrs')
                ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
                ->where('orders.order_user_id', $learnerId)
                ->count();
        }

        $walletBalance = (float) (DB::table('tbl_user_settings')
            ->where('user_id', $learnerId)
            ->value('user_wallet_balance') ?? 0);

        return [
            'scheduled_lessons' => $scheduledLessons,
            'total_lessons' => $totalLessons,
            'total_classes' => $totalClasses,
            'total_courses' => $totalCourses,
            'wallet_balance' => round($walletBalance, 2),
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
