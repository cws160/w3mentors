<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrdersListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_orders as orders')
            ->leftJoin('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->leftJoin('tbl_order_lessons as ordles', function ($join) {
                $join->on('orders.order_id', '=', 'ordles.ordles_order_id')
                    ->where('orders.order_type', '=', AdminOrderHelper::TYPE_LESSON);
            })
            ->leftJoin('tbl_order_classes as ordcls', function ($join) {
                $join->on('orders.order_id', '=', 'ordcls.ordcls_order_id')
                    ->where('orders.order_type', '=', AdminOrderHelper::TYPE_GCLASS);
            })
            ->leftJoin('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->whereNull('learner.user_deleted')
            ->groupBy([
                'orders.order_id',
                'orders.order_net_amount',
                'orders.order_type',
                'orders.order_status',
                'orders.order_user_id',
                'orders.order_payment_status',
                'orders.order_addedon',
                'learner.user_first_name',
                'learner.user_last_name',
                'ordles.ordles_offline',
                'grpcls.grpcls_offline',
            ])
            ->select([
                'orders.order_id as id',
                'orders.order_id as order_id',
                'orders.order_type as order_type',
                'orders.order_status as order_status',
                'orders.order_net_amount as order_net_amount',
                'orders.order_payment_status as order_payment_status',
                'orders.order_addedon as order_addedon',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_full_name'),
                'ordles.ordles_offline',
                'grpcls.grpcls_offline',
            ]);

        $this->applyFeatureFilters($request, $query);
        $this->applySearchFilters($request, $query);

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count(DB::raw('DISTINCT orders.order_id'));
        $rows = $query
            ->orderByDesc('orders.order_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row, $langId))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row, int $langId): array
    {
        $orderId = (int) $row['order_id'];
        $orderType = (int) $row['order_type'];
        $serviceType = $this->resolveServiceType($orderType, $orderId, $row);

        return [
            'id' => $orderId,
            'order_id' => $orderId,
            'order_id_formatted' => AdminOrderHelper::formatOrderId($orderId),
            'learner_full_name' => trim((string) ($row['learner_full_name'] ?? '')),
            'order_type' => $orderType,
            'order_type_label' => AdminOrderHelper::orderTypeLabel($orderType),
            'service_type' => $serviceType,
            'service_type_label' => AdminOrderHelper::serviceTypeLabel($serviceType === '' ? null : (int) $serviceType),
            'order_net_amount' => (float) ($row['order_net_amount'] ?? 0),
            'order_payment_status' => (int) ($row['order_payment_status'] ?? 0),
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) ($row['order_payment_status'] ?? 0)),
            'order_status' => (int) ($row['order_status'] ?? 0),
            'order_status_label' => AdminOrderHelper::orderStatusLabel((int) ($row['order_status'] ?? 0)),
            'order_addedon' => (string) ($row['order_addedon'] ?? ''),
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function resolveServiceType(int $orderType, int $orderId, array $row): int|string
    {
        return match ($orderType) {
            AdminOrderHelper::TYPE_LESSON => (int) ($row['ordles_offline'] ?? AdminOrderHelper::SERVICE_ONLINE),
            AdminOrderHelper::TYPE_GCLASS => (int) ($row['grpcls_offline'] ?? AdminOrderHelper::SERVICE_ONLINE),
            AdminOrderHelper::TYPE_SUBSCR => $this->subscriptionOffline($orderId),
            AdminOrderHelper::TYPE_PACKGE => $this->packageOffline($orderId),
            AdminOrderHelper::TYPE_COURSE,
            AdminOrderHelper::TYPE_SUBPLAN,
            AdminOrderHelper::TYPE_WALLET,
            AdminOrderHelper::TYPE_GFTCRD => '',
            default => AdminOrderHelper::SERVICE_ONLINE,
        };
    }

    private function subscriptionOffline(int $orderId): int
    {
        $offline = DB::table('tbl_order_subscriptions')
            ->where('ordsub_order_id', $orderId)
            ->value('ordsub_offline');

        return (int) ($offline ?? AdminOrderHelper::SERVICE_ONLINE);
    }

    private function packageOffline(int $orderId): int
    {
        $offline = DB::table('tbl_order_packages')
            ->where('ordpkg_order_id', $orderId)
            ->value('ordpkg_offline');

        return (int) ($offline ?? AdminOrderHelper::SERVICE_ONLINE);
    }

    private function applyFeatureFilters(Request $request, Builder $query): void
    {
        $configs = DB::table('tbl_configurations')
            ->whereIn('conf_name', ['CONF_ENABLE_COURSES', 'CONF_ENABLE_GROUP_CLASSES', 'CONF_ENABLE_SUBSCRIPTION_PLAN'])
            ->pluck('conf_val', 'conf_name');

        if ((int) ($configs['CONF_ENABLE_COURSES'] ?? 1) !== 1) {
            $query->where('orders.order_type', '!=', AdminOrderHelper::TYPE_COURSE);
        }
        if ((int) ($configs['CONF_ENABLE_GROUP_CLASSES'] ?? 1) !== 1) {
            $query->whereNotIn('orders.order_type', [AdminOrderHelper::TYPE_GCLASS, AdminOrderHelper::TYPE_PACKGE]);
        }
        if ((int) ($configs['CONF_ENABLE_SUBSCRIPTION_PLAN'] ?? 1) !== 1) {
            $query->where('orders.order_type', '!=', AdminOrderHelper::TYPE_SUBPLAN);
        }
    }

    private function applySearchFilters(Request $request, Builder $query): void
    {
        $orderId = AdminOrderHelper::parseOrderId((string) $request->query('order_id', ''));
        if ($orderId > 0) {
            $query->where('orders.order_id', '=', $orderId);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $parsed = AdminOrderHelper::parseOrderId($keyword);
            $query->where(function (Builder $q) use ($keyword, $parsed) {
                $q->whereRaw(
                    'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                    ['%'.$keyword.'%'],
                );
                if ($parsed > 0) {
                    $q->orWhere('orders.order_id', '=', $parsed);
                }
            });
        }

        $orderUser = trim((string) $request->query('order_user', ''));
        if ($orderUser !== '') {
            $query->whereRaw(
                'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                ['%'.$orderUser.'%'],
            );
        }

        $orderUserId = $request->integer('order_user_id', 0);
        if ($orderUserId > 0) {
            $query->where('orders.order_user_id', '=', $orderUserId);
        }

        $orderType = $request->integer('order_type', 0);
        if ($orderType > 0) {
            $query->where('orders.order_type', '=', $orderType);
        }

        if ($request->has('service_type') && $request->query('service_type') !== '' && $request->query('service_type') !== null) {
            $serviceType = (int) $request->query('service_type');
            $query->where(function (Builder $q) use ($serviceType) {
                $q->where('ordles.ordles_offline', '=', $serviceType)
                    ->orWhere('grpcls.grpcls_offline', '=', $serviceType)
                    ->orWhereExists(function ($sub) use ($serviceType) {
                        $sub->select(DB::raw(1))
                            ->from('tbl_order_packages as ordpkg')
                            ->whereColumn('ordpkg.ordpkg_order_id', 'orders.order_id')
                            ->where('ordpkg.ordpkg_offline', '=', $serviceType);
                    })
                    ->orWhereExists(function ($sub) use ($serviceType) {
                        $sub->select(DB::raw(1))
                            ->from('tbl_order_subscriptions as ordsub')
                            ->whereColumn('ordsub.ordsub_order_id', 'orders.order_id')
                            ->where('ordsub.ordsub_offline', '=', $serviceType);
                    });
            });
        }

        if ($request->has('order_payment_status') && $request->query('order_payment_status') !== '' && $request->query('order_payment_status') !== null) {
            $query->where('orders.order_payment_status', '=', (int) $request->query('order_payment_status'));
        }

        if ($request->has('order_status') && $request->query('order_status') !== '' && $request->query('order_status') !== null) {
            $query->where('orders.order_status', '=', (int) $request->query('order_status'));
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
}
