<?php

class ExportWalletBalanceReport extends Export
{
    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::WALLET_BALANCE;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $headers = [
            'user_name' => Label::getLabel('LBL_USER_NAME'),
            'user_type' => Label::getLabel('LBL_USER_TYPE'),
            'user_wallet_balance' => Label::getLabel('LBL_REMAINING_BALANCE') . '[' . $currencySymbol . ']',
        ];
        $fields = [
            'CONCAT(user.user_first_name, " ", user.user_last_name) as user_name', 'user_is_affiliate', 'user_is_teacher', 'user_registered_as',
            'uset.user_wallet_balance'
        ];
        $this->headers = $headers;
        return $fields;
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $userTypeArray = User::getUserTypes();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            if ($row['user_is_affiliate']) {
                $row['user_type'] = $userTypeArray[User::AFFILIATE];
            } else {
                $row['user_type'] = $userTypeArray[User::LEARNER];
                if ($row['user_is_teacher']) {
                    $row['user_type'] .=  ' | ' .$userTypeArray[User::TEACHER];
                }
            }
            fputcsv($fh, [
                'user_name' => $row['user_name'],
                'user_type' => $row['user_type'],
                'user_wallet_balance' => MyUtility::formatMoney($row['user_wallet_balance'], false),
            ]);
            $count++;
        }
        return $count;
    }
}