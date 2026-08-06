<?php

/**
 * This Controller is to manage cancellation requests
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseRefundRequestsController extends AdminBaseController
{

    /**
     * Initialize Requests
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (!Course::isEnabled()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $this->objPrivilege->canViewCourseRefundRequests();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $frmSearch = $this->getSearchForm($this->siteLangId);
        $frmSearch->fill(FatApp::getQueryStringData());
        $this->set("canEdit", $this->objPrivilege->canEditCourseRefundRequests(true));
        $this->set("frmSearch", $frmSearch);
        $this->_template->render();
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
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '');
        $frm->addTextBox(Label::getLabel('LBL_LEARNER'), 'learner', '');
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'corere_status', Course::getRefundStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'start_date', '', ['readonly' => 'readonly', 'autocomplete' => 'off',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'end_date', '', ['readonly' => 'readonly', 'autocomplete' => 'off',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'learner_id', '');
        $frm->addHiddenField('', 'page', 1);
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Search & Listing
     */
    public function search()
    {
        $form = $this->getSearchForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new CourseRefundRequestSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'corere.corere_user_id = u.user_id', 'u');
        $srch->addSearchListingFields();
        $srch->applySearchConditions($post);
        $srch->setPageNumber($post['page']);
        $srch->setPageSize($post['pagesize']);
        $srch->addOrder('corere_id', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $data = $srch->fetchAndFormat();
        $this->sets([
            'arrListing' => $data,
            'requestStatus' => Course::getRefundStatuses(),
            'page' => $post['page'],
            'postedData' => $post,
            'pageSize' => $post['pagesize'],
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditCourseRefundRequests(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * View Request Detail
     *
     * @param int $requestId
     * @return html
     */
    public function view(int $requestId)
    {
        $requestId = FatUtility::int($requestId);
        if ($requestId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new CourseRefundRequestSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'corere.corere_user_id = u.user_id', 'u');
        $srch->joinTable(CourseProgress::DB_TBL, 'LEFT JOIN', 'crspro.crspro_ordcrs_id = corere.corere_ordcrs_id', 'crspro');
        $srch->addSearchListingFields();
        $srch->addMultipleFields(['IFNULL(crspro_progress, 0) as crspro_progress', 'crspro_status']);
        $srch->applySearchConditions(['corere_id' => $requestId]);
        $data = $srch->fetchAndFormat(true);
        if (!$data) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $this->set('requestData', $data);
        $this->_template->render(false, false);
    }

    /**
     * Change status form
     *
     * @param int $requestId
     * @return form
     */
    public function form(int $requestId)
    {
        $this->objPrivilege->canEditCourseRefundRequests();
        $requestId = FatUtility::int($requestId);
        if ($requestId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new SearchBase(Course::DB_TBL_REFUND_REQUEST, 'corere');
        $srch->joinTable(CourseProgress::DB_TBL, 'LEFT JOIN', 'crspro.crspro_ordcrs_id = corere.corere_ordcrs_id', 'crspro');
        $srch->doNotCalculateRecords();
        $srch->addCondition('corere_id', '=', $requestId);
        $srch->setPageSize(1);
        $srch->addMultipleFields(['IFNULL(crspro_progress, 0) as crspro_progress', 'crspro_status']);
        if (!$data = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm();
        $frm->fill(['corere_id' => $requestId]);
        $this->set('frm', $frm);
        $this->set('data', $data);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     *
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmStatus');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'corere_id', 0)->requirements()->setInt();
        $statusList = Course::getRefundStatuses();
        unset($statusList[Course::REFUND_PENDING]);
        $status = $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'corere_status', $statusList, '', [], Label::getLabel('LBL_SELECT'));
        $status->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBL_COMMENT'), 'corere_comment', '');
        $fld->requirements()->setRequired();
        $requiredFld = new FormFieldRequirement('corere_comment', Label::getLabel('LBL_COMMENT'));
        $requiredFld->setRequired(true);
        $notRequiredFld = new FormFieldRequirement('corere_comment', Label::getLabel('LBL_COMMENT'));
        $notRequiredFld->setRequired(false);
        $status->requirements()->addOnChangerequirementUpdate(Course::REFUND_APPROVED, 'eq', 'corere_comment', $notRequiredFld);
        $status->requirements()->addOnChangerequirementUpdate(Course::REFUND_DECLINED, 'eq', 'corere_comment', $requiredFld);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_UPDATE'));
        return $frm;
    }

    /**
     * Update status
     *
     * @return bool
     */
    public function updateStatus()
    {
        $this->objPrivilege->canEditCourseRefundRequests();
        $form = $this->getForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $course = new Course(0, 0, 0, $this->siteLangId);
        if (!$course->updateRefundRequestStatus($post)) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }
}
