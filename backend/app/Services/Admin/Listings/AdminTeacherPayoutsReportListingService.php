<?php

namespace App\Services\Admin\Listings;

use App\Models\Configuration;
use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTeacherPayoutsReportListingService
{
    use AdminListingSupport;

    private const TYPE_TEACHER_PAYMENT = 9;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $currency = $this->currencySymbol();

        $baseQuery = DB::table('tbl_user_transactions as usrtxn')
            ->join('tbl_users as user', 'usrtxn.usrtxn_user_id', '=', 'user.user_id')
            ->where('usrtxn.usrtxn_type', '=', self::TYPE_TEACHER_PAYMENT);

        $this->applyFilters($request, $baseQuery);

        $total = (clone $baseQuery)->distinct()->count('usrtxn.usrtxn_user_id');

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $rows = (clone $baseQuery)
            ->select([
                'usrtxn.usrtxn_user_id as user_id',
                DB::raw('SUM(usrtxn.usrtxn_amount) as total_amount'),
                DB::raw('TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) as user_name'),
            ])
            ->groupBy('usrtxn.usrtxn_user_id', 'user.user_first_name', 'user.user_last_name')
            ->orderBy('user.user_first_name')
            ->orderBy('usrtxn.usrtxn_user_id')
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
            $query->where('usrtxn.usrtxn_user_id', '=', $userId);

            return;
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->whereRaw(
                'TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) LIKE ?',
                ['%'.$keyword.'%'],
            );
        }

        $fromDate = trim((string) $request->query('fromDate', $request->query('from_date', '')));
        if ($fromDate !== '') {
            $query->where('usrtxn.usrtxn_datetime', '>=', $fromDate.' 00:00:00');
        }

        $toDate = trim((string) $request->query('toDate', $request->query('to_date', '')));
        if ($toDate !== '') {
            $query->where('usrtxn.usrtxn_datetime', '<=', $toDate.' 23:59:59');
        }
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row, string $currency): array
    {
        return [
            'id' => (int) $row['user_id'],
            'user_id' => (int) $row['user_id'],
            'user_name' => (string) $row['user_name'],
            'total_amount' => AdminOrderHelper::formatMoney((float) ($row['total_amount'] ?? 0), $currency),
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
