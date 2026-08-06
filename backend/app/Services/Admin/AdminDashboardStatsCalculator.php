<?php

namespace App\Services\Admin;

use App\Support\Admin\AdminDateRange;
use Illuminate\Support\Facades\DB;

class AdminDashboardStatsCalculator
{
    private const ORDER_TYPE_LESSON = 1;

    private const ORDER_TYPE_SUBSCR = 2;

    private const ORDER_TYPE_GCLASS = 3;

    private const ORDER_TYPE_PACKGE = 4;

    private const ORDER_TYPE_COURSE = 5;

    private const ORDER_PAID = 1;

    private const ORDER_STATUS_COMPLETED = 2;

    private const LESSON_COMPLETED = 3;

    private const REFUND_APPROVED = 1;

    private const USER_AFFILIATE = 1;

    /** @return array<string, float|int> */
    public function calculate(bool $coursesEnabled, bool $groupClassesEnabled, bool $affiliateEnabled): array
    {
        $stats = [
            'TM_LESSONS_REVENUE' => $this->lessonsRevenue(false),
            'ALL_LESSONS_REVENUE' => $this->lessonsRevenue(true),
            'TM_SUBSCRIPTION_REVENUE' => $this->subscriptionsRevenue(false),
            'ALL_SUBSCRIPTION_REVENUE' => $this->subscriptionsRevenue(true),
            'TM_ADMIN_EARNINGS' => $this->adminEarnings(false),
            'ALL_ADMIN_EARNINGS' => $this->adminEarnings(true),
            'TM_LESSONS_TOTAL' => $this->lessonsTotal(false),
            'ALL_LESSONS_TOTAL' => $this->lessonsTotal(true),
            'TM_SUBSCRIPTIONS_TOTAL' => $this->subscriptionsTotal(false),
            'ALL_SUBSCRIPTIONS_TOTAL' => $this->subscriptionsTotal(true),
            'TM_COMPLETED_LESSONS' => $this->completedLessons(false),
            'ALL_COMPLETED_LESSONS' => $this->completedLessons(true),
            'TM_COMPLETED_SUBSCRIPTIONS' => $this->completedSubscriptions(false),
            'ALL_COMPLETED_SUBSCRIPTIONS' => $this->completedSubscriptions(true),
            'TM_USERS_TOTAL' => $this->usersTotal(false),
            'ALL_USERS_TOTAL' => $this->usersTotal(true),
            'TM_ORDERS_TOTAL' => $this->ordersTotal(false, $coursesEnabled, $groupClassesEnabled),
            'ALL_ORDERS_TOTAL' => $this->ordersTotal(true, $coursesEnabled, $groupClassesEnabled),
        ];

        if ($coursesEnabled) {
            $stats['TM_COURSES_REVENUE'] = $this->coursesRevenue(false);
            $stats['ALL_COURSES_REVENUE'] = $this->coursesRevenue(true);
            $stats['TM_COURSES_TOTAL'] = $this->coursesTotal(false);
            $stats['ALL_COURSES_TOTAL'] = $this->coursesTotal(true);
            $stats['TM_REFUNDED_COURSES'] = $this->refundedCourses(false);
            $stats['ALL_REFUNDED_COURSES'] = $this->refundedCourses(true);
        }

        if ($groupClassesEnabled) {
            $stats['TM_CLASSES_REVENUE'] = $this->classesRevenue(false);
            $stats['ALL_CLASSES_REVENUE'] = $this->classesRevenue(true);
            $stats['TM_COMPLETED_CLASSES'] = $this->completedClasses(false);
            $stats['ALL_COMPLETED_CLASSES'] = $this->completedClasses(true);
            $stats['TM_CLASSES_TOTAL'] = $this->classesTotal(false);
            $stats['ALL_CLASSES_TOTAL'] = $this->classesTotal(true);
        }

        if ($affiliateEnabled) {
            $stats['TM_AFFILIATES_TOTAL'] = $this->usersTotal(false, self::USER_AFFILIATE);
            $stats['ALL_AFFILIATES_TOTAL'] = $this->usersTotal(true, self::USER_AFFILIATE);
        }

        return $stats;
    }

    /** @return array<string, array<string, float|int>> */
    public function chartData(bool $coursesEnabled, bool $groupClassesEnabled): array
    {
        $duration = AdminDateRange::TYPE_LAST_12_MONTH;

        return [
            'userData' => $this->usersChart($duration),
            'lessonData' => $this->lessonEarningsChart($duration),
            'classData' => $groupClassesEnabled ? $this->classEarningsChart($duration) : [],
            'courseData' => $coursesEnabled ? $this->courseEarningsChart($duration) : [],
        ];
    }

