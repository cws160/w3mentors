<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderSubListingsService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchLessons(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_order_lessons as ordles')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'ordles.ordles_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->whereNull('learner.user_deleted')
            ->select([
                'ordles.ordles_id as id',
                'ordles.ordles_id as ordles_id',
                'orders.order_id as order_id',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as ordles_language_name'),
                'ordles.ordles_offline as ordles_offline',
                'ordles.ordles_amount as ordles_amount',
                'ordles.ordles_discount as ordles_discount',
                'ordles.ordles_reward_discount as ordles_reward_discount',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
                'ordles.ordles_status as ordles_status',
            ]);

        $this->applyLessonFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'ordles.ordles_id', fn (array $row) => [
            'id' => (int) $row['ordles_id'],
            'ordles_id' => (int) $row['ordles_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'learner_name' => (string) $row['learner_name'],
            'teacher_name' => (string) $row['teacher_name'],
            'ordles_language_name' => (string) $row['ordles_language_name'],
            'ordles_offline' => (int) $row['ordles_offline'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) $row['ordles_offline']),
            'ordles_net_amount' => AdminOrderHelper::lessonNetAmount(
                (float) $row['ordles_amount'],
                (float) $row['ordles_discount'],
                (float) $row['ordles_reward_discount'],
            ),
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_addedon' => (string) $row['order_addedon'],
            'ordles_status' => (int) $row['ordles_status'],
            'ordles_status_label' => AdminOrderHelper::lessonStatusLabel((int) $row['ordles_status']),
        ]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchSubscriptions(Request $request): array
    {
        $query = DB::table('tbl_order_subscriptions as ordsub')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordsub.ordsub_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordsub.ordsub_teacher_id')
            ->whereNull('learner.user_deleted')
            ->select([
                'ordsub.ordsub_id as id',
                'ordsub.ordsub_id as ordsub_id',
                'orders.order_id as order_id',
                'ordsub.ordsub_startdate as ordsub_startdate',
                'ordsub.ordsub_enddate as ordsub_enddate',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                'ordsub.ordsub_offline as ordsub_offline',
                'orders.order_net_amount as order_net_amount',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
                'ordsub.ordsub_status as ordsub_status',
            ]);

        $this->applySubscriptionFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'ordsub.ordsub_id', fn (array $row) => [
            'id' => (int) $row['ordsub_id'],
            'ordsub_id' => (int) $row['ordsub_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'ordsub_startdate' => (string) ($row['ordsub_startdate'] ?? ''),
            'ordsub_enddate' => (string) ($row['ordsub_enddate'] ?? ''),
            'learner_name' => (string) $row['learner_name'],
            'teacher_name' => (string) $row['teacher_name'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) $row['ordsub_offline']),
            'order_net_amount' => (float) $row['order_net_amount'],
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_addedon' => (string) $row['order_addedon'],
            'ordsub_status' => (int) $row['ordsub_status'],
            'ordsub_status_label' => $this->subscriptionStatusLabel((int) $row['ordsub_status']),
        ]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchClasses(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_order_classes as ordcls')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'grpcls.grpcls_teacher_id')
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'grpcls.grpcls_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('orders.order_type', '=', AdminOrderHelper::TYPE_GCLASS)
            ->where('grpcls.grpcls_type', '=', 1)
            ->where('ordcls.ordcls_type', '=', 1)
            ->where('ordcls.ordcls_id', '>', 0)
            ->whereNull('learner.user_deleted')
            ->select([
                'ordcls.ordcls_id as id',
                'ordcls.ordcls_id as ordcls_id',
                'orders.order_id as order_id',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as grpcls_language_name'),
                'grpcls.grpcls_offline as grpcls_offline',
                'ordcls.ordcls_amount as ordcls_amount',
                'ordcls.ordcls_discount as ordcls_discount',
                'ordcls.ordcls_reward_discount as ordcls_reward_discount',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
                'ordcls.ordcls_status as ordcls_status',
            ]);

        $this->applyClassFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'ordcls.ordcls_id', fn (array $row) => [
            'id' => (int) $row['ordcls_id'],
            'ordcls_id' => (int) $row['ordcls_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'learner_name' => (string) $row['learner_name'],
            'teacher_name' => (string) $row['teacher_name'],
            'grpcls_language_name' => (string) $row['grpcls_language_name'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) $row['grpcls_offline']),
            'ordcls_net_amount' => (float) ($row['ordcls_amount'] ?? 0)
                - (float) ($row['ordcls_discount'] ?? 0)
                - (float) ($row['ordcls_reward_discount'] ?? 0),
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_addedon' => (string) $row['order_addedon'],
            'ordcls_status' => (int) $row['ordcls_status'],
            'ordcls_status_label' => $this->classStatusLabel((int) $row['ordcls_status']),
        ]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchCourseOrders(Request $request): array
    {
        $query = DB::table('tbl_order_courses as ordcrs')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'course.course_user_id')
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->where('orders.order_type', '=', AdminOrderHelper::TYPE_COURSE)
            ->whereNull('learner.user_deleted')
            ->select([
                'ordcrs.ordcrs_id as id',
                'ordcrs.ordcrs_id as ordcrs_id',
                'orders.order_id as order_id',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(crsdetail.course_title, course.course_slug) as course_title'),
                'ordcrs.ordcrs_amount as ordcrs_amount',
                'ordcrs.ordcrs_discount as ordcrs_discount',
                'orders.order_reward_value as order_reward_value',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_created',
                'ordcrs.ordcrs_status as ordcrs_status',
            ]);

        $this->applyCourseOrderFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'ordcrs.ordcrs_id', fn (array $row) => [
            'id' => (int) $row['ordcrs_id'],
            'ordcrs_id' => (int) $row['ordcrs_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'learner_name' => (string) $row['learner_name'],
            'teacher_name' => (string) $row['teacher_name'],
            'course_title' => (string) $row['course_title'],
            'ordcrs_net_amount' => (float) ($row['ordcrs_amount'] ?? 0)
                - (float) ($row['ordcrs_discount'] ?? 0)
                - (float) ($row['order_reward_value'] ?? 0),
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_created' => (string) $row['order_created'],
            'ordcrs_status' => (int) $row['ordcrs_status'],
            'ordcrs_status_label' => AdminOrderHelper::courseOrderStatusLabel((int) $row['ordcrs_status']),
        ]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchPackages(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_order_packages as ordpkg')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordpkg.ordpkg_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordpkg.ordpkg_package_id')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'grpcls.grpcls_teacher_id')
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'grpcls.grpcls_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_group_classes_lang as gclang', function ($join) use ($langId) {
                $join->on('gclang.gclang_grpcls_id', '=', 'grpcls.grpcls_id')
                    ->where('gclang.gclang_lang_id', '=', $langId);
            })
            ->where('grpcls.grpcls_type', '=', 2)
            ->where('orders.order_type', '=', AdminOrderHelper::TYPE_PACKGE)
            ->whereNull('learner.user_deleted')
            ->select([
                'ordpkg.ordpkg_id as id',
                'ordpkg.ordpkg_id as ordpkg_id',
                'orders.order_id as order_id',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as grpcls_language_name'),
                'ordpkg.ordpkg_offline as ordpkg_offline',
                'orders.order_net_amount as order_net_amount',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
                'ordpkg.ordpkg_status as ordpkg_status',
            ]);

        $this->applyPackageFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'ordpkg.ordpkg_id', fn (array $row) => [
            'id' => (int) $row['ordpkg_id'],
            'ordpkg_id' => (int) $row['ordpkg_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'learner_name' => (string) $row['learner_name'],
            'teacher_name' => (string) $row['teacher_name'],
            'grpcls_language_name' => (string) $row['grpcls_language_name'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) $row['ordpkg_offline']),
            'order_net_amount' => (float) $row['order_net_amount'],
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_addedon' => (string) $row['order_addedon'],
            'ordpkg_status' => (int) $row['ordpkg_status'],
            'ordpkg_status_label' => $this->packageStatusLabel((int) $row['ordpkg_status']),
        ]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchGiftcards(Request $request): array
    {
        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_giftcards as ordgift', 'ordgift.ordgift_order_id', '=', 'orders.order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->where('orders.order_type', '=', AdminOrderHelper::TYPE_GFTCRD)
            ->whereNull('learner.user_deleted')
            ->select([
                'ordgift.ordgift_id as id',
                'orders.order_id as order_id',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as user_full_name'),
                'orders.order_total_amount as order_total_amount',
                'ordgift.ordgift_status as ordgift_status',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
            ]);

        $this->applyGiftcardFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'orders.order_id', fn (array $row) => [
            'id' => (int) $row['order_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'user_full_name' => (string) $row['user_full_name'],
            'order_total_amount' => (float) $row['order_total_amount'],
            'ordgift_status' => (int) $row['ordgift_status'],
            'ordgift_status_label' => $this->giftcardStatusLabel((int) $row['ordgift_status']),
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_addedon' => (string) $row['order_addedon'],
        ]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchWallet(Request $request): array
    {
        $query = DB::table('tbl_orders as orders')
            ->join('tbl_users as user', 'user.user_id', '=', 'orders.order_user_id')
            ->where('orders.order_type', '=', AdminOrderHelper::TYPE_WALLET)
            ->select([
                'orders.order_id as id',
                'orders.order_id as order_id',
                DB::raw('TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) as user_full_name'),
                'orders.order_total_amount as order_total_amount',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
            ]);

        $this->applyWalletFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'orders.order_id', fn (array $row) => [
            'id' => (int) $row['order_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'user_full_name' => (string) $row['user_full_name'],
            'order_total_amount' => (float) $row['order_total_amount'],
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_addedon' => (string) $row['order_addedon'],
        ]);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function searchOrderSubscriptionPlans(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_order_subscription_plans as ordsplan')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordsplan.ordsplan_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_subscription_plans as sp', 'sp.subplan_id', '=', 'ordsplan.ordsplan_plan_id')
            ->leftJoin('tbl_subscription_plans_lang as splang', function ($join) use ($langId) {
                $join->on('splang.subplang_subplan_id', '=', 'sp.subplan_id')
                    ->where('splang.subplang_lang_id', '=', $langId);
            })
            ->whereNull('learner.user_deleted')
            ->select([
                'ordsplan.ordsplan_id as id',
                'ordsplan.ordsplan_id as ordsplan_id',
                'orders.order_id as order_id',
                DB::raw('IFNULL(splang.subplang_subplan_title, sp.subplan_title) as plan_name'),
                'ordsplan.ordsplan_start_date as ordsplan_start_date',
                'ordsplan.ordsplan_end_date as ordsplan_end_date',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                'orders.order_net_amount as order_net_amount',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
                'ordsplan.ordsplan_status as ordsplan_status',
            ]);

        $this->applyOrderSubscriptionPlanFilters($request, $query);

        return $this->runFormattedQuery($request, $query, 'ordsplan.ordsplan_id', fn (array $row) => [
            'id' => (int) $row['ordsplan_id'],
            'ordsplan_id' => (int) $row['ordsplan_id'],
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'plan_name' => (string) $row['plan_name'],
            'ordsplan_start_date' => (string) ($row['ordsplan_start_date'] ?? ''),
            'ordsplan_end_date' => (string) ($row['ordsplan_end_date'] ?? ''),
            'learner_name' => (string) $row['learner_name'],
            'order_net_amount' => (float) $row['order_net_amount'],
            'order_payment_status' => (int) $row['order_payment_status'],
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $row['order_payment_status']),
            'order_addedon' => (string) $row['order_addedon'],
            'ordsplan_status' => (int) $row['ordsplan_status'],
            'ordsplan_status_label' => $this->subscriptionPlanStatusLabel((int) $row['ordsplan_status']),
        ]);
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $formatter
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function runFormattedQuery(Request $request, Builder $query, string $orderColumn, callable $formatter): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc($orderColumn)
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $formatter((array) $row))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function applyLessonFilters(Request $request, Builder $query): void
    {
        $orderId = $request->integer('order_id', 0);
        if ($orderId > 0) {
            $query->where('orders.order_id', '=', $orderId);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $parsed = AdminOrderHelper::parseOrderId($keyword);
            $query->where(function (Builder $q) use ($keyword, $parsed) {
                $q->whereRaw(
                    'TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) LIKE ?',
                    ['%'.$keyword.'%'],
                )->orWhereRaw(
                    'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                    ['%'.$keyword.'%'],
                );
                if ($parsed > 0) {
                    $q->orWhere('ordles.ordles_id', '=', $parsed)
                        ->orWhere('ordles.ordles_order_id', '=', $parsed);
                }
            });
        }

        $tlangId = $request->integer('ordles_tlang_id', 0);
        if ($tlangId > 0) {
            $query->where('ordles.ordles_tlang_id', '=', $tlangId);
        }

        $lessonType = $request->integer('ordles_type', 0);
        if ($lessonType > 0) {
            $query->where('ordles.ordles_type', '=', $lessonType);
        }

        if ($request->has('ordles_offline') && $request->query('ordles_offline') !== '' && $request->query('ordles_offline') !== null) {
            $query->where('ordles.ordles_offline', '=', (int) $request->query('ordles_offline'));
        }

        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }

        if ($request->has('ordles_status') && $request->query('ordles_status') !== '' && $request->query('ordles_status') !== null) {
            $query->where('ordles.ordles_status', '=', (int) $request->query('ordles_status'));
        }

        $dateFrom = trim((string) $request->query('order_addedon_from', ''));
        if ($dateFrom !== '') {
            $query->where('orders.order_addedon', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('order_addedon_till', ''));
        if ($dateTo !== '') {
            $query->where('orders.order_addedon', '<=', $dateTo.' 23:59:59');
        }
    }

    private function applySubscriptionFilters(Request $request, Builder $query): void
    {
        $this->applyCommonOrderKeywordFilter($request, $query, 'ordsub.ordsub_id', 'ordsub.ordsub_order_id');
        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }
        if ($request->has('ordsub_offline') && $request->query('ordsub_offline') !== '' && $request->query('ordsub_offline') !== null) {
            $query->where('ordsub.ordsub_offline', '=', (int) $request->query('ordsub_offline'));
        }
        if ($request->has('ordsub_status') && $request->query('ordsub_status') !== '' && $request->query('ordsub_status') !== null) {
            $query->where('ordsub.ordsub_status', '=', (int) $request->query('ordsub_status'));
        }
        $startDate = trim((string) $request->query('ordsub_startdate', ''));
        if ($startDate !== '') {
            $query->where('ordsub.ordsub_startdate', '>=', $startDate);
        }
        $endDate = trim((string) $request->query('ordsub_enddate', ''));
        if ($endDate !== '') {
            $query->where('ordsub.ordsub_enddate', '<=', $endDate);
        }
    }

    private function applyClassFilters(Request $request, Builder $query): void
    {
        $orderId = $request->integer('order_id', 0);
        if ($orderId > 0) {
            $query->where('orders.order_id', '=', $orderId);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $parsed = AdminOrderHelper::parseOrderId($keyword);
            $query->where(function (Builder $q) use ($keyword, $parsed) {
                $q->whereRaw(
                    'TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) LIKE ?',
                    ['%'.$keyword.'%'],
                )->orWhereRaw(
                    'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                    ['%'.$keyword.'%'],
                );
                if ($parsed > 0) {
                    $q->orWhere('ordcls.ordcls_id', '=', $parsed)
                        ->orWhere('ordcls.ordcls_order_id', '=', $parsed);
                }
            });
        }

        $tlangId = $request->integer('ordcls_tlang_id', 0);
        if ($tlangId > 0) {
            $query->where(function (Builder $q) use ($tlangId) {
                $q->where('grpcls.grpcls_tlang_id', '=', $tlangId)
                    ->orWhereRaw('FIND_IN_SET(?, tlang.tlang_parentids)', [$tlangId]);
            });
        }

        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }
        if ($request->has('ordcls_status') && $request->query('ordcls_status') !== '' && $request->query('ordcls_status') !== null) {
            $query->where('ordcls.ordcls_status', '=', (int) $request->query('ordcls_status'));
        }

        if ($request->has('grpcls_offline') && $request->query('grpcls_offline') !== '' && $request->query('grpcls_offline') !== null) {
            $query->where('grpcls.grpcls_offline', '=', (int) $request->query('grpcls_offline'));
        }

        $dateFrom = trim((string) $request->query('order_addedon_from', ''));
        if ($dateFrom !== '') {
            $query->where('orders.order_addedon', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('order_addedon_till', ''));
        if ($dateTo !== '') {
            $query->where('orders.order_addedon', '<=', $dateTo.' 23:59:59');
        }
    }

    private function applyCourseOrderFilters(Request $request, Builder $query): void
    {
        $orderId = $request->integer('order_id', 0);
        if ($orderId > 0) {
            $query->where('orders.order_id', '=', $orderId);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $parsed = AdminOrderHelper::parseOrderId($keyword);
            $query->where(function (Builder $q) use ($keyword, $parsed) {
                $q->where('crsdetail.course_title', 'like', '%'.$keyword.'%')
                    ->orWhereRaw(
                        'TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) LIKE ?',
                        ['%'.$keyword.'%'],
                    )
                    ->orWhereRaw(
                        'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                        ['%'.$keyword.'%'],
                    );
                if ($parsed > 0) {
                    $q->orWhere('ordcrs.ordcrs_id', '=', $parsed)
                        ->orWhere('ordcrs.ordcrs_order_id', '=', $parsed);
                }
            });
        }

        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }
        if ($request->has('ordcrs_status') && $request->query('ordcrs_status') !== '' && $request->query('ordcrs_status') !== null) {
            $query->where('ordcrs.ordcrs_status', '=', (int) $request->query('ordcrs_status'));
        }

        $dateFrom = trim((string) $request->query('order_addedon_from', ''));
        if ($dateFrom !== '') {
            $query->where('orders.order_addedon', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('order_addedon_till', ''));
        if ($dateTo !== '') {
            $query->where('orders.order_addedon', '<=', $dateTo.' 23:59:59');
        }
    }

    private function applyPackageFilters(Request $request, Builder $query): void
    {
        $orderId = $request->integer('order_id', 0);
        if ($orderId > 0) {
            $query->where('orders.order_id', '=', $orderId);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $parsed = AdminOrderHelper::parseOrderId($keyword);
            $query->where(function (Builder $q) use ($keyword, $parsed) {
                $q->where('gclang.grpcls_title', 'like', '%'.$keyword.'%')
                    ->orWhere('grpcls.grpcls_title', 'like', '%'.$keyword.'%')
                    ->orWhereRaw(
                        'TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) LIKE ?',
                        ['%'.$keyword.'%'],
                    )
                    ->orWhereRaw(
                        'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                        ['%'.$keyword.'%'],
                    );
                if ($parsed > 0) {
                    $q->orWhere('ordpkg.ordpkg_id', '=', $parsed)
                        ->orWhere('ordpkg.ordpkg_order_id', '=', $parsed)
                        ->orWhere('grpcls.grpcls_id', '=', $parsed);
                }
            });
        }

        $tlangId = $request->integer('ordcls_tlang_id', 0);
        if ($tlangId > 0) {
            $query->where(function (Builder $q) use ($tlangId) {
                $q->where('grpcls.grpcls_tlang_id', '=', $tlangId)
                    ->orWhereRaw('FIND_IN_SET(?, tlang.tlang_parentids)', [$tlangId]);
            });
        }

        if ($request->has('ordpkg_offline') && $request->query('ordpkg_offline') !== '' && $request->query('ordpkg_offline') !== null) {
            $query->where('ordpkg.ordpkg_offline', '=', (int) $request->query('ordpkg_offline'));
        }

        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }
        if ($request->has('ordpkg_status') && $request->query('ordpkg_status') !== '' && $request->query('ordpkg_status') !== null) {
            $query->where('ordpkg.ordpkg_status', '=', (int) $request->query('ordpkg_status'));
        }

        $dateFrom = trim((string) $request->query('order_addedon_from', ''));
        if ($dateFrom !== '') {
            $query->where('orders.order_addedon', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('order_addedon_till', ''));
        if ($dateTo !== '') {
            $query->where('orders.order_addedon', '<=', $dateTo.' 23:59:59');
        }
    }

    private function applyGiftcardFilters(Request $request, Builder $query): void
    {
        $this->applyCommonOrderKeywordFilter($request, $query, 'ordgift.ordgift_id', 'orders.order_id');
        if ($request->has('giftcard_status') && $request->query('giftcard_status') !== '' && $request->query('giftcard_status') !== null) {
            $query->where('ordgift.ordgift_status', '=', (int) $request->query('giftcard_status'));
        }
        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }

        $dateFrom = trim((string) $request->query('date_from', ''));
        if ($dateFrom !== '') {
            $query->where('orders.order_addedon', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('date_to', ''));
        if ($dateTo !== '') {
            $query->where('orders.order_addedon', '<=', $dateTo.' 23:59:59');
        }
    }

    private function applyWalletFilters(Request $request, Builder $query): void
    {
        $orderId = $request->integer('order_id', 0);
        if ($orderId > 0) {
            $query->where('orders.order_id', '=', $orderId);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $parsed = AdminOrderHelper::parseOrderId($keyword);
            $query->where(function (Builder $q) use ($keyword, $parsed) {
                $q->whereRaw(
                    'TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) LIKE ?',
                    ['%'.$keyword.'%'],
                );
                if ($parsed > 0) {
                    $q->orWhere('orders.order_id', '=', $parsed);
                }
            });
        }

        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }

        $dateFrom = trim((string) $request->query('order_addedon_from', $request->query('date_from', '')));
        if ($dateFrom !== '') {
            $query->where('orders.order_addedon', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('order_addedon_till', $request->query('date_to', '')));
        if ($dateTo !== '') {
            $query->where('orders.order_addedon', '<=', $dateTo.' 23:59:59');
        }
    }

    private function applyOrderSubscriptionPlanFilters(Request $request, Builder $query): void
    {
        $this->applyCommonOrderKeywordFilter($request, $query, 'ordsplan.ordsplan_id', 'ordsplan.ordsplan_order_id');
        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }
        if ($request->has('ordsplan_status') && $request->query('ordsplan_status') !== '' && $request->query('ordsplan_status') !== null) {
            $query->where('ordsplan.ordsplan_status', '=', (int) $request->query('ordsplan_status'));
        }
    }

    private function applyCommonOrderKeywordFilter(Request $request, Builder $query, string $idColumn, string $orderColumn): void
    {
        $orderId = $request->integer('order_id', 0);
        if ($orderId > 0) {
            $query->where($orderColumn, '=', $orderId);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword === '') {
            return;
        }

        $parsed = AdminOrderHelper::parseOrderId($keyword);
        $query->where(function (Builder $q) use ($keyword, $parsed, $idColumn, $orderColumn) {
            $q->whereRaw(
                'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                ['%'.$keyword.'%'],
            );
            if ($parsed > 0) {
                $q->orWhere($idColumn, '=', $parsed)
                    ->orWhere($orderColumn, '=', $parsed);
            }
        });
    }

    private function subscriptionStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Active',
            2 => 'Completed',
            3 => 'Cancelled',
            default => '—',
        };
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

    private function courseOrderStatusLabel(int $status): string
    {
        return AdminOrderHelper::courseOrderStatusLabel($status);
    }

    private function packageStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Active',
            2 => 'Completed',
            3 => 'Cancelled',
            default => '—',
        };
    }

    private function giftcardStatusLabel(int $status): string
    {
        return match ($status) {
            0 => 'Unused',
            1 => 'Used',
            2 => 'Cancelled',
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
}
