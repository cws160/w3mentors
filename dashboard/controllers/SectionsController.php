<?php

/**
 * This Controller is used for handling course sections
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class SectionsController extends DashboardController
{

    /**
     * Initialize sections
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
    }

    /**
     * Render Search Form
     *
     */
    public function index()
    {
        FatUtility::dieWithError(Label::getLabel('LBL_INVALID_REQUEST'));
    }

    /**
     * Search Sections
     *
     * @param int $courseId
     */
    public function search(int $courseId)
    {
        $sectionId = FatApp::getPostedData('section_id', FatUtility::VAR_INT, 0);

        $srch = new SectionSearch($this->siteLangId, $this->siteUserId, User::TEACHER);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        if ($sectionId > 0) {
            $srch->addCondition('section.section_id', '=', $sectionId);
        }
        $srch->addCondition('section.section_course_id', '=', $courseId);

        $srch->addOrder('section.section_order', 'ASC');
        $srch->doNotLimitRecords();
        $sections = $srch->fetchAndFormat();
        $courseStatus =  Course::getAttributesById($courseId, 'course_status');

        $this->set('sectionsList', $sections);
        $this->set('courseStatus', $courseStatus);

        $this->_template->render(false, false);
    }

    /**
     * Render Section Forms
     *
     * @param int $courseId
     */
    public function form(int $courseId)
    {
        $courseId = FatUtility::int($courseId);
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        /* validate course id */
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }

        $sectionId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);

        $section = [];
        $formData = [
            'section_course_id' => $courseId,
            'section_id' => $sectionId,
        ];
        if ($sectionId > 0) {
            $srch = new SectionSearch($this->siteLangId, $this->siteUserId, User::TEACHER);
            $srch->applyPrimaryConditions();
            $srch->applySearchConditions([
                'section_id' => $sectionId,
                'course_id' => $courseId
            ]);
            $srch->addSearchListingFields();
            $srch->setPageSize(1);
            if ($section = FatApp::getDb()->fetch($srch->getResultSet())) {
                $formData = $formData + $section;
            }
        }

        /* get form and fill */
        $frm = $this->getForm();
        $frm->fill($formData);
        $this->set('frm', $frm);

        $sectionDivId = $sectionId;
        if ($sectionId < 1) {
            $sectionDivId = FatApp::getPostedData('section_order', FatUtility::VAR_INT, 1) . '1';
        }
        $this->set('order', $sectionDivId);
        $this->set('sectionId', $sectionId);
        $this->_template->render(false, false);
    }

    /**
     * Get Form
     *
     */
    private function getForm(): Form
    {
        $frm = new Form('frmSection');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox(Label::getLabel('LBl_TITLE'), 'section_title');
        $fld->requirements()->setLength(1, 80);
        $fld->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBl_DESCRIPTION'), 'section_details');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 300);
        $frm->addHiddenField('', 'section_id')->requirements()->setInt();
        $frm->addHiddenField('', 'section_course_id')->requirements()->setInt();
        $frm->addButton('', 'btn_cancel', Label::getLabel('LBL_CANCEL'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Setup sections data
     *
     * @return json
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $course = new Course($post['section_course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $course = new Course($post['section_course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        if ($post['section_id'] > 0) {
            if (Section::getAttributesById($post['section_id'], 'section_course_id') != $post['section_course_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
            }
        }
        $section = new Section($post['section_id']);

        if (!$section->setup($post)) {
            FatUtility::dieJsonError($section->getError());
        }

        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Updating Sections sort order
     *
     * @param int $courseId
     * @return json
     */
    public function updateOrder(int $courseId)
    {
        $course = new Course(FatApp::getPostedData('course_id'), $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $ids = FatApp::getPostedData('order');
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $section = new Section();
        if (!$section->updateOrder($ids)) {
            FatUtility::dieJsonError($section->getError());
        }
        $lecture = new Lecture();
        if (!$lecture->resetOrder($courseId)) {
            FatUtility::dieJsonError($lecture->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_ORDER_SETUP_SUCCESSFUL'));
    }

    /**
     * function to delete section
     *
     * @param int $sectionId
     */
    public function delete($sectionId)
    {
        $sectionId = FatUtility::int($sectionId);
        if ($sectionId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $section = new Section($sectionId, $this->siteUserId);
        if (!$section->delete()) {
            FatUtility::dieJsonError($section->getError());
        }

        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_SUCCESSFULLY'));
    }
}
