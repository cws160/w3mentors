<?php

/**
 * This Controller is used for attach quizzes with classes, lessons and courses
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class AttachQuizzesController extends DashboardController
{
    /**
     * Initialize
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        if ($this->siteUserType != User::TEACHER) {
            FatUtility::exitWithErrorCode(404);
        }
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $recordType = FatApp::getPostedData('recordType', FatUtility::VAR_INT, 0);

        /* validate record type */
        if ($recordId < 1 || !array_key_exists($recordType, AppConstant::getSessionTypes())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
        }
        /* validate record id */
        $quizLinked = new QuizLinked(0, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$quizLinked->validateRecord($recordId, $recordType)) {
            FatUtility::dieJsonError($quizLinked->getError());
        }

        $frm = QuizSearch::getSearchForm();
        $frm->fill(['record_id' => $recordId, 'record_type' => $recordType]);

        $quizFrm = QuizSearch::getQuizForm();
        $quizFrm->fill([
            'quilin_record_id' => $recordId, 'quilin_record_type' => $recordType, 'quilin_user_id' => $this->siteUserId
        ]);

        $this->sets([
            'quizFrm' => $quizFrm,
            'frm' => $frm
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Search & List Quizzes
     */
    public function search()
    {
        if ($this->siteUserType != User::TEACHER) {
            FatUtility::exitWithErrorCode(404);
        }
        $frm = QuizSearch::getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        /* get already binded quizzes list */
        $srch = new SearchBase(QuizLinked::DB_TBL);
        $srch->addCondition('quilin_record_id', '=', $post['record_id']);
        $srch->addCondition('quilin_record_type', '=', $post['record_type']);
        $srch->addCondition('quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('quilin_quiz_id');
        $srch->doNotCalculateRecords();
        $quizzes = FatApp::getDb()->fetchAll($srch->getResultSet(), 'quilin_quiz_id');
        $quizzes = array_keys($quizzes);

        /* get quizzes list */
        $srch = new QuizSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applyPrimaryConditions();
        if ($post['record_type'] == AppConstant::COURSE) {
            $srch->addCondition('quiz_type', '=', Quiz::TYPE_AUTO_GRADED);
        }
        $srch->applySearchConditions($post);
        if (count($quizzes) > 0) {
            $srch->addCondition('quiz_id', 'NOT IN', $quizzes);
        }
        $srch->addSearchListingFields();
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('quiz_active', 'DESC');
        $srch->addOrder('quiz_id', 'DESC');
        $srch->addCondition('quiz_active', '=', AppConstant::YES);
        $srch->addCondition('quiz_status', '=', Quiz::STATUS_PUBLISHED);

        $this->sets([
            'quizzes' => $srch->fetchAndFormat(),
            'types' => Quiz::getTypes(),
            'status' => Quiz::getStatuses(),
            'post' => $post,
            'recordCount' => $srch->recordCount(),
        ]);

        $html = $this->_template->render(false, false, 'attach-quizzes/search.php', true, true);
        $loadMore = 0;
        $nextPage = $post['pageno'];
        if ($post['pageno'] < ceil($srch->recordCount() / $post['pagesize'])) {
            $loadMore = 1;
            $nextPage = $post['pageno'] + 1;
        }

        FatUtility::dieJsonSuccess([
            'html' => $html,
            'loadMore' => $loadMore,
            'nextPage' => $nextPage
        ]);
    }

    /**
     * Attach quizzes with Lesson, Class & Courses
     *
     * @return json
     */
    public function setup()
    {
        if ($this->siteUserType != User::TEACHER) {
            FatUtility::exitWithErrorCode(404);
        }
        $frm = QuizSearch::getQuizForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['quilin_quiz_id'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $quiz = new QuizLinked(0, $post['quilin_user_id'], $this->siteUserType, $this->siteLangId);
        if (!$quiz->setup($post['quilin_record_id'], $post['quilin_record_type'], $post['quilin_quiz_id'])) {
            FatUtility::dieJsonError($quiz->getError());
        }

        FatUtility::dieJsonSuccess(Label::getLabel('LBL_QUIZZES_ATTACHED_SUCCESSFULLY'));
    }
    
    /**
     * View quizzes list
     */
    public function view()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $recordType = FatApp::getPostedData('recordType', FatUtility::VAR_INT, 0);

        /* validate record type */
        if ($recordId < 1 || !array_key_exists($recordType, AppConstant::getSessionTypes())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
        }

        $obj = new QuizLinked(0, $this->siteUserId, $this->siteUserType);
        $quizzes = $obj->getAttachedQuizzes($recordId, $recordType, true);
        $this->set('quizzes', $quizzes);
        if ($this->siteUserType == User::TEACHER) {
            $this->set('recordType', $recordType);
            $this->set('recordId', $recordId);
            $this->_template->render(false, false, 'attach-quizzes/view.php');
        } else {
            $this->_template->render(false, false, 'attach-quizzes/attempts.php');
        }
    }

    /**
     * Deleted attached quizes
     *
     * @return json
     */
    public function delete()
    {
        if ($this->siteUserType != User::TEACHER) {
            FatUtility::exitWithErrorCode(404);
        }
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_DATA_SENT'));
        }
        $quizLink = new QuizLinked($id, $this->siteUserId, User::TEACHER, $this->siteLangId);
        if (!$quizLink->remove()) {
            FatUtility::dieJsonError($quizLink->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_QUIZZES_REMOVED_SUCCESSFULLY'));
    }
}
