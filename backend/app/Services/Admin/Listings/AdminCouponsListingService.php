<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCouponsListingService
{
    use AdminListingSupport;

    private const PERCENTAGE = 1;

    private const FLAT_VALUE = 2;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $timezone = (string) (DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_TIMEZONE')
            ->value('conf_val') ?: 'UTC');
        $now = now($timezone)->format('Y-m-d H:i:s');
        $currencySymbol = $this->defaultCurrencySymbol();

        $query = DB::table('tbl_coupons as coupon')
            ->leftJoin('tbl_coupons_lang as couponlang', function ($join) use ($langId) {
                $join->on('couponlang.couponlang_coupon_id', '=', 'coupon.coupon_id')
                    ->where('couponlang.couponlang_lang_id', '=', $langId);
            })
            ->orderByDesc('coupon.coupon_active')
            ->orderByDesc('coupon.coupon_id')
            ->select([
                'coupon.coupon_id as id',
                'coupon.coupon_code',
                'coupon.coupon_active',
                'coupon.coupon_discount_type',
                'coupon.coupon_discount_value',
                'coupon.coupon_start_date',
                'coupon.coupon_end_date',
                DB::raw('IFNULL(couponlang.coupon_title, coupon.coupon_identifier) as coupon_title'),
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('coupon.coupon_code', 'like', "%{$keyword}%")
                    ->orWhere('couponlang.coupon_title', 'like', "%{$keyword}%")
                    ->orWhere('coupon.coupon_identifier', 'like', "%{$keyword}%");
            });
        }

        $active = $request->query('coupon_active', '');
        if ($active !== '' && $active !== null) {
            $query->where('coupon.coupon_active', '=', (int) $active);
        }

        $expire = $request->query('coupon_expire', '');
        if ($expire === '1') {
            $query->where('coupon.coupon_end_date', '<', $now);
        } elseif ($expire === '0') {
            $query->where('coupon.coupon_end_date', '>=', $now);
        }

        $total = (clone $query)->count('coupon.coupon_id');

        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) use ($now, $currencySymbol) {
                $endDate = (string) ($row->coupon_end_date ?? '');
                $isExpired = $endDate !== ''
                    && ! str_starts_with($endDate, '0000-00-00')
                    && $now > $endDate;

                return [
                    'id' => (int) $row->id,
                    'coupon_title' => (string) ($row->coupon_title ?? ''),
                    'coupon_code' => (string) ($row->coupon_code ?? ''),
                    'coupon_discount' => $this->formatDiscount(
                        (int) $row->coupon_discount_type,
                        (float) $row->coupon_discount_value,
                        $currencySymbol,
                    ),
                    'available' => $this->formatDateRange(
                        (string) $row->coupon_start_date,
                        (string) $row->coupon_end_date,
                    ),
                    'coupon_active' => (int) ($row->coupon_active ?? 0),
                    'is_expired' => $isExpired,
                ];
            })
            ->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }

    private function formatDiscount(int $type, float $value, string $currencySymbol): string
    {
        if ($type === self::PERCENTAGE) {
            $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

            return $formatted.'%';
        }

        if ($type === self::FLAT_VALUE) {
            return AdminOrderHelper::formatMoney($value, $currencySymbol);
        }

        return '0';
    }

    private function formatDateRange(string $start, string $end): string
    {
        $startLabel = $this->formatDateTime($start);
        $endLabel = $this->formatDateTime($end);

        if ($startLabel === '' && $endLabel === '') {
            return '';
        }

        return trim($startLabel.' - '.$endLabel, ' -');
    }

    private function formatDateTime(string $value): string
    {
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('M j, Y H:i', $timestamp) : $value;
    }

    private function defaultCurrencySymbol(): string
    {
        $symbol = DB::table('tbl_currencies')
            ->where('currency_is_default', 1)
            ->value('currency_symbol');

        return (string) ($symbol ?: '$');
    }
}
