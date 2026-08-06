<?php

/**
 * This Controller is used for binding questions with quiz
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class QuizQuestionsController extends DashboardController
{
    /**
     * Initialize Quizzes
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType != User::TEACHER) {
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Question Search Form
     */
    public function index()
    {
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $quiz = new Quiz($id, $this->siteUserId);
        /* validate data */
        if (!$quiz->validate()) {
            FatUtility::dieJsonError($quiz->getError());
        }

        /* get search form */
        $frm = $this->getSearchForm();
        $frm->fill(['quiz_id' => $id]);
        /* get add questions form */
        $quesFrm = $this->getform();
        $quesFrm->fill(['quiz_id' => $id]);
        $this->sets([
            'frm' => $frm,
            'quesFrm' => $quesFrm,
            'quizType' => Quiz::getAttributesById($id, 'quiz_type'),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Search & List Questions
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['ques_subcate_id']);

        /* validate data */
        $quiz = new Quiz($post['quiz_id'], $this->siteUserId);
        if (!$quiz->validate()) {
            FatUtility::dieJsonError($quiz->getError());
        }

        /* get binded questions */
        $srch = new SearchBase(Quiz::DB_TBL_QUIZ_QUESTIONS);
        $srch->addCondition('quique_quiz_id', '=', $post['quiz_id']);
        $srch->doNotCalculateRecords();
        $srch->addFld('quique_ques_id');
        $questions = FatApp::getDb()->fetchAll($srch->getResultSet(), 'quique_ques_id');
        $post['questions'] = array_keys($questions);

        /* get questions list */
        $post['type'] = Quiz::getAttributesById($post['quiz_id'], 'quiz_type');
        unset($post['quiz_id']);
        $srch = new QuestionSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applyPrimaryConditions();
        $srch->applySearchConditions($post);
        $srch->addCondition('ques.ques_status', '=', AppConstant::ACTIVE);
        $srch->addMultipleFields(['ques_id', 'ques_cate_id', 'ques_subcate_id', 'ques_type', 'ques_title']);
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('ques_id', 'DESC');
        $this->sets([
            'questions' => $srch->fetchAndFormat()
        ]);
        $html = $this->_template->render(false, false, 'quiz-questions/search.php', true);

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
     * Setup Quiz questions
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['questions'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $quiz = new Quiz($post['quiz_id'], $this->siteUserId);
        if (!$quiz->bindQuestions($post['questions'])) {
            FatUtility::dieJsonError($quiz->getError());
        }

        FatUtility::dieJsonSuccess([
            'quizId' => $post['quiz_id'],
            'msg' => Label::getLabel('MSG_SETUP_SUCCESSFUL')
        ]);
    }

    /**
     * Update questions display order
     */
    public function updateOrder()
    {
        $order = FatApp::getPostedData('order');
        $id = FatApp::getPostedData('id');
        $quiz = new Quiz($id, $this->siteUserId);
        if (!$quiz->updateOrder($order)) {
            FatUtility::dieJsonError($quiz->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Delete Binded Questions
     *
     * @return void
     */
    public function remove()
    {
        $quizId = FatApp::getPostedData('quizId', FatUtility::VAR_INT, 0);
        $quesId = FatApp::getPostedData('quesId', FatUtility::VAR_INT, 0);
        if ($quizId < 1 || $quesId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $quiz = new Quiz($quizId, $this->siteUserId);
        if (!$quiz->deleteQuestion($quesId)) {
            FatUtility::dieJsonError($quiz->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_DELETED_SUCCESSFULLY!'));
    }

    /**
     * Search Form
     */
    private function getSearchForm()
    {
        $frm = QuestionSearch::getSearchForm($this->siteLangId);
        $frm->addHiddenField('', 'quiz_id');
        return $frm;
    }

    /**
     * Get Quizzes Form
     *
     * @return Form
     */
    private function getForm()
    {
        $frm = new Form('frmQuestions');
        $quesFld = $frm->addCheckBoxes('', 'questions', []);
        $quesFld->requirements()->setRequired();
        $quesFld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PLEASE_SELECT_QUESTION(S)'));
        $fld = $frm->addHiddenField('', 'quiz_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }
}
