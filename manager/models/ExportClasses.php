<?php

class ExportClasses extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::CLASSES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'ordcls_id' => Label::getLabel('LBL_CLASS_ID'),
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'learner_name' => Label::getLabel('LBL_LEARNER'),
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'grpcls_tlang_id' => Label::getLabel('LBL_LANGUAGE'),
            'service_type' => Label::getLabel('LBL_SERVICE_TYPE'),
            'ordcls_net_amount' => Label::getLabel('LBL_NET_TOTAL').'['.$currencySymbol.']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
            'ordcls_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) AS learner_name',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) AS teacher_name',
            '(ordcls_amount - ordcls_discount - ordcls_reward_discount) AS ordcls_net_amount',
            'ordcls_amount', 'ordcls_discount', 'order_id', 'ordcls_id', 'grpcls_tlang_id',
            'order_payment_status', 'ordcls_status', 'order_pmethod_id', 'order_addedon',
            'ordcls_reward_discount', 'grpcls_offline'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $tlangs = TeachLanguage::getAllLangs($this->langId);
        $serviceTypes = AppConstant::getServiceType();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'ordcls_id' => $row['ordcls_id'],
                'order_id' => Order::formatOrderId($row['order_id']),
                'learner_name' => $row['learner_name'],
                'teacher_name' => $row['teacher_name'],
                'grpcls_tlang_id' => $tlangs[$row['grpcls_tlang_id']] ?? 'NA',
                'service_type' => $serviceTypes[$row['grpcls_offline']],
                'ordcls_net_amount' => MyUtility::formatMoney($row['ordcls_net_amount'],false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
                'ordcls_status' => OrderClass::getStatuses($row['ordcls_status']),
            ]);
            $count++;
        }
        return $count;
    }

}
