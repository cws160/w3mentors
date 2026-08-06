<?php

class ExportSubscriptions extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::SUBSCRIPTIONS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'ordsub_id' => Label::getLabel('LBL_RECURRING_LESSON_ID'),
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'ordsub_startdate' => Label::getLabel('LBL_START_DATE'),
            'ordsub_enddate' => Label::getLabel('LBL_END_DATE'),
            'learner_name' => Label::getLabel('LBL_LEARNER'),
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'service_type' => Label::getLabel('LBL_SERVICE_TYPE'),
            'order_net_amount' => Label::getLabel('LBL_NET_TOTAL') . '[' . $currencySymbol . ']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
            'ordsub_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) AS learner_name',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) AS teacher_name',
            'ordsub_id', 'ordsub_startdate', 'ordsub_enddate', 'order_discount_value',
            'order_id', 'order_total_amount', 'order_net_amount', 'ordsub_status',
            'order_reward_value', 'order_payment_status', 'order_addedon', 'ordsub_offline'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        $serviceTypes = AppConstant::getServiceType();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $status = Subscription::getStatuses($row['ordsub_status']);
            if ($row['ordsub_status'] == Subscription::ACTIVE && strtotime($row['ordsub_enddate']) < strtotime(MyDate::formatDate(date('Y-m-d H:i:s')))) {
                $status = Label::getLabel('LBL_EXPIRED');
            }
            fputcsv($fh, [
                'ordsub_id' => $row['ordsub_id'],
                'order_id' => Order::formatOrderId($row['order_id']),
                'ordsub_startdate' => MyDate::formatDate($row['ordsub_startdate'], 'Y-m-d'),
                'ordsub_enddate' => MyDate::formatDate($row['ordsub_enddate'], 'Y-m-d'),
                'learner_name' => $row['learner_name'],
                'teacher_name' => $row['teacher_name'],
                'service_type' => $serviceTypes[$row['ordsub_offline']],
                'order_net_amount' => MyUtility::formatMoney($row['order_net_amount'], false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
                'ordsub_status' => $status,
            ]);
            $count++;
        }
        return $count;
    }
}
