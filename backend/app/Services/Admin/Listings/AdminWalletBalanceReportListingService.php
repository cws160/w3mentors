<?php

namespace App\Services\Admin\Listings;

use App\Models\Configuration;
use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWalletBalanceReportListingService
{
    use AdminListingSupport;

    private const USER_LEARNER = 1;

    private const USER_TEACHER = 2;

    private const USER_AFFILIATE = 5;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $currency = $this->currencySymbol();

        $query = DB::table('tbl_users as user')
            ->join('tbl_user_settings as uset', 'uset.user_id', '=', 'user.user_id')
            ->whereNull('user.user_deleted')
            ->select([
                'user.user_id as id',
                'user.user_id as user_id',
                DB::raw('TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) as user_name'),
                'uset.user_wallet_balance',
                'user.user_is_teacher',
                'user.user_is_affiliate',
                'uset.user_registered_as',
            ]);

        $this->applyFilters($request, $query);

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('uset.user_wallet_balance')
            ->orderBy('user.user_first_name')
            ->orderBy('user.user_id')
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
            $query->where('user.user_id', '=', $userId);

            return;
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->whereRaw(
                'TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) LIKE ?',
                ['%'.$keyword.'%'],
            );
        }

        $userType = $request->query('user_type', '');
        if ($userType === '' || $userType === null) {
            return;
        }

        switch ((int) $userType) {
            case self::USER_LEARNER:
                $query->where(function (Builder $q) {
                    $q->whereNull('user.user_is_affiliate')
                        ->orWhere('user.user_is_affiliate', '=', 0);
                });
                break;
            case self::USER_TEACHER:
                $query->where('user.user_is_teacher', '=', 1);
                break;
            case self::USER_AFFILIATE:
                $query->where('user.user_is_affiliate', '=', 1);
                break;
        }
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row, string $currency): array
    {
        return [
            'id' => (int) $row['user_id'],
            'user_id' => (int) $row['user_id'],
            'user_name' => (string) $row['user_name'],
            'user_wallet_balance' => AdminOrderHelper::formatMoney((float) ($row['user_wallet_balance'] ?? 0), $currency),
            'user_is_teacher' => (int) ($row['user_is_teacher'] ?? 0),
            'user_is_affiliate' => (int) ($row['user_is_affiliate'] ?? 0),
            'user_registered_as' => $row['user_registered_as'] !== null ? (int) $row['user_registered_as'] : null,
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
