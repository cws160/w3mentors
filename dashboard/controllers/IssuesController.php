<?php

/**
 * Issues Controller is used for handling Reported Issues
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class IssuesController extends DashboardController
{

    /**
     * Initialize Issues
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Reported Issues Search Form 
     * 
     * @param int $classId
     */
    public function index($classId = 0)
    {
        $frm = IssueSearch::getSearchForm();
        $frm->fill(['grpcls_id' => $classId]);
        $this->set('frm', $frm);
        $this->_template->addJs('issues/page-js/common.js');
        $this->_template->render();
    }

    /**
     * Search & List Reported Issues
     */
    public function search()
    {
        $langId = $this->siteLangId;
        $userId = $this->siteUserId;
        $userType = $this->siteUserType;
        $posts = FatApp::getPostedData();
        $frm = IssueSearch::getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $post['pagesize'] = empty($post['pagesize']) ? AppConstant::PAGESIZE : $post['pagesize'];
        $post['pageno'] = empty($post['pageno']) ? 1 : $post['pageno'];
        $srch = new IssueSearch($langId, $userId, $userType);
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls_lang.gclang_grpcls_id '
            . ' = grpcls.grpcls_id AND grpcls_lang.gclang_lang_id = ' . $langId, 'grpcls_lang');
        $srch->addFld('IFNULL(grpcls_lang.grpcls_title, grpcls.grpcls_title) as grpcls_title');
        if (!GroupClass::isEnabled()) {
            $srch->addCondition('repiss.repiss_record_type', '!=', AppConstant::GCLASS);
        }
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('repiss_status', 'ASC');
        $srch->addOrder('repiss_reported_on', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $rows = $srch->fetchAndFormat();
        foreach ($rows as $key => $row) {
            $rows[$key]['ordles_lesson_starttime'] = MyDate::convert($row['ordles_lesson_starttime']);
        }
        $this->sets([
            'post' => $post,
            'lessons' => $rows,
            'recordCount' => $srch->recordCount()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Reported Issue Form
     */
    public function form()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT);
        $recordType = FatApp::getPostedData('recordType', FatUtility::VAR_INT);
        $issue = new Issue(0, $this->siteUserId, User::LEARNER);
        if (!$issue->validateRecord($recordId, $recordType)) {
            MyUtility::dieJsonError($issue->getError());
        }

        /* get rewards data */
        $rewardDiscount = 0;
        if ($this->siteUserType == User::LEARNER) {
            $class = 'Lesson';
            $field = 'ordles_reward_discount';
            if ($recordType == AppConstant::GCLASS) {
                $class = 'OrderClass';
                $field = 'ordcls_reward_discount';
            }
            $rewardDiscount = $class::getAttributesById($recordId, $field);
        }

        $frm = $this->getForm();
        $frm->fill(['repiss_record_id' => $recordId, 'repiss_record_type' => $recordType]);
        $this->set('frm', $frm);
        $this->set('rewardDiscount', $rewardDiscount);
        $this->_template->render(false, false);
    }

    /**
     * Setup Reported Issue
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $issue = new Issue(0, $this->siteUserId, User::LEARNER);
        if (!$issue->setupIssue($this->siteLangId, $post)) {
            MyUtility::dieJsonError($issue->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Get Reported Issue Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('reportIssueFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $options = IssueReportOptions::getOptionsArray($this->siteLangId);
        $frm->addSelectBox(Label::getLabel('LBL_Subject'), 'repiss_title', $options, '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired();
        $comment = $frm->addTextArea(Label::getLabel('LBL_Comment'), 'repiss_comment', '');
        $comment->requirements()->setRequired();
        $comment->requirements()->setLength(10, 2000);
        $frm->addHiddenField('', 'repiss_record_type')->requirements()->setRequired();
        $frm->addHiddenField('', 'repiss_record_id')->requirements()->setRequired();
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SUBMIT'));
        return $frm;
    }

    /**
     * View Reported Issue Detail
     */
    public function view()
    {
        $issueId = FatApp::getPostedData('issueId');
        $issueObj = new Issue($issueId, $this->siteUserId, $this->siteUserType);
        $issue = $issueObj->getIssueDetail($this->siteLangId);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($issue['repiss_record_type'] == AppConstant::GCLASS) {
            $orderId = $issue['ordcls_order_id'];
        } else {
            $orderId = $issue['ordles_order_id'];
        }
        $logs = $issueObj->getLogs();
        $issue['ordles_lesson_starttime'] = MyDate::convert($issue['ordles_lesson_starttime']);
        $issue['ordles_teacher_starttime'] = MyDate::convert($issue['ordles_teacher_starttime']);
        $issue['ordles_teacher_endtime'] = MyDate::convert($issue['ordles_teacher_endtime']);
        $issue['ordles_student_starttime'] = MyDate::convert($issue['ordles_student_starttime']);
        $issue['ordles_student_endtime'] = MyDate::convert($issue['ordles_student_endtime']);
        $this->sets([
            'issue' => $issue,
            'logs' => $logs,
            'order' => (new Order($orderId))->getOrderInfo(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Resolve Issue Form
     */
    public function resolve()
    {
        $issueId = FatApp::getPostedData('issueId');
        $issueObj = new Issue($issueId, $this->siteUserId, $this->siteUserType);
        $issue = $issueObj->getIssueDetail($this->siteLangId);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($issue['repiss_status'] > Issue::STATUS_PROGRESS) {
            FatUtility::dieJsonError(Label::getLabel('LBL_RESOLUTION_ALREADY_PROVIDED'));
        }
        $frm = $this->getResolveForm($issue['repiss_record_type'], $issue['ordles_ordsplan_id']);
        $frm->fill(['reislo_repiss_id' => $issue['repiss_id']]);
        if ($issue['repiss_record_type'] == AppConstant::GCLASS) {
            $orderId = $issue['ordcls_order_id'];
        } else {
            $orderId = $issue['ordles_order_id'];
        }
        $this->sets([
            'frm' => $frm,
            'issue' => $issue,
            'order' => (new Order($orderId))->getOrderInfo(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Resolve Reported Issue Setup
     */
    public function resolveSetup()
    {
        $issueId = FatApp::getPostedData('reislo_repiss_id', FatUtility::VAR_INT);
        $issueObj = new Issue($issueId, $this->siteUserId, $this->siteUserType);
        $issue = $issueObj->getIssueDetail($this->siteLangId);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getResolveForm($issue['repiss_record_type'], $issue['ordles_ordsplan_id']);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        if ($issue['repiss_status'] > Issue::STATUS_PROGRESS) {
            FatUtility::dieJsonError(Label::getLabel('LBL_RESOLUTION_ALREADY_PROVIDED'));
        }
        $issueObj = new Issue($issueId, $this->siteUserId, $this->siteUserType);
        if (!$issueObj->setupAction($post['reislo_action'], $post['reislo_comment'], false)) {
            FatUtility::dieJsonError($issueObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Get Resolve Form
     * 
     * @param int $type
     * @return Form
     */
    private function getResolveForm(int $type, int $ordSubPlanId = null): Form
    {
        $frm = new Form('actionFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $repissId = $frm->addHiddenField('', 'reislo_repiss_id');
        $repissId->requirements()->setRequired();
        $repissId->requirements()->setIntPositive();
        $options = Issue::getActionsArr();
        unset($options[Issue::ACTION_ESCALATE_TO_ADMIN]);
        if (AppConstant::GCLASS === $type || !empty($ordSubPlanId)) {
            unset($options[Issue::ACTION_RESET_AND_UNSCHEDULED]);
        }
        $frm->addSelectBox(Label::getLabel('LBL_TAKE_ACTION'), 'reislo_action', $options, '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired(true);
        $comment = $frm->addTextArea(Label::getLabel('LBL_YOUR_COMMENT'), 'reislo_comment', '');
        $comment->requirements()->setRequired();
        $comment->requirements()->setLength(10, 2000);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Submit'));
        return $frm;
    }

    /**
     * Render Escalate Issue Form
     */
    public function escalate()
    {
        $issueId = FatApp::getPostedData('issueId', FatUtility::VAR_INT, 0);
        $issueObj = new Issue($issueId, $this->siteUserId, User::LEARNER);
        $issue = $issueObj->getIssueDetail($this->siteLangId);
        if ($this->siteUserType != User::LEARNER || empty($issue) || $issue['repiss_status'] != Issue::STATUS_RESOLVED) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $logs = $issueObj->getLogs();
        $log = end($logs);
        if ($log['reislo_added_by'] == $this->siteUserId) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $escalateHour = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
        $escalateDate = strtotime($issue['repiss_updated_on'] . " +" . $escalateHour . " hour");
        if ($escalateDate <= strtotime(date('Y-m-d H:i:s'))) {
            MyUtility::dieJsonError(Label::getLabel('LBL_ISSUE_ESCALATION_TIME_HAS_PASSED'));
        }
        $frm = $this->getEscalateForm();
        $frm->fill(['reislo_repiss_id' => $issue['repiss_id']]);
        $this->set('frm', $frm);
        $this->set("issue", $issue);
        $this->_template->render(false, false);
    }

    /**
     * Setup Escalated Issue
     */
    public function escalateSetup()
    {
        $frm = $this->getEscalateForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $issueId = FatUtility::int($post['reislo_repiss_id']);
        $issueObj = new Issue($issueId, $this->siteUserId, User::LEARNER);
        $issue = $issueObj->getIssueDetail($this->siteLangId);
        if (empty($issue) || $issue['repiss_status'] != Issue::STATUS_RESOLVED) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $escalateHour = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
        $escalateDate = strtotime($issue['repiss_updated_on'] . " +" . $escalateHour . " hour");
        if ($escalateDate <= strtotime(date('Y-m-d H:i:s'))) {
            MyUtility::dieJsonError(Label::getLabel('LBL_ISSUE_ESCALATION_TIME_HAS_PASSED'));
        }
        if (!$issueObj->setupAction(Issue::ACTION_ESCALATE_TO_ADMIN, $post['reislo_comment'], false)) {
            MyUtility::dieJsonError($issueObj->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Get Escalate Issue Form
     * 
     * @return Form
     */
    private function getEscalateForm(): Form
    {
        $frm = new Form('actionFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $repissId = $frm->addHiddenField('', 'reislo_repiss_id');
        $repissId->requirements()->setRequired();
        $repissId->requirements()->setIntPositive();
        $comment = $frm->addTextArea(Label::getLabel('LBL_YOUR_COMMENT'), 'reislo_comment', '');
        $comment->requirements()->setRequired();
        $comment->requirements()->setLength(10, 2000);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Submit'));
        return $frm;
    }
}
