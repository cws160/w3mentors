<?php

class ExportCourseOrders extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::COURSE_ORDERS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'ordcrs_id' => Label::getLabel('LBL_ID'),
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'learner_name' => Label::getLabel('LBL_LEARNER'),
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'course_title' => Label::getLabel('LBL_TITLE'),
            'ordcrs_net_amount' => Label::getLabel('LBL_NET_TOTAL') .'['.$currencySymbol.']',
            'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
            'order_addedon' => Label::getLabel('LBL_DATETIME'),
            'ordcrs_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'ordcrs_id', 'order_id', 'course_active', 'course_title', 'ordcrs_amount',
            'ordcrs_discount', 'order_reward_value', 'order_payment_status', 'order_pmethod_id',
            '(ordcrs_amount-ordcrs_discount-order_reward_value) AS ordcrs_net_amount',
            'course_cate_id', 'course_subcate_id', 'order_reward_value', 'ordcrs_status', 'order_addedon',
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) as learner_name',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) as teacher_name'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'ordcrs_id' => $row['ordcrs_id'],
                'order_id' => Order::formatOrderId($row['order_id']),
                'learner_name' => $row['learner_name'],
                'teacher_name' => $row['teacher_name'],
                'course_title' => $row['course_title'],
                'ordcrs_net_amount' => MyUtility::formatMoney($row['ordcrs_net_amount'],false),
                'order_payment_status' => Order::getPaymentArr($row['order_payment_status']),
                'order_addedon' => MyDate::formatDate($row['order_addedon']),
                'ordcrs_status' => OrderCourse::getStatuses($row['ordcrs_status']),
            ]);
            $count++;
        }
        return $count;
    }

    public static function getCategories()
    {
        $srch = new SearchBased(Category::DB_TBL, 'cate');
        $srch->joinTable(Category::DB_LANG_TBL, 'LEFT JOIN', 'cate.cate_id = catelang.catelang_cate_id '
                . ' AND catelang.catelang_lang_id = ' . MyUtility::getSiteLangId(), 'catelang');
        $srch->addMultipleFields(['cate_id', 'IFNULL(cate_name, cate_identifier) AS cate_name']);
        $srch->addCondition('cate_type', '=', Category::TYPE_COURSE);
        $srch->addDirectCondition('cate_deleted IS NULL');
        $srch->addOrder('cate_order');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

}
