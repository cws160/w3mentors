<?php

namespace App\Services\Admin\Listings;

use App\Models\Configuration;
use App\Services\Admin\AdminOrderHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSettlementsListingService
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
                    '(IFNULL(slstat_les_refund,0) + IFNULL(slstat_cls_refund,0) + IFNULL(slstat_crs_refund,0) + IFNULL(slstat_subplan_refund,0)) AS slstat_refund'
                ),
                DB::raw(
                    '(IFNULL(slstat_les_earnings,0) + IFNULL(slstat_cls_earnings,0) + IFNULL(slstat_crs_earnings,0)) AS slstat_earnings'
                ),
                DB::raw(
                    '(IFNULL(slstat_les_teacher_paid,0) + IFNULL(slstat_cls_teacher_paid,0) + IFNULL(slstat_crs_teacher_paid,0)) AS slstat_teacher_paid'
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
            'slstat_refund' => AdminOrderHelper::formatMoney((float) ($row['slstat_refund'] ?? 0), $currency),
            'slstat_earnings' => AdminOrderHelper::formatMoney((float) ($row['slstat_earnings'] ?? 0), $currency),
            'slstat_teacher_paid' => AdminOrderHelper::formatMoney((float) ($row['slstat_teacher_paid'] ?? 0), $currency),
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
