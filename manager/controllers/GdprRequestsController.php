<?php

/**
 * GDPR Requests is used for GDPR Requests handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GdprRequestsController extends AdminBaseController
{

    /**
     * Initialize GDPR Request
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewGdprRequests();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set('frmSrch', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List GDPR Requests
     */
    public function search()
    {
        $frmSrch = $this->getSearchForm();
        if (!$post = $frmSrch->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frmSrch->getValidationErrors()));
        }
        $srch = new SearchBased(GdprRequest::DB_TBL, 'gdpr');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'gdpr.gdpreq_user_id = user.user_id', 'user');
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $fullName = 'mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)';
            $cond = $srch->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
            $cond->attachCondition('gdpreq_reason', 'LIKE', '%' . $keyword . '%');
        }
        if (!empty($post['status'])) {
            $srch->addCondition('gdpreq_status', '=', $post['status']);
        }
        if (!empty($post['added_on_from'])) {
            $fromdate = MyDate::formatToSystemTimezone($post['added_on_from'] . ' 00:00:00');
            $srch->addCondition('gdpreq_added_on', '>=', $fromdate);
        }
        if (!empty($post['added_on_to'])) {
            $tilldate = MyDate::formatToSystemTimezone($post['added_on_to'] . ' 23:59:59');
            $srch->addCondition('gdpreq_added_on', '<=', $tilldate);
        }

        $srch->addOrder('gdpreq_added_on', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $srch->setPageNumber($post['page']);
        $srch->setPageSize($post['pageSize']);
        $gdprRequests = FatApp::getDb()->fetchAll($srch->getResultSet());
        $gdprRequests = $this->fetchAndFormat($gdprRequests);
        $this->set('postedData', $post);
        $this->set('gdprRequests', $gdprRequests);
        $this->set('page', $post['page']);
        $this->set('pageSize', $post['pageSize']);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('canEdit', $this->objPrivilege->canEditGdprRequests(true));
        $this->_template->render(false, false);
    }

    /**
     * View GDPR request
     */
    public function view()
    {
        $this->objPrivilege->canEditGdprRequests();

        $requestId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($requestId <= 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new SearchBase(GdprRequest::DB_TBL, 'gdpr');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'gdpr.gdpreq_user_id = u.user_id', 'u');
        $srch->addCondition('gdpreq_id', '=', $requestId);
        $srch->doNotCalculateRecords();
        $reqDetail = FatApp::getDb()->fetchAll($srch->getResultSet());
        $reqDetail = $this->fetchAndFormat($reqDetail, true);
        if (empty($reqDetail)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($reqDetail['gdpreq_status'] == GdprRequest::STATUS_PENDING) {
            $frm = $this->getChangeStatusForm();
            $frm->fill($reqDetail);
            $this->set("frm", $frm);
        }
        $this->set("data", $reqDetail);

        $this->_template->render(false, false);
    }

    /**
     * Get Search Form

     * @return Form
     */
    protected function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Search_By_Keyword'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addHiddenField('', 'pageSize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        $statuses = GdprRequest::getStatusArr();
        $frm->addSelectBox(Label::getLabel('LBl_Status'), 'status', $statuses, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBl_Added_On_From'), 'added_on_from', '', ['class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBl_Added_On_To'), 'added_on_to', '', ['class' => 'small dateTimeFld field--calender']);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addButton('', 'btn_reset', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Get Status Form
     * 
     * @return Form
     */
    private function getChangeStatusForm(): Form
    {
        $frm = new Form('changeStatusForm');
        $frm = CommonHelper::setFormProperties($frm);
        $status = GdprRequest::getStatusArr();
        unset($status[GdprRequest::STATUS_PENDING]);
        $status = $frm->addSelectBox(Label::getLabel('LBL_REQUEST_STATUS'), 'gdpreq_status', $status, '', [], Label::getLabel('LBL_SELECT'));
        $status->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBL_COMMENT'), 'gdpreq_comment', '');
        $fld->requirements()->setRequired();
        $requiredFld = new FormFieldRequirement('gdpreq_comment', Label::getLabel('LBL_COMMENT'));
        $requiredFld->setRequired(true);
        $notRequiredFld = new FormFieldRequirement('gdpreq_comment', Label::getLabel('LBL_COMMENT'));
        $notRequiredFld->setRequired(false);
        $status->requirements()->addOnChangerequirementUpdate(GdprRequest::STATUS_APPROVED, 'eq', 'gdpreq_comment', $notRequiredFld);
        $status->requirements()->addOnChangerequirementUpdate(GdprRequest::STATUS_DECLINED, 'eq', 'gdpreq_comment', $requiredFld);
        $frm->addHiddenField('', 'gdpreq_id', 0);
        $frm->addSubmitButton('', 'btn_submit', 'Update');
        return $frm;
    }

    /**
     * Update GDPR Request Status
     */
    public function updateStatus()
    {
        $form = $this->getChangeStatusForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $gdpr = new GdprRequest($post['gdpreq_id']);
        if (!$gdpr->updateStatus($post)) {
            FatUtility::dieJsonError($gdpr->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_UPDATED_SUCCESSFULLY'));
    }

    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['gdpreq_added_on'] = MyDate::formatDate($row['gdpreq_added_on']);
            $row['gdpreq_updated_on'] = MyDate::formatDate($row['gdpreq_updated_on']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
