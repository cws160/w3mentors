<?php

/**
 * This class is used to handle Wallet Search
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class WalletSearch extends W3mentorsSearch
{

    /**
     * Initialize Wallet Search
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
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = orders.order_user_id', 'user');
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('orders.order_type', '=', Order::TYPE_WALLET);
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
        if (isset($post['order_payment_status']) && $post['order_payment_status'] !== '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }
        if (!empty($post['keyword'])) {
            $fullName = 'mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)';
            $cond = $this->addCondition($fullName, 'LIKE', '%' . trim($post['keyword']) . '%', 'AND', true);
            $cond->attachCondition('orders.order_id', '=', FatUtility::int(str_replace("O", "", $post['keyword'])));
        }
        if (!empty($post['date_from'])) {
            $start = $post['date_from'] . ' 00:00:00';
            $this->addCondition('orders.order_addedon', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['date_to'])) {
            $end = $post['date_to'] . ' 23:59:59';
            $this->addCondition('orders.order_addedon', '<=', MyDate::formatToSystemTimezone($end));
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
            $row['order_addedon'] = MyDate::formatDate($row['order_addedon']);
            $rows[$key] = $row;
        }
        return $rows;
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
            'orders.order_currency_code' => 'order_currency_code',
            'orders.order_currency_value' => 'order_currency_value',
            'orders.order_payment_status' => 'order_payment_status',
            'orders.order_total_amount' => 'order_total_amount',
            'orders.order_addedon' => 'order_addedon',
            'CONCAT(user.user_first_name," ", user.user_last_name)' => 'user_full_name'
        ];
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
