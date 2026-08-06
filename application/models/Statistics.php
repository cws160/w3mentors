<?php

/**
 * This class is used to handle Statistics
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Statistics extends FatModel
{

    const REPORT_EARNING = 1;
    const REPORT_SOLD_SESSIONS = 2;

    private $userId;

    /**
     * Initialize Statistics
     * 
     * @param int $userId
     */
    public function __construct(int $userId = 0)
    {
        parent::__construct();
        $this->userId = $userId;
    }

    /**
     * Get Report TypesF
     * 
     * @return array
     */
    public static function getReportType(): array
    {
        return [
            static::REPORT_EARNING => Label::getLabel('LBL_EARNING'),
            static::REPORT_SOLD_SESSIONS => Label::getLabel('LBL_SOLD_SESSIONS')
        ];
    }

    /**
     * Get Earning
     * 
     * @todo to be updated
     * We will store stats in cache and provide an 
     * action to user to refresh/regenerate cache data
     * 
     * @param int $duration
     * @param bool $forGraph
     * @return array
     */
    public function getEarning(int $duration, int $type, bool $forGraph = false): array
    {
        $userTimezone = MyUtility::getSiteTimezone();
        $datetime = MyDate::getStartEndDate($duration, $userTimezone, true);
        $srch = new SearchBase(Transaction::DB_TBL);
        $srch->addCondition('usrtxn_datetime', '>=', $datetime['startDate']);
        $srch->addCondition('usrtxn_datetime', '<=', $datetime['endDate']);
        $srch->addCondition('usrtxn_user_id', '=', $this->userId);
        $srch->addCondition('usrtxn_type', '=', $type);
        $srch->addMultipleFields(['IFNULL(sum(usrtxn_amount),0) as earning', 'usrtxn_datetime']);
        /**
         * Currently switch case only from graph data, 
         * We can change according to new changes
         */
        if ($forGraph) {
            switch ($duration) {
                case MyDate::TYPE_TODAY:
                    $srch->addFld("DATE_FORMAT(usrtxn_datetime,'%h:%i %p') as groupDate");
                    break;
                case MyDate::TYPE_THIS_YEAR:
                case MyDate::TYPE_LAST_YEAR:
                    $srch->addFld("DATE_FORMAT(usrtxn_datetime, '%m-%Y') as groupDate");
                    break;
                default:
                    $srch->addFld("DATE_FORMAT(usrtxn_datetime, '%Y-%m-%d') as groupDate");
                    break;
            }
            $srch->addGroupBy("groupDate");
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $data = [
            'fromDate' => $datetime['startDate'],
            'toDate' => $datetime['endDate'],
            'earningData' => [],
            'earning' => 0
        ];
        if ($forGraph) {
            $earningData = FatApp::getDb()->fetchAll($srch->getResultSet(), 'groupDate');
            $data['earningData'] = $earningData;
            $data['earning'] = array_sum(array_column($earningData, 'earning'));
            return $data;
        }
        $earningData = FatApp::getDb()->fetch($srch->getResultSet());
        $data['earningData'] = $earningData;
        $data['earning'] = $earningData['earning'] ?? 0;
        return $data;
    }

    /**
     * Get Sold Sessions
     * 
     * @param int $duration
     * @param int $langId
     * @param bool $forGraph
     * @return array
     */
    public function getSoldSessions(int $duration, int $langId, bool $forGraph = false): array
    {
        $orderTypes = [Order::TYPE_LESSON, Order::TYPE_SUBSCR];
        if (GroupClass::isEnabled()) {
            array_push($orderTypes, ...[Order::TYPE_GCLASS, Order::TYPE_PACKGE]);
        }
        $datetime = MyDate::getStartEndDate($duration, MyUtility::getSiteTimezone(), true);
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'LEFT JOIN', 'orders.order_type IN (' . Order::TYPE_LESSON . ',' . Order::TYPE_SUBSCR . ') AND orders.order_id = ordles.ordles_order_id', 'ordles');
        $srch->joinTable(OrderClass::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_GCLASS . ' AND orders.order_id = ordcls.ordcls_order_id', 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_GCLASS . ' AND ordcls.ordcls_grpcls_id = grpcls.grpcls_id', 'grpcls');
        $srch->joinTable(OrderPackage::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_PACKGE . ' AND orders.order_id = ordpkg.ordpkg_order_id', 'ordpkg');
        $srch->joinTable(GroupClass::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_PACKGE . ' AND ordpkg.ordpkg_package_id = packageCls.grpcls_id', 'packageCls');
        $cond = $srch->addCondition('ordles.ordles_teacher_id', '=', $this->userId);
        $cond->attachCondition('grpcls.grpcls_teacher_id', '=', $this->userId);
        $cond->attachCondition('packageCls.grpcls_teacher_id', '=', $this->userId);
        $srch->addCondition('orders.order_type', 'IN', $orderTypes);
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('order_addedon', '>=', $datetime['startDate']);
        $srch->addCondition('order_addedon', '<=', $datetime['endDate']);
        /**
         * (count(ordles_id) + count(grpcls_id))
         * @todo To be discussed
         */
        $srch->addMultipleFields([
            'count(*) as sessionCount', 'MIN(order_addedon) as fromDate',
            'MAX(order_addedon) as toDate', 'order_addedon'
        ]);
        if ($forGraph) {
            switch ($duration) {
                case MyDate::TYPE_TODAY:
                    $srch->addFld("DATE_FORMAT(order_addedon, '%H:%i') as groupDate");
                    break;
                case MyDate::TYPE_THIS_YEAR:
                case MyDate::TYPE_LAST_YEAR:
                    $srch->addFld("DATE_FORMAT(order_addedon, '%m-%Y') as groupDate");
                    break;
                default:
                    $srch->addFld("DATE_FORMAT(order_addedon, '%Y-%m-%d') as groupDate");
                    break;
            }
            $srch->addGroupBy("groupDate");
        }
        $srch->removGroupBy('orders.order_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $data = [
            'fromDate' => $datetime['startDate'],
            'toDate' => $datetime['endDate'],
            'sessionData' => [],
            'sessionCount' => 0
        ];
        if ($forGraph) {
            $sessionData = FatApp::getDb()->fetchAll($srch->getResultSet(), 'groupDate');
            $data['sessionData'] = $sessionData;
            $data['sessionCount'] = array_sum(array_column($sessionData, 'sessionCount'));
            return $data;
        }
        $sessionData = FatApp::getDb()->fetch($srch->getResultSet());
        $data['sessionData'] = $sessionData;
        $data['sessionCount'] = $sessionData['sessionCount'];
        return $data;
    }

    /**
     * Get Session Stats
     * 
     * @return array
     */
    public function getSessionStats(): array
    {
        $lessStatsCount = (new Lesson(0, $this->userId, User::TEACHER))->getLessStatsCount();
        $schClassStats = (new GroupClass(0, $this->userId, User::TEACHER))->getSchedClassStats();
        return [
            'lessStats' => $lessStatsCount,
            'classStats' => $schClassStats,
            'totalSession' => $lessStatsCount['schLessonCount'] + $schClassStats['schClassCount'],
            'schSessionCount' => $lessStatsCount['schLessonCount'] + $schClassStats['schClassCount'],
            'upcomingSession' => $lessStatsCount['upcomingLesson'] + $schClassStats['upcomingClass']
        ];
    }
}
