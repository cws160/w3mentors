<?php

/**
 * This class is used to handle Sale Stats
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SaleStat extends FatModel
{

    const DB_TBL = 'tbl_sales_stats';

    /**
     * Initialize SaleStat
     * 
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * regenerate the sale report
     *
     * @return string
     */
    public function regenerate(): string
    {
        $date = FatApp::getConfig('CONF_SALES_REPORT_GENERATED_DATE');
        $date = date('Y-m-d', strtotime($date));
        $saleData = [];
        $saleData = $this->generateLessonsReport($date, $saleData);
        $saleData = $this->generateLessonsNetSale($date, $saleData);
        $saleData = $this->generateClassesReport($date, $saleData);
        $saleData = $this->generateClassesNetSale($date, $saleData);
        $saleData = $this->generateCoursesReport($date, $saleData);
        $saleData = $this->generateCoursesNetSale($date, $saleData);
        $saleData = $this->generateSubscriptionPlansNetSale($date, $saleData);
        $saleData = $this->generateSubscriptionPlansReport($date, $saleData);
        $db = FatApp::getDb();
        $db->startTransaction();
        foreach ($saleData as $sale) {
            $record = new TableRecord(static::DB_TBL);
            $record->assignValues($sale);
            if (!$record->addNew([], $sale)) {
                $db->rollbackTransaction();
                $this->error = $record->getError();
                return false;
            }
        }
        if (!AdminStatistic::getDashboardStats(true)) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('LBL_SOMETHING_WENT_WRONG');
            return false;
        }
        if (!(new HoursTaughtStat())->regenerate()) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('LBL_SOMETHING_WENT_WRONG');
            return false;
        }
        $record = new TableRecord(Configurations::DB_TBL);
        $dateNow = date('Y-m-d H:i:s');
        $record->setFldValue('conf_val', $dateNow);
        if (!$record->update(['smt' => 'conf_name = ?', 'vals' => ['CONF_SALES_REPORT_GENERATED_DATE']])) {
            $db->rollbackTransaction();
            $this->error = $record->getError();
            return false;
        }
        $db->commitTransaction();
        return $dateNow;
    }

    /**
     * Generate Lessons Report
     * 
     * @return array
     */
    private function generateLessonsReport(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'ordles.ordles_order_id = orders.order_id', 'ordles');
        $srch->addCondition('mysql_func_DATE(ordles.ordles_updated)', ">=", $date, 'AND', true);
        $srch->addDirectCondition('((ordles.ordles_status = ' . Lesson::COMPLETED .
                ' AND ordles_teacher_paid IS NOT NULL) OR ( ordles.ordles_status = ' . Lesson::CANCELLED . ') )');
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addMultipleFields([
            'DATE(ordles.ordles_updated) AS slstat_date',
            'SUM(IFNULL(ordles.ordles_refund, 0)) AS slstat_les_refund',
            'SUM(IFNULL(ordles.ordles_earnings, 0)) AS slstat_les_earnings',
            'SUM(IFNULL(ordles.ordles_teacher_paid, 0)) AS slstat_les_teacher_paid',
        ]);
        $srch->addGroupBy('DATE(ordles.ordles_updated)');
        $srch->addOrder('ordles.ordles_updated');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']] = [
                'slstat_date' => $row['slstat_date'],
                'slstat_les_refund' => $row['slstat_les_refund'],
                'slstat_les_earnings' => $row['slstat_les_earnings'],
                'slstat_les_teacher_paid' => $row['slstat_les_teacher_paid'],
            ];
        }
        return $data;
    }

    /**
     * Generate Lessons NetSale
     */
    public function generateLessonsNetSale(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->addMultipleFields([
            'DATE(orders.order_addedon) AS slstat_date',
            'SUM(orders.order_net_amount) AS slstat_les_sales',
            'SUM(order_discount_value) AS slstat_les_discount',
            'SUM(order_reward_value) AS slstat_les_credit_discount'
        ]);
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_LESSON, Order::TYPE_SUBSCR]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('mysql_func_DATE(orders.order_addedon)', ">=", $date, 'AND', true);
        $srch->addGroupBy('DATE(orders.order_addedon)');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']]['slstat_date'] = $row['slstat_date'] ?? 0;
            $data[$row['slstat_date']]['slstat_les_sales'] = $row['slstat_les_sales'] ?? 0;
            $data[$row['slstat_date']]['slstat_les_discount'] = $row['slstat_les_discount'] ?? 0;
            $data[$row['slstat_date']]['slstat_les_credit_discount'] = $row['slstat_les_credit_discount'] ?? 0;
        }
        return $data;
    }

    /**
     * Generate Classes Report
     */
    private function generateClassesReport(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(OrderClass::DB_TBL, 'INNER JOIN', 'ordcls.ordcls_order_id = orders.order_id', 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->addCondition('mysql_func_DATE(ordcls.ordcls_updated)', ">=", $date, 'AND', true);
        $srch->addDirectCondition('((ordcls.ordcls_status = ' . OrderClass::COMPLETED .
                ' AND ordcls_teacher_paid IS NOT NULL) OR (ordcls.ordcls_status = ' . OrderClass::CANCELLED . '))');
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addMultipleFields([
            'DATE(ordcls.ordcls_updated) AS slstat_date',
            'SUM(IFNULL(ordcls.ordcls_refund, 0)) AS slstat_cls_refund',
            'SUM(IFNULL(ordcls.ordcls_earnings, 0)) AS slstat_cls_earnings',
            'SUM(IFNULL(ordcls.ordcls_teacher_paid, 0)) AS slstat_cls_teacher_paid',
        ]);
        $srch->addGroupBy('DATE(ordcls.ordcls_updated)');
        $srch->addOrder('ordcls.ordcls_updated');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']]['slstat_date'] = $row['slstat_date'];
            $data[$row['slstat_date']]['slstat_cls_refund'] = $row['slstat_cls_refund'];
            $data[$row['slstat_date']]['slstat_cls_earnings'] = $row['slstat_cls_earnings'];
            $data[$row['slstat_date']]['slstat_cls_teacher_paid'] = $row['slstat_cls_teacher_paid'];
        }
        return $data;
    }

    /**
     * Generate Classes NetSale
     */
    public function generateClassesNetSale(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->addMultipleFields([
            'DATE(orders.order_addedon) AS slstat_date',
            'SUM(orders.order_net_amount) AS slstat_cls_sales',
            'SUM(order_discount_value) AS slstat_cls_discount',
            'SUM(order_reward_value) AS slstat_cls_credit_discount'
        ]);
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_GCLASS, Order::TYPE_PACKGE]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('mysql_func_DATE(orders.order_addedon)', ">=", $date, 'AND', true);
        $srch->addGroupBy('DATE(orders.order_addedon)');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']]['slstat_date'] = $row['slstat_date'];
            $data[$row['slstat_date']]['slstat_cls_sales'] = $row['slstat_cls_sales'] ?? 0;
            $data[$row['slstat_date']]['slstat_cls_discount'] = $row['slstat_cls_discount'] ?? 0;
            $data[$row['slstat_date']]['slstat_cls_credit_discount'] = $row['slstat_cls_credit_discount'] ?? 0;
        }
        return $data;
    }

    /**
     * Generate Courses Report
     * 
     * @return array
     */
    private function generateCoursesReport(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_order_id = orders.order_id', 'ordcrs');
        $srch->addCondition('mysql_func_DATE(ordcrs.ordcrs_updated)', ">=", $date, 'AND', true);
        $srch->addDirectCondition('ordcrs_teacher_paid IS NOT NULL');
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addMultipleFields([
            'DATE(ordcrs.ordcrs_updated) AS slstat_date',
            'SUM(IFNULL(ordcrs.ordcrs_refund, 0)) AS slstat_crs_refund',
            'SUM(IFNULL(ordcrs.ordcrs_earnings, 0)) AS slstat_crs_earnings',
            'SUM(IFNULL(ordcrs.ordcrs_teacher_paid, 0)) AS slstat_crs_teacher_paid',
        ]);
        $srch->addGroupBy('DATE(ordcrs.ordcrs_updated)');
        $srch->addOrder('ordcrs.ordcrs_updated');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']]['slstat_date'] = $row['slstat_date'];
            $data[$row['slstat_date']]['slstat_crs_refund'] = $row['slstat_crs_refund'];
            $data[$row['slstat_date']]['slstat_crs_earnings'] = $row['slstat_crs_earnings'];
            $data[$row['slstat_date']]['slstat_crs_teacher_paid'] = $row['slstat_crs_teacher_paid'];
        }
        return $data;
    }

    /**
     * Generate Courses NetSale
     */
    public function generateCoursesNetSale(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->addMultipleFields([
            'DATE(orders.order_addedon) AS slstat_date',
            'SUM(orders.order_net_amount) AS slstat_crs_sales',
            'SUM(order_discount_value) AS slstat_crs_discount',
            'SUM(order_reward_value) AS slstat_crs_credit_discount'
        ]);
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_COURSE]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('mysql_func_DATE(orders.order_addedon)', ">=", $date, 'AND', true);
        $srch->addGroupBy('DATE(orders.order_addedon)');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']]['slstat_date'] = $row['slstat_date'];
            $data[$row['slstat_date']]['slstat_crs_sales'] = $row['slstat_crs_sales'] ?? 0;
            $data[$row['slstat_date']]['slstat_crs_discount'] = $row['slstat_crs_discount'] ?? 0;
            $data[$row['slstat_date']]['slstat_crs_credit_discount'] = $row['slstat_crs_credit_discount'] ?? 0;
        }
        return $data;
    }

    /**
     * Generate SubscriptionPlans Report
     * 
     * @return array
     */
    private function generateSubscriptionPlansReport(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(OrderSubscriptionPlan::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_order_id = orders.order_id', 'ordsplan');
        $srch->addCondition('mysql_func_DATE(ordsplan.ordsplan_updated)', ">=", $date, 'AND', true);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addDirectCondition('ordsplan.ordsplan_earnings IS NOT NULL');
        $srch->addMultipleFields([
            'DATE(ordsplan.ordsplan_updated) AS slstat_date',
            'SUM(IFNULL(ordsplan.ordsplan_refund, 0)) AS slstat_subplan_refund',
            'SUM(IFNULL(ordsplan.ordsplan_earnings, 0)) AS slstat_subplan_earnings',
        ]);
        $srch->addGroupBy('DATE(ordsplan.ordsplan_updated)');
        $srch->addOrder('ordsplan.ordsplan_updated');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']]['slstat_date'] = $row['slstat_date'];
            $data[$row['slstat_date']]['slstat_subplan_refund'] = $row['slstat_subplan_refund'];
            $data[$row['slstat_date']]['slstat_subplan_earnings'] = $row['slstat_subplan_earnings'];
            $data[$row['slstat_date']]['slstat_subplan_teacher_paid'] = 0;
        }
        return $data;
    }

     /**
     * Generate Subscription Plans NetSale
     */
    public function generateSubscriptionPlansNetSale(string $date, array $data): array
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->addMultipleFields([
            'DATE(orders.order_addedon) AS slstat_date',
            'SUM(orders.order_net_amount) AS slstat_subplan_sales',
            'SUM(order_discount_value) AS slstat_subplan_discount',
            'SUM(order_reward_value) AS slstat_subplan_credit_discount'
        ]);
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_SUBPLAN]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('mysql_func_DATE(orders.order_addedon)', ">=", $date, 'AND', true);
        $srch->addGroupBy('DATE(orders.order_addedon)');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data[$row['slstat_date']]['slstat_date'] = $row['slstat_date'];
            $data[$row['slstat_date']]['slstat_subplan_sales'] = $row['slstat_subplan_sales'] ?? 0;
            $data[$row['slstat_date']]['slstat_subplan_discount'] = $row['slstat_subplan_discount'] ?? 0;
            $data[$row['slstat_date']]['slstat_subplan_credit_discount'] = $row['slstat_subplan_credit_discount'] ?? 0;
        }
        return $data;
    }

}
