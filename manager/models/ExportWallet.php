<?php

class ExportWallet extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::WALLET;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'order_id' => Label::getLabel('LBL_Order_ID'),
            'user_full_name' => Label::getLabel('LBL_user_name'),
            'order_total_amount' => Label::getLabel('LBL_Total').'['.$currencySymbol.']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
        ];
        return [
            'CONCAT(user_first_name, " ", user_last_name) AS user_full_name', 'order_id',
            'order_total_amount', 'order_pmethod_id', 'order_payment_status', 'order_addedon'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'order_id' => Order::formatOrderId($row['order_id']),
                'user_full_name' => $row['user_full_name'],
                'order_total_amount' => MyUtility::formatMoney($row['order_total_amount'],false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
            ]);
            $count++;
        }
        return $count;
    }

}
