<?php

namespace App\Services\Admin;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminSalesReportRegenerateService
{
    private const CLASS_COMPLETED = 2;

    private const CLASS_CANCELLED = 3;

    private const GROUP_CLASS_COMPLETED = 2;

    private const GROUP_CLASS_TYPE_REGULAR = 1;

    public function __construct(private AdminDashboardService $dashboard)
    {
    }

    public function regenerate(): string
    {
        set_time_limit(0);

        $configDate = (string) Configuration::getValue('CONF_SALES_REPORT_GENERATED_DATE', '2020-01-01');
        $date = date('Y-m-d', strtotime($configDate));

        $saleData = $this->generateLessonsReport($date, []);
        $saleData = $this->generateLessonsNetSale($date, $saleData);
        $saleData = $this->generateClassesReport($date, $saleData);
        $saleData = $this->generateClassesNetSale($date, $saleData);
        $saleData = $this->generateCoursesReport($date, $saleData);
        $saleData = $this->generateCoursesNetSale($date, $saleData);
        $saleData = $this->generateSubscriptionPlansNetSale($date, $saleData);
        $saleData = $this->generateSubscriptionPlansReport($date, $saleData);

        $dateNow = date('Y-m-d H:i:s');

        DB::transaction(function () use ($saleData, $date, $dateNow) {
            foreach ($saleData as $sale) {
                $dateKey = (string) ($sale['slstat_date'] ?? '');
                if ($dateKey === '') {
                    continue;
                }

                DB::table('tbl_sales_stats')->updateOrInsert(
                    ['slstat_date' => $dateKey],
                    $sale,
                );
            }

            if (! $this->dashboard->refreshCachedStats()) {
                throw new \RuntimeException('Failed to refresh dashboard stats');
            }

            if (! $this->regenerateHoursTaught($date)) {
                throw new \RuntimeException('Failed to regenerate hours taught stats');
            }

            DB::table('tbl_configurations')
                ->where('conf_name', 'CONF_SALES_REPORT_GENERATED_DATE')
                ->update(['conf_val' => $dateNow]);

            Cache::forget('conf_CONF_SALES_REPORT_GENERATED_DATE');
            Cache::forget('conf_CONF_ADMIN_DASHBOARD_STATS');
        });

        return $dateNow;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateLessonsReport(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->join('tbl_order_lessons as ordles', 'ordles.ordles_order_id', '=', 'orders.order_id')
            ->whereRaw('DATE(ordles.ordles_updated) >= ?', [$date])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('ordles.ordles_status', '=', AdminOrderHelper::LESSON_COMPLETED)
                        ->whereNotNull('ordles.ordles_teacher_paid');
                })->orWhere('ordles.ordles_status', '=', AdminOrderHelper::LESSON_CANCELLED);
            })
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->select([
                DB::raw('DATE(ordles.ordles_updated) AS slstat_date'),
                DB::raw('SUM(IFNULL(ordles.ordles_refund, 0)) AS slstat_les_refund'),
                DB::raw('SUM(IFNULL(ordles.ordles_earnings, 0)) AS slstat_les_earnings'),
                DB::raw('SUM(IFNULL(ordles.ordles_teacher_paid, 0)) AS slstat_les_teacher_paid'),
            ])
            ->groupBy(DB::raw('DATE(ordles.ordles_updated)'))
            ->orderByDesc(DB::raw('DATE(ordles.ordles_updated)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $data[(string) $row['slstat_date']] = [
                'slstat_date' => $row['slstat_date'],
                'slstat_les_refund' => $row['slstat_les_refund'],
                'slstat_les_earnings' => $row['slstat_les_earnings'],
                'slstat_les_teacher_paid' => $row['slstat_les_teacher_paid'],
            ];
        }

        return $data;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateLessonsNetSale(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->whereIn('orders.order_type', [AdminOrderHelper::TYPE_LESSON, AdminOrderHelper::TYPE_SUBSCR])
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->whereRaw('DATE(orders.order_addedon) >= ?', [$date])
            ->select([
                DB::raw('DATE(orders.order_addedon) AS slstat_date'),
                DB::raw('SUM(orders.order_net_amount) AS slstat_les_sales'),
                DB::raw('SUM(orders.order_discount_value) AS slstat_les_discount'),
                DB::raw('SUM(orders.order_reward_value) AS slstat_les_credit_discount'),
            ])
            ->groupBy(DB::raw('DATE(orders.order_addedon)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $key = (string) $row['slstat_date'];
            $data[$key]['slstat_date'] = $row['slstat_date'];
            $data[$key]['slstat_les_sales'] = $row['slstat_les_sales'] ?? 0;
            $data[$key]['slstat_les_discount'] = $row['slstat_les_discount'] ?? 0;
            $data[$key]['slstat_les_credit_discount'] = $row['slstat_les_credit_discount'] ?? 0;
        }

        return $data;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateClassesReport(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->join('tbl_order_classes as ordcls', 'ordcls.ordcls_order_id', '=', 'orders.order_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->whereRaw('DATE(ordcls.ordcls_updated) >= ?', [$date])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('ordcls.ordcls_status', '=', self::CLASS_COMPLETED)
                        ->whereNotNull('ordcls.ordcls_teacher_paid');
                })->orWhere('ordcls.ordcls_status', '=', self::CLASS_CANCELLED);
            })
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->select([
                DB::raw('DATE(ordcls.ordcls_updated) AS slstat_date'),
                DB::raw('SUM(IFNULL(ordcls.ordcls_refund, 0)) AS slstat_cls_refund'),
                DB::raw('SUM(IFNULL(ordcls.ordcls_earnings, 0)) AS slstat_cls_earnings'),
                DB::raw('SUM(IFNULL(ordcls.ordcls_teacher_paid, 0)) AS slstat_cls_teacher_paid'),
            ])
            ->groupBy(DB::raw('DATE(ordcls.ordcls_updated)'))
            ->orderByDesc(DB::raw('DATE(ordcls.ordcls_updated)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $key = (string) $row['slstat_date'];
            $data[$key]['slstat_date'] = $row['slstat_date'];
            $data[$key]['slstat_cls_refund'] = $row['slstat_cls_refund'];
            $data[$key]['slstat_cls_earnings'] = $row['slstat_cls_earnings'];
            $data[$key]['slstat_cls_teacher_paid'] = $row['slstat_cls_teacher_paid'];
        }

        return $data;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateClassesNetSale(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->whereIn('orders.order_type', [AdminOrderHelper::TYPE_GCLASS, AdminOrderHelper::TYPE_PACKGE])
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->whereRaw('DATE(orders.order_addedon) >= ?', [$date])
            ->select([
                DB::raw('DATE(orders.order_addedon) AS slstat_date'),
                DB::raw('SUM(orders.order_net_amount) AS slstat_cls_sales'),
                DB::raw('SUM(orders.order_discount_value) AS slstat_cls_discount'),
                DB::raw('SUM(orders.order_reward_value) AS slstat_cls_credit_discount'),
            ])
            ->groupBy(DB::raw('DATE(orders.order_addedon)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $key = (string) $row['slstat_date'];
            $data[$key]['slstat_date'] = $row['slstat_date'];
            $data[$key]['slstat_cls_sales'] = $row['slstat_cls_sales'] ?? 0;
            $data[$key]['slstat_cls_discount'] = $row['slstat_cls_discount'] ?? 0;
            $data[$key]['slstat_cls_credit_discount'] = $row['slstat_cls_credit_discount'] ?? 0;
        }

        return $data;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateCoursesReport(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->join('tbl_order_courses as ordcrs', 'ordcrs.ordcrs_order_id', '=', 'orders.order_id')
            ->whereRaw('DATE(ordcrs.ordcrs_updated) >= ?', [$date])
            ->whereNotNull('ordcrs.ordcrs_teacher_paid')
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->select([
                DB::raw('DATE(ordcrs.ordcrs_updated) AS slstat_date'),
                DB::raw('SUM(IFNULL(ordcrs.ordcrs_refund, 0)) AS slstat_crs_refund'),
                DB::raw('SUM(IFNULL(ordcrs.ordcrs_earnings, 0)) AS slstat_crs_earnings'),
                DB::raw('SUM(IFNULL(ordcrs.ordcrs_teacher_paid, 0)) AS slstat_crs_teacher_paid'),
            ])
            ->groupBy(DB::raw('DATE(ordcrs.ordcrs_updated)'))
            ->orderByDesc(DB::raw('DATE(ordcrs.ordcrs_updated)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $key = (string) $row['slstat_date'];
            $data[$key]['slstat_date'] = $row['slstat_date'];
            $data[$key]['slstat_crs_refund'] = $row['slstat_crs_refund'];
            $data[$key]['slstat_crs_earnings'] = $row['slstat_crs_earnings'];
            $data[$key]['slstat_crs_teacher_paid'] = $row['slstat_crs_teacher_paid'];
        }

        return $data;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateCoursesNetSale(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->where('orders.order_type', '=', AdminOrderHelper::TYPE_COURSE)
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->whereRaw('DATE(orders.order_addedon) >= ?', [$date])
            ->select([
                DB::raw('DATE(orders.order_addedon) AS slstat_date'),
                DB::raw('SUM(orders.order_net_amount) AS slstat_crs_sales'),
                DB::raw('SUM(orders.order_discount_value) AS slstat_crs_discount'),
                DB::raw('SUM(orders.order_reward_value) AS slstat_crs_credit_discount'),
            ])
            ->groupBy(DB::raw('DATE(orders.order_addedon)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $key = (string) $row['slstat_date'];
            $data[$key]['slstat_date'] = $row['slstat_date'];
            $data[$key]['slstat_crs_sales'] = $row['slstat_crs_sales'] ?? 0;
            $data[$key]['slstat_crs_discount'] = $row['slstat_crs_discount'] ?? 0;
            $data[$key]['slstat_crs_credit_discount'] = $row['slstat_crs_credit_discount'] ?? 0;
        }

        return $data;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateSubscriptionPlansReport(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->join('tbl_order_subscription_plans as ordsplan', 'ordsplan.ordsplan_order_id', '=', 'orders.order_id')
            ->whereRaw('DATE(ordsplan.ordsplan_updated) >= ?', [$date])
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->whereNotNull('ordsplan.ordsplan_earnings')
            ->select([
                DB::raw('DATE(ordsplan.ordsplan_updated) AS slstat_date'),
                DB::raw('SUM(IFNULL(ordsplan.ordsplan_refund, 0)) AS slstat_subplan_refund'),
                DB::raw('SUM(IFNULL(ordsplan.ordsplan_earnings, 0)) AS slstat_subplan_earnings'),
            ])
            ->groupBy(DB::raw('DATE(ordsplan.ordsplan_updated)'))
            ->orderByDesc(DB::raw('DATE(ordsplan.ordsplan_updated)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $key = (string) $row['slstat_date'];
            $data[$key]['slstat_date'] = $row['slstat_date'];
            $data[$key]['slstat_subplan_refund'] = $row['slstat_subplan_refund'];
            $data[$key]['slstat_subplan_earnings'] = $row['slstat_subplan_earnings'];
            $data[$key]['slstat_subplan_teacher_paid'] = 0;
        }

        return $data;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function generateSubscriptionPlansNetSale(string $date, array $data): array
    {
        $rows = DB::table('tbl_orders as orders')
            ->where('orders.order_type', '=', AdminOrderHelper::TYPE_SUBPLAN)
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->whereRaw('DATE(orders.order_addedon) >= ?', [$date])
            ->select([
                DB::raw('DATE(orders.order_addedon) AS slstat_date'),
                DB::raw('SUM(orders.order_net_amount) AS slstat_subplan_sales'),
                DB::raw('SUM(orders.order_discount_value) AS slstat_subplan_discount'),
                DB::raw('SUM(orders.order_reward_value) AS slstat_subplan_credit_discount'),
            ])
            ->groupBy(DB::raw('DATE(orders.order_addedon)'))
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $key = (string) $row['slstat_date'];
            $data[$key]['slstat_date'] = $row['slstat_date'];
            $data[$key]['slstat_subplan_sales'] = $row['slstat_subplan_sales'] ?? 0;
            $data[$key]['slstat_subplan_discount'] = $row['slstat_subplan_discount'] ?? 0;
            $data[$key]['slstat_subplan_credit_discount'] = $row['slstat_subplan_credit_discount'] ?? 0;
        }

        return $data;
    }

    private function regenerateHoursTaught(string $date): bool
    {
        $stats = $this->generateHoursTaughtLessons($date, []);
        $stats = $this->generateHoursTaughtClasses($date, $stats);

        foreach ($stats as $rows) {
            foreach ($rows as $row) {
                DB::table('tbl_hours_taught_stats')->updateOrInsert(
                    [
                        'hts_date' => $row['hts_date'],
                        'hts_teacher_id' => $row['hts_teacher_id'],
                    ],
                    [
                        'hts_lesson_duration' => $row['hts_lesson_duration'] ?? 0,
                        'hts_class_duration' => $row['hts_class_duration'] ?? 0,
                    ],
                );
            }
        }

        return true;
    }

    /** @param  array<string, array<int, array<string, mixed>>>  $stats */
    private function generateHoursTaughtLessons(string $date, array $stats): array
    {
        $rows = DB::table('tbl_order_lessons as ordles')
            ->where('ordles.ordles_status', '=', AdminOrderHelper::LESSON_COMPLETED)
            ->whereRaw('DATE(ordles.ordles_lesson_starttime) >= ?', [$date])
            ->whereRaw('DATE(ordles.ordles_teacher_starttime) IS NOT NULL')
            ->select([
                DB::raw('DATE(ordles.ordles_lesson_starttime) AS hts_date'),
                DB::raw('ordles.ordles_teacher_id AS hts_teacher_id'),
                DB::raw('SUM(ordles.ordles_duration) AS hts_lesson_duration'),
            ])
            ->groupBy(DB::raw('DATE(ordles.ordles_lesson_starttime)'), 'ordles.ordles_teacher_id')
            ->orderByDesc('ordles.ordles_teacher_id')
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $dateKey = (string) $row['hts_date'];
            $teacherId = (int) $row['hts_teacher_id'];
            $stats[$dateKey][$teacherId]['hts_date'] = $row['hts_date'];
            $stats[$dateKey][$teacherId]['hts_teacher_id'] = $teacherId;
            $stats[$dateKey][$teacherId]['hts_lesson_duration'] = $row['hts_lesson_duration'] ?? 0;
        }

        return $stats;
    }

    /** @param  array<string, array<int, array<string, mixed>>>  $stats */
    private function generateHoursTaughtClasses(string $date, array $stats): array
    {
        $rows = DB::table('tbl_group_classes as grpcls')
            ->where('grpcls.grpcls_status', '=', self::GROUP_CLASS_COMPLETED)
            ->where('grpcls.grpcls_type', '=', self::GROUP_CLASS_TYPE_REGULAR)
            ->whereRaw('DATE(grpcls.grpcls_start_datetime) >= ?', [$date])
            ->whereRaw('DATE(grpcls.grpcls_teacher_starttime) IS NOT NULL')
            ->select([
                DB::raw('DATE(grpcls.grpcls_start_datetime) AS hts_date'),
                DB::raw('grpcls.grpcls_teacher_id AS hts_teacher_id'),
                DB::raw('SUM(grpcls.grpcls_duration) AS hts_class_duration'),
            ])
            ->groupBy(DB::raw('DATE(grpcls.grpcls_start_datetime)'), 'grpcls.grpcls_teacher_id')
            ->orderByDesc('grpcls.grpcls_teacher_id')
            ->get();

        foreach ($rows as $row) {
            $row = (array) $row;
            $dateKey = (string) $row['hts_date'];
            $teacherId = (int) $row['hts_teacher_id'];
            $stats[$dateKey][$teacherId]['hts_date'] = $row['hts_date'];
            $stats[$dateKey][$teacherId]['hts_teacher_id'] = $teacherId;
            $stats[$dateKey][$teacherId]['hts_class_duration'] = $row['hts_class_duration'] ?? 0;
        }

        return $stats;
    }
}
