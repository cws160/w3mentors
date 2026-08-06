<?php

/**
 * This class is used to handle Subscription Search  Listing
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SubscriptionSearch extends W3mentorsSearch
{

    /**
     * Initialize Subscription Search
     * 
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = Subscription::DB_TBL;
        $this->alias = 'ordsub';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordsub.ordsub_order_id', 'orders');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordsub.ordsub_teacher_id', 'teacher');
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
            $this->addDirectCondition('learner.user_deleted IS NULL');
            $this->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        } elseif ($this->userType === User::TEACHER) {
            $this->addCondition('ordsub.ordsub_teacher_id', '=', $this->userId);
            $this->addDirectCondition('teacher.user_deleted IS NULL');
            $this->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
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
        if (!empty($post['keyword'])) {
            $keyword = trim($post['keyword']);
            if ($this->userType === User::LEARNER) {
                $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
                $cond = $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
            } elseif ($this->userType === User::TEACHER) {
                $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
                $cond = $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
            } else {
                $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
                $cond = $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
                $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
                $cond->attachCondition($fullName, 'LIKE', '%' . $keyword . '%', 'OR', true);
            }
            $orderId = FatUtility::int(str_replace('O', '', $keyword));
            if (!empty($orderId)) {
                $cond->attachCondition('ordsub.ordsub_id', '=', $orderId);
                $cond->attachCondition('ordsub.ordsub_order_id', '=', $orderId);
            }
        }
        if (!empty($post['ordsub_id'])) {
            $this->addCondition('ordsub.ordsub_id', '=', $post['ordsub_id']);
        }
        if (!empty($post['order_id'])) {
            $this->addCondition('orders.order_id', '=', $post['order_id']);
        }
        if (isset($post['ordsub_status']) && $post['ordsub_status'] !== '') {
            if ($post['ordsub_status'] == Subscription::EXPIRED) {
                $currentTimeUnix = (MyDate::formatDate(date('Y-m-d H:i:s')));
                $this->addCondition('ordsub.ordsub_enddate', '<', $currentTimeUnix);
                $this->addCondition('ordsub.ordsub_status', 'NOT IN', [Subscription::CANCELLED, Subscription::COMPLETED]);
            } elseif ($post['ordsub_status'] == Subscription::ACTIVE) {
                $currentTimeUnix = (MyDate::formatDate(date('Y-m-d H:i:s')));
                $this->addCondition('ordsub.ordsub_enddate', '>', $currentTimeUnix);
                $this->addCondition('ordsub.ordsub_status', '=', $post['ordsub_status']);
            } else {
                $this->addCondition('ordsub.ordsub_status', '=', $post['ordsub_status']);
            }
        }
        if (isset($post['ordsub_offline']) && $post['ordsub_offline'] !== '') {
            $this->addCondition('ordsub_offline', '=', $post['ordsub_offline']);
        }
        if (isset($post['order_payment_status']) && $post['order_payment_status'] !== '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }
        if (!empty($post['ordsub_startdate'])) {
            $start = $post['ordsub_startdate'] . ' 00:00:00';
            $this->addCondition('ordsub.ordsub_startdate', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['ordsub_enddate'])) {
            $end = $post['ordsub_enddate'] . ' 23:59:59';
            $this->addCondition('ordsub.ordsub_enddate', '<=', MyDate::formatToSystemTimezone($end));
        }
    }

    public function canCancel(array $subsc): bool
    {
        return ($this->userType == User::LEARNER && $subsc['ordsub_status'] == Subscription::ACTIVE &&
            strtotime($subsc['ordsub_enddate']) > $subsc['ordsub_currenttime_unix']);
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
        $orderIds = array_column($rows, 'order_id');
        $statuses = Subscription::getStatuses();
        $lessosCount = static::getLessonCount($orderIds);
        $teachLangs = static::getTeachLangName($orderIds, $this->langId);
        $currentTimeUnix = strtotime(MyDate::formatDate(date('Y-m-d H:i:s')));
        foreach ($rows as $key => $row) {
            $row['ordsub_startdate'] = MyDate::formatDate($row['ordsub_startdate']);
            $row['ordsub_enddate'] = MyDate::formatDate($row['ordsub_enddate']);
            $row['order_addedon'] = MyDate::formatDate($row['order_addedon']);
            $row['ordsub_currenttime_unix'] = $currentTimeUnix;
            $row['lessonCount'] = $lessosCount[$row['order_id']] ?? 0;
            $row['canCancel'] = $this->canCancel($row);
            $row['langName'] = $teachLangs[$row['order_id']] ?? '';
            $row['statusText'] = $statuses[$row['ordsub_status']];
            $row = $this->addUserDetails($row);
            $rows[$key] = $row;
        }
        return $rows;
    }

    private function addUserDetails(array $row): array
    {
        $row['first_name'] = $row['teacher_first_name'];
        $row['last_name'] = $row['teacher_last_name'];
        $row['user_id'] = $row['ordsub_teacher_id'];
        if ($this->userType == User::TEACHER) {
            $row['first_name'] = $row['learner_first_name'];
            $row['last_name'] = $row['learner_last_name'];
            $row['user_id'] = $row['order_user_id'];
        }
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
        $frm->addHiddenField('', 'ordsub_id')->requirements()->setInt();
        $frm->addHiddenField('', 'order_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Get Detail Fields
     * 
     * @return array
     */
    public static function getDetailFields(): array
    {
        return static::getListingFields();
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
            'ordsub.ordsub_id' => 'ordsub_id',
            'ordsub.ordsub_teacher_id' => 'ordsub_teacher_id',
            'ordsub.ordsub_startdate' => 'ordsub_startdate',
            'ordsub.ordsub_enddate' => 'ordsub_enddate',
            'ordsub.ordsub_recurring' => 'ordsub_recurring',
            'ordsub.ordsub_created' => 'ordsub_created',
            'ordsub.ordsub_updated' => 'ordsub_updated',
            'ordsub.ordsub_status' => 'ordsub_status',
            'ordsub_offline' => 'ordsub_offline',
            'teacher.user_first_name' => 'teacher_first_name',
            'teacher.user_last_name' => 'teacher_last_name',
            'learner.user_first_name' => 'learner_first_name',
            'learner.user_last_name' => 'learner_last_name',
            'learner.user_deleted' => 'learner_deleted'
        ];
    }

    /**
     * Get Lesson Count
     * 
     * @param array $orderIds
     * @return array
     */
    private function getLessonCount(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->addMultipleFields(['ordles_order_id', 'COUNT(ordles_id) as total']);
        $srch->addCondition('ordles.ordles_order_id', 'IN', $orderIds);
        $srch->addGroupBy('ordles_order_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Teach Lang Name
     * 
     * @param array $orderIds
     * @param int $langId
     * @return array
     */
    private function getTeachLangName(array $orderIds, int $langId): array
    {
        if (empty($orderIds)) {
            return [];
        }
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'ordles.ordles_tlang_id = tlang.tlang_id', 'tlang');
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id =' . $langId, 'tlanglang');
        $srch->addMultipleFields(['ordles.ordles_order_id', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name']);
        $srch->addCondition('ordles.ordles_order_id', 'IN', $orderIds);
        $srch->addGroupBy('ordles_order_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }
}
