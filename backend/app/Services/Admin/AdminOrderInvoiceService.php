<?php

namespace App\Services\Admin;

use App\Support\Branding;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminOrderInvoiceService
{
    public function __construct(
        private AdminOrderManageService $orders,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function build(int $orderId, int $langId = 1, ?int $subOrderId = null): ?array
    {
        $base = $this->orders->showOrder($orderId, $langId);
        if (! $base) {
            return null;
        }

        $order = $base['order'];
        $orderType = (int) $order['order_type'];
        $subOrders = $this->getSubOrders($orderId, $orderType, $langId, $subOrderId);
        $firstSub = $subOrders[0] ?? null;

        $totals = [
            'order_total_amount' => (float) $order['order_total_amount'],
            'order_discount_value' => (float) $order['order_discount_value'],
            'order_reward_value' => (float) $order['order_reward_value'],
            'order_net_amount' => (float) $order['order_net_amount'],
        ];

        $detail = $this->buildDetailFields($orderType, $firstSub, $subOrders, $subOrderId, $totals, $langId);

        $pmethodId = (int) ($order['order_pmethod_id'] ?? 0);
        $payMethod = $base['payment_methods'][$pmethodId] ?? 'N/A';

        $relatedOrderId = (int) DB::table('tbl_orders')
            ->where('order_id', $orderId)
            ->value('order_related_order_id');

        return [
            'logo_url' => rtrim((string) config('app.frontend_url', config('app.url')), '/').Branding::LOGO_URL,
            'order' => array_merge($order, $totals, [
                'order_addedon_formatted' => $this->formatDate($order['order_addedon']),
                'pay_method' => $payMethod,
                'service_type_label' => $detail['service_type_label'] ?? '',
                'order_related_order_id' => $relatedOrderId,
                'order_related_order_id_formatted' => $relatedOrderId > 0
                    ? AdminOrderHelper::formatOrderId($relatedOrderId)
                    : '',
            ]),
            'order_type' => $orderType,
            'sub_orders' => $subOrders,
            'detail' => $detail,
            'show_items_table' => ! in_array($orderType, [
                AdminOrderHelper::TYPE_COURSE,
                AdminOrderHelper::TYPE_WALLET,
            ], true),
            'items_table' => $this->itemsTableMeta($orderType),
            'sub_order_id' => $subOrderId,
        ];
    }

    /** @return array<string, mixed> */
    private function buildDetailFields(
        int $orderType,
        ?array $subOrder,
        array $subOrders,
        ?int $subOrderId,
        array &$totals,
        int $langId,
    ): array {
        $detail = [
            'item_name' => '',
            'teacher_name' => '',
            'lesson_duration' => '',
            'class_duration' => '',
            'teach_language' => '',
            'quantity' => 0,
            'receiver_name' => '',
            'receiver_email' => '',
            'service_type_label' => '',
            'subplan_start' => '',
            'subplan_end' => '',
            'subplan_validity' => '',
            'subplan_lessons' => '',
            'subplan_duration' => '',
            'subplan_status' => '',
        ];

        if (! $subOrder) {
            return $detail;
        }

        switch ($orderType) {
            case AdminOrderHelper::TYPE_LESSON:
            case AdminOrderHelper::TYPE_SUBSCR:
                $detail['item_name'] = $this->lessonLanguageName($subOrder, $langId);
                $detail['lesson_duration'] = ((int) ($subOrder['ordles_duration'] ?? 0)).' Minutes';
                $detail['quantity'] = count($subOrders);
                $detail['teacher_name'] = $this->teacherName($subOrder);
                $detail['service_type_label'] = AdminOrderHelper::serviceTypeLabel((int) ($subOrder['is_offline'] ?? 0));
                if ($subOrderId) {
                    $totals['order_total_amount'] = (float) ($subOrder['ordles_amount'] ?? 0);
                    $totals['order_discount_value'] = (float) ($subOrder['ordles_discount'] ?? 0);
                    $totals['order_reward_value'] = (float) ($subOrder['ordles_reward_discount'] ?? 0);
                    $totals['order_net_amount'] = AdminOrderHelper::lessonNetAmount(
                        $totals['order_total_amount'],
                        $totals['order_discount_value'],
                        $totals['order_reward_value'],
                    );
                }
                break;
            case AdminOrderHelper::TYPE_GCLASS:
            case AdminOrderHelper::TYPE_PACKGE:
                $detail['item_name'] = $orderType === AdminOrderHelper::TYPE_GCLASS
                    ? (string) ($subOrder['grpcls_title'] ?? '')
                    : (string) ($subOrder['package_title'] ?? '');
                $detail['teach_language'] = (string) ($subOrder['tlang_name'] ?? '');
                $detail['class_duration'] = ((int) ($subOrder['grpcls_duration'] ?? 0)).' Minutes';
                $detail['teacher_name'] = $this->teacherName($subOrder);
                $detail['service_type_label'] = AdminOrderHelper::serviceTypeLabel((int) ($subOrder['is_offline'] ?? 0));
                if ($subOrderId) {
                    $totals['order_total_amount'] = (float) ($subOrder['ordcls_amount'] ?? 0);
                    $totals['order_discount_value'] = (float) ($subOrder['ordcls_discount'] ?? 0);
                    $totals['order_reward_value'] = (float) ($subOrder['ordcls_reward_discount'] ?? 0);
                    $totals['order_net_amount'] = $totals['order_total_amount']
                        - $totals['order_discount_value']
                        - $totals['order_reward_value'];
                }
                break;
            case AdminOrderHelper::TYPE_COURSE:
                $detail['item_name'] = (string) ($subOrder['course_title'] ?? '');
                $detail['teach_language'] = (string) ($subOrder['clang_name'] ?? '');
                $detail['class_duration'] = (string) ($subOrder['course_duration_label'] ?? '');
                $detail['teacher_name'] = $this->teacherName($subOrder);
                break;
            case AdminOrderHelper::TYPE_GFTCRD:
                $detail['receiver_name'] = (string) ($subOrder['ordgift_receiver_name'] ?? '');
                $detail['receiver_email'] = (string) ($subOrder['ordgift_receiver_email'] ?? '');
                break;
            case AdminOrderHelper::TYPE_SUBPLAN:
                $detail['item_name'] = (string) ($subOrder['plan_name'] ?? '');
                $detail['subplan_start'] = $this->formatDate($subOrder['ordsplan_start_date'] ?? null);
                $detail['subplan_end'] = $this->formatDate($subOrder['ordsplan_end_date'] ?? null);
                $detail['subplan_validity'] = (string) ($subOrder['ordsplan_validity'] ?? '');
                $detail['subplan_lessons'] = (string) ($subOrder['ordsplan_lessons'] ?? '');
                $detail['subplan_duration'] = (string) ($subOrder['ordsplan_duration'] ?? '');
                $detail['subplan_status'] = $this->subscriptionPlanStatusLabel((int) ($subOrder['ordsplan_status'] ?? 0));
                if ($subOrderId) {
                    $totals['order_total_amount'] = (float) ($subOrder['ordsplan_amount'] ?? 0);
                    $totals['order_discount_value'] = (float) ($subOrder['ordsplan_discount'] ?? 0);
                    $totals['order_reward_value'] = (float) ($subOrder['ordsplan_reward_discount'] ?? 0);
                    $totals['order_net_amount'] = $totals['order_total_amount']
                        - $totals['order_discount_value']
                        - $totals['order_reward_value'];
                }
                break;
        }

        return $detail;
    }

    /** @return array<string, string> */
    private function itemsTableMeta(int $orderType): array
    {
        return match ($orderType) {
            AdminOrderHelper::TYPE_LESSON, AdminOrderHelper::TYPE_SUBSCR => [
                'type' => 'lessons',
                'col_id' => 'Lesson ID',
                'col_start' => 'Lesson start time',
                'col_end' => 'Lesson end time',
                'col_status' => 'Status',
            ],
            AdminOrderHelper::TYPE_GCLASS, AdminOrderHelper::TYPE_PACKGE => [
                'type' => 'classes',
                'col_id' => 'Class ID',
                'col_start' => 'Class start time',
                'col_end' => 'Class end time',
                'col_status' => 'Status',
            ],
            AdminOrderHelper::TYPE_GFTCRD => [
                'type' => 'giftcard',
            ],
            AdminOrderHelper::TYPE_SUBPLAN => [
                'type' => 'subplan',
            ],
            default => ['type' => 'none'],
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function getSubOrders(int $orderId, int $orderType, int $langId, ?int $subOrderId): array
    {
        $rows = match ($orderType) {
            AdminOrderHelper::TYPE_LESSON, AdminOrderHelper::TYPE_SUBSCR => $this->lessonSubOrders($orderId, $orderType, $langId, $subOrderId),
            AdminOrderHelper::TYPE_GCLASS, AdminOrderHelper::TYPE_PACKGE => $this->classSubOrders($orderId, $orderType, $langId, $subOrderId),
            AdminOrderHelper::TYPE_GFTCRD => $this->giftcardSubOrders($orderId),
            AdminOrderHelper::TYPE_SUBPLAN => $this->subscriptionPlanSubOrders($orderId, $langId, $subOrderId),
            AdminOrderHelper::TYPE_COURSE => $this->courseSubOrders($orderId, $langId),
            default => [],
        };

        return array_map(function (array $row) use ($orderType) {
            if (in_array($orderType, [AdminOrderHelper::TYPE_LESSON, AdminOrderHelper::TYPE_SUBSCR], true)) {
                $row['ordles_lesson_starttime_fmt'] = $this->formatDate($row['ordles_lesson_starttime'] ?? null);
                $row['ordles_lesson_endtime_fmt'] = $this->formatDate($row['ordles_lesson_endtime'] ?? null);
                $row['ordles_status_label'] = AdminOrderHelper::lessonStatusLabel((int) ($row['ordles_status'] ?? 0));
            }
            if (in_array($orderType, [AdminOrderHelper::TYPE_GCLASS, AdminOrderHelper::TYPE_PACKGE], true)) {
                $row['grpcls_start_datetime_fmt'] = $this->formatDate($row['grpcls_start_datetime'] ?? null);
                $row['grpcls_end_datetime_fmt'] = $this->formatDate($row['grpcls_end_datetime'] ?? null);
                $row['ordcls_status_label'] = $this->classStatusLabel((int) ($row['ordcls_status'] ?? 0));
            }
            if ($orderType === AdminOrderHelper::TYPE_SUBPLAN) {
                $row['ordsplan_start_date_fmt'] = $this->formatDate($row['ordsplan_start_date'] ?? null);
                $row['ordsplan_end_date_fmt'] = $this->formatDate($row['ordsplan_end_date'] ?? null);
                $row['ordsplan_status_label'] = $this->subscriptionPlanStatusLabel((int) ($row['ordsplan_status'] ?? 0));
            }

            return $row;
        }, $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private function lessonSubOrders(int $orderId, int $orderType, int $langId, ?int $subOrderId): array
    {
        $query = DB::table('tbl_order_lessons as ordles')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'ordles.ordles_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('ordles.ordles_order_id', $orderId)
            ->when($subOrderId, fn ($q) => $q->where('ordles.ordles_id', $subOrderId))
            ->orderBy('ordles.ordles_id')
            ->select([
                'ordles.*',
                'teacher.user_first_name',
                'teacher.user_last_name',
                DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as tlang_name'),
                DB::raw('ordles.ordles_offline as is_offline'),
            ]);

        if ($orderType === AdminOrderHelper::TYPE_SUBSCR) {
            $query->leftJoin('tbl_order_subscriptions as ordsub', 'ordsub.ordsub_order_id', '=', 'ordles.ordles_order_id')
                ->addSelect(['ordsub.ordsub_startdate', 'ordsub.ordsub_enddate', 'ordsub.ordsub_status']);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function classSubOrders(int $orderId, int $orderType, int $langId, ?int $subOrderId): array
    {
        $query = DB::table('tbl_order_classes as ordcls')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'grpcls.grpcls_teacher_id')
            ->leftJoin('tbl_group_classes_lang as gclang', function ($join) use ($langId) {
                $join->on('gclang.gclang_grpcls_id', '=', 'grpcls.grpcls_id')
                    ->where('gclang.gclang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'grpcls.grpcls_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('ordcls.ordcls_order_id', $orderId)
            ->when($subOrderId, fn ($q) => $q->where('ordcls.ordcls_id', $subOrderId));

        $select = [
            'ordcls.*',
            'grpcls.grpcls_start_datetime',
            'grpcls.grpcls_end_datetime',
            'grpcls.grpcls_duration',
            'teacher.user_first_name',
            'teacher.user_last_name',
            DB::raw('IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title'),
            DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as tlang_name'),
            DB::raw('grpcls.grpcls_offline as is_offline'),
        ];

        if ($orderType === AdminOrderHelper::TYPE_GCLASS) {
            $query->where('ordcls.ordcls_type', 1);
        } else {
            $query->where('ordcls.ordcls_type', 2)
                ->leftJoin('tbl_order_packages as ordpkg', 'ordpkg.ordpkg_order_id', '=', 'ordcls.ordcls_order_id')
                ->leftJoin('tbl_group_classes as package', 'package.grpcls_id', '=', 'ordpkg.ordpkg_package_id')
                ->leftJoin('tbl_group_classes_lang as packlang', function ($join) use ($langId) {
                    $join->on('packlang.gclang_grpcls_id', '=', 'package.grpcls_id')
                        ->where('packlang.gclang_lang_id', '=', $langId);
                });
            $select[] = DB::raw('IFNULL(packlang.grpcls_title, package.grpcls_title) as package_title');
        }

        return $query
            ->orderBy('ordcls.ordcls_id')
            ->select($select)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function giftcardSubOrders(int $orderId): array
    {
        $row = DB::table('tbl_order_giftcards')
            ->where('ordgift_order_id', $orderId)
            ->first();

        return $row ? [(array) $row] : [];
    }

    /** @return array<int, array<string, mixed>> */
    private function subscriptionPlanSubOrders(int $orderId, int $langId, ?int $planOrderId): array
    {
        $query = DB::table('tbl_order_subscription_plans as ordsplan')
            ->join('tbl_subscription_plans as sp', 'sp.subplan_id', '=', 'ordsplan.ordsplan_plan_id')
            ->leftJoin('tbl_subscription_plans_lang as splang', function ($join) use ($langId) {
                $join->on('splang.subplang_subplan_id', '=', 'sp.subplan_id')
                    ->where('splang.subplang_lang_id', '=', $langId);
            })
            ->where('ordsplan.ordsplan_order_id', $orderId)
            ->when($planOrderId, fn ($q) => $q->where('ordsplan.ordsplan_id', $planOrderId));

        return $query
            ->select([
                'ordsplan.*',
                DB::raw('IFNULL(splang.subplang_subplan_title, sp.subplan_title) as plan_name'),
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function courseSubOrders(int $orderId, int $langId): array
    {
        $row = DB::table('tbl_order_courses as ordcrs')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'course.course_user_id')
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->leftJoin('tbl_course_languages as clang', 'clang.clang_id', '=', 'course.course_clang_id')
            ->leftJoin('tbl_course_languages_lang as clanglang', function ($join) use ($langId) {
                $join->on('clanglang.clanglang_clang_id', '=', 'clang.clang_id')
                    ->where('clanglang.clanglang_lang_id', '=', $langId);
            })
            ->where('ordcrs.ordcrs_order_id', $orderId)
            ->select([
                'ordcrs.*',
                DB::raw('IFNULL(crsdetail.course_title, course.course_slug) as course_title'),
                DB::raw('IFNULL(clanglang.clang_name, clang.clang_identifier) as clang_name'),
                'course.course_duration',
                'teacher.user_first_name',
                'teacher.user_last_name',
            ])
            ->first();

        if (! $row) {
            return [];
        }

        $data = (array) $row;
        $minutes = (int) ($data['course_duration'] ?? 0);
        $data['course_duration_label'] = $minutes > 0 ? $minutes.' Minutes' : '';

        return [$data];
    }

    /** @param  array<string, mixed>  $subOrder */
    private function lessonLanguageName(array $subOrder, int $langId): string
    {
        if ((int) ($subOrder['ordles_type'] ?? 0) === 2) {
            return 'Free Trial';
        }
        $name = trim((string) ($subOrder['tlang_name'] ?? ''));

        return $name !== '' ? $name : 'N/A';
    }

    /** @param  array<string, mixed>  $subOrder */
    private function teacherName(array $subOrder): string
    {
        return ucwords(trim(((string) ($subOrder['user_first_name'] ?? '')).' '.((string) ($subOrder['user_last_name'] ?? ''))));
    }

    private function formatDate(mixed $value): string
    {
        if (! $value || $value === '0000-00-00 00:00:00') {
            return '—';
        }
        try {
            return Carbon::parse((string) $value)->format('M d, Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function classStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Scheduled',
            2 => 'Completed',
            3 => 'Cancelled',
            default => '—',
        };
    }

    private function subscriptionPlanStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Active',
            2 => 'Expired',
            3 => 'Cancelled',
            default => '—',
        };
    }

    public static function formatInvoiceMoney(float $amount): string
    {
        return '$'.number_format($amount, 2, '.', ',');
    }
}