    /** @return array<int, array{language: string, totalsold: int}> */
    public function topLessonLanguages(int $langId, int $interval, int $limit = 50): array
    {
        $interval = AdminDateRange::normalizeInterval($interval);
        $datetime = AdminDateRange::bounds($interval, null, true);

        $rows = DB::table('tbl_orders as orders')
            ->join('tbl_order_lessons as ordles', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->whereIn('orders.order_type', [self::ORDER_TYPE_LESSON, self::ORDER_TYPE_SUBSCR])
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_addedon', '>=', $datetime['startDate'])
            ->where('orders.order_addedon', '<=', $datetime['endDate'])
            ->where('ordles.ordles_tlang_id', '>', 0)
            ->groupBy('ordles.ordles_tlang_id')
            ->orderByDesc('totalsold')
            ->orderBy('ordles.ordles_tlang_id')
            ->limit($limit)
            ->get([
                DB::raw('COUNT(ordles.ordles_tlang_id) AS totalsold'),
                'ordles.ordles_tlang_id',
            ]);

        return $this->mapTeachLanguages($rows, 'ordles_tlang_id', $langId);
    }

    /** @return array<int, array{language: string, totalsold: int}> */
    public function topClassLanguages(int $langId, int $interval, int $limit = 50): array
    {
        $interval = AdminDateRange::normalizeInterval($interval);
        $datetime = AdminDateRange::bounds($interval, null, true);

        $rows = DB::table('tbl_orders as orders')
            ->join('tbl_order_classes as ordcls', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->whereIn('orders.order_type', [self::ORDER_TYPE_GCLASS, self::ORDER_TYPE_PACKGE])
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_addedon', '>=', $datetime['startDate'])
            ->where('orders.order_addedon', '<=', $datetime['endDate'])
            ->where('grpcls.grpcls_tlang_id', '>', 0)
            ->groupBy('grpcls.grpcls_tlang_id')
            ->orderByDesc('totalsold')
            ->orderBy('grpcls.grpcls_tlang_id')
            ->limit($limit)
            ->get([
                DB::raw('COUNT(grpcls.grpcls_tlang_id) AS totalsold'),
                'grpcls.grpcls_tlang_id',
            ]);

        return $this->mapTeachLanguages($rows, 'grpcls_tlang_id', $langId);
    }

    /** @return array<int, array{category: string, totalsold: int}> */
    public function topCourseCategories(int $langId, int $interval, int $limit = 50): array
    {
        $interval = AdminDateRange::normalizeInterval($interval);
        $datetime = AdminDateRange::bounds($interval, null, true);

        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_courses as ordcrs', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->join('tbl_categories as cate', 'course.course_cate_id', '=', 'cate.cate_id')
            ->where('orders.order_type', self::ORDER_TYPE_COURSE)
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_addedon', '>=', $datetime['startDate'])
            ->where('course.course_cate_id', '>', 0)
            ->where('cate.cate_parent', 0)
            ->groupBy('course.course_cate_id')
            ->orderByDesc('totalsold')
            ->limit($limit);

        if ($interval !== AdminDateRange::TYPE_ALL) {
            $query->where('orders.order_addedon', '<=', $datetime['endDate']);
        }

        $rows = $query->get([
            DB::raw('COUNT(course.course_cate_id) AS totalsold'),
            'course.course_cate_id',
        ]);

        $ids = $rows->pluck('course_cate_id')->filter()->unique()->values()->all();
        $names = $this->categoryNames($ids, $langId);
        $result = [];

        foreach ($rows as $row) {
            $id = (int) $row->course_cate_id;
            if (! isset($names[$id])) {
                continue;
            }
            $result[] = [
                'category' => $names[$id],
                'totalsold' => (int) $row->totalsold,
            ];
        }

        return $result;
    }

    private function lessonsRevenue(bool $all): float
    {
        return $this->salesSum('slstat_les_sales', $all);
    }

    private function classesRevenue(bool $all): float
    {
        return $this->salesSum('slstat_cls_sales', $all);
    }

    private function coursesRevenue(bool $all): float
    {
        return $this->salesSum('slstat_crs_sales', $all);
    }

    private function subscriptionsRevenue(bool $all): float
    {
        return $this->salesSum('slstat_subplan_sales', $all);
    }

    private function adminEarnings(bool $all): float
    {
        $query = DB::table('tbl_sales_stats');
        $this->applyMonthFilter($query, $all, 'slstat_date');

        return (float) ($query->value(DB::raw(
            'SUM(IFNULL(slstat_les_earnings, 0) + IFNULL(slstat_cls_earnings, 0) + IFNULL(slstat_crs_earnings, 0))'
        )) ?? 0);
    }

    private function salesSum(string $column, bool $all): float
    {
        $query = DB::table('tbl_sales_stats');
        $this->applyMonthFilter($query, $all, 'slstat_date');

        return (float) ($query->value(DB::raw("SUM(IFNULL({$column}, 0))")) ?? 0);
    }

    private function lessonsTotal(bool $all): int
    {
        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_lessons as ordles', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->whereIn('orders.order_type', [self::ORDER_TYPE_LESSON, self::ORDER_TYPE_SUBSCR])
            ->where('orders.order_payment_status', self::ORDER_PAID);
        $this->applyMonthFilter($query, $all, 'orders.order_addedon');

        return (int) $query->count('ordles.ordles_id');
    }

    private function completedLessons(bool $all): int
    {
        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_lessons as ordles', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->whereIn('orders.order_type', [self::ORDER_TYPE_LESSON, self::ORDER_TYPE_SUBSCR])
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('ordles.ordles_status', self::LESSON_COMPLETED);
        $this->applyMonthFilter($query, $all, 'orders.order_addedon');

        return (int) $query->count('ordles.ordles_id');
    }

    private function subscriptionsTotal(bool $all): int
    {
        $query = DB::table('tbl_orders')
            ->where('order_type', self::ORDER_TYPE_SUBSCR)
            ->where('order_payment_status', self::ORDER_PAID);
        $this->applyMonthFilter($query, $all, 'order_addedon');

        return (int) $query->count('order_id');
    }

    private function completedSubscriptions(bool $all): int
    {
        $query = DB::table('tbl_orders')
            ->where('order_type', self::ORDER_TYPE_SUBSCR)
            ->where('order_payment_status', self::ORDER_PAID)
            ->where('order_status', self::ORDER_STATUS_COMPLETED);
        $this->applyMonthFilter($query, $all, 'order_addedon');

        return (int) $query->count('order_id');
    }

    private function completedClasses(bool $all): int
    {
        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_classes as ordcls', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
            ->whereIn('orders.order_type', [self::ORDER_TYPE_GCLASS, self::ORDER_TYPE_PACKGE])
            ->where('orders.order_payment_status', self::ORDER_PAID);
        $this->applyMonthFilter($query, $all, 'orders.order_addedon');

        return (int) $query->count('ordcls.ordcls_id');
    }

    private function classesTotal(bool $all): int
    {
        $query = DB::table('tbl_group_classes')->where('grpcls_type', 1);
        if (! $all) {
            $datetime = AdminDateRange::bounds(AdminDateRange::TYPE_THIS_MONTH, null, true);
            $query->where('grpcls_start_datetime', '>=', $datetime['startDate'])
                ->where('grpcls_end_datetime', '<=', $datetime['endDate']);
        }

        return (int) $query->count('grpcls_id');
    }

    private function coursesTotal(bool $all): int
    {
        $query = DB::table('tbl_courses')->whereNull('course_deleted');
        if (! $all) {
            $datetime = AdminDateRange::bounds(AdminDateRange::TYPE_THIS_MONTH, null, true);
            $query->where('course_created', '>=', $datetime['startDate'])
                ->where('course_created', '<=', $datetime['endDate']);
        }

        return (int) $query->count('course_id');
    }

    private function refundedCourses(bool $all): int
    {
        $query = DB::table('tbl_course_refund_requests as corere')
            ->where('corere_status', self::REFUND_APPROVED);
        if (! $all) {
            $datetime = AdminDateRange::bounds(AdminDateRange::TYPE_THIS_MONTH, null, true);
            $query->where('corere_updated', '>=', $datetime['startDate'])
                ->where('corere_updated', '<=', $datetime['endDate']);
        }

        return (int) $query->count('corere_id');
    }

    private function usersTotal(bool $all, int $type = 0): int
    {
        $query = DB::table('tbl_users')->whereNull('user_deleted');
        if ($type === self::USER_AFFILIATE) {
            $query->where('user_is_affiliate', 1);
        }
        $this->applyMonthFilter($query, $all, 'user_created');

        return (int) $query->count('user_id');
    }

    private function ordersTotal(bool $all, bool $coursesEnabled, bool $groupClassesEnabled): int
    {
        $query = DB::table('tbl_orders');
        if (! $coursesEnabled) {
            $query->where('order_type', '!=', self::ORDER_TYPE_COURSE);
        }
        if (! $groupClassesEnabled) {
            $query->whereNotIn('order_type', [self::ORDER_TYPE_GCLASS, self::ORDER_TYPE_PACKGE]);
        }
        $this->applyMonthFilter($query, $all, 'order_addedon');

        return (int) $query->count('order_id');
    }

    /** @return array<string, int> */
    private function usersChart(int $duration): array
    {
        $datetime = AdminDateRange::bounds($duration, null, true);
        $groupExpr = "DATE_FORMAT(user_created, '%m-%Y')";

        $rows = DB::table('tbl_users')
            ->whereNull('user_deleted')
            ->where('user_created', '>=', $datetime['startDate'])
            ->where('user_created', '<=', $datetime['endDate'])
            ->selectRaw("{$groupExpr} as group_date, COUNT(user_id) as total")
            ->groupBy('group_date')
            ->orderByRaw('YEAR(user_created) ASC')
            ->orderByRaw('MONTH(user_created) ASC')
            ->get();

        return $this->formatChartRows($rows, 'total');
    }

    /** @return array<string, float> */
    private function lessonEarningsChart(int $duration): array
    {
        return $this->earningsChart($duration, 'slstat_les_earnings');
    }

    /** @return array<string, float> */
    private function classEarningsChart(int $duration): array
    {
        return $this->earningsChart($duration, 'slstat_cls_earnings');
    }

    /** @return array<string, float> */
    private function courseEarningsChart(int $duration): array
    {
        return $this->earningsChart($duration, 'slstat_crs_earnings');
    }

    /** @return array<string, float> */
    private function earningsChart(int $duration, string $column): array
    {
        $datetime = AdminDateRange::bounds($duration, null, false, 'Y-m-d');
        $groupExpr = "DATE_FORMAT(slstat_date, '%m-%Y')";

        $rows = DB::table('tbl_sales_stats')
            ->where('slstat_date', '>=', $datetime['startDate'])
            ->where('slstat_date', '<=', $datetime['endDate'])
            ->selectRaw("{$groupExpr} as group_date, SUM(IFNULL({$column}, 0)) as total")
            ->groupBy('group_date')
            ->orderByRaw('YEAR(slstat_date) ASC')
            ->orderByRaw('MONTH(slstat_date) ASC')
            ->get();

        return $this->formatChartRows($rows, 'total', true);
    }

  /**
   * @param  \Illuminate\Support\Collection<int, object>  $rows
   * @return array<string, float|int>
   */
    private function formatChartRows($rows, string $valueKey, bool $asFloat = false): array
    {
        $stats = [];
        foreach ($rows as $row) {
            $label = date('M Y', strtotime('13-'.$row->group_date));
            $stats[$label] = $asFloat ? (float) $row->{$valueKey} : (int) $row->{$valueKey};
        }

        return $stats;
    }

    private function applyMonthFilter($query, bool $all, string $column): void
    {
        if ($all) {
            return;
        }
        $datetime = AdminDateRange::bounds(AdminDateRange::TYPE_THIS_MONTH, null, true);
        $query->where($column, '>=', $datetime['startDate'])
            ->where($column, '<=', $datetime['endDate']);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, array{language: string, totalsold: int}>
     */
    private function mapTeachLanguages($rows, string $idKey, int $langId): array
    {
        $ids = $rows->pluck($idKey)->filter()->unique()->values()->all();
        $names = $this->teachLanguageNames($ids, $langId);
        $result = [];

        foreach ($rows as $row) {
            $id = (int) $row->{$idKey};
            if (! isset($names[$id])) {
                continue;
            }
            $result[] = [
                'language' => $names[$id],
                'totalsold' => (int) $row->totalsold,
            ];
        }

        return $result;
    }

    /** @param  array<int, int>  $ids */
    private function teachLanguageNames(array $ids, int $langId): array
    {
        if ($ids === []) {
            return [];
        }

        $names = [];
        $rows = DB::table('tbl_teach_languages as tl')
            ->leftJoin('tbl_teach_languages_lang as tll', function ($join) use ($langId) {
                $join->on('tll.tlanglang_tlang_id', '=', 'tl.tlang_id')
                    ->where('tll.tlanglang_lang_id', '=', $langId);
            })
            ->whereIn('tl.tlang_id', $ids)
            ->selectRaw('tl.tlang_id as id, IFNULL(tll.tlang_name, tl.tlang_identifier) as name')
            ->get();

        foreach ($rows as $row) {
            $names[(int) $row->id] = (string) $row->name;
        }

        return $names;
    }

    /** @param  array<int, int>  $ids */
    private function categoryNames(array $ids, int $langId): array
    {
        if ($ids === []) {
            return [];
        }

        $names = [];
        $rows = DB::table('tbl_categories as c')
            ->leftJoin('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('cl.catelang_cate_id', '=', 'c.cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->whereIn('c.cate_id', $ids)
            ->selectRaw('c.cate_id as id, IFNULL(cl.cate_name, c.cate_identifier) as name')
            ->get();

        foreach ($rows as $row) {
            $names[(int) $row->id] = (string) $row->name;
        }

        return $names;
    }
}
