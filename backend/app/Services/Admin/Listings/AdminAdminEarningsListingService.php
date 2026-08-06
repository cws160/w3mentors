<?php

namespace App\Services\Admin\Listings;

use App\Models\Configuration;
use App\Services\Admin\AdminOrderHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAdminEarningsListingService
{
    use AdminListingSupport;

    public const TYPE_LESSON = 1;

    public const TYPE_GCLASS = 2;

    public const TYPE_COURSE = 3;

    public const TYPE_SUBPLAN = 4;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $currency = $this->currencySymbol();

        $query = DB::table('tbl_admin_transactions as admtxn')
            ->select([
                'admtxn.admtxn_id as id',
                'admtxn.admtxn_id',
                'admtxn.admtxn_amount',
                'admtxn.admtxn_record_id',
                'admtxn.admtxn_record_type',
                'admtxn.admtxn_comment',
                'admtxn.admtxn_datetime',
            ]);

        $recordType = $request->query('admtxn_record_type', '');
        if ($recordType !== '' && $recordType !== null) {
            $query->where('admtxn.admtxn_record_type', '=', (int) $recordType);
        }

        $from = trim((string) $request->query('admtxn_date_from', ''));
        if ($from !== '') {
            $query->where('admtxn.admtxn_datetime', '>=', "{$from} 00:00:00");
        }

        $to = trim((string) $request->query('admtxn_date_to', ''));
        if ($to !== '') {
            $query->where('admtxn.admtxn_datetime', '<=', "{$to} 23:59:59");
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('admtxn.admtxn_datetime')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row, $currency))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    /** @return array<int, string> */
    public static function typeLabelKeys(): array
    {
        return [
            self::TYPE_LESSON => 'ADMTXN_LESSON',
            self::TYPE_GCLASS => 'ADMTXN_GROUP_CLASS',
            self::TYPE_COURSE => 'ADMTXN_COURSE',
            self::TYPE_SUBPLAN => 'ADMTXN_SUBSCRIPTION_PLAN',
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row, string $currency): array
    {
        $type = (int) ($row['admtxn_record_type'] ?? 0);
        $datetime = (string) ($row['admtxn_datetime'] ?? '');
        $formattedDatetime = $datetime !== ''
            ? Carbon::parse($datetime)->format('M d, Y h:i A')
            : '';

        return [
            'id' => (int) $row['admtxn_id'],
            'admtxn_id' => (int) $row['admtxn_id'],
            'admtxn_amount' => AdminOrderHelper::formatMoney((float) ($row['admtxn_amount'] ?? 0), $currency),
            'admtxn_record_id' => (int) $row['admtxn_record_id'],
            'admtxn_record_type' => $type,
            'admtxn_record_type_label_key' => self::typeLabelKeys()[$type] ?? '',
            'admtxn_comment' => (string) ($row['admtxn_comment'] ?? ''),
            'admtxn_datetime' => $formattedDatetime,
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
