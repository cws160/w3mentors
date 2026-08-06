<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class UserPaymentService
{
    public const TYPE_PAYOUT = 2;

    public const BANK_PAYOUT = 'BankPayout';

    public const PAYPAL_PAYOUT = 'PaypalPayout';

    /**
     * @return array<string, array{code: string, id: int}>
     */
    public function activePayoutMethods(): array
    {
        $rows = DB::table('tbl_payment_methods')
            ->where('pmethod_active', 1)
            ->where('pmethod_type', self::TYPE_PAYOUT)
            ->get(['pmethod_id', 'pmethod_code']);

        $methods = [];
        foreach ($rows as $row) {
            $code = (string) $row->pmethod_code;
            $methods[$code] = [
                'code' => $code,
                'id' => (int) $row->pmethod_id,
            ];
        }

        return $methods;
    }

    /**
     * @return array<string, mixed>
     */
    public function index(int $userId): array
    {
        $methods = $this->activePayoutMethods();
        $bank = $this->getBankDetails($userId);
        $defaultTab = isset($methods[self::BANK_PAYOUT]) ? 'bank' : 'paypal';

        return [
            'payout_methods' => array_values($methods),
            'has_bank' => isset($methods[self::BANK_PAYOUT]),
            'has_paypal' => isset($methods[self::PAYPAL_PAYOUT]),
            'default_tab' => $defaultTab,
            'bank' => $bank,
            'paypal_email' => (string) ($bank['paypal_email'] ?? ''),
        ];
    }

    /**
     * @param  array<string, string>  $data
     */
    public function saveBank(int $userId, array $data): void
    {
        if (! isset($this->activePayoutMethods()[self::BANK_PAYOUT])) {
            throw new \InvalidArgumentException('Bank payout is not enabled.');
        }

        $payload = [
            'ub_user_id' => $userId,
            'ub_bank_name' => $data['bank_name'],
            'ub_account_holder_name' => $data['account_holder_name'],
            'ub_account_number' => $data['account_number'],
            'ub_ifsc_swift_code' => $data['ifsc_swift_code'],
            'ub_bank_address' => $data['bank_address'] ?? '',
        ];

        DB::table('tbl_user_bank_details')->updateOrInsert(
            ['ub_user_id' => $userId],
            $payload
        );
    }

    public function savePaypal(int $userId, string $email): void
    {
        if (! isset($this->activePayoutMethods()[self::PAYPAL_PAYOUT])) {
            throw new \InvalidArgumentException('PayPal payout is not enabled.');
        }

        DB::table('tbl_user_bank_details')->updateOrInsert(
            ['ub_user_id' => $userId],
            [
                'ub_user_id' => $userId,
                'ub_paypal_email_address' => $email,
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    private function getBankDetails(int $userId): array
    {
        $row = DB::table('tbl_user_bank_details')
            ->where('ub_user_id', $userId)
            ->first([
                'ub_bank_name',
                'ub_account_holder_name',
                'ub_account_number',
                'ub_ifsc_swift_code',
                'ub_bank_address',
                'ub_paypal_email_address',
            ]);

        if (! $row) {
            return [
                'bank_name' => '',
                'account_holder_name' => '',
                'account_number' => '',
                'ifsc_swift_code' => '',
                'bank_address' => '',
                'paypal_email' => '',
            ];
        }

        return [
            'bank_name' => (string) $row->ub_bank_name,
            'account_holder_name' => (string) $row->ub_account_holder_name,
            'account_number' => (string) $row->ub_account_number,
            'ifsc_swift_code' => (string) $row->ub_ifsc_swift_code,
            'bank_address' => (string) $row->ub_bank_address,
            'paypal_email' => (string) $row->ub_paypal_email_address,
        ];
    }
}
