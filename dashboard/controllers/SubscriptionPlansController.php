<?php

/**
 * Subscription Plans Controller is used for handling Plans
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SubscriptionPlansController extends DashboardController
{

    /**
     * Initialize Plans
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (!SubscriptionPlan::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
        $this->setUserSubscription();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getQueryStringData());
        $this->set('frm', $frm);
        $this->set('autoRenew', FatUtility::int($this->siteUser['user_autorenew_subscription']));
        $this->_template->addJs('js/jquery-confirm.min.js');
        $this->_template->render(true, true);
    }

    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = $this->getSearchForm();
        $post = $frm->getFormDataFromArray($posts);
        if ($this->siteUserType == User::TEACHER) {
            $this->subscriptionUsers($post);
            exit;
        }
        $srch = new SearchBased(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordsplan.ordsplan_order_id', 'orders');
        $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_plan_id = sp.subplan_id', 'sp');
        $srch->joinTable(SubscriptionPlan::DB_TBL_LANG, 'LEFT JOIN', 'subplang.subplang_subplan_id = sp.subplan_id and subplang.subplang_lang_id = ' . $this->siteLangId, 'subplang');

        $srch->addCondition('ordsplan.ordsplan_user_id', '=',  $this->siteUserId);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordsplan.ordsplan_status', '!=',  OrderSubscriptionPlan::PENDING);
        $srch->addMultipleFields(['sp.subplan_lesson_count', 'sp.subplan_lesson_count', 'sp.subplan_lesson_duration', 'sp.subplan_price', 'IFNULL(subplang.subplang_subplan_title, sp.subplan_title) AS name', 'sp.subplan_validity', 'ordsplan.ordsplan_start_date', 'ordsplan.ordsplan_end_date', 'ordsplan.ordsplan_status', 'ordsplan.ordsplan_used_lesson_count', 'ordsplan.ordsplan_user_id', 'ordsplan.ordsplan_id', 'ordsplan_validity', 'ordsplan_duration', 'ordsplan_lessons']);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $condition = $srch->addCondition('subplang.subplang_subplan_title', 'like', '%' . $keyword . '%');
            $condition->attachCondition('sp.subplan_title', 'like', '%' . $keyword . '%', 'OR');
        }
        $srch->addOrder('ordsplan_updated', 'DESC');
        $srch->addOrder('ordsplan_id', 'DESC');
        $srch->setPageNumber($posts['pageno']);
        $srch->setPageSize($posts['pagesize']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $activeRecordKey = null;
        foreach ($records as $key => $row) {
            if ($row['ordsplan_end_date'] < date('Y-m-d H:i:s') && $row['ordsplan_status'] !=  OrderSubscriptionPlan::CANCELLED) {
                $row['ordsplan_status'] = OrderSubscriptionPlan::COMPLETED;
            }
            if ($row['ordsplan_status'] ==  OrderSubscriptionPlan::ACTIVE) {
                $activeRecordKey = $key;
            }
            $row['ordsplan_start_date'] = MyDate::convert($row['ordsplan_start_date']);
            $row['ordsplan_end_date'] = MyDate::convert($row['ordsplan_end_date']);
            $records[$key] = $row;
        }
        if ($activeRecordKey) {
            $activeRecord = $records[$activeRecordKey];
            unset($records[$activeRecordKey]);
            array_unshift($records, $activeRecord);
        }
        $this->sets([
            'post' => $post,
            'recordCount' => $srch->recordCount(),
            'subscriptions' =>  $records,
            'activePlan' => $this->activePlan
        ]);

        $this->_template->render(false, false);
    }

    public function cancelSetup()
    {
        $ordsubId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (empty($ordsubId)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $subscription = new OrderSubscriptionPlan($ordsubId, $this->siteUserId);
        if (!$subscrp = $subscription->validateSubscription(OrderSubscriptionPlan::ACTIVE)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_NO_ACTIVE_SUBSCRIPTION'));
        }
        if (!$subscription->cancel($subscrp)) {
            MyUtility::dieJsonError($subscription->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_SUBSCRIPTION_CANCELLED_SUCCESSFULLY'));
    }

    public function upgrade()
    {
        $ordsubId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (empty($ordsubId)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $subscription = new OrderSubscriptionPlan($ordsubId, $this->siteUserId);
        if (!$subscrp = $subscription->validateSubscription(OrderSubscriptionPlan::EXPIRED)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_NOT_FOUND'));
        }
        if (!$subscription->markCompleted($subscrp)) {
            MyUtility::dieJsonError($subscription->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_SUBSCRIPTION_COMPLETED_SUCCESSFULLY'));
    }

    public function autoRenew()
    {
        $autoRenew = FatApp::getPostedData('autoRenew', FatUtility::VAR_INT, 0);
        $setting = new UserSetting($this->siteUserId);
        if (!$setting->saveData(['user_autorenew_subscription' => $autoRenew])) {
            MyUtility::dieJsonError($setting->getError());
        }
    }

    public function renewPlan()
    {
        $ordsubId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (empty($ordsubId)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $subscription = new OrderSubscriptionPlan($ordsubId, $this->siteUserId);
        $walletPay = PaymentMethod::getByCode(WalletPay::KEY);
        if (empty($walletPay)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_WALLET_PAY_NOT_ACTIVE'));
        }
        if (!$subscrp = $subscription->validateSubscription(OrderSubscriptionPlan::EXPIRED)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_NOT_FOUND'));
        }
        $subplan = (new SubscriptionPlan())->getByIds(0, [$subscrp['ordsplan_plan_id']]);
        if (empty($subplan)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_NOT_FOUND'));
        }
        if ($subscrp['ordsplan_end_date'] < date('Y-m-d H:i:s')) {
            $subscrp['ordsplan_duration'] = $subplan['subplan_lesson_duration'];
            $subscrp['ordsplan_validity'] = $subplan['subplan_validity'];
            $subscrp['ordsplan_lessons'] = $subplan['subplan_lesson_count'];
            $subscrp['ordsplan_price'] = $subplan['subplan_price'];
        }
        $walletBlnc = User::getWalletBalance($subscrp['ordsplan_user_id']);
        if ($walletBlnc < $subscrp['ordsplan_amount']) {
            MyUtility::dieJsonError(Label::getLabel('LBL_LOW_WALLET_BALANCE'));
        }
        $days = $subscrp['ordsplan_validity'] * 7;
        $startEndDate = MyDate::getSubscriptionPlanDates($days);
        $subscrp['ordsplan_start_date'] = $startEndDate['ordsplan_start_date'];
        $subscrp['ordsplan_end_date'] = $startEndDate['ordsplan_end_date'];
        if (!$subscription->renew($subscrp)) {
            MyUtility::dieJsonError($subscription->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_SUBSCRIPTION_RENEWED_SUCCESSFULLY'));
    }

    public function renewForm()
    {
        if (!empty($this->activePlan)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_IS_ACTIVE'));
        }
        $ordsubId = FatApp::getPostedData('ordsubId', FatUtility::VAR_INT, 0);
        $subscription = new OrderSubscriptionPlan($ordsubId, $this->siteUserId);
        if (!$subscription = $subscription->validateSubscription(OrderSubscriptionPlan::EXPIRED)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_FOUND'));
        }
        $frm = $this->getRenewForm();
        $frm->fill(['ordsub_id' => $ordsubId]);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmSubsSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword');
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', 10)->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }
    /**
     * Get Renew Form
     * 
     * @return Form
     */
    private function getRenewForm(): Form
    {
        $frm = new Form('renewForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'ordsub_id')->requirements()->setRequired();
        $frm->addButton('', 'btn_renew', Label::getLabel('LBL_RENEW'));
        $frm->addResetButton('', 'btn_upgrade', Label::getLabel('LBL_UPGRADE'));
        return $frm;
    }

    private function subscriptionUsers($posts)
    {
        $srch = new SearchBased(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(OrderSubscriptionPlan::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_id = ordles.ordles_ordsplan_id', 'ordsplan');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = ordsplan.ordsplan_user_id', 'user');
        $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_plan_id = sp.subplan_id', 'sp');
        $srch->joinTable(SubscriptionPlan::DB_TBL_LANG, 'LEFT JOIN', 'subplang.subplang_subplan_id = sp.subplan_id and subplang.subplang_lang_id = ' . $this->siteLangId, 'subplang');
        $srch->addCondition('ordles.ordles_teacher_id', '=',  $this->siteUserId);
        $srch->addDirectCondition('ordles.ordles_ordsplan_id is NOT NULL');
        $srch->addMultipleFields(['sp.subplan_lesson_count', 'sp.subplan_lesson_count', 'sp.subplan_lesson_duration', 'sp.subplan_price', 'IFNULL(subplang.subplang_subplan_title, sp.subplan_title) AS plan_name', 'sp.subplan_validity', 'ordsplan.ordsplan_start_date', 'ordsplan.ordsplan_end_date', 'ordsplan.ordsplan_status', 'ordsplan.ordsplan_used_lesson_count', 'ordsplan.ordsplan_user_id', 'ordsplan.ordsplan_id', 'ordsplan_validity', 'ordsplan_duration', 'ordsplan_lessons', 'COUNT(ordles_id) AS lessons', 'user.user_first_name', 'user.user_last_name', 'ordsplan_amount']);
        $keyword = trim($posts['keyword'] ?? '');
        if (!empty($keyword)) {
            $condition = $srch->addCondition('subplang.subplang_subplan_title', 'like', '%' . $keyword . '%');
            $condition->attachCondition('sp.subplan_title', 'like', '%' . $keyword . '%', 'OR');
            $condition->attachCondition('mysql_func_CONCAT(user_first_name," ", user_last_name)', 'like', '%' . $keyword . '%',  'OR', true);
        }
        $srch->addGroupby('ordles_ordsplan_id');
        $srch->addOrder('ordsplan_id', 'DESC');
        $srch->setPageNumber($posts['pageno']);
        $srch->setPageSize($posts['pagesize']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($records as $key => $row) {
            if ($row['ordsplan_end_date'] < date('Y-m-d H:i:s') && $row['ordsplan_status'] !=  OrderSubscriptionPlan::CANCELLED) {
                $row['ordsplan_status'] = OrderSubscriptionPlan::COMPLETED;
            }
            $row['ordsplan_start_date'] = MyDate::convert($row['ordsplan_start_date']);
            $row['ordsplan_end_date'] = MyDate::convert($row['ordsplan_end_date']);
            $records[$key] = $row;
        }
        $this->sets([
            'post' => $posts,
            'recordCount' => $srch->recordCount(),
            'records' =>  $records
        ]);
        $this->_template->render(false, false, 'subscription-plans/subscribed-users.php');
    }
}
