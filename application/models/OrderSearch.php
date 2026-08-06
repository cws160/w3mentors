<?php

/**
 * This class is used Search Orders
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderSearch extends W3mentorsSearch
{

    /**
     * Initialize Order Search
     * 
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = 'tbl_orders';
        $this->alias = 'orders';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $this->joinTable(Order::DB_TBL_LESSON, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_LESSON . ' AND orders.order_id = ordles.ordles_order_id', 'ordles');
        $this->joinTable(OrderClass::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_GCLASS . ' AND orders.order_id = ordcls.ordcls_order_id', 'ordcls');
        $this->joinTable(GroupClass::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_GCLASS . ' AND ordcls.ordcls_grpcls_id = grpcls.grpcls_id', 'grpcls');
        $this->joinTable(OrderCourse::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_COURSE . ' AND ordcrs.ordcrs_order_id = orders.order_id', 'ordcrs');
        $this->joinTable(Course::DB_TBL, 'LEFT JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'course');
        $this->joinTable(OrderSubscriptionPlan::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_SUBPLAN . ' AND ordsplan.ordsplan_order_id = orders.order_id', 'ordsplan');
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        if ($this->userType === User::LEARNER) {
            $this->addCondition('orders.order_user_id', '=', $this->userId);
            $this->addGroupBy('orders.order_id');
        } elseif ($this->userType === User::TEACHER) {
            $this->addGroupBy('orders.order_id');
            $cond = $this->addCondition('ordles.ordles_teacher_id', '=', $this->userId);
            $cond->attachCondition('grpcls.grpcls_teacher_id', '=', $this->userId);
            $cond->attachCondition('course.course_user_id', '=', $this->userId);
            $this->addCondition('orders.order_type', 'IN', [
                Order::TYPE_LESSON,
                Order::TYPE_GCLASS,
                Order::TYPE_COURSE
            ]);
        } else {
            $this->addGroupBy('orders.order_id');
        }
    }

    /**
     * Apply Search Conditions
     * 
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (!empty($post['order_id'])) {
            $this->addCondition('orders.order_id', '=', FatUtility::int(str_replace('O', '', $post['order_id'])));
        }
        if (!empty($post['keyword'])) {
            $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
            $cond = $this->addCondition($fullName, 'LIKE', '%' . trim($post['keyword']) . '%', 'AND', true);
            $orderId = FatUtility::int(str_replace('O', '', $post['keyword']));
            if (!empty($orderId)) {
                $cond->attachCondition('orders.order_id', '=', $orderId);
            }
        }
        if (!empty($post['order_type'])) {
            $this->addCondition('orders.order_type', '=', $post['order_type']);
        }
        if (isset($post['service_type']) && $post['service_type'] != '') {
            $cnd = $this->addCondition('ordles_offline', '=', $post['service_type']);
            $cnd->attachCondition('grpcls_offline', '=', $post['service_type'], 'OR');

            $this->joinTable(OrderPackage::DB_TBL, 'LEFT JOIN', 'orders.order_id = ordpkg_order_id');
            $cnd->attachCondition('ordpkg_offline', '=', $post['service_type'], 'OR');

            $this->joinTable(Subscription::DB_TBL, 'LEFT JOIN', 'orders.order_id = ordsub_order_id');
            $cnd->attachCondition('ordsub_offline', '=', $post['service_type'], 'OR');
        }
        if (!empty($post['order_user_id'])) {
            $this->addCondition('orders.order_user_id', '=', $post['order_user_id']);
        } elseif (!empty($post['order_user'])) {
            $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
            $this->addCondition($fullName, 'LIKE', '%' . trim($post['order_user']) . '%', 'AND', true);
        }
        if (isset($post['order_payment_status']) && $post['order_payment_status'] !== '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }
        if (isset($post['order_pmethod_id']) && $post['order_pmethod_id'] !== '') {
            $this->addCondition('orders.order_pmethod_id', '=', $post['order_pmethod_id']);
        }
        if (isset($post['order_status']) && $post['order_status'] !== '') {
            $this->addCondition('orders.order_status', '=', $post['order_status']);
        }
        if (!empty($post['date_from'])) {
            $fromdate = MyDate::formatToSystemTimezone($post['date_from'] . ' 00:00:00');
            $this->addCondition('orders.order_addedon', '>=', $fromdate);
        }
        if (!empty($post['date_to'])) {
            $tilldate = MyDate::formatToSystemTimezone($post['date_to'] . ' 23:59:59');
            $this->addCondition('orders.order_addedon', '<=', $tilldate);
        }
    }

    /**
     * Fetch And Format
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($rows) == 0) {
            return [];
        }
        $pmethods = PaymentMethod::getPayins(true, false);
        $countryIds = array_column($rows, 'learner_country_id');
        $countries = Country::getNames($this->langId, $countryIds);

        $serviceTypes = self::getServiceType($rows);
        foreach ($rows as $key => $row) {
            $row['order_pmethod'] = $pmethods[$row['order_pmethod_id']] ?? Label::getLabel('LBL_NA');
            $row['learner_country'] = $countries[$row['learner_country_id']] ?? Label::getLabel('LBL_NA');
            $row['order_addedon'] = MyDate::convert($row['order_addedon']);
            $row['service_type'] = $serviceTypes[$row['order_id']];
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Get service types
     *
     * @param array $orders
     * @return array
     */
    public static function getServiceType(array $orders)
    {
        $orderIds = array_column($orders, 'order_id');
        $subs = self::getSubcriptions($orderIds);
        $packg = self::getPackages($orderIds);
        $serviceTypes = [];
        foreach ($orders as $data) {
            switch ($data['order_type']) {
                case Order::TYPE_LESSON:
                    $isOffline = $data['ordles_offline'];
                    break;
                case Order::TYPE_SUBSCR:
                    $isOffline = $subs[$data['order_id']] ?? AppConstant::NO;
                    break;
                case Order::TYPE_GCLASS:
                    $isOffline = $data['grpcls_offline'];
                    break;
                case Order::TYPE_PACKGE:
                    $isOffline = $packg[$data['order_id']] ?? AppConstant::NO;
                    break;
                case Order::TYPE_COURSE:
                case Order::TYPE_SUBPLAN:
                case Order::TYPE_WALLET:
                case Order::TYPE_GFTCRD:
                    $isOffline = '';
                    break;
                default:
                    $isOffline = AppConstant::NO;
                    break;
            }
            $label = Label::getLabel('LBL_NA');
            if ($isOffline == AppConstant::YES) {
                $label = Label::getLabel('LBL_OFFLINE');
            } elseif ($isOffline == AppConstant::NO) {
                $label = Label::getLabel('LBL_ONLINE');
            }
            $serviceTypes[$data['order_id']] = $label;
        }

        return $serviceTypes;
    }

    /**
     * Get Offline status for subscriptions
     *
     * @param array $orderIds
     * @return array
     */
    private static function getSubcriptions(array $orderIds)
    {
        $srch = new SearchBase(Subscription::DB_TBL, 'ordsub');
        $srch->addMultipleFields(['ordsub_order_id', 'ordsub_offline']);
        $srch->addDirectCondition('ordsub_order_id IN (' . implode(',', $orderIds) . ')');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Offline status for packages
     *
     * @param array $orderIds
     * @return array
     */
    private static function getPackages(array $orderIds)
    {
        $srch = new SearchBase(OrderPackage::DB_TBL, 'ordpkg');
        $srch->addMultipleFields(['ordpkg_order_id', 'ordpkg_offline']);
        $srch->addDirectCondition('ordpkg_order_id IN (' . implode(',', $orderIds) . ')');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Detail Fields
     * 
     * @return array
     */
    public static function getDetailFields(): array
    {
        return static::getListingFields() + [
            'learner.user_timezone' => 'user_timezone',
            'order_related_order_id' => 'order_related_order_id'
        ];
    }

    /**
     * Get Listing Fields
     * 
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'orders.order_id' => 'order_id',
            'orders.order_net_amount' => 'order_net_amount',
            'orders.order_type' => 'order_type',
            'orders.order_status' => 'order_status',
            'orders.order_user_id' => 'order_user_id',
            'orders.order_item_count' => 'order_item_count',
            'orders.order_pmethod_id' => 'order_pmethod_id',
            'orders.order_discount_value' => 'order_discount_value',
            'orders.order_reward_value' => 'order_reward_value',
            'orders.order_currency_code' => 'order_currency_code',
            'orders.order_currency_value' => 'order_currency_value',
            'orders.order_payment_status' => 'order_payment_status',
            'orders.order_total_amount' => 'order_total_amount',
            'order_related_order_id' => 'order_related_order_id',
            'orders.order_addedon' => 'order_addedon',
            'ordcrs.ordcrs_amount' => 'ordcrs_amount',
            'ordsplan.ordsplan_lessons' => 'ordsplan_lessons',
            'ordsplan.ordsplan_duration' => 'ordsplan_duration',
            'ordsplan.ordsplan_validity' => 'ordsplan_validity',
            'learner.user_email' => 'learner_email',
            'learner.user_first_name' => 'learner_first_name',
            'learner.user_last_name' => 'learner_last_name',
            'learner.user_country_id' => 'learner_country_id',
            'CONCAT(learner.user_first_name," ", learner.user_last_name)' => 'learner_full_name',
            'ordles_offline' => 'ordles_offline',
            'grpcls_offline' => 'grpcls_offline',
        ];
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    public static function getSearchForm(): Form
    {
        $orderType = Order::getTypeArr();
        $frm = new Form('orderSearchFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addSelectBox(Label::getLabel('LBL_Order_Type'), 'order_type', $orderType, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_PAYMENT_METHOD'), 'order_pmethod_id', PaymentMethod::getPayins(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_Date_From'), 'date_from', '', ['readonly' => 'readonly']);
        $frm->addDateField(Label::getLabel('LBL_Date_To'), 'date_to', '', ['readonly' => 'readonly']);
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear'));
        return $frm;
    }
}
