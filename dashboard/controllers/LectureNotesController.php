<?php

/**
 * This Controller is used for handling lecture notes
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class LectureNotesController extends DashboardController
{
    /**
     * Initialize Tutorials
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
     */
    public function index()
    {
        $courseId = FatApp::getPostedData('course_id');
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $ordcrs_id = FatApp::getPostedData('ordcrs_id');
        /* get notes form */
        $frm = $this->getSearchForm();
        $frm->fill(['course_id' => $courseId, 'ordcrs_id' => $ordcrs_id]);
        $this->set('frm', $frm);
        $this->set('isPreview', FatApp::getPostedData('is_preview', FatUtility::VAR_INT, 0));
        $this->_template->render(false, false);
    }

    /**
     * Get notes listing
     *
     * @return void
     */
    public function search()
    {
        $post = FatApp::getPostedData();
        /* get notes list */
        $srch = new SearchBase(LectureNote::DB_TBL, 'lecnote');
        $srch->joinTable(Lecture::DB_TBL, 'INNER JOIN', 'lecnote.lecnote_lecture_id = lec.lecture_id', 'lec');
        $srch->addCondition('lecnote_course_id', '=', $post['course_id']);
        $srch->addCondition('lecnote_ordcrs_id', '=', $post['ordcrs_id']);
        $srch->addCondition('lecnote_user_id', '=', $this->siteUserId);
        $srch->addCondition('lecnote_notes', 'LIKE', '%' . $post['keyword'] . '%');
        $srch->addCondition('lecnote_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addMultipleFields([
            'lecnote_id',
            'lecnote_notes',
            'lecture_title',
            'lecture_order'
        ]);
        $srch->addOrder('lecnote_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['page']);
        $notes = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->sets([
            'notes' => $notes,
            'post' => $post,
            'recordCount' => $srch->recordCount(),
            'isPreview' => FatApp::getPostedData('is_preview', FatUtility::VAR_INT, 0)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Add/Edit notes form
     *
     * @param int $notesId
     * @return void
     */
    public function form(int $notesId = 0)
    {
        $frm = $this->getForm();
        $data = FatApp::getPostedData();
        if ($notesId == 0 && !empty($data['mod'])) {
            $msg = ($data['mod'] == 'quiz') ? 'LBL_NOTES_CANNOT_BE_ADDED_FOR_QUIZ' : 'LBL_NOTES_CANNOT_BE_ADDED_FOR_ARCHIVED_LECTURES';
            FatUtility::dieJsonError(Label::getLabel($msg));
        }
        if ($notesId > 0) {
            if (!$notes = (new LectureNote($notesId))->getNotesById()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            if ($notes['lecnote_user_id'] != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
            }
            $data['lecnote_id'] = $notesId;
            $data['lecnote_notes'] = $notes['lecnote_notes'];
            $data['lecnote_lecture_id'] = $notes['lecnote_lecture_id'];
        }
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Add/Edit notes data
     *
     * @return json
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($post['lecnote_lecture_id'] < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PLEASE_SELECT_A_LECTURE_FIRST_TO_ADD/EDIT_NOTES'));
        }
        $notesId = FatApp::getPostedData('lecnote_id', FatUtility::VAR_INT, 0);
        $note = new LectureNote($notesId);
        if ($notesId > 0) {
            if (!$data = $note->getNotesById()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            if ($data['lecnote_user_id'] != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
            }
        }
        $post['lecnote_user_id'] = $this->siteUserId;
        $ordcrs = new OrderCourse(FatUtility::int($post['lecnote_ordcrs_id']), $this->siteUserId);
        if (!$ordcrsData = $ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($ordcrsData['ordcrs_course_id'] != $post['lecnote_course_id']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $lecture = new Lecture(FatUtility::int($post['lecnote_lecture_id']));
        if (!$lecture->getByCourseId($post['lecnote_course_id'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$note->setup($post)) {
            FatUtility::dieJsonError($note->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Remove notes
     *
     * @return json
     */
    public function delete()
    {
        $notesId = FatApp::getPostedData('lecnote_id', FatUtility::VAR_INT, 0);
        $ordcrsId = FatApp::getPostedData('lecnote_ordcrs_id', FatUtility::VAR_INT, 0);
        $note = new LectureNote($notesId);
        if (!$data = $note->getNotesById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $ordcrs = new OrderCourse($ordcrsId, $this->siteUserId);
        if (!$ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }
        if ($data['lecnote_user_id'] != $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }
        $db = FatApp::getDb();
        $where = [
            'smt' => 'lecnote_id = ?',
            'vals' => [$notesId]
        ];
        if (!$db->updateFromArray(LectureNote::DB_TBL, ['lecnote_deleted' => date('Y-m-d H:i:s')], $where)) {
            FatUtility::dieJsonError($db->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_SUCCESSFULLY'));
    }

    /**
     * Get notes form
     *
     * @return form
     */
    private function getForm()
    {
        $frm = new Form('frmNotes');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextArea(Label::getLabel('LBl_NOTES'), 'lecnote_notes')->requirements()->setRequired();
        $frm->addHiddenField('', 'lecnote_course_id');
        $frm->addHiddenField('', 'lecnote_lecture_id');
        $frm->addHiddenField('', 'lecnote_ordcrs_id');
        $frm->addHiddenField('', 'lecnote_id');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        $frm->addResetButton('', 'btn_cancel', Label::getLabel('LBL_CANCEL'));
        return $frm;
    }

    /**
     * Get notes search form
     *
     */
    private function getSearchForm()
    {
        $frm = new Form('frmNotesSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBl_SEARCH'), 'keyword');
        $frm->addHiddenField('', 'course_id');
        $frm->addHiddenField('', 'ordcrs_id');
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setInt();
        $frm->addHiddenField('', 'page', 1)->requirements()->setInt();
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_RESET'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        return $frm;
    }
}
