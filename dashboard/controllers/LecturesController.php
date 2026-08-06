<?php

/**
 * This Controller is used for handling lectures
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class LecturesController extends DashboardController
{

    /**
     * Initialize lectures
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
     * Search Lecture
     *
     * @param int $lectureId
     */
    public function search(int $lectureId)
    {
        /* get lectures list */
        $obj = new LectureSearch();
        $lecture = $obj->getById($lectureId);
        $this->canEdit($lecture['lecture_course_id']);
        $this->set('lecture', $lecture);
        $this->_template->render(false, false);
    }

    /**
     * Render Lecture Forms
     *
     * @param int $sectionId
     */
    public function form(int $sectionId)
    {
        $sectionId = FatUtility::int($sectionId);
        if ($sectionId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        /* validate section id */
        if (!Section::getAttributesById($sectionId, 'section_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $lectureId = FatApp::getPostedData('lecture_id', FatUtility::VAR_INT, 0);
        $courseId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        $this->canEdit($courseId);

        $data = [
            'lecture_section_id' => $sectionId,
            'lecture_course_id' => $courseId,
            'lecture_id' => $lectureId,
        ];

        if ($lectureId > 0) {
            $srch = new LectureSearch();
            $srch->applyPrimaryConditions();
            $srch->applySearchConditions([
                'lecture_id' => $lectureId,
                'section_id' => $sectionId
            ]);
            $srch->addSearchListingFields();
            $srch->setPageSize(1);
            $data = FatApp::getDb()->fetch($srch->getResultSet());
        }

        /* get form and fill */
        $frm = $this->getForm();
        $frm->fill($data);
        $this->set('frm', $frm);

        $lectureDivId = $lectureId;
        if ($lectureId < 1) {
            $lectureDivId = FatApp::getPostedData('lecture_order', FatUtility::VAR_INT, 0) . '1';
            $data['lecture_order'] = '';
        }
        $this->set('lectureDivId', $lectureDivId);
        $this->set('lecture', $data);
        $this->_template->render(false, false);
    }

    /**
     * Get Form
     *
     */
    private function getForm(): Form
    {
        $frm = new Form('frmLecture');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox(Label::getLabel('LBl_TITLE'), 'lecture_title');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 255);
        $frm->addCheckBox(Label::getLabel('LBl_FOR_PREVIEW'), 'lecture_is_trial', AppConstant::YES, [], false, AppConstant::NO);
        $frm->addHtmlEditor(Label::getLabel('LBl_DESCRIPTION'), 'lecture_details')->requirements()->setRequired();
        $fld = $frm->addHiddenField('', 'lecture_section_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addHiddenField('', 'lecture_course_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addHiddenField('', 'lecture_id')->requirements()->setInt();
        $frm->addButton('', 'btn_cancel', Label::getLabel('LBL_CANCEL'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Setup Lectures data
     *
     * @return json
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $this->canEdit($post['lecture_course_id']);
        if (Section::getAttributesById($post['lecture_section_id'], 'section_course_id') != $post['lecture_course_id']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
        }
        if ($post['lecture_id'] > 0) {
            if (Lecture::getAttributesById($post['lecture_id'], 'lecture_section_id') != $post['lecture_section_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
            }
        }
        $lecture = new Lecture($post['lecture_id']);
        if (!$lecture->setup($post)) {
            FatUtility::dieJsonError($lecture->getError());
        }
        FatUtility::dieJsonSuccess([
            'sectionId' => $post['lecture_section_id'],
            'lectureId' => $lecture->getMainTableRecordId(),
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')
        ]);
    }

    /**
     * function to delete lecture
     *
     * @param int $lectureId
     */
    public function delete($lectureId)
    {
        $lectureId = FatUtility::int($lectureId);
        if ($lectureId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $courseId = Lecture::getAttributesById($lectureId, 'lecture_course_id');
        $this->canEdit($courseId);
        $lecture = new Lecture($lectureId, $this->siteUserId);
        if (!$lecture->delete()) {
            FatUtility::dieJsonError($lecture->getError());
        }
        CourseProgress::updateCoveredLecturesOnDelete($courseId, $lectureId);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_SUCCESSFULLY'));
    }

    /**
     * Render Lecture Media Form
     *
     * @param int $lectureId
     */
    public function mediaForm(int $lectureId)
    {
        $lectureId = FatUtility::int($lectureId);
        if ($lectureId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* validate lecture id */
        $obj = new LectureSearch();
        if (!$lecture = $obj->getById($lectureId, [
            'lecture_title',
            'lecture_section_id',
            'lecture_order',
            'lecture_course_id'
        ])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->canEdit($lecture['lecture_course_id']);

        $obj = new Lecture($lectureId);
        $data = $obj->getMedia(Lecture::TYPE_RESOURCE_EXTERNAL_URL);

        $data['lecsrc_lecture_id'] = $lectureId;
        $data['lecsrc_course_id'] = $lecture['lecture_course_id'];

        /* get form and fill */
        $frm = $this->getMediaForm();
        $frm->fill($data);

        $error = $videoUrl = '';
        $video = new VideoStreamer();
        if ($data['lecsrc_link'] ?? '') {
            if (!$videoUrl = $video->getUrl($data['lecsrc_link'])) {
                $error = $video->getError();
            }
        }
        $courseStatus =  Course::getAttributesById($lecture['lecture_course_id'], 'course_status');

        $this->sets([
            'frm' => $frm,
            'lecture' => $lecture,
            'lectureId' => $lectureId,
            'lectureRes' => $data,
            'videoUrl' => $videoUrl,
            'error' => $error,
            'filesize' => MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_COURSE_PREVIEW_VIDEO)),
            'videoExtensions' => Afile::getAllowedExts(Afile::TYPE_COURSE_LECTURE_VIDEO),
            'courseStatus' => $courseStatus
        ]);

        $this->_template->render(false, false);
    }

    /**
     * Setup Lectures media
     *
     * @return json
     */
    public function setupMedia()
    {
        $frm = $this->getMediaForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData() + $_FILES)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $this->canEdit($post['lecsrc_course_id']);
        if (Lecture::getAttributesById($post['lecsrc_lecture_id'], 'lecture_course_id') != $post['lecsrc_course_id']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
        }
        if ($post['lecsrc_id'] > 0) {
            $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE);
            $srch->addCondition('lecsrc_id', '=', $post['lecsrc_id']);
            $srch->addFld('lecsrc_lecture_id');
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $resource = FatApp::getDb()->fetch($srch->getResultSet());
            if (!$resource || $resource['lecsrc_lecture_id'] != $post['lecsrc_lecture_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
            }
        }
        $lecture = new Lecture($post['lecsrc_lecture_id']);
        if (!$lecture->setupMedia($post + $_FILES)) {
            FatUtility::dieJsonError($lecture->getError());
        }
        $this->updateDuration($post['lecsrc_lecture_id'], $post['lecsrc_course_id']);
        if (!$lecture->setUpdatedDate($post['lecsrc_course_id'])) {
            FatUtility::dieJsonError($lecture->getError());
        }
        FatUtility::dieJsonSuccess([
            'lectureId' => $post['lecsrc_lecture_id'],
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')
        ]);
    }

    public function removeMedia()
    {
        $lecResourceId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $lectureId = FatApp::getPostedData('lecture_id', FatUtility::VAR_INT, 0);
        $courseId = FatApp::getPostedData('courseId', FatUtility::VAR_INT, 0);
        if ($lecResourceId < 1 || $lectureId < 1 || $courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->canEdit($courseId);
        /* validate lecture id */
        if (Lecture::getAttributesById($lectureId, 'lecture_course_id') != $courseId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
        }
        if (!Lecture::validateResource($lecResourceId, $courseId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
        }

        $lecture = new Lecture($lectureId);
        if (!$lecture->removeMedia($lecResourceId)) {
            FatUtility::dieJsonError($lecture->getError());
        }
        $this->updateDuration($lectureId, $courseId);
        if (!$lecture->setUpdatedDate($courseId)) {
            FatUtility::dieJsonError($lecture->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_VIDEO_REMOVED_SUCCESSFULLY'));
    }


    private function updateDuration($lectureId, $courseId)
    {
        if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_MUX) {
            /* update lecture duration */
            $lecture = new Lecture($lectureId);
            if (!$lecture->setDuration()) {
                FatUtility::dieJsonError($lecture->getError());
            }

            /* update section duration */
            $sectionId = Lecture::getAttributesById($lectureId, 'lecture_section_id');
            $section = new Section($sectionId);
            if (!$section->setDuration()) {
                FatUtility::dieJsonError($section->getError());
            }

            /* update course duration */
            $course = new Course($courseId);
            if (!$course->setDuration()) {
                FatUtility::dieJsonError($course->getError());
            }
        }
    }


    /**
     * Updating Lectures sort order
     *
     * @return json
     */
    public function updateOrder()
    {
        $course = new Course(FatApp::getPostedData('course_id'), $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $ids = FatApp::getPostedData('order');
        $lecture = new Lecture();
        if (!$lecture->updateOrder($ids)) {
            FatUtility::dieJsonError($lecture->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_ORDER_SETUP_SUCCESSFUL'));
    }

    /**
     * Get Media Form
     *
     * @param int $type
     */
    private function getMediaForm(): Form
    {
        $frm = new Form('frmLectureMedia');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addFileUpload(Label::getLabel('LBl_LECTURE_VIDEO'), 'lecsrc_link');
        $fld->requirements()->setRequired();
        $fld = $frm->addHiddenField('', 'lecsrc_lecture_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addHiddenField('', 'lecsrc_course_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addHiddenField('', 'lecsrc_id')->requirements()->setInt();
        $frm->addButton('', 'btn_cancel', Label::getLabel('LBL_CANCEL'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    private function canEdit($courseId)
    {
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
    }
}
