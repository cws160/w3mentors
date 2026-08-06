<?php

/**
 * This class is used to handle GiftCard Search
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class GiftcardSearch extends W3mentorsSearch
{

    /**
     * Initialize GiftCard Search
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
        $this->joinTable(Giftcard::DB_TBL, 'INNER JOIN', 'ordgift.ordgift_order_id = orders.order_id', 'ordgift');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = orders.order_user_id', 'user');
        $this->doNotCalculateRecords();
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('orders.order_type', '=', Order::TYPE_GFTCRD);
        if ($this->userType === User::LEARNER) {
            $this->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $this->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        } elseif ($this->userType === User::TEACHER) {
            $this->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $this->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
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
            $this->addCondition('orders.order_id', '=', $post['order_id']);
        }
        if (!empty($post['order_type'])) {
            $this->addCondition('orders.order_type', '=', $post['order_type']);
        }
        if (isset($post['order_payment_status']) && $post['order_payment_status'] !== '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }
        if (isset($post['giftcard_status']) && $post['giftcard_status'] != '') {
            $this->addCondition('ordgift_status', '=', $post['giftcard_status']);
        }
        if (!empty($post['keyword'])) {
            $post['keyword'] = trim($post['keyword']);
            if ($this->userType == User::SUPPORT || $post['giftcard_type'] == GiftCard::RECEIVED) {
                $fullName = 'mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)';
                $cond = $this->addCondition($fullName, 'LIKE', '%' . $post['keyword'] . '%', 'AND', true);
                $cond->attachCondition('user.user_email', 'like', '%' . $post['keyword'] . '%');
                $cond->attachCondition('ordgift.ordgift_code', 'like', '%' . $post['keyword'] . '%');
            } else {
                $cond = $this->addCondition('ordgift_code', 'like', '%' . $post['keyword'] . '%');
                $cond->attachCondition('ordgift_receiver_name', 'like', '%' . $post['keyword'] . '%');
                $cond->attachCondition('ordgift_receiver_email', 'like', '%' . $post['keyword'] . '%');
            }
            $orderId = FatUtility::int(str_replace("O", "", $post['keyword']));
            $cond->attachCondition('ordgift.ordgift_id', '=', $orderId);
            $cond->attachCondition('ordgift.ordgift_order_id', '=', $orderId);
        }
        if (!empty($post['giftcard_type'])) {
            if ($post['giftcard_type'] == GiftCard::RECEIVED) {
                $this->addCondition('ordgift.ordgift_receiver_id', ' = ', $this->userId);
            } else {
                $this->addCondition('orders.order_user_id', ' = ', $this->userId);
            }
        }
        if (!empty($post['date_from'])) {
            $start = $post['date_from'] . ' 00:00:00';
            $this->addCondition('orders.order_addedon', ' >= ', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['date_to'])) {
            $end = $post['date_to'] . ' 23:59:59';
            $this->addCondition('orders.order_addedon', ' <= ', MyDate::formatToSystemTimezone($end));
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
        foreach ($rows as $key => $row) {
            $row['order_addedon'] = MyDate::convert($row['order_addedon']);
            $row['ordgift_expiry'] = MyDate::convert($row['ordgift_expiry']);
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Get Detail Page Fields
     * 
     * @return array
     */
    public static function getDetailFields(): array
    {
        return static::getListingFields();
    }

    /**
     * Get Listing Page Fields
     * 
     * @return array
     */
    public static function getListingFields(): array
    {
        $fields = [
            'orders.order_id' => 'order_id',
            'orders.order_type' => 'order_type',
            'orders.order_user_id' => 'order_user_id',
            'orders.order_pmethod_id' => 'order_pmethod_id',
            'orders.order_currency_code' => 'order_currency_code',
            'orders.order_currency_value' => 'order_currency_value',
            'orders.order_payment_status' => 'order_payment_status',
            'orders.order_total_amount' => 'order_total_amount',
            'orders.order_net_amount' => 'order_net_amount',
            'orders.order_addedon' => 'order_addedon',
            'ordgift.ordgift_id' => 'ordgift_id',
            'ordgift.ordgift_status' => 'ordgift_status',
            'ordgift.ordgift_expiry' => 'ordgift_expiry',
            'ordgift.ordgift_code' => 'code',
            'ordgift.ordgift_receiver_id' => 'receiver_id',
            'ordgift.ordgift_receiver_name' => 'receiver_name',
            'ordgift.ordgift_receiver_email' => 'receiver_email',
            'CONCAT(user.user_first_name, " ", user.user_last_name)' => 'user_full_name'
        ];
        return $fields;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    public static function getSearchForm(): Form
    {
        $frm = new Form('frmSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addSelectBox(Label::getLabel('LBL_Order_Type'), 'order_type', Order::getTypeArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'status', Order::getPaymentArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_Date_From'), 'date_from', '', ['readonly' => 'readonly']);
        $frm->addDateField(Label::getLabel('LBL_Date_To'), 'date_to', '', ['readonly' => 'readonly']);
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }

}
