<?php

/**
 * Courses Edit Requests Controller to manage edit requests
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseEditRequestsController extends DashboardController
{

    /**
     * Initialize Course Requests
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType == User::LEARNER || !Course::isEnabled()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("frm", $this->getSearchForm($this->siteLangId));
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
        $frm->addTextBox(Label::getLabel('LBL_COURSE_TITLE'), 'keyword', '');
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'coedre_status', Course::getEditRequestStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', AppConstant::PAGESIZE)->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addHiddenField('', 'teacher_id', '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
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
        $srch = new CourseEditRequestSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = u.user_id', 'u');
        $srch->addSearchListingFields();
        $srch->applySearchConditions(['teacher_id' => $this->siteUserId]);
        $srch->addFld('course_deleted');
        $srch->addOrder('coedre_id', 'DESC');
        $srch->applySearchConditions($post);
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $data = $srch->fetchAndFormat();
        $this->sets([
            'editRequests' => $data,
            'requestStatus' => Course::getEditRequestStatuses(),
            'post' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
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
        $srch = new CourseEditRequestSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = u.user_id', 'u');
        $srch->applySearchConditions(['coedre_id' => $requestId]);
        $srch->addSearchListingFields();
        $srch->setPageSize(1);
        $courses = $srch->fetchAndFormat();
        $this->set('requestData', current($courses));
        $this->_template->render(false, false);
    }
}
