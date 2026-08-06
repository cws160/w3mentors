<?php

/**
 * Subscription Plans Controller is used for Subscription Plans handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SubscriptionPlansController extends AdminBaseController
{

    /**
     * Initialize SubscriptionPlans
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSubscriptionPlan();
        if (!SubscriptionPlan::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("search", $this->getSearchForm($this->siteLangId));
        $this->set('canEdit', $this->objPrivilege->canEditSubscriptionPlan(true));
        $this->_template->render();
    }

    /**
     * Search & List Subscription Plans
     */
    public function search()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = 100;
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $srch = new SearchBased(SubscriptionPlan::DB_TBL, 'sp');
        $srch->joinTable(SubscriptionPlan::DB_TBL_LANG, 'LEFT JOIN', 'subplang.subplang_subplan_id = sp.subplan_id and subplang.subplang_lang_id = ' . $this->siteLangId, 'subplang');

        $srch->addMultipleFields(['sp.*', 'subplang.subplang_subplan_title as name']);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $condition = $srch->addCondition('subplang.subplang_subplan_title', 'like', '%' . $keyword . '%');
            $condition->attachCondition('sp.subplan_title', 'like', '%' . $keyword . '%', 'OR');
        }
        $srch->addOrder('subplan_active', 'DESC');
        $srch->addOrder('subplan_order', 'ASC');
        $srch->addOrder('subplan_id', 'DESC');

        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $records = $this->fetchAndFormat(FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('canEdit', $this->objPrivilege->canEditSubscriptionPlan(true));
        $this->set('activeInactiveArr', AppConstant::getActiveArr());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }


    /**
     * Render Subscription Plan Form
     * 
     * @param int $subPlanID
     */
    public function form(int $subPlanID = 0)
    {
        $this->objPrivilege->canEditSubscriptionPlan();
        $data = SubscriptionPlan::getAttributesById($subPlanID);
        $frm = $this->getForm();
        $frm->fill($data);
        $this->sets([
            'languages' => Language::getAllNames(),
            'subPlanId' => $subPlanID,
            'frm' => $frm
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup plan
     */
    public function setup()
    {
        $this->objPrivilege->canEditSubscriptionPlan();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $planID = FatUtility::int($post['subplan_id']);
        // unique identifier check
        $srch = new SearchBase(SubscriptionPlan::DB_TBL);
        $srch->addCondition('mysql_func_LOWER(subplan_title)', '=', strtolower(trim($post['subplan_title'])), 'AND', true);
        $srch->addCondition('subplan_id', '!=', $post['subplan_id']);
        $srch->doNotCalculateRecords();
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_IDENTIFIER_IS_ALREADY_IN_USE', $this->siteLangId));
        }
        if ($planID > 0) {
            $data = SubscriptionPlan::getAttributesById($planID);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
        }
        $plan = new SubscriptionPlan($planID);
        if (!$plan->saveRecord($post)) {
            FatUtility::dieJsonError($plan->getError());
        }
        $data = [
            'subPlanId' => $plan->getMainTableRecordId(),
            'msg' => Label::getLabel('LBL_SUBSCRIPTION_PLAN_SETUP_SUCCESSFUL')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Subscription Plan Lang Data
     */
    public function langForm()
    {
        $this->objPrivilege->canEditSubscriptionPlan();
        $post = FatApp::getPostedData();
        $subPlanID = $post['subplang_subplan_id'];
        $langId = $post['subplang_lang_id'];
        if (!SubscriptionPlan::getAttributesById($subPlanID, ['subplan_id'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* get lang data */
        $srch = new SearchBase(SubscriptionPlan::DB_TBL_LANG);
        $srch->addCondition('subplang_lang_id', '=', $langId);
        $srch->addCondition('subplang_subplan_id', '=', $subPlanID);
        $srch->addMultipleFields(['subplang_subplan_title', 'subplang_lang_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        /* fill form data */
        $frm = $this->getLangForm($langId, $subPlanID);
        $frm->fill($data);
        $frm->fill(['subplang_lang_id' => $langId, 'subplang_subplan_id' => $subPlanID]);
        $this->sets([
            'subPlanId' => $subPlanID,
            'langFrm' => $frm,
            'formLayout' => Language::getLayoutDirection($langId),
            'languages' => Language::getAllNames(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Lang Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditSubscriptionPlan();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['subplang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!SubscriptionPlan::getAttributesById($post['subplang_subplan_id'], 'subplan_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $subscriptionPlan = new SubscriptionPlan($post['subplang_subplan_id']);
        if (!$subscriptionPlan->setupLangData($post)) {
            FatUtility::dieJsonError($subscriptionPlan->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(SubscriptionPlan::DB_TBL_LANG, $post['subplang_subplan_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')
        ]);
    }


    public function view(int $subPlanID)
    {
        $this->objPrivilege->canViewSubscriptionPlan();
        if (empty($subPlanID)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $records = SubscriptionPlan::getByIds($this->siteLangId, [$subPlanID], false);
        if (empty($records)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $records = $this->fetchAndFormat($records);
        $this->sets([
            'plan' => current($records)
        ]);
        $this->_template->render(false, false);
    }


    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear_Search'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Form
     * 
     * @param int $subscription plan Id
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmSubscriptionPlan');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_IDENTIFIER'), 'subplan_title');
        $fld->requirements()->setRequired();
        $frm->addHiddenField('', 'subplan_id');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Lesson_duration'), 'subplan_lesson_duration', array_combine(MyUtility::getActiveSlots(), MyUtility::getActiveSlots()), '', [], '');
        $fld->requirements()->setRequired();
        $fld = $frm->addTextBox(Label::getLabel("LBL_LESSON_COUNT"), 'subplan_lesson_count');
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRange(1, 9999);
        $fld->requirements()->setRequired();
        $currency = MyUtility::getSystemCurrency();
        $fld = $frm->addTextBox(Label::getLabel("LBL_PRICE") . '[' . $currency['currency_code'] . ']', 'subplan_price');
        $fld->requirements()->setRange(1, 9999999999);
        $fld->requirements()->setRequired();
        $fld = $frm->addIntegerField(Label::getLabel("LBL_PLAN_VALIDITY"), 'subplan_validity');
        $fld->requirements()->setRange(1, 100);
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'subplan_active', AppConstant::getActiveArr(), '', [], '');
        $fld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Language Form
     * 
     * @param int $subPlan_Id
     * @param int $lang_id
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $subPlanId = 0): Form
    {
        $frm = new Form('frmsubplang');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'subplang_subplan_id');
        $fld->requirements()->setRequired();
        $fld = $frm->addHiddenField('', 'subplang_lang_id');
        $fld->requirements()->setRequired();
        $frm->addRequiredField(Label::getLabel('LBL_Plan_Name', $langId), 'subplang_subplan_title');
        Translator::addTranslatorActions($frm, $langId, $subPlanId, SubscriptionPlan::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Change Status of the Subscription Plan
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditSubscriptionPlan();
        $subPlanId = FatApp::getPostedData('subPlanId', FatUtility::VAR_INT, 0);
        if (0 == $subPlanId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $subPlan = new SubscriptionPlan($subPlanId);
        if (!$subPlan->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $subPlanData = $subPlan->getFlds();
        $status = ($subPlanData['subplan_active'] == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        $status = FatUtility::int($status);
        $subPlanId = FatUtility::int($subPlanId);
        if (1 > $subPlanId || -1 == $status) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $subPlan = new SubscriptionPlan($subPlanId);
        if (!$subPlan->changeStatus($status)) {
            FatUtility::dieJsonError($subPlan->getError());
        }
        if ($status == 0) {
            $subPlan->sendInactivePlanNotification(500, 1);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }


    /**
     * Update Sort Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditSubscriptionPlan();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $subPlan = new SubscriptionPlan();
            if (!$subPlan->updateOrder($post['subscripiton-plans'])) {
                FatUtility::dieJsonError($subPlan->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
        }
    }

    private function fetchAndFormat($rows)
    {
        $timezone = Admin::getAttributesById($this->siteAdminId, ['admin_timezone'])['admin_timezone'];
        $activeLearnerPlanIds = SubscriptionPlan::activeLearnerPlans();
        foreach ($rows as $key => $row) {
            $row['subplan_created'] = MyDate::convert($row['subplan_created'], $timezone);
            $row['subplan_updated'] = MyDate::convert($row['subplan_updated'], $timezone);
            $row['can_edit'] = $this->objPrivilege->canEditSubscriptionPlan(true) && !in_array($row['subplan_id'], $activeLearnerPlanIds);
            $rows[$key] = $row;
        }
        return $rows;
    }
}
