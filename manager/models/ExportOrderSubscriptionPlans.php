<?php

class ExportOrderSubscriptionPlans extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::ORDER_SUBSCRIPTION_PLANS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'ordsplan_id' => Label::getLabel('LBL_SUBSCRIPTION_ID'),
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'plan_name' => Label::getLabel('LBL_PLAN_NAME'),
            'ordsplan_start_date' => Label::getLabel('LBL_START_DATE'),
            'ordsplan_end_date' => Label::getLabel('LBL_END_DATE'),
            'learner_name' => Label::getLabel('LBL_LEARNER'),
            'order_net_amount' => Label::getLabel('LBL_NET_TOTAL') . '[' . $currencySymbol . ']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
            'ordsplan_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) AS learner_name',
            'order_net_amount',
            'IFNULL(subplang.subplang_subplan_title, sp.subplan_title) AS plan_name',
            'ordsplan_amount', 'ordsplan_discount', 'ordsplan_reward_discount', 'order_id',
            'ordsplan_id', 'order_payment_status', 'ordsplan_status', 'order_addedon', 'ordsplan.ordsplan_start_date',
            'ordsplan.ordsplan_end_date'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'ordsplan_id' => $row['ordsplan_id'],
                'order_id' => Order::formatOrderId($row['order_id']),
                'plan_name' => $row['plan_name'],
                'ordsplan_start_date' => MyDate::formatDate($row['ordsplan_start_date'], 'Y-m-d'),
                'ordsplan_end_date' => MyDate::formatDate($row['ordsplan_end_date'], 'Y-m-d'),
                'learner_name' => $row['learner_name'],
                'order_net_amount' => MyUtility::formatMoney($row['order_net_amount'], false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
                'ordsplan_status' => OrderSubscriptionPlan::getStatuses($row['ordsplan_status']),
            ]);
            $count++;
        }
        return $count;
    }
}
