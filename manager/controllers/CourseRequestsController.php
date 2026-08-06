<?php

/**
 * Courses Requests Controller to manage approval requests
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseRequestsController extends AdminBaseController
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
        $this->objPrivilege->canViewCourseRequests();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditCourseRequests(true));
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
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'coapre_status', Course::getRequestStatuses(), '', [], Label::getLabel('LBL_SELECT'));
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

        $srch = new CourseRequestSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = u.user_id', 'u');
        $srch->addSearchListingFields();
        $srch->addFld('course_deleted');
        $srch->addOrder('coapre_id', 'DESC');
        $srch->applySearchConditions($post);
        $srch->setPageNumber($post['page']);
        $srch->setPageSize($post['pagesize']);
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $data = $srch->fetchAndFormat();

        $this->sets([
            'arrListing' => $data,
            'requestStatus' => Course::getRequestStatuses(),
            'page' => $post['page'],
            'postedData' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditCourseRequests(true),
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
        $requestId = FatUtility::int($requestId);
        if ($requestId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $srch = new CourseRequestSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->joinUser();
        $srch->applySearchConditions(['coapre_id' => $requestId]);
        $srch->addSearchListingFields();
        $srch->setPageSize(1);
        $courses = $srch->fetchAndFormat();
        $data = current($courses);
        $data['coapre_learners'] = json_decode($data['coapre_learners'], true);
        $data['coapre_learnings'] = json_decode($data['coapre_learnings'], true);
        $data['coapre_requirements'] = json_decode($data['coapre_requirements'], true);
        $data['coapre_srchtags'] = json_decode($data['coapre_srchtags'], true);
        $crsLang = (new CourseLanguage($data['coapre_clang_id'], $this->siteLangId))->getById();
        $data['coapre_clang_name'] = $crsLang['clang_name'] ?? Label::getLabel('LBL_NA');

        $data['coapre_quiz_title'] = '';
        if ($data['coapre_quilin_id'] > 0) {
            $data['coapre_quiz_title'] = QuizLinked::getAttributesById($data['coapre_quilin_id'], 'quilin_title');
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
        $this->objPrivilege->canEditCourseRequests();
        $requestId = FatUtility::int($requestId);
        if ($requestId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $frm = $this->getForm();
        $frm->fill(['coapre_id' => $requestId]);
        $this->set('frm', $frm);

        $this->_template->render(false, false);
    }

    public function frame(int $id)
    {
        $srch = new SearchBase(Course::DB_TBL_APPROVAL_REQUEST);
        $srch->addCondition('coapre_id', '=', $id);
        $srch->addFld('coapre_details');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('data', $data['coapre_details']);
        $this->_template->render(false, false, '_partial/frame.php');
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
        $frm->addHiddenField('', 'coapre_id', 0)->requirements()->setInt();
        $statusList = Course::getRequestStatuses();
        unset($statusList[Course::REQUEST_PENDING]);
        $status = $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'coapre_status', $statusList, '', [], Label::getLabel('LBL_SELECT'));
        $status->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBL_COMMENT'), 'coapre_remark', '');
        $fld->requirements()->setRequired();
        $requiredFld = new FormFieldRequirement('coapre_remark', Label::getLabel('LBL_COMMENT'));
        $requiredFld->setRequired(true);
        $notRequiredFld = new FormFieldRequirement('coapre_remark', Label::getLabel('LBL_COMMENT'));
        $notRequiredFld->setRequired(false);
        $status->requirements()->addOnChangerequirementUpdate(Course::REQUEST_APPROVED, 'eq', 'coapre_remark', $notRequiredFld);
        $status->requirements()->addOnChangerequirementUpdate(Course::REQUEST_DECLINED, 'eq', 'coapre_remark', $requiredFld);
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
        $this->objPrivilege->canEditCourseRequests();
        $form = $this->getForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new CourseRequestSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->joinUser();
        $srch->applySearchConditions(['coapre_id' => $post['coapre_id']]);
        $srch->addSearchListingFields();
        $srch->addFld('user_lang_id');
        if (!$requestData = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $requestData = array_merge($requestData, $post);
        $course = new Course($requestData['coapre_course_id'], 0, 0, $requestData['user_lang_id']);
        if (!$course->updateRequestStatus($requestData)) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

}
