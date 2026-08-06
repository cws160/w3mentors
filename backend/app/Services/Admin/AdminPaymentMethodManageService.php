<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminPaymentMethodManageService
{
    private const TYPE_PAYOUT = 2;

    private const WALLET_PAY_CODE = 'WalletPay';

    private const PERCENTAGE = 1;

    private const FLAT_VALUE = 2;

    /** @return array<string, mixed> */
    public function settingForm(int $methodId): array
    {
        $row = $this->findOrFail($methodId);
        $settings = $this->decodeSettings((string) ($row->pmethod_settings ?? ''));

        return [
            'pmethod_id' => (int) $row->pmethod_id,
            'pmethod_code' => (string) $row->pmethod_code,
            'pmethod_info' => (string) ($row->pmethod_info ?? ''),
            'fields' => array_map(function (array $field) {
                return [
                    'key' => (string) ($field['key'] ?? ''),
                    'type' => (string) ($field['type'] ?? 'text'),
                    'value' => $field['value'] ?? '',
                    'label_key' => 'PGL_'.($field['key'] ?? ''),
                ];
            }, $settings),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function settingSetup(array $payload): int
    {
        $methodId = (int) ($payload['pmethod_id'] ?? 0);
        $row = $this->findOrFail($methodId);

        $incomingSettings = $payload['pmethod_settings'] ?? [];
        $incomingTypes = $payload['pmethod_type'] ?? [];
        if (! is_array($incomingSettings) || $incomingSettings === []) {
            throw new \InvalidArgumentException('Nothing to save');
        }

        $settings = [];
        foreach ($incomingSettings as $key => $value) {
            $type = (string) ($incomingTypes[$key] ?? 'text');
            if ($type === 'checkbox') {
                $value = (int) (bool) $value;
            }
            $settings[] = [
                'key' => (string) $key,
                'value' => $value,
                'type' => $type,
            ];
        }

        DB::table('tbl_payment_methods')->where('pmethod_id', $methodId)->update([
            'pmethod_settings' => json_encode($settings),
        ]);

        return $methodId;
    }

    /** @return array<string, mixed> */
    public function txnfeeForm(int $methodId): array
    {
        $row = $this->findOrFail($methodId);
        if ((int) $row->pmethod_type !== self::TYPE_PAYOUT) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $fees = json_decode((string) ($row->pmethod_fees ?? ''), true);
        if (! is_array($fees)) {
            $fees = ['type' => self::PERCENTAGE, 'fee' => 0];
        }

        return [
            'pmethod_id' => (int) $row->pmethod_id,
            'pmethod_code' => (string) $row->pmethod_code,
            'type' => (int) ($fees['type'] ?? self::PERCENTAGE),
            'fee' => (float) ($fees['fee'] ?? 0),
            'fee_type_options' => [
                ['value' => self::FLAT_VALUE, 'label' => 'Flat value'],
                ['value' => self::PERCENTAGE, 'label' => 'Percentage'],
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    public function txnfeeSetup(array $payload): int
    {
        $methodId = (int) ($payload['pmethod_id'] ?? 0);
        $row = $this->findOrFail($methodId);
        if ((int) $row->pmethod_type !== self::TYPE_PAYOUT) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $type = (int) ($payload['type'] ?? 0);
        $fee = (float) ($payload['fee'] ?? 0);
        if ($type !== self::PERCENTAGE && $type !== self::FLAT_VALUE) {
            throw new \InvalidArgumentException('Invalid fee type');
        }
        if ($fee < 0) {
            throw new \InvalidArgumentException('Invalid fee value');
        }
        if ($type === self::PERCENTAGE && $fee > 100) {
            throw new \InvalidArgumentException('Invalid fee value');
        }

        DB::table('tbl_payment_methods')->where('pmethod_id', $methodId)->update([
            'pmethod_fees' => json_encode(['type' => $type, 'fee' => $fee]),
        ]);

        return $methodId;
    }

    public function changeStatus(int $methodId, int $status): void
    {
        $row = $this->findOrFail($methodId);
        if ((string) $row->pmethod_code === self::WALLET_PAY_CODE) {
            throw new \InvalidArgumentException('Invalid request');
        }

        DB::table('tbl_payment_methods')->where('pmethod_id', $methodId)->update([
            'pmethod_active' => $status === 1 ? 1 : 0,
        ]);
    }

    /** @param array<int, int> $ids */
    public function updateOrder(array $ids): void
    {
        $order = array_values(array_filter(array_map('intval', $ids)));
        if ($order === []) {
            throw new \InvalidArgumentException('Invalid request');
        }

        foreach ($order as $index => $id) {
            if ($id < 1) {
                continue;
            }
            DB::table('tbl_payment_methods')->where('pmethod_id', $id)->update([
                'pmethod_order' => $index + 1,
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function decodeSettings(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function findOrFail(int $methodId): object
    {
        if ($methodId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $row = DB::table('tbl_payment_methods')->where('pmethod_id', $methodId)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request');
        }

        return $row;
    }
}
