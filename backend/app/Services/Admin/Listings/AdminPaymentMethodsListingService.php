<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentMethodsListingService
{
    private const TYPE_PAYIN = 1;

    private const TYPE_PAYOUT = 2;

    private const WALLET_PAY_CODE = 'WalletPay';

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $keyword = trim((string) $request->query('keyword', ''));

        $query = DB::table('tbl_payment_methods')
            ->orderByDesc('pmethod_active')
            ->orderBy('pmethod_order')
            ->orderBy('pmethod_id')
            ->select([
                'pmethod_id as id',
                'pmethod_code as code',
                'pmethod_type as type',
                'pmethod_active as active',
                'pmethod_settings',
                'pmethod_fees',
            ]);

        if ($keyword !== '') {
            $query->where('pmethod_code', 'like', "%{$keyword}%");
        }

        $rows = $query->get()->map(function ($row) {
            $settings = $row->pmethod_settings;
            $fees = $row->pmethod_fees;

            return [
                'id' => (int) $row->id,
                'code' => (string) ($row->code ?? ''),
                'type' => (int) ($row->type ?? 0),
                'type_label' => $this->typeLabel((int) ($row->type ?? 0)),
                'active' => (int) ($row->active ?? 0),
                'is_wallet' => (string) $row->code === self::WALLET_PAY_CODE,
                'has_settings' => $settings !== null && $settings !== '',
                'has_fees' => $fees !== null && $fees !== '',
                'can_toggle_status' => (string) $row->code !== self::WALLET_PAY_CODE,
            ];
        })->all();

        $total = count($rows);

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => $total > 0 ? $total : 1,
                'total' => $total,
                'last_page' => 1,
            ],
        ];
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_PAYIN => 'Payin',
            self::TYPE_PAYOUT => 'Payout',
            default => '',
        };
    }
}
