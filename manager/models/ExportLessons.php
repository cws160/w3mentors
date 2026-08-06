<?php

class ExportLessons extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::LESSONS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'ordles_id' => Label::getLabel('LBL_LESSON_ID'),
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'learner_name' => Label::getLabel('LBL_LEARNER'),
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'ordles_tlang_id' => Label::getLabel('LBL_LANGUAGE'),
            'service_type' => Label::getLabel('LBL_SERVICE_TYPE'),
            'ordles_net_amount' => Label::getLabel('LBL_NET_TOTAL') . '[' . $currencySymbol . ']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
            'ordles_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) AS learner_name',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) AS teacher_name',
            '(ordles_amount - IFNULL(ordles_discount, 0) - IFNULL(ordles_reward_discount, 0)) AS ordles_net_amount',
            'ordles_amount', 'ordles_discount', 'ordles_reward_discount', 'order_id',
            'ordles_id', 'ordles_tlang_id', 'order_payment_status', 'ordles_status',
            'order_pmethod_id', 'order_addedon', 'ordles_offline'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $tlangs = TeachLanguage::getAllLangs($this->langId);
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'ordles_id' => $row['ordles_id'],
                'order_id' => Order::formatOrderId($row['order_id']),
                'learner_name' => $row['learner_name'],
                'teacher_name' => $row['teacher_name'],
                'ordles_tlang_id' => $tlangs[$row['ordles_tlang_id']] ?? Label::getLabel('LBL_FREE_TRIAL'),
                'ordles_offline' => AppConstant::getServiceType($row['ordles_offline']),
                'ordles_net_amount' => MyUtility::formatMoney($row['ordles_net_amount'], false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
                'ordles_status' => Lesson::getStatuses($row['ordles_status']),
            ]);
            $count++;
        }
        return $count;
    }
}
