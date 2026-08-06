<?php

/**
 * Reported Issues Controller is used for Reported Issues handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ReportedIssuesController extends AdminBaseController
{

    /**
     * Initialize Reported Issues
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewIssuesReported();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set('frm', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Render Escalated Form
     */
    public function escalated()
    {
        $frm = $this->getSearchForm();
        $frm->fill(['repiss_status' => Issue::STATUS_ESCALATED]);
        $this->set('frm', $frm);
        $this->_template->addJs('reported-issues/page-js/index.js');
        $this->_template->render();
    }

    /**
     * Search & List Reported Issue
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new IssueSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls_lang.gclang_grpcls_id = grpcls.grpcls_id AND grpcls_lang.gclang_lang_id = ' . $this->siteLangId, 'grpcls_lang');
        $srch->addFld('IFNULL(grpcls_lang.grpcls_title, grpcls.grpcls_title) as grpcls_title');
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('repiss_status', 'ASC');
        $srch->addOrder('repiss_reported_on', 'DESC');
        $srch->setPageSize($post['pageSize']);
        $srch->setPageNumber($post['page']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $rows = $srch->fetchAndFormat();
        $this->set('post', $post);
        $this->set('records', $rows);
        $this->set('recordCount', $srch->recordCount());
        $this->set('canEdit', $this->objPrivilege->canEditIssuesReported(true));
        $this->_template->render(false, false, null, false, false);
    }

    /**
     * View Issue Detail
     * 
     * @param int $issueId
     */
    public function view($issueId)
    {
        $issue = Issue::getAttributesById($issueId);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /**
         * @need to update this function 
         */
        $issueObj = new Issue($issue['repiss_id'], $issue['repiss_reported_by'], User::LEARNER);
        $issue = $issueObj->getIssueDetail($this->siteLangId);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $orderId = ($issue['repiss_record_type'] == AppConstant::GCLASS) ? $issue['ordcls_order_id'] : $issue['ordles_order_id'];
        $order = (new Order($orderId))->getOrderInfo();

        $issue['ordles_teacher_starttime'] = MyDate::convert($issue['ordles_teacher_starttime']);
        $issue['ordles_teacher_endtime'] = MyDate::convert($issue['ordles_teacher_endtime']);
        $issue['ordles_student_starttime'] = MyDate::convert($issue['ordles_student_starttime']);
        $issue['ordles_student_endtime'] = MyDate::convert($issue['ordles_student_endtime']);

        $this->sets([
            "issue" => $issue,
            "actionArr" => Issue::getActionsArr(),
            "logs" => $issueObj->getLogs(),
            "order" => $order,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Action Form
     * 
     * @param int $issueId
     */
    public function actionForm($issueId)
    {
        $this->objPrivilege->canEditIssuesReported();
        $issue = Issue::getAttributesById($issueId);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /**
         * @need to update this function 
         */
        $issueObj = new Issue($issue['repiss_id'], $issue['repiss_reported_by'], User::LEARNER);
        $issue = $issueObj->getIssueDetail($this->siteLangId);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $logs = $issueObj->getLogs();
        $lastLog = end($logs);
        $lastLog['reislo_added_on'] = MyDate::formatDate($lastLog['reislo_added_on']);
        $isGroupClass = ($issue['repiss_record_type'] == AppConstant::GCLASS);
        $frm = $this->getActionForm($isGroupClass, $issue['ordles_ordsplan_id']);
        $frm->fill([
            'reislo_repiss_id' => $issue['repiss_id'],
            'reislo_action' => $lastLog['reislo_action'] ?? ''
        ]);
        $orderId = ($issue['repiss_record_type'] == AppConstant::GCLASS) ? $issue['ordcls_order_id'] : $issue['ordles_order_id'];
        $order = (new Order($orderId))->getOrderInfo();
        $this->sets([
            'frm' => $frm,
            'logs' => $logs,
            'issue' => $issue,
            'order' => $order,
            'statusArr' => Issue::getStatusArr(),
            'actionArr' => Issue::getActionsArr(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Action
     */
    public function setupAction()
    {
        $this->objPrivilege->canEditIssuesReported();
        $frm = $this->getActionForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (Issue::ACTION_ESCALATE_TO_ADMIN == $post['reislo_action']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PLEASE_SELECT_DIFFERENT_ACTION'));
        }
        $issue = Issue::getAttributesById($post['reislo_repiss_id']);
        if (empty($issue)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($issue['repiss_status'] == Issue::STATUS_CLOSED) {
            FatUtility::dieJsonError(Label::getLabel('LBL_ISSUE_ALREADY_CLOSED'));
        }
        if ($issue['repiss_record_type'] == AppConstant::GCLASS && $post['reislo_action'] == Issue::ACTION_RESET_AND_UNSCHEDULED) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $reportedIssue = new Issue($post['reislo_repiss_id'], $this->siteAdminId, Issue::USER_TYPE_SUPPORT);
        if (!$reportedIssue->setupAction($post['reislo_action'], $post['reislo_comment'], true)) {
            FatUtility::dieJsonError($reportedIssue->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
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
        $frm->addTextBox(Label::getLabel('LBL_Teacher'), 'teacher');
        $frm->addTextBox(Label::getLabel('LBL_Learner'), 'learner');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'repiss_status', Issue::getStatusArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addTextBox(Label::getLabel('LBL_Order_Id'), 'order_id');
        $fldLbl = GroupClass::isEnabled() ? Label::getLabel('LBL_CLASS/LESSON_ID') : Label::getLabel('LBL_LESSON_ID');
        $frm->addTextBox($fldLbl, 'repiss_record_id');
        $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'repiss_record_type', AppConstant::getClassTypes(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'pageSize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'teacher_id', 0);
        $frm->addHiddenField('', 'learner_id', 0);
        $fld_submit = $frm->addSubmitButton('', 'BTN_SUBMIT', LABEL::GETLABEL('LBL_SEARCH'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Action Form
     * 
     * @param bool $isGroupClass
     * @return Form
     */
    private function getActionForm(bool $isGroupClass = false, int $ordSplanId = 0): Form
    {
        $frm = new Form('actionFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $repissId = $frm->addHiddenField('', 'reislo_repiss_id');
        $repissId->requirements()->setRequired();
        $repissId->requirements()->setIntPositive();
        $options = Issue::getActionsArr();
        unset($options[Issue::ACTION_ESCALATE_TO_ADMIN]);
        if ($isGroupClass || !empty($ordSplanId)) {
            unset($options[Issue::ACTION_RESET_AND_UNSCHEDULED]);
        }
        $frm->addSelectBox(Label::getLabel('LBL_TAKE_ACTION'), 'reislo_action', $options, '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired();
        $comment = $frm->addTextArea(Label::getLabel('LBL_ADMIN_COMMENT'), 'reislo_comment', '');
        $comment->requirements()->setRequired();
        $comment->requirements()->setLength(10, 2000);
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_Save'));
        return $frm;
    }
}
