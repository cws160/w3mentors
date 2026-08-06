<?php

namespace App\Services\Admin\Listings;

use App\Models\Configuration;
use App\Services\Admin\AdminOrderHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSalesReportListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $currency = $this->currencySymbol();
        $from = $this->dateFilter($request, 'slstat_date_from');
        $to = $this->dateFilter($request, 'slstat_date_to');

        $base = DB::table('tbl_sales_stats');
        if ($from !== null) {
            $base->where('slstat_date', '>=', $from);
        }
        if ($to !== null) {
            $base->where('slstat_date', '<=', $to);
        }

        $total = (clone $base)->distinct()->count('slstat_date');

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $rows = (clone $base)
            ->select([
                'slstat_date',
                DB::raw(
                    '(IFNULL(slstat_les_sales,0) + IFNULL(slstat_cls_sales,0) + IFNULL(slstat_crs_sales,0) + IFNULL(slstat_les_discount,0) '
                    .'+ IFNULL(slstat_cls_discount,0) + IFNULL(slstat_les_credit_discount,0) + IFNULL(slstat_cls_credit_discount,0) + IFNULL(slstat_crs_discount,0) + IFNULL(slstat_crs_credit_discount,0)  + IFNULL(slstat_subplan_sales,0)  + IFNULL(slstat_subplan_discount,0) + IFNULL(slstat_subplan_credit_discount,0)) AS slstat_total_sales'
                ),
                DB::raw(
                    '(IFNULL(slstat_les_sales,0) + IFNULL(slstat_cls_sales,0) + IFNULL(slstat_crs_sales,0) + IFNULL(slstat_subplan_sales,0)) AS slstat_net_sales'
                ),
                DB::raw(
                    '(IFNULL(slstat_les_discount,0) + IFNULL(slstat_cls_discount,0) + IFNULL(slstat_crs_discount,0) + IFNULL(slstat_subplan_discount,0)) AS slstat_discount'
                ),
                DB::raw(
                    '(IFNULL(slstat_les_credit_discount,0) + IFNULL(slstat_cls_credit_discount,0) + IFNULL(slstat_crs_credit_discount,0)  + IFNULL(slstat_subplan_credit_discount,0)) AS slstat_credit_discount'
                ),
            ])
            ->groupBy('slstat_date')
            ->orderByDesc('slstat_date')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row, $currency))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row, string $currency): array
    {
        $date = (string) ($row['slstat_date'] ?? '');
        $formattedDate = $date !== ''
            ? Carbon::parse($date)->format('M d, Y')
            : '';

        return [
            'id' => $date,
            'slstat_date' => $formattedDate,
            'slstat_total_sales' => AdminOrderHelper::formatMoney((float) ($row['slstat_total_sales'] ?? 0), $currency),
            'slstat_discount' => AdminOrderHelper::formatMoney((float) ($row['slstat_discount'] ?? 0), $currency),
            'slstat_credit_discount' => AdminOrderHelper::formatMoney((float) ($row['slstat_credit_discount'] ?? 0), $currency),
            'slstat_net_sales' => AdminOrderHelper::formatMoney((float) ($row['slstat_net_sales'] ?? 0), $currency),
        ];
    }

    private function dateFilter(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));
        if ($value === '') {
            return null;
        }

        return $value;
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
