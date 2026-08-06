<?php

/**
 * Admin Class is used to handle Admin Statistic
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminStatistic
{

    /**
     * Get Dashboard Stats
     * 
     * @param bool $recalculate
     * @return boolean
     */
    public static function getDashboardStats(bool $recalculate = false)
    {
        $stats = json_decode(FatApp::getConfig('CONF_ADMIN_DASHBOARD_STATS'), true);
        if (!$recalculate) {
            return $stats;
        }
        $stats = [
            'TM_LESSONS_REVENUE' => static::getLessonsRevenue(),
            'ALL_LESSONS_REVENUE' => static::getLessonsRevenue(true),
            'TM_CLASSES_REVENUE' => static::getClassesRevenue(),
            'ALL_CLASSES_REVENUE' => static::getClassesRevenue(true),
            'TM_SUBSCRIPTION_REVENUE' => static::getSubscriptionsRevenue(),
            'ALL_SUBSCRIPTION_REVENUE' => static::getSubscriptionsRevenue(true),
            'TM_ADMIN_EARNINGS' => static::getAdminEarnings(),
            'ALL_ADMIN_EARNINGS' => static::getAdminEarnings(true),
            'TM_LESSONS_TOTAL' => static::getLessonsTotal(),
            'ALL_LESSONS_TOTAL' => static::getLessonsTotal(true),
            'TM_SUBSCRIPTIONS_TOTAL' => static::getSubscriptionsTotal(),
            'ALL_SUBSCRIPTIONS_TOTAL' => static::getSubscriptionsTotal(true),
            'TM_COMPLETED_LESSONS' => static::getCompletedLessons(),
            'ALL_COMPLETED_LESSONS' => static::getCompletedLessons(true),
            'TM_CANCELLED_LESSONS' => static::getCancelledLessons(),
            'ALL_CANCELLED_LESSONS' => static::getCancelledLessons(true),
            'TM_COMPLETED_SUBSCRIPTIONS' => static::getCompletedSubscriptions(),
            'ALL_COMPLETED_SUBSCRIPTIONS' => static::getCompletedSubscriptions(true),
            'TM_UNSCHEDULE_LESSONS' => static::getUnscheduleLessons(),
            'ALL_UNSCHEDULE_LESSONS' => static::getUnscheduleLessons(true),
            'TM_USERS_TOTAL' => static::getUsersTotal(),
            'ALL_USERS_TOTAL' => static::getUsersTotal(true),
            'TM_ORDERS_TOTAL' => static::getOrdersTotal(),
            'ALL_ORDERS_TOTAL' => static::getOrdersTotal(true),
        ];
        if (Course::isEnabled()) {
            $stats['TM_COURSES_REVENUE'] = static::getCoursesRevenue();
            $stats['ALL_COURSES_REVENUE'] = static::getCoursesRevenue(true);
            $stats['TM_COURSES_TOTAL'] = static::getCoursesTotal();
            $stats['ALL_COURSES_TOTAL'] = static::getCoursesTotal(true);
            $stats['TM_REFUNDED_COURSES'] = static::getRefundedCourses();
            $stats['ALL_REFUNDED_COURSES'] = static::getRefundedCourses(true);
        }
        if (GroupClass::isEnabled()) {
            $stats['TM_COMPLETED_CLASSES'] = static::getCompletedClasses();
            $stats['ALL_COMPLETED_CLASSES'] = static::getCompletedClasses(true);
            $stats['TM_CANCELLED_CLASSES'] = static::getCancelledClasses();
            $stats['ALL_CANCELLED_CLASSES'] = static::getCancelledClasses(true);
            $stats['TM_CLASSES_REVENUE'] = static::getClassesRevenue();
            $stats['ALL_CLASSES_REVENUE'] = static::getClassesRevenue(true);
            $stats['TM_CLASSES_TOTAL'] = static::getClassesTotal();
            $stats['ALL_CLASSES_TOTAL'] = static::getClassesTotal(true);
        }
        if (User::isAffiliateEnabled()) {
            $stats['TM_AFFILIATES_TOTAL'] = static::getUsersTotal(false, User::AFFILIATE);
            $stats['ALL_AFFILIATES_TOTAL'] = static::getUsersTotal(true, User::AFFILIATE);
        }
        if ($recalculate) {
            $assignValues = ['conf_name' => 'CONF_ADMIN_DASHBOARD_STATS', 'conf_val' => json_encode($stats)];
            if (!FatApp::getDb()->insertFromArray(Configurations::DB_TBL, $assignValues, false, [], $assignValues)) {
                return false;
            }
        }
        return $stats;
    }

    /**
     * Get Lessons Revenue
     * 
     * @param bool $all
     * @return float
     */
    private static function getLessonsRevenue(bool $all = false): float
    {
        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, CONF_SERVER_TIMEZONE, false, 'Y-m-d');
            $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
            $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['SUM(IFNULL(slstat_les_sales,0)) as les_sales']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['les_sales'] ?? 0.00;
    }

    /**
     * Get Classes Revenue
     * 
     * @param bool $all
     * @return float
     */
    private static function getClassesRevenue(bool $all = false): float
    {
        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, CONF_SERVER_TIMEZONE, false, 'Y-m-d');
            $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
            $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['SUM(IFNULL(slstat_cls_sales,0)) as cls_sales']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['cls_sales'] ?? 0.00;
    }

    /**
     * Get Lessons Revenue
     * 
     * @param bool $all
     * @return float
     */
    private static function getCoursesRevenue(bool $all = false): float
    {
        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, CONF_SERVER_TIMEZONE, false, 'Y-m-d');
            $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
            $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['SUM(IFNULL(slstat_crs_sales,0)) as crs_sales']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['crs_sales'] ?? 0.00;
    }

    /**
     * Get Subscriptions Revenue
     * 
     * @param bool $all
     * @return float
     */
    private static function getSubscriptionsRevenue(bool $all = false): float
    {
        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, CONF_SERVER_TIMEZONE, false, 'Y-m-d');
            $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
            $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['SUM(IFNULL(slstat_subplan_sales,0)) as subs_sales']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['subs_sales'] ?? 0.00;
    }

    /**
     * Get Admin Earnings
     * 
     * @param bool $all
     * @return float
     */
    private static function getAdminEarnings(bool $all = false): float
    {
        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, CONF_SERVER_TIMEZONE, false, 'Y-m-d');
            $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
            $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        }
        $srch->addFld('SUM(IFNULL(slstat_les_earnings, 0) + IFNULL(slstat_cls_earnings, 0) + IFNULL(slstat_crs_earnings, 0)) as earnings');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['earnings'] ?? 0.00;
    }

    /**
     * Get Lessons Total
     * 
     * @param bool $all
     * @return int
     */
    private static function getLessonsTotal(bool $all = false): int
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'ordles');
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_LESSON, Order::TYPE_SUBSCR]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('order_addedon', '>=', $datetime['startDate']);
            $srch->addCondition('order_addedon', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(ordles_id) AS totalLesson']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalLesson'] ?? 0;
    }

    /**
     * Get Lessons Total
     * 
     * @param bool $all
     * @return int
     */
    private static function getSubscriptionsTotal(bool $all = false): int
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_SUBPLAN, 'INNER JOIN', 'orders.order_id = ordsplan.ordsplan_order_id', 'ordsplan');
        $srch->addCondition('orders.order_type', '=', Order::TYPE_SUBPLAN);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('order_addedon', '>=', $datetime['startDate']);
            $srch->addCondition('order_addedon', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(ordsplan_id) AS totalSubscription']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalSubscription'] ?? 0;
    }

    /**
     * Get Classes Total
     * 
     * @param bool $all
     * @return int
     */
    private static function getClassesTotal(bool $all = false): int
    {
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('grpcls_start_datetime', '>=', $datetime['startDate']);
            $srch->addCondition('grpcls_end_datetime', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(grpcls_id) AS totalClasses']);
        $srch->addCondition('grpcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalClasses'] ?? 0;
    }

    /**
     * Get Courses Total
     * 
     * @param bool $all
     * @return int
     */
    private static function getCoursesTotal(bool $all = false): int
    {
        $srch = new CourseSearch(0, 0, User::SUPPORT);
        $srch->applyPrimaryConditions();
        $srch->joinTable(
            Course::DB_TBL_APPROVAL_REQUEST,
            'INNER JOIN',
            'course.course_id = coapre.coapre_course_id',
            'coapre'
        );
        $srch->addCondition('coapre.coapre_status', '=', Course::REQUEST_APPROVED);
        $srch->addCondition('course.course_status', '=', Course::PUBLISHED);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('course.course_created', '>=', $datetime['startDate']);
            $srch->addCondition('course.course_created', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(course.course_id) AS totalCourses']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalCourses'] ?? 0;
    }

    /**
     * Get Completed Lessons
     * 
     * @param bool $all
     * @return int
     */
    private static function getCompletedLessons(bool $all = false): int
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'ordles');
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_LESSON, Order::TYPE_SUBSCR]);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordles.ordles_status', '=', Lesson::COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('ordles_updated', '>=', $datetime['startDate']);
            $srch->addCondition('ordles_updated', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(ordles_id) AS totalLesson']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalLesson'] ?? 0;
    }

    /**
     * Get Completed Classes
     * 
     * @param bool $all
     * @return int
     */
    private static function getCompletedClasses(bool $all = false): int
    {
        $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');   
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('order_addedon', '>=', $datetime['startDate']);
            $srch->addCondition('order_addedon', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(ordcls_id) AS totalClasses']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalClasses'] ?? 0;
    }

    /**
     * Get Completed Subscriptions
     * 
     * @param bool $all
     * @return int
     */
    private static function getCompletedSubscriptions(bool $all = false): int
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_SUBPLAN, 'INNER JOIN', 'orders.order_id = ordsplan.ordsplan_order_id', 'ordsplan');
        $srch->addCondition('orders.order_type', '=', Order::TYPE_SUBPLAN);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordsplan.ordsplan_status', '=', OrderSubscriptionPlan::COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('ordsplan_updated', '>=', $datetime['startDate']);
            $srch->addCondition('ordsplan_updated', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(ordsplan_id) AS totalSubscription']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalSubscription'] ?? 0;
    }

    /**
     * Get Cancelled Lessons
     * 
     * @param bool $all
     * @return int
     */
    private static function getCancelledLessons(bool $all = false): int
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'ordles');
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_LESSON, Order::TYPE_SUBSCR]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordles.ordles_status', '=', Lesson::CANCELLED);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('ordles_updated', '>=', $datetime['startDate']);
            $srch->addCondition('ordles_updated', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(ordles_id) AS totalLesson']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalLesson'] ?? 0;
    }

    /**
     * Get Cancelled Classes
     * 
     * @param bool $all
     * @return int
     */
    private static function getCancelledClasses(bool $all = false): int
    {
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->addCondition('grpcls_status', '=', GroupClass::CANCELLED);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('grpcls_added_on', '>=', $datetime['startDate']);
            $srch->addCondition('grpcls_added_on', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(grpcls_id) AS totalClasses']);
        $srch->addCondition('grpcls_parent', '=', 0);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalClasses'] ?? 0;
    }

    /**
     * Get Refunded Courses
     * 
     * @param bool $all
     * @return int
     */
    private static function getRefundedCourses(bool $all = false): int
    {
        $srch = new CourseRefundRequestSearch(0, 0, User::SUPPORT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'corere.corere_user_id = u.user_id', 'u');
        $srch->addCondition('corere_status', '=', Course::REFUND_APPROVED);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('corere_updated', '>=', $datetime['startDate']);
            $srch->addCondition('corere_updated', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(corere_id) AS totalCourses']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalCourses'] ?? 0;
    }

    /**
     * Get Unscheduled Lessons
     * 
     * @param bool $all
     * @return int
     */
    private static function getUnscheduleLessons(bool $all = false): int
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'ordles');
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_LESSON, Order::TYPE_SUBSCR]);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordles.ordles_status', '=', Lesson::UNSCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('order_addedon', '>=', $datetime['startDate']);
            $srch->addCondition('order_addedon', '<=', $datetime['endDate']);
        }
        $srch->addMultipleFields(['COUNT(ordles_id) AS totalLesson']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalLesson'] ?? 0;
    }

    /**
     * Get Users Total
     * 
     * @param bool $all
     * @return int
     */
    private static function getUsersTotal(bool $all = false, int $type = 0): int
    {
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->addDirectCondition('user_deleted IS NULL');
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('user_created', '>=', $datetime['startDate']);
            $srch->addCondition('user_created', '<=', $datetime['endDate']);
        }
        switch ($type) {
            case User::AFFILIATE:
                $srch->addCondition('user_is_affiliate', '=', AppConstant::YES);
                break;
        }
        $srch->addMultipleFields(['COUNT(user_id) AS totalUser']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalUser'] ?? 0;
    }

    /**
     * Lesson Top Language
     * 
     * @param int $siteLangId
     * @param int $interval
     * @param int $limit
     * @return array
     */
    public static function lessonTopLanguage($siteLangId, int $interval, int $limit = 10): array
    {

        $datetime = MyDate::getStartEndDate($interval, NULL, true);
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'ordles');
        $srch->addMultipleFields(['COUNT(ordles_tlang_id) AS totalsold', 'ordles_tlang_id']);
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_LESSON, Order::TYPE_SUBSCR]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_addedon', '>=', $datetime['startDate']);
        $srch->addCondition('orders.order_addedon', '<=', $datetime['endDate']);
        $srch->addCondition('ordles.ordles_tlang_id', '>', 0);
        $srch->addGroupBy('ordles_tlang_id');
        $srch->addOrder('totalsold', 'DESC');
        $srch->addOrder('ordles_tlang_id', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize($limit);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $langData = [];
        if (!empty($records)) {
            $teachLangIds = array_column($records, 'ordles_tlang_id');
            $teachLangs = TeachLanguage::getNames($siteLangId, $teachLangIds, false);
            foreach ($records as &$record) {
                if (!array_key_exists($record['ordles_tlang_id'], $teachLangs)) {
                    continue;
                }
                $record['language'] = $teachLangs[$record['ordles_tlang_id']];
                $langData[] = $record;
            }
        }
        return $langData;
    }

    /**
     * Course Top Categories
     * 
     * @param int $siteLangId
     * @param int $interval
     * @param int $limit
     * @return array
     */
    public static function courseTopCategories($siteLangId, int $interval, int $limit = 10): array
    {
        $datetime = MyDate::getStartEndDate($interval, NULL, true);
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcrs.ordcrs_order_id', 'ordcrs');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'course.course_id = ordcrs.ordcrs_course_id', 'course');
        $srch->joinTable(Category::DB_TBL, 'INNER JOIN', 'course.course_cate_id = cate.cate_id', 'cate');
        $srch->joinTable(Category::DB_LANG_TBL, 'LEFT OUTER JOIN', 'cate.cate_id = cate_l.catelang_cate_id AND cate_l.catelang_lang_id = ' . $siteLangId, 'cate_l');
        $srch->addMultipleFields(['COUNT(course_cate_id) AS totalsold', 'course_cate_id']);
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_COURSE]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_addedon', '>=', $datetime['startDate']);
        if ($interval != MyDate::TYPE_ALL) {
            $srch->addCondition('orders.order_addedon', '<=', $datetime['endDate']);
        }
        $srch->addCondition('course.course_cate_id', '>', 0);
        $srch->addCondition('cate.cate_parent', '=', 0);
        $srch->addGroupBy('course_cate_id');
        $srch->addOrder('totalsold', 'DESC');
        $srch->addOrder('cate_name', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize($limit);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $catgData = [];
        if (!empty($records)) {
            $catgIds = array_column($records, 'course_cate_id');
            $categories = Category::getNames($catgIds, $siteLangId);
            foreach ($records as &$record) {
                if (!array_key_exists($record['course_cate_id'], $categories)) {
                    continue;
                }
                $record['category'] = $categories[$record['course_cate_id']];
                $catgData[] = $record;
            }
        }
        return $catgData;
    }

    /**
     * Classes Top Language
     * 
     * @param int $siteLangId
     * @param int $interval
     * @param int $limit
     * @return array
     */
    public static function classTopLanguage($siteLangId, int $interval, int $limit = 10): array
    {

        $datetime = MyDate::getStartEndDate($interval, NULL, true);
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(OrderClass::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->addMultipleFields(['COUNT(grpcls_tlang_id) AS totalsold', 'grpcls_tlang_id']);
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_GCLASS, Order::TYPE_PACKGE]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_addedon', '>=', $datetime['startDate']);
        $srch->addCondition('orders.order_addedon', '<=', $datetime['endDate']);
        $srch->addCondition('grpcls.grpcls_tlang_id', '>', 0);
        $srch->addGroupBy('grpcls_tlang_id');
        $srch->addOrder('totalsold', 'DESC');
        $srch->addOrder('grpcls_tlang_id', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize($limit);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $langData = [];
        if (!empty($records)) {
            $teachLangIds = array_column($records, 'grpcls_tlang_id');
            $teachLangs = TeachLanguage::getNames($siteLangId, $teachLangIds, false);
            foreach ($records as &$record) {
                if (!array_key_exists($record['grpcls_tlang_id'], $teachLangs)) {
                    continue;
                }
                $record['language'] = $teachLangs[$record['grpcls_tlang_id']];
                $langData[] = $record;
            }
        }
        return $langData;
    }

    /**
     * Get Users Stat
     * 
     * @param int $durationType
     * @return array
     */
    public static function getUsersStat(int $durationType): array
    {
        $datetime = MyDate::getStartEndDate($durationType, NULL, true);
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->addCondition('user_created', '>=', $datetime['startDate']);
        $srch->addCondition('user_created', '<=', $datetime['endDate']);
        switch ($durationType) {
            case MyDate::TYPE_TODAY:
                $srch->addFld("DATE_FORMAT(user_created, '%H:%i') as groupDate");
                break;
            case MyDate::TYPE_THIS_YEAR:
            case MyDate::TYPE_LAST_YEAR:
            case MyDate::TYPE_LAST_12_MONTH:
                $srch->addFld("DATE_FORMAT(user_created, '%m-%Y') as groupDate");
                break;
            default:
                $srch->addFld("DATE_FORMAT(user_created, '%Y-%m-%d') as groupDate");
                break;
        }
        $srch->addMultipleFields(['COUNT(user_id) AS totalUser']);
        $srch->addOrder('user_created');
        $srch->addGroupBy("groupDate");
        $srch->addOrder("YEAR(user_created)", 'ASC');
        $srch->addOrder("MONTH(user_created)", 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'groupDate');
        $stats = [];
        foreach ($records as $date => $data) {
            $stats[date('M Y', strtotime('13-' . $date))] = $data['totalUser'];
        }
        return $stats;
    }

    /**
     * Get Admin Lesson Earning Stats
     * 
     * @param int $durationType
     * @return array
     */
    public static function getAdminLessonEarningStats(int $durationType): array
    {
        $datetime = MyDate::getStartEndDate($durationType, NULL, true);
        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
        $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        switch ($durationType) {
            case MyDate::TYPE_THIS_YEAR:
            case MyDate::TYPE_LAST_YEAR:
            case MyDate::TYPE_LAST_12_MONTH:
                $srch->addFld("DATE_FORMAT(slstat_date, '%m-%Y') as groupDate");
                break;
            default:
                $srch->addFld("DATE_FORMAT(slstat_date, '%Y-%m-%d') as groupDate");
                break;
        }
        $srch->addMultipleFields(['sum(IFNULL(slstat_les_earnings,0)) as les_earnings']);
        $srch->addOrder('slstat_date');
        $srch->addGroupBy("groupDate");
        $srch->addOrder("YEAR(slstat_date)", 'ASC');
        $srch->addOrder("MONTH(slstat_date)", 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'groupDate');
        $stats = [];
        foreach ($records as $date => $data) {
            $stats[date('M Y', strtotime('13-' . $date))] = $data['les_earnings'];
        }
        return $stats;
    }

    /**
     * Get Admin Class Earning Stats
     * 
     * @param int $durationType
     * @return array
     */
    public static function getAdminClassEarningStats(int $durationType): array
    {
        $datetime = MyDate::getStartEndDate($durationType, NULL, true);
        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
        $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        switch ($durationType) {
            case MyDate::TYPE_THIS_YEAR:
            case MyDate::TYPE_LAST_YEAR:
            case MyDate::TYPE_LAST_12_MONTH:
                $srch->addFld("DATE_FORMAT(slstat_date, '%m-%Y') as groupDate");
                break;
            default:
                $srch->addFld("DATE_FORMAT(slstat_date, '%Y-%m-%d') as groupDate");
                break;
        }
        $srch->addMultipleFields(['sum(IFNULL(slstat_cls_earnings,0)) as cls_earnings']);
        $srch->addOrder('slstat_date');
        $srch->addGroupBy("groupDate");
        $srch->addOrder("YEAR(slstat_date)", 'ASC');
        $srch->addOrder("MONTH(slstat_date)", 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'groupDate');
        $stats = [];
        foreach ($records as $date => $data) {
            $stats[date('M Y', strtotime('13-' . $date))] = $data['cls_earnings'];
        }
        return $stats;
    }

    /**
     * Get Admin Course Earning Stats
     * 
     * @param int $durationType
     * @return array
     */
    public static function getAdminCourseEarningStats(int $durationType): array
    {
        $datetime = MyDate::getStartEndDate($durationType, NULL, true);

        $srch = new SearchBase('tbl_sales_stats', 'slstat');
        $srch->addCondition('slstat_date', '>=', $datetime['startDate']);
        $srch->addCondition('slstat_date', '<=', $datetime['endDate']);
        switch ($durationType) {
            case MyDate::TYPE_THIS_YEAR:
            case MyDate::TYPE_LAST_YEAR:
            case MyDate::TYPE_LAST_12_MONTH:
                $srch->addFld("DATE_FORMAT(slstat_date, '%m-%Y') as groupDate");
                break;
            default:
                $srch->addFld("DATE_FORMAT(slstat_date, '%Y-%m-%d') as groupDate");
                break;
        }
        $srch->addMultipleFields(['sum(IFNULL(slstat_crs_earnings,0)) as crs_earnings']);
        $srch->addGroupBy("groupDate");
        $srch->addOrder("YEAR(slstat_date)", 'ASC');
        $srch->addOrder("MONTH(slstat_date)", 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'groupDate');
        $stats = [];
        foreach ($records as $date => $data) {
            $stats[date('M Y', strtotime('13-' . $date))] = $data['crs_earnings'];
        }
        return $stats;
    }

    /**
     * Get ORDERS Total
     * 
     * @param bool $all
     * @return int
     */
    private static function getOrdersTotal(bool $all = false): int
    {
        $srch = new SearchBase(Order::DB_TBL);
        if (!$all) {
            $datetime = MyDate::getStartEndDate(MyDate::TYPE_THIS_MONTH, NULL, true);
            $srch->addCondition('order_addedon', '>=', $datetime['startDate']);
            $srch->addCondition('order_addedon', '<=', $datetime['endDate']);
        }
        if (!Course::isEnabled()) {
            $srch->addCondition('order_type', '!=', Order::TYPE_COURSE);
        }
        if (!GroupClass::isEnabled()) {
            $srch->addCondition('order_type', '!=', Order::TYPE_GCLASS);
            $srch->addCondition('order_type', '!=', Order::TYPE_PACKGE);
        }
        $srch->addMultipleFields(['COUNT(order_id) AS totalOrders']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return $records['totalOrders'] ?? 0;
    }
}
