<?php

namespace App\Services\Admin\Listings;

use App\Models\Configuration;
use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAffiliateReportListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $currency = $this->currencySymbol();

        $query = DB::table('tbl_users as affiliate')
            ->leftJoin('tbl_affiliate_stats as afstat', 'afstat.afstat_user_id', '=', 'affiliate.user_id')
            ->where('affiliate.user_is_affiliate', '=', 1)
            ->where(function (Builder $q) {
                $q->where('afstat.afstat_referees', '>', 0)
                    ->orWhere('afstat.afstat_referee_sessions', '>', 0);
            })
            ->select([
                'affiliate.user_id as id',
                'affiliate.user_id as user_id',
                DB::raw('TRIM(CONCAT(COALESCE(affiliate.user_first_name, ""), " ", COALESCE(affiliate.user_last_name, ""))) as affiliate_name'),
                'afstat.afstat_referees',
                'afstat.afstat_referee_sessions',
                'afstat.afstat_signup_revenue',
                'afstat.afstat_order_revenue',
            ]);

        $this->applyFilters($request, $query);

        $query->orderByDesc('afstat.afstat_referees')
            ->orderByDesc('afstat.afstat_referee_sessions');

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row, $currency))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function applyFilters(Request $request, Builder $query): void
    {
        $userId = $request->integer('user_id', 0);
        if ($userId > 0) {
            $query->where('affiliate.user_id', '=', $userId);

            return;
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->whereRaw(
                'TRIM(CONCAT(COALESCE(affiliate.user_first_name, ""), " ", COALESCE(affiliate.user_last_name, ""))) LIKE ?',
                ['%'.$keyword.'%'],
            );
        }
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row, string $currency): array
    {
        $signupRevenue = (float) ($row['afstat_signup_revenue'] ?? 0);
        $orderRevenue = (float) ($row['afstat_order_revenue'] ?? 0);

        return [
            'id' => (int) $row['user_id'],
            'user_id' => (int) $row['user_id'],
            'affiliate_name' => (string) $row['affiliate_name'],
            'afstat_referees' => (int) ($row['afstat_referees'] ?? 0),
            'afstat_referee_sessions' => (int) ($row['afstat_referee_sessions'] ?? 0),
            'afstat_signup_revenue' => AdminOrderHelper::formatMoney($signupRevenue, $currency),
            'afstat_order_revenue' => AdminOrderHelper::formatMoney($orderRevenue, $currency),
            'total_revenue' => AdminOrderHelper::formatMoney($signupRevenue + $orderRevenue, $currency),
        ];
    }

    private function currencySymbol(): string
    {
        $currencyId = (int) Configuration::getValue('CONF_CURRENCY', 1);
        $symbol = DB::table('tbl_currencies')
            ->where('currency_id', $currencyId)
            ->value('currency_symbol');

        return is_string($symbol) ? $symbol : '';
    }
}
