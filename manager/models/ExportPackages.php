<?php

class ExportPackages extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::PACKAGES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'ordpkg_id' => Label::getLabel('LBL_PACKAGE_ID'),
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'learner_name' => Label::getLabel('LBL_LEARNER'),
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'grpcls_tlang_id' => Label::getLabel('LBL_LANGUAGE'),
            'service_type' => Label::getLabel('LBL_SERVICE_TYPE'),
            'ordpkg_net_amount' => Label::getLabel('LBL_NET_TOTAL') . '[' . $currencySymbol . ']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
            'ordpkg_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) AS learner_name',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) AS teacher_name',
            '(ordpkg_amount - ordpkg_discount - ordpkg_reward_discount) AS ordpkg_net_amount',
            'ordpkg_amount', 'ordpkg_discount', 'order_id', 'ordpkg_id', 'grpcls_tlang_id',
            'order_payment_status', 'ordpkg_status', 'ordpkg_offline', 'order_addedon'
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
                'ordpkg_id' => $row['ordpkg_id'],
                'order_id' => Order::formatOrderId($row['order_id']),
                'learner_name' => $row['learner_name'],
                'teacher_name' => $row['teacher_name'],
                'grpcls_tlang_id' => $tlangs[$row['grpcls_tlang_id']] ?? 'NA',
                'service_type' => $serviceTypes[$row['ordpkg_offline']],
                'ordpkg_net_amount' => MyUtility::formatMoney($row['ordpkg_net_amount'], false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
                'ordpkg_status' => OrderClass::getStatuses($row['ordpkg_status']),
            ]);
            $count++;
        }
        return $count;
    }
}
