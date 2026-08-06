<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminCouponsManageService
{
    private const ACTIVE = 1;

    private const PERCENTAGE = 1;

    private const FLAT_VALUE = 2;

    /** @return array<string, mixed> */
    public function form(int $couponId): array
    {
        if ($couponId < 1) {
            return [
                'coupon' => [
                    'coupon_id' => 0,
                    'coupon_identifier' => '',
                    'coupon_code' => '',
                    'coupon_discount_type' => self::FLAT_VALUE,
                    'coupon_discount_value' => '',
                    'coupon_max_discount' => '',
                    'coupon_min_order' => '',
                    'coupon_max_uses' => 1,
                    'coupon_user_uses' => 1,
                    'coupon_start_date' => '',
                    'coupon_end_date' => '',
                    'coupon_active' => self::ACTIVE,
                ],
                'is_expired' => false,
                'site_languages' => $this->siteLanguages(),
                'options' => $this->formOptions(),
            ];
        }

        $row = DB::table('tbl_coupons')->where('coupon_id', $couponId)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Coupon not found');
        }

        $now = now()->format('Y-m-d H:i:s');
        $isExpired = (string) $row->coupon_end_date !== '' && (string) $row->coupon_end_date < $now;

        return [
            'coupon' => [
                'coupon_id' => (int) $row->coupon_id,
                'coupon_identifier' => (string) $row->coupon_identifier,
                'coupon_code' => (string) $row->coupon_code,
                'coupon_discount_type' => (int) $row->coupon_discount_type,
                'coupon_discount_value' => (float) $row->coupon_discount_value,
                'coupon_max_discount' => $row->coupon_max_discount !== null ? (float) $row->coupon_max_discount : '',
                'coupon_min_order' => $row->coupon_min_order !== null ? (float) $row->coupon_min_order : '',
                'coupon_max_uses' => (int) $row->coupon_max_uses,
                'coupon_user_uses' => (int) $row->coupon_user_uses,
                'coupon_start_date' => $this->toDatetimeLocal((string) $row->coupon_start_date),
                'coupon_end_date' => $this->toDatetimeLocal((string) $row->coupon_end_date),
                'coupon_active' => (int) $row->coupon_active,
            ],
            'is_expired' => $isExpired,
            'site_languages' => $this->siteLanguages(),
            'options' => $this->formOptions(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $couponId, int $langId): ?array
    {
        if ($couponId < 1 || $langId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        if (! DB::table('tbl_coupons')->where('coupon_id', $couponId)->exists()) {
            return null;
        }

        $row = DB::table('tbl_coupons_lang')
            ->where('couponlang_coupon_id', $couponId)
            ->where('couponlang_lang_id', $langId)
            ->first(['coupon_title', 'coupon_description']);

        return [
            'coupon_id' => $couponId,
            'lang_id' => $langId,
            'coupon_title' => (string) ($row->coupon_title ?? ''),
            'coupon_description' => (string) ($row->coupon_description ?? ''),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function setup(array $payload): int
    {
        $couponId = (int) ($payload['coupon_id'] ?? 0);
        $existing = $couponId > 0
            ? DB::table('tbl_coupons')->where('coupon_id', $couponId)->first()
            : null;

        if ($couponId > 0 && ! $existing) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $identifier = trim((string) ($payload['coupon_identifier'] ?? ''));
        $code = trim((string) ($payload['coupon_code'] ?? ''));
        $discountType = (int) ($payload['coupon_discount_type'] ?? self::FLAT_VALUE);
        $discountValue = (float) ($payload['coupon_discount_value'] ?? 0);
        $maxDiscount = $payload['coupon_max_discount'] !== '' && $payload['coupon_max_discount'] !== null
            ? (float) $payload['coupon_max_discount']
            : null;
        $minOrder = $payload['coupon_min_order'] !== '' && $payload['coupon_min_order'] !== null
            ? (float) $payload['coupon_min_order']
            : null;
        $maxUses = (int) ($payload['coupon_max_uses'] ?? 1);
        $userUses = (int) ($payload['coupon_user_uses'] ?? 1);
        $active = (int) ($payload['coupon_active'] ?? self::ACTIVE);
        $startDate = $this->fromDatetimeLocal((string) ($payload['coupon_start_date'] ?? ''));
        $endDate = $this->fromDatetimeLocal((string) ($payload['coupon_end_date'] ?? ''));

        if ($identifier === '' || $code === '') {
            throw new \InvalidArgumentException('Identifier and code are required');
        }

        if (! in_array($discountType, [self::PERCENTAGE, self::FLAT_VALUE], true)) {
            throw new \InvalidArgumentException('Invalid discount type');
        }

        if ($discountType === self::PERCENTAGE) {
            if ($discountValue < 1 || $discountValue > 100) {
                throw new \InvalidArgumentException('Discount value must be between 1 and 100');
            }
            if ($maxDiscount === null || $maxDiscount < 1) {
                throw new \InvalidArgumentException('Max discount is required for percentage coupons');
            }
        } elseif ($discountValue < 1) {
            throw new \InvalidArgumentException('Discount value must be greater than 0');
        }

        if ($maxUses < 1 || $maxUses > 9999) {
            throw new \InvalidArgumentException('Max uses must be between 1 and 9999');
        }

        if ($userUses < 1 || $userUses > 9999 || $userUses > $maxUses) {
            throw new \InvalidArgumentException('Uses per user must be between 1 and max uses');
        }

        if ($startDate === '' || $endDate === '') {
            throw new \InvalidArgumentException('Start and end dates are required');
        }

        if ($endDate <= $startDate) {
            throw new \InvalidArgumentException('End date must be after start date');
        }

        $duplicate = DB::table('tbl_coupons')
            ->where('coupon_code', $code)
            ->when($couponId > 0, fn ($q) => $q->where('coupon_id', '!=', $couponId))
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Coupon code already exists');
        }

        $now = now()->format('Y-m-d H:i:s');
        if ($existing && (string) $existing->coupon_end_date < $now && $active === self::ACTIVE) {
            throw new \InvalidArgumentException('An expired coupon cannot be re-activated');
        }

        $values = [
            'coupon_identifier' => $identifier,
            'coupon_code' => $code,
            'coupon_discount_type' => $discountType,
            'coupon_discount_value' => $discountValue,
            'coupon_max_discount' => $discountType === self::PERCENTAGE ? $maxDiscount : ($maxDiscount ?? 0),
            'coupon_min_order' => $minOrder ?? 0,
            'coupon_max_uses' => $maxUses,
            'coupon_user_uses' => $userUses,
            'coupon_start_date' => $startDate,
            'coupon_end_date' => $endDate,
            'coupon_active' => $active,
            'coupon_updated' => $now,
        ];

        if ($couponId > 0) {
            DB::table('tbl_coupons')->where('coupon_id', $couponId)->update($values);
        } else {
            $values['coupon_used_uses'] = 0;
            $values['coupon_created'] = $now;
            $couponId = (int) DB::table('tbl_coupons')->insertGetId($values);
        }

        return $couponId;
    }

    /** @param array<string, mixed> $payload */
    public function langSetup(array $payload): int
    {
        $couponId = (int) ($payload['couponlang_coupon_id'] ?? $payload['coupon_id'] ?? 0);
        $langId = (int) ($payload['couponlang_lang_id'] ?? $payload['lang_id'] ?? 0);
        $title = trim((string) ($payload['coupon_title'] ?? ''));
        $description = trim((string) ($payload['coupon_description'] ?? ''));

        if ($couponId < 1 || $langId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        if (! DB::table('tbl_coupons')->where('coupon_id', $couponId)->exists()) {
            throw new \InvalidArgumentException('Coupon not found');
        }

        if ($title === '') {
            throw new \InvalidArgumentException('Coupon title is required');
        }

        if (strlen($description) > 250) {
            throw new \InvalidArgumentException('Description must be 250 characters or less');
        }

        $exists = DB::table('tbl_coupons_lang')
            ->where('couponlang_coupon_id', $couponId)
            ->where('couponlang_lang_id', $langId)
            ->exists();

        $data = [
            'couponlang_coupon_id' => $couponId,
            'couponlang_lang_id' => $langId,
            'coupon_title' => $title,
            'coupon_description' => $description,
        ];

        if ($exists) {
            DB::table('tbl_coupons_lang')
                ->where('couponlang_coupon_id', $couponId)
                ->where('couponlang_lang_id', $langId)
                ->update([
                    'coupon_title' => $title,
                    'coupon_description' => $description,
                ]);
        } else {
            DB::table('tbl_coupons_lang')->insert($data);
        }

        return $couponId;
    }

    public function delete(int $couponId): void
    {
        if ($couponId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        if (! DB::table('tbl_coupons')->where('coupon_id', $couponId)->exists()) {
            throw new \InvalidArgumentException('Invalid request');
        }

        DB::table('tbl_coupons_lang')->where('couponlang_coupon_id', $couponId)->delete();
        DB::table('tbl_coupons')->where('coupon_id', $couponId)->delete();
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function uses(int $couponId, int $page): array
    {
        if ($couponId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $perPage = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ADMIN_PAGESIZE')
            ->value('conf_val');
        $perPage = $perPage > 0 ? $perPage : 10;
        $page = max(1, $page);
        $currencySymbol = $this->defaultCurrencySymbol();

        $query = DB::table('tbl_coupons_history as couhis')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'couhis.couhis_order_id')
            ->join('tbl_users as users', 'users.user_id', '=', 'orders.order_user_id')
            ->where('couhis.couhis_coupon_id', $couponId)
            ->orderByDesc('couhis.couhis_id')
            ->select([
                'orders.order_id',
                'orders.order_total_amount',
                'orders.order_addedon',
                'couhis.couhis_released',
                'users.user_first_name',
                'users.user_last_name',
            ]);

        $total = (clone $query)->count('couhis.couhis_id');

        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) use ($currencySymbol) {
                return [
                    'order_id' => AdminOrderHelper::formatOrderId((int) $row->order_id),
                    'customer_name' => trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? '')),
                    'order_total_amount' => AdminOrderHelper::formatMoney((float) $row->order_total_amount, $currencySymbol),
                    'order_addedon' => $this->formatDisplayDate((string) $row->order_addedon),
                    'is_released' => $this->isCouponHistoryReleased($row->couhis_released ?? null),
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

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'discount_types' => [
                ['value' => self::PERCENTAGE, 'label' => 'Percentage'],
                ['value' => self::FLAT_VALUE, 'label' => 'Flat value'],
            ],
            'status_options' => [
                ['value' => 1, 'label' => 'Active'],
                ['value' => 0, 'label' => 'Inactive'],
            ],
        ];
    }

    /** @return array<int, array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', self::ACTIVE)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function defaultCurrencySymbol(): string
    {
        $symbol = DB::table('tbl_currencies')
            ->where('currency_is_default', 1)
            ->value('currency_symbol');

        return (string) ($symbol ?: '$');
    }

    private function toDatetimeLocal(string $value): string
    {
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
    }

    private function fromDatetimeLocal(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $normalized = str_replace('T', ' ', $value);
        if (strlen($normalized) === 16) {
            $normalized .= ':00';
        }

        $timestamp = strtotime($normalized);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    private function formatDisplayDate(string $value): string
    {
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('M j, Y H:i', $timestamp) : $value;
    }

    private function isCouponHistoryReleased(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $released = (string) $value;

        return ! str_starts_with($released, '0000-00-00');
    }
}
