<?php

/**
 * This class is used to handle Subscription Search  Listing
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderSubscriptionPlanSearch extends W3mentorsSearch
{

    /**
     * Initialize Subscription Search
     * 
     * @param int $langId
     * @param int $userType
     */
    public function __construct(int $langId)
    {
        $this->table = OrderSubscriptionPlan::DB_TBL;
        $this->alias = 'ordsplan';
        parent::__construct($langId);
        $this->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordsplan.ordsplan_order_id', 'orders');
        $this->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_plan_id = sp.subplan_id', 'sp');
        $this->joinTable(SubscriptionPlan::DB_TBL_LANG, 'LEFT JOIN', 'subplang.subplang_subplan_id = sp.subplan_id and subplang.subplang_lang_id = ' . $langId, 'subplang');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
    }

    /**
     * Apply Search Conditions
     * 
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (!empty($post['keyword'])) {
            $keyword = trim($post['keyword']);

            $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
            $cond = $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%','AND',true);
            $cond->attachCondition('subplang.subplang_subplan_title', 'like', '%' . $keyword . '%');
            $cond->attachCondition('sp.subplan_title', 'like', '%' . $keyword . '%', 'OR');
            $orderId = FatUtility::int(str_replace('O', '', $keyword));
            if (!empty($orderId)) {
                $cond->attachCondition('ordsplan.ordsplan_id', '=', $orderId);
                $cond->attachCondition('ordsplan.ordsplan_order_id', '=', $orderId);
            }
        }
        if (!empty($post['ordsplan_id'])) {
            $this->addCondition('ordsplan.ordsplan_id', '=', $post['ordsplan_id']);
        }
        if (!empty($post['order_id'])) {
            $this->addCondition('orders.order_id', '=', $post['order_id']);
        }
        if (isset($post['ordsplan_status']) && $post['ordsplan_status'] !== '') {
             if ($post['ordsplan_status'] == OrderSubscriptionPlan::ACTIVE) {
                $this->addCondition('ordsplan.ordsplan_status', 'IN', [OrderSubscriptionPlan::EXPIRED,OrderSubscriptionPlan::ACTIVE]);
            } else {
                $this->addCondition('ordsplan.ordsplan_status', '=', $post['ordsplan_status']);
            }
        }
        if (isset($post['order_payment_status']) && $post['order_payment_status'] !== '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }
        if (!empty($post['ordsplan_start_date'])) {
            $start = $post['ordsplan_start_date'] . ' 00:00:00';
            $this->addCondition('ordsplan.ordsplan_start_date', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['ordsplan_end_date'])) {
            $end = $post['ordsplan_end_date'] . ' 23:59:59';
            $this->addCondition('ordsplan.ordsplan_end_date', '<=', MyDate::formatToSystemTimezone($end));
        }
    }


    /**
     * Fetch & Format classes
     * 
     * @param bool $single
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($rows) == 0) {
            return [];
        }
        $statuses = OrderSubscriptionPlan::getStatuses();
        $currentTimeUnix = strtotime(MyDate::formatDate(date('Y-m-d H:i:s')));
        foreach ($rows as $key => $row) {
            $row['ordsplan_start_date'] = MyDate::convert($row['ordsplan_start_date']);
            $row['ordsplan_end_date'] = MyDate::convert($row['ordsplan_end_date']);
            $row['order_addedon'] = MyDate::convert($row['order_addedon']);
            $row['ordsplan_currenttime_unix'] = $currentTimeUnix;
            $row['statusText'] = $statuses[$row['ordsplan_status']];
            $row = $this->addUserDetails($row);
            $rows[$key] = $row;
        }
        return $rows;
    }

    private function addUserDetails(array $row): array
    {
        $row['first_name'] = $row['learner_first_name'];
        $row['last_name'] = $row['learner_last_name'];
        $row['user_id'] = $row['order_user_id'];
        return $row;
    }

    /**
     * Get Search Form
     * 
     * @param int $usertype
     * @return Form
     */
    public static function getSearchForm(): Form
    {
        $frm = new Form('frmSubsSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword');
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', 10)->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addHiddenField('', 'ordsplan_id')->requirements()->setInt();
        $frm->addHiddenField('', 'order_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
        return $frm;
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
            'orders.order_type' => 'order_type',
            'orders.order_user_id' => 'order_user_id',
            'orders.order_pmethod_id' => 'order_pmethod_id',
            'orders.order_discount_value' => 'order_discount_value',
            'orders.order_reward_value' => 'order_reward_value',
            'orders.order_net_amount' => 'order_net_amount',
            'orders.order_currency_code' => 'order_currency_code',
            'orders.order_status' => 'order_status',
            'orders.order_currency_value' => 'order_currency_value',
            'orders.order_payment_status' => 'order_payment_status',
            'orders.order_total_amount' => 'order_total_amount',
            'orders.order_addedon' => 'order_addedon',
            'ordsplan.ordsplan_id' => 'ordsplan_id',
            'ordsplan.ordsplan_start_date' => 'ordsplan_start_date',
            'ordsplan.ordsplan_end_date' => 'ordsplan_end_date',
            'ordsplan.ordsplan_validity' => 'ordsplan_validity',
            'ordsplan.ordsplan_duration' => 'ordsplan_duration',
            'ordsplan.ordsplan_lessons' => 'ordsplan_lessons',
            'ordsplan.ordsplan_used_lesson_count' => 'ordsplan_used_lesson_count',
            'ordsplan.ordsplan_created' => 'ordsplan_created',
            'ordsplan.ordsplan_updated' => 'ordsplan_updated',
            'ordsplan.ordsplan_status' => 'ordsplan_status',
            'learner.user_first_name' => 'learner_first_name',
            'learner.user_last_name' => 'learner_last_name',
            'learner.user_deleted' => 'learner_deleted',
            'IFNULL(subplang.subplang_subplan_title, sp.subplan_title)' => 'plan_name'
        ];
    }

    /**
     * Get Detail Fields
     * 
     * @return array
     */
    public static function getDetailFields(): array
    {
        return static::getListingFields() + [
            'ordsplan.ordsplan_refund' => 'ordsplan_refund',
            'ordsplan.ordsplan_discount' => 'ordsplan_discount',
            'ordsplan.ordsplan_reward_discount' => 'ordsplan_reward_discount',
        ];
    }
}
