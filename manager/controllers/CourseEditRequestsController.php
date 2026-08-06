<?php

/**
 * Courses Edit Requests Controller to manage edit requests
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseEditRequestsController extends AdminBaseController
{

    /**
     * Initialize Course Requests
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
        $this->objPrivilege->canViewCourseEditRequests();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditCourseEditRequests(true));
        $this->set("frmSearch", $this->getSearchForm($this->siteLangId));
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
        $frm->addTextBox(Label::getLabel('LBL_TEACHER'), 'teacher', '');
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'coedre_status', Course::getEditRequestStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'start_date', '', ['readonly' => 'readonly', 'autocomplete' => 'off',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'end_date', '', ['readonly' => 'readonly', 'autocomplete' => 'off',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'teacher_id', '');
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
        $srch = new CourseEditRequestSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = u.user_id', 'u');
        $srch->addSearchListingFields();
        $srch->addFld('course_deleted');
        $srch->addOrder('coedre_id', 'DESC');
        $srch->applySearchConditions($post);
        $srch->setPageNumber($post['page']);
        $srch->setPageSize($post['pagesize']);
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $data = $srch->fetchAndFormat();
        $this->sets([
            'arrListing' => $data,
            'requestStatus' => Course::getEditRequestStatuses(),
            'page' => $post['page'],
            'postedData' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditCourseEditRequests(true),
            'canEditUsers' => $this->objPrivilege->canEditUsers(true),
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
        if ($requestId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new CourseEditRequestSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->joinUser();
        $srch->applySearchConditions(['coedre_id' => $requestId]);
        $srch->addSearchListingFields();
        $srch->setPageSize(1);
        $courses = $srch->fetchAndFormat();
        $this->set('requestData', current($courses));
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
        $this->objPrivilege->canEditCourseEditRequests();
        $requestId = FatUtility::int($requestId);
        if ($requestId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm();
        $frm->fill(['coedre_id' => $requestId]);
        $this->set('frm', $frm);
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
        $frm->addHiddenField('', 'coedre_id', 0)->requirements()->setInt();
        $statusList = Course::getEditRequestStatuses();
        unset($statusList[Course::EDIT_REQUEST_PENDING]);
        $status = $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'coedre_status', $statusList, '', [], Label::getLabel('LBL_SELECT'));
        $status->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBL_COMMENT'), 'coedre_comment', '');
        $fld->requirements()->setRequired();
        $requiredFld = new FormFieldRequirement('coedre_comment', Label::getLabel('LBL_COMMENT'));
        $requiredFld->setRequired(true);
        $notRequiredFld = new FormFieldRequirement('coedre_comment', Label::getLabel('LBL_COMMENT'));
        $notRequiredFld->setRequired(false);
        $status->requirements()->addOnChangerequirementUpdate(Course::EDIT_REQUEST_APPROVED, 'eq', 'coedre_comment', $notRequiredFld);
        $status->requirements()->addOnChangerequirementUpdate(Course::EDIT_REQUEST_DECLINED, 'eq', 'coedre_comment', $requiredFld);
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
        $this->objPrivilege->canEditCourseEditRequests();
        $form = $this->getForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new CourseEditRequestSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->joinUser();
        $srch->applySearchConditions(['coedre_id' => $post['coedre_id']]);
        $srch->addSearchListingFields();
        $srch->addFld('user_lang_id');
        if (!$requestData = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $requestData = array_merge($requestData, $post);
        $course = new Course($requestData['coedre_course_id'], 0, 0, $requestData['user_lang_id']);
        if (!$course->updateEditRequest($requestData)) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

}
