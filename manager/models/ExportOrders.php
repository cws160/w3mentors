<?php

class ExportOrders extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::ORDERS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'user_full_name' => Label::getLabel('LBL_USER_NAME'),
            'order_type' => Label::getLabel('LBL_ORDER_TYPE'),
            'service_type' => Label::getLabel('LBL_SERVICE_TYPE'),
            'order_net_amount' => Label::getLabel('LBL_NET_TOTAL') . '[' . $currencySymbol . ']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_status' => Label::getLabel('LBL_STATUS'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
        ];
        return [
            'CONCAT(learner.user_first_name," ", learner.user_last_name) AS user_full_name', 'order_status',
            'orders.order_id', 'orders.order_type', 'orders.order_addedon', 'ordles_offline', 'grpcls_offline',
            'orders.order_net_amount', 'orders.order_payment_status'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        $rows = FatApp::getDb()->fetchAll($rs);
        $serviceTypes = OrderSearch::getServiceType($rows);
        foreach ($rows as $row) {
            fputcsv($fh, [
                'order_id' => Order::formatOrderId($row['order_id']),
                'user_full_name' => $row['user_full_name'],
                'order_type' => Order::getTypeArr($row['order_type']),
                'service_type' => $serviceTypes[$row['order_id']],
                'order_net_amount' => MyUtility::formatMoney($row['order_net_amount'], false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_status' => Order::getStatusArr($row['order_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
            ]);
            $count++;
        }
        return $count;
    }
}
