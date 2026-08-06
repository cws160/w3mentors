<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCurrencyListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int|mixed>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_currencies as curr')
            ->leftJoin('tbl_currencies_lang as cl', function ($join) use ($langId) {
                $join->on('cl.currencylang_currency_id', '=', 'curr.currency_id')
                    ->where('cl.currencylang_lang_id', '=', $langId);
            })
            ->orderByDesc('curr.currency_active')
            ->orderBy('curr.currency_order')
            ->select([
                'curr.currency_id as id',
                'curr.currency_code',
                'curr.currency_symbol',
                'curr.currency_active',
                'curr.currency_is_default',
                'curr.currency_order',
                DB::raw('IFNULL(cl.currency_name, curr.currency_code) as currency_name'),
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('curr.currency_code', 'like', "%{$keyword}%")
                    ->orWhere('cl.currency_name', 'like', "%{$keyword}%")
                    ->orWhere('curr.currency_symbol', 'like', "%{$keyword}%");
            });
        }

        $rows = $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'currency_name' => (string) ($row->currency_name ?? ''),
            'currency_code' => (string) ($row->currency_code ?? ''),
            'currency_symbol' => (string) ($row->currency_symbol ?? ''),
            'currency_active' => (int) ($row->currency_active ?? 0),
            'currency_is_default' => (int) ($row->currency_is_default ?? 0),
            'currency_order' => (int) ($row->currency_order ?? 0),
        ])->all();

        $fixer = $this->fixerConfig();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => count($rows) ?: 1,
                'total' => count($rows),
                'last_page' => 1,
                'fixer_status' => (int) ($fixer['status'] ?? 0),
                'fixer_last_synced' => (string) ($fixer['last_synced'] ?? ''),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function fixerConfig(): array
    {
        $raw = DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_FIXER')
            ->value('conf_val');

        $config = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];

        return is_array($config) ? $config : [];
    }
}
