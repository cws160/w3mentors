<?php

use GuzzleHttp\Psr7\Query;

/**
 * This Controller is used for user quiz solving
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserQuizController extends DashboardController
{
    /**
     * Initialize
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType != User::LEARNER) {
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Instructions Form
     *
     * @param int $id
     */
    public function index(int $id)
    {
        $data = QuizAttempt::getById($id);
        if (empty($data)) {
            FatUtility::exitWithErrorCode(404);
        }
        if ($data['quizat_user_id'] != $this->siteUserId || $data['quizat_active'] == AppConstant::NO) {
            FatUtility::dieWithError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }

        if ($data['quizat_status'] == QuizAttempt::STATUS_IN_PROGRESS) {
            FatApp::redirectUser(MyUtility::generateUrl('UserQuiz', 'questions', [$id]));
        } elseif ($data['quizat_status'] == QuizAttempt::STATUS_COMPLETED) {
            FatApp::redirectUser(MyUtility::generateUrl('UserQuiz', 'completed', [$id]));
        }

        if ($data['quizat_status'] == QuizAttempt::STATUS_CANCELED) {
            FatUtility::dieWithError(Label::getLabel('LBL_ACCESS_TO_CANCELED_QUIZ_IS_NOT_ALLOWED'));
        } elseif (
            $data['quilin_record_type'] != AppConstant::COURSE &&
            strtotime(date('Y-m-d H:i:s')) >= strtotime($data['quilin_validity'])
        ) {
            FatUtility::dieWithError(Label::getLabel('LBL_ACCESS_TO_EXPIRED_QUIZ_IS_NOT_ALLOWED'));
        }

        $data['quilin_validity'] = MyDate::formatDate($data['quilin_validity']);
        $this->set('data', $data);
        $attempt = new QuizAttempt(0, $data['quizat_user_id']);
        $this->set('attempts', $attempt->getAttemptCount($data['quizat_quilin_id']));
        $this->set('courseQuiz', ($data['quilin_record_type'] === AppConstant::COURSE));
        $this->_template->render();
    }

    /**
     * Start Quiz
     */
    public function start()
    {
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $attempt = new QuizAttempt($id, $this->siteUserId, $this->siteUserType);
        if (!$attempt->start()) {
            FatUtility::dieJsonError($attempt->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_QUIZ_STARTED_SUCCESSFULLY'));
    }

    /**
     * Quiz questions forms
     *
     * @param int $id
     */
    public function questions(int $id)
    {
        $data = QuizAttempt::getById($id);
        if (empty($data)) {
            FatUtility::exitWithErrorCode(404);
        }
        if ($data['quizat_user_id'] != $this->siteUserId || $data['quizat_active'] == AppConstant::NO) {
            FatUtility::exitWithErrorCode(404);
        }

        if ($data['quizat_status'] == QuizAttempt::STATUS_PENDING) {
            FatApp::redirectUser(MyUtility::generateUrl('UserQuiz', 'index', [$id]));
        } elseif ($data['quizat_status'] == QuizAttempt::STATUS_COMPLETED) {
            FatApp::redirectUser(MyUtility::generateUrl('UserQuiz', 'completed', [$id]));
        }

        $endtime = $data['quilin_duration'] + strtotime($data['quizat_started']);
        if ($data['quizat_status'] == QuizAttempt::STATUS_CANCELED) {
            FatUtility::dieWithError(Label::getLabel('LBL_ACCESS_TO_CANCELED_QUIZ_IS_NOT_ALLOWED'));
        } elseif ($data['quilin_duration'] > 0 && strtotime(date('Y-m-d H:i:s')) > $endtime) {
            $quiz = new QuizAttempt($id, $this->siteUserId);
            if (!$quiz->markComplete(date('Y-m-d H:i:s', $endtime))) {
                FatUtility::dieWithError($quiz->getError());
            }
            Message::addErrorMessage(Label::getLabel('LBL_QUIZ_DURATION_IS_OVER'));
            FatApp::redirectUser(MyUtility::makeUrl('UserQuiz', 'completed', [$id]));
        } elseif ($data['quilin_duration'] == 0 && $data['quilin_record_type'] != AppConstant::COURSE && strtotime(date('Y-m-d H:i:s')) >= strtotime($data['quilin_validity'])) {
            FatUtility::dieWithError(Label::getLabel('LBL_ACCESS_TO_EXPIRED_QUIZ_IS_NOT_ALLOWED'));
        }
        
        $this->sets([
            'data' => $data,
            'attemptId' =>  $id,
            'courseQuiz' =>  ($data['quilin_record_type'] === AppConstant::COURSE)
        ]);

        $this->_template->addJs(['js/app.timer.js', 'js/jquery.cookie.js']);
        $this->_template->render();
    }

    /**
     * Quiz questions forms
     *
     * @return json
     */
    public function view()
    {
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        /* get quiz details */
        $data = QuizAttempt::getById($id);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUIZ_NOT_FOUND'));
        }
        
        /* validate logged in user */
        if ($data['quizat_user_id'] != $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }

        /* get current question & options data */
        $question = QuizLinked::getQuestionById($data['quizat_qulinqu_id']);
        if (empty($question)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUESTION_NOT_FOUND'));
        }

        /* get question attempt data */
        $srch = new SearchBase(QuizLinked::DB_TBL_QUIZ_LINKED_QUESTIONS);
        $srch->joinTable(
            QuizAttempt::DB_TBL_QUESTIONS,
            'LEFT JOIN',
            'quatqu_qulinqu_id = qulinqu_id AND quatqu_quizat_id = ' . $id
        );
        $srch->addCondition('qulinqu_quilin_id', '=', $data['quilin_id']);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['quatqu_id', 'quatqu_answer', 'qulinqu_id', 'qulinqu_order']);
        $srch->addOrder('qulinqu_order', 'ASC');
        $attemptedQues = FatApp::getDb()->fetchAll($srch->getResultSet(), 'qulinqu_id');

        $answer = [];
        $attemtQuesId = '';
        $currentQuesId = $data['quizat_qulinqu_id'];
        $attemtQuesId = $attemptedQues[$currentQuesId]['quatqu_id'];
        if (!empty($attemptedQues[$currentQuesId]['quatqu_answer'])) {
            $answer = json_decode($attemptedQues[$currentQuesId]['quatqu_answer'], 0);
        }
        if ($question['qulinqu_type'] == Question::TYPE_TEXT) {
            $answer = $answer[0] ?? '';
        }

        /* get question form */
        $frm = $this->getForm($question['qulinqu_type']);
        $frm->fill([
            'ques_id' => $data['quizat_qulinqu_id'], 'ques_attempt_id' => $id, 'quatqu_id' => $attemtQuesId,
            'ques_type' => $question['qulinqu_type'], 'ques_answer' => $answer
        ]);

        $this->sets([
            'frm' => $frm,
            'data' => $data,
            'attemptedQues' => $attemptedQues,
            'question' => $question,
            'options' => json_decode($question['qulinqu_options'], true),
            'expired' => 0
        ]);

        FatUtility::dieJsonSuccess([
            'html' => $this->_template->render(false, false, 'user-quiz/view.php', true),
            'questionNumber' => $question['qulinqu_order'],
            'totalMarks' => floatval($data['quilin_marks']),
            'progressPercent' => MyUtility::formatPercent($data['quizat_progress']),
            'progress' => $data['quizat_progress'],
        ]);
    }

    /**
     * Set question next, previous or by id
     *
     * @return json
     */
    public function setQuestion()
    {
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $next = FatApp::getPostedData('next', FatUtility::VAR_INT, AppConstant::YES);
        $quesId = FatApp::getPostedData('ques_id', FatUtility::VAR_INT, 0);
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        /* get quiz details */
        $data = QuizAttempt::getById($id);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUIZ_NOT_FOUND'));
        }

        /* validate logged in user */
        if ($data['quizat_user_id'] != $this->siteUserId || $data['quizat_active'] == AppConstant::NO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }
        
        $quiz = new QuizAttempt($id, $this->siteUserId);
        if ($quesId > 0) {
            $quiz->assignValues(['quizat_qulinqu_id' => $quesId]);
            if (!$quiz->save()) {
                FatUtility::dieJsonError($quiz->getError());
            }
        } else {
            if (!$quiz->setQuestion($next)) {
                FatUtility::dieJsonError($quiz->getError());
            }
        }
        FatUtility::dieJsonSuccess('');
    }

    /**
     * Save & fetch next/previous question
     *
     * @param int $next
     * @return json
     */
    public function saveAndNext(int $next = AppConstant::YES)
    {
        $post = FatApp::getPostedData();
        $frm = $this->getForm($post['ques_type']);
        if (!$post = $frm->getFormDataFromArray($post, ['ques_answer'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $quiz = new QuizAttempt($post['ques_attempt_id'], $this->siteUserId);
        if (!$quiz->setup($post, $next)) {
            FatUtility::dieJsonError($quiz->getError());
        }
        $msg = ($next == AppConstant::NO) ? Label::getLabel('LBL_QUIZ_SAVED_SUCCESSFULLY') : '';
        FatUtility::dieJsonSuccess([
            'id' => $post['ques_attempt_id'],
            'msg' => $msg
        ]);
    }

    /**
     * Save & Finish Quiz
     *
     * @return json
     */
    public function saveAndFinish()
    {
        $attemptId = FatApp::getPostedData('ques_attempt_id');
        $answer = FatApp::getPostedData('ques_answer', FatUtility::VAR_STRING, '');
        $quiz = new QuizAttempt($attemptId, $this->siteUserId);
        if (!empty($answer)) {
            if (!$quiz->setup(FatApp::getPostedData(), AppConstant::NO)) {
                FatUtility::dieJsonError($quiz->getError());
            }
        }
        if (!$quiz->markComplete()) {
            FatUtility::dieJsonError($quiz->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_QUIZ_COMPLETED_SUCCESSFULLY'),
            'id' => $attemptId
        ]);
    }

    /**
     * Quiz completion page
     *
     * @param int $id
     */
    public function completed(int $id)
    {
        $quiz = new QuizAttempt($id, $this->siteUserId);
        if (!$quiz->validate(QuizAttempt::STATUS_COMPLETED)) {
            FatUtility::dieWithError($quiz->getError());
        }
        $data = $quiz->getData();
        if ($data['quizat_active'] == AppConstant::NO) {
            FatUtility::dieWithError(Label::getLabel('LBL_INVALID_ACCESS'));
        }

        /* check course status */
        $courseStatus = 0;
        if ($data['quilin_record_type'] == AppConstant::COURSE) {
            $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
            $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_order_id = orders.order_id', 'orders');
            $srch->joinTable(CourseProgress::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_id = crspro.crspro_ordcrs_id', 'crspro');
            $srch->addCondition('orders.order_user_id', '=', $data['quizat_user_id']);
            $srch->addCondition('ordcrs.ordcrs_course_id', '=', $data['quilin_record_id']);
            $srch->addCondition('ordcrs.ordcrs_status', '!=', OrderCourse::CANCELLED);
            $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
            $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $srch->addFld('crspro_status');
            if ($order = FatApp::getDb()->fetch($srch->getResultSet())) {
                $courseStatus = $order['crspro_status'];
            }
        }

        $this->sets([
            'user' => User::getAttributesById($this->siteUserId, ['user_first_name', 'user_last_name']),
            'data' => $data,
            'attemptId' => $id,
            'order' => ($order) ?? [],
            'canRetake' => $quiz->canRetake(),
            'canDownloadCertificate' => $quiz->canDownloadCertificate(),
            'courseQuiz' => ($data['quilin_record_type'] === AppConstant::COURSE),
            'courseStatus' => ($courseStatus === CourseProgress::COMPLETED)
        ]);
        $this->_template->render();
    }

    /**
     * Retake quiz
     *
     * @return json
     */
    public function retake()
    {
        $id = FatApp::getPostedData('id');
        $quiz = new QuizAttempt($id, $this->siteUserId);
        if (!$quiz->retake()) {
            FatUtility::dieJsonError($quiz->getError());
        }
        Message::addMessage(Label::getLabel('LBL_QUIZ_PROGRESS_RESET_SUCCESSFULLY'));
        FatUtility::dieJsonSuccess([
            'id' => $quiz->getMainTableRecordId()
        ]);
    }

    /**
     * Download certificate
     *
     * @param int $id
     */
    public function downloadCertificate(int $id)
    {
        $quiz = new QuizAttempt($id, $this->siteUserId);
        if (!$quiz->validate(QuizAttempt::STATUS_COMPLETED)) {
            FatUtility::exitWithErrorCode(404);
        }
        if (!$quiz->canDownloadCertificate()) {
            Message::addErrorMessage($quiz->getError());
            FatApp::redirectUser(MyUtility::makeUrl('UserQuiz', 'completed', [$id]));
        }
        $_SESSION['certificate_type'] = Certificate::TYPE_QUIZ_EVALUATION;
        FatApp::redirectUser(MyUtility::makeUrl('Certificates', 'quiz', [$id]));
    }

    public function frame(int $id)
    {
        $this->set('data', QuizLinked::getAttributesById($id, 'quilin_detail'));
        $this->_template->render(false, false, '_partial/frame.php');
    }
    

    /**
     * Get question form
     *
     * @param int $type
     */
    private function getForm(int $type)
    {
        $frm = new Form('frmQuiz');
        if ($type == Question::TYPE_SINGLE) {
            $fld = $frm->addRadioButtons('', 'ques_answer', []);
            $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PLEASE_SELECT_ANSWER'));
        } elseif ($type == Question::TYPE_MULTIPLE) {
            $fld = $frm->addCheckBoxes('', 'ques_answer', []);
            $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PLEASE_SELECT_ANSWER'));
        } elseif ($type == Question::TYPE_TEXT) {
            $fld = $frm->addTextArea(Label::getLabel('LBL_ANSWER'), 'ques_answer', '', ['placeholder' => Label::getLabel('LBL_TYPE_YOUR_ANSWER_HERE')]);
        } else {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_QUESTION_TYPE'));
        }
        $fld->requirements()->setRequired();

        $fld = $frm->addHiddenField('', 'ques_type');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();

        $fld = $frm->addHiddenField('', 'ques_attempt_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();

        $fld = $frm->addHiddenField('', 'ques_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();

        $fld = $frm->addHiddenField('', 'quatqu_id');
        $fld->requirements()->setInt();

        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_&_NEXT'));
        $frm->addButton('', 'btn_skip', Label::getLabel('LBL_SKIP'));
        return $frm;
    }
}
