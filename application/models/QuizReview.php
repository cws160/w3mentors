<?php

class QuizReview extends MyAppModel
{
    public const DB_TBL = 'tbl_quiz_attempts';
    public const DB_TBL_PREFIX = 'quizat_';

    private $quiz;
    private $userId;
    private $userType;
    private $langId;

    /**
     * Initialize User Quiz
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     * @param int $langId
     */
    public function __construct(int $id = 0, int $userId = 0, int $userType = 0, int $langId = 0)
    {
        parent::__construct(static::DB_TBL, 'quizat_id', $id);
        $this->userId = $userId;
        $this->userType = $userType;
        $this->langId = $langId;
    }

    /**
     * Start Quiz Review
     *
     * @return bool
     */
    public function start()
    {
        if ($this->userType == User::TEACHER) {
            $this->setFldValue('quizat_qulinqu_id', 0);
            if (!$this->save()) {
                return false;
            }
        }
        if (!$this->validate()) {
            return false;
        }
        if (!$this->setQuestion()) {
            return false;
        }
        return true;
    }

    /**
     * Get next & previous question
     *
     * @param int $next
     * @return bool
     */
    public function setQuestion(int $next = AppConstant::YES)
    {
        /* get next question id */
        $srch = new SearchBase(QuizLinked::DB_TBL_QUIZ_LINKED_QUESTIONS);
        $srch->addCondition('qulinqu_quilin_id', '=', $this->quiz['quizat_quilin_id']);
        if ($next == AppConstant::NO) {
            $srch->addCondition('qulinqu_id', '<', $this->quiz['quizat_qulinqu_id']);
            $srch->addOrder('qulinqu_id', 'DESC');
        } else {
            $srch->addCondition('qulinqu_id', '>', $this->quiz['quizat_qulinqu_id']);
            $srch->addOrder('qulinqu_id', 'ASC');
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields(['qulinqu_id as quizat_qulinqu_id', 'qulinqu_quilin_id']);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($data)) {
            $data = ['quizat_qulinqu_id' => $this->quiz['quizat_qulinqu_id']];
        }

        /* setup question id */
        if ($this->userType == User::LEARNER) {
            if (isset($data['qulinqu_quilin_id'])) {
                $_SESSION['quiz'][$data['qulinqu_quilin_id']]['current_ques_id'] = $data['quizat_qulinqu_id'];
            }
            return true;
        }

        $this->assignValues($data);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Get data by id
     *
     * @param int $id
     * @return array
     */
    public static function getById(int $id)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quizat_quilin_id = quilin_id');
        $srch->addCondition('quizat_id', '=', $id);
        $srch->addMultipleFields([
            'quilin_title', 'quilin_detail', 'quizat_status', 'quizat_user_id', 'quilin_user_id', 'quizat_marks',
            'quilin_marks', 'quizat_scored', 'quilin_duration', 'quizat_started', 'quizat_updated',
            'quizat_evaluation', 'quizat_id', 'quizat_quilin_id', 'quizat_qulinqu_id', 'quilin_id', 'quilin_questions',
            'quizat_progress', 'quilin_record_type', 'quizat_active', 'quilin_attempts', 'quilin_type',
            'quilin_record_id'
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Validate quiz
     *
     * @return bool
     */
    public function validate()
    {
        $data = static::getById($this->getMainTableRecordId());
        if (empty($data)) {
            $this->error = Label::getLabel('LBL_QUIZ_NOT_FOUND');
            return false;
        }

        if (
            ($this->userType == User::LEARNER && $data['quizat_user_id'] != $this->userId) ||
            ($this->userType == User::TEACHER && $data['quilin_user_id'] != $this->userId)
        ) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }

        if ($data['quizat_active'] == AppConstant::INACTIVE) {
            $this->error = Label::getLabel('LBL_LINK_HAS_EXPIRED_AS_THE_USER_HAS_REATTEMPTED_THE_QUIZ');
            return false;
        }

        if ($data['quizat_status'] != QuizAttempt::STATUS_COMPLETED) {
            $this->error = Label::getLabel('LBL_ACCESS_TO_PENDING_OR_IN_PROGRESS_QUIZZES_IS_NOT_ALLOWED');
            return false;
        }

        if (
            $this->userType == User::LEARNER &&
            $data['quilin_type'] == Quiz::TYPE_NON_GRADED &&
            $data['quizat_evaluation'] == QuizAttempt::EVALUATION_PENDING
        ) {
            $this->error = Label::getLabel('LBL_EVALUATION_IS_PENDING._ACCESS_NOT_ALLOWED');
            return false;
        }
        
        if ($this->userType == User::LEARNER) {
            if (!isset($_SESSION['quiz'][$data['quilin_id']]['current_ques_id'])) {
                $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
                return false;
            }
            $data['quizat_qulinqu_id'] = $_SESSION['quiz'][$data['quilin_id']]['current_ques_id'];
        }

        $this->quiz = $data;
        return true;
    }

    /**
     * Return quiz data
     *
     * @return array
     */
    public function getData()
    {
        return $this->quiz;
    }

    /**
     * Setup manual evaluation
     *
     * @param array $data
     * @return void
     */
    public function setup(array $data)
    {
        $db = FatApp::getDb();
        $db->startTransaction();
        
        $record = new TableRecord(QuizAttempt::DB_TBL_QUESTIONS);
        $record->setFldValue('quatqu_scored', $data['quatqu_scored']);
        $record->setFldValue('quatqu_comment', $data['quatqu_comment']);
        if (!$record->update(['smt' => 'quatqu_id = ?', 'vals' => [$data['quatqu_id']]])) {
            $this->error = $record->getError();
            return false;
        }

        /* calculations */
        $quiz = new QuizAttempt($data['quizat_id']);
        if (!$quiz->setupQuizProgress()) {
            $this->error = $quiz->getError();
            $db->rollbackTransaction();
            return false;
        }

        $db->commitTransaction();
        return true;
    }

    /**
     * Submit final evaluation
     *
     * @param int $submit
     * @return bool
     */
    public function setupEvaluation(int $submit = AppConstant::NO)
    {
        $db = FatApp::getDb();
        $db->startTransaction();

        $this->setFldValue('quizat_qulinqu_id', 0);
        if (!$this->save()) {
            return false;
        }
        if ($submit == AppConstant::YES) {
            if ($this->quiz['quizat_evaluation'] != QuizAttempt::EVALUATION_PENDING) {
                $db->rollbackTransaction();
                $this->error = Label::getLabel('LBL_EVALUATION_ALREADY_SUBMITTED');
                return false;
            }
            $quiz = new QuizAttempt($this->getMainTableRecordId());
            if (!$quiz->setupEvaluation()) {
                $db->rollbackTransaction();
                $this->error = $quiz->getError();
                return false;
            }
            $this->sendQuizEvaluationSubmissionNotification();
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Send quiz evaluation notification to teacher
     */
    private function sendQuizEvaluationSubmissionNotification()
    {
        $userSiteLangId = $this->langId;

        /* get quiz details */
        $data = static::getById($this->getMainTableRecordId());

        /* get users details */
        $srch = new SearchBase(User::DB_TBL);
        $srch->addCondition('user_id', 'IN', [$data['quilin_user_id'], $data['quizat_user_id']]);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['user_first_name', 'user_last_name', 'user_email', 'user_lang_id', 'user_id']);
        $users = FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
        $learner = $users[$data['quizat_user_id']];
        $teacher = $users[$data['quilin_user_id']];

        MyUtility::setSiteLanguage(['language_id' => $learner['user_lang_id']]);
        $sessionType = AppConstant::getSessionTypes($data['quilin_record_type']);
        $score = ($data['quizat_scored']) ? $data['quizat_scored'] : 0;
        $duration = strtotime($data['quizat_updated']) - strtotime($data['quizat_started']);
        $duration = CommonHelper::convertDuration($duration, true, true);

        /* get session title */
        if ($data['quilin_record_type'] == AppConstant::GCLASS) {
            $sessionData = QuizLinked::getClassData($data['quilin_record_id']);
            $sessionTitle = $sessionData[$learner['user_lang_id']] ?? current($sessionData);
        } else {
            $sessionData = QuizLinked::getLessonData($data['quilin_record_id']);
            $sessionData = $sessionData[$learner['user_lang_id']] ?? current($sessionData);
            $sessionTitle = str_replace(
                ['{teach-lang}', '{n}'],
                [$sessionData['ordles_tlang_name'], $sessionData['ordles_duration']],
                Label::getLabel('LBL_{teach-lang},_{n}_minutes_of_Lesson')
            );
        }


        $template = 'nongraded_quiz_evaluation_submission_email';
        $mail = new FatMailer($learner['user_lang_id'], $template);
        $vars = [
            '{learner_full_name}' => ucwords($learner['user_first_name'] . ' ' . $learner['user_last_name']),
            '{teacher_full_name}' => ucwords($teacher['user_first_name'] . ' ' . $teacher['user_last_name']),
            '{session_type}' => $sessionType,
            '{session_title}' => $sessionTitle,
            '{quiz_title}' => '<a target="_blank" href="' . MyUtility::makeFullUrl('QuizReview', 'index', [$data['quizat_id']]) . '">' . $data['quilin_title'] . '</a>',
            '{progress_percentage}' => MyUtility::formatPercent($data['quizat_progress']),
            '{pass_fail_status}' => QuizAttempt::getEvaluationStatuses($data['quizat_evaluation']),
            '{marks_acheived}' => $data['quizat_marks'],
            '{score_percentage}' => MyUtility::formatPercent($score),
            '{completion_time}' => $duration,
        ];
        $mail->setVariables($vars);
        $mail->sendMail([$learner['user_email']]);

        $notifi = new Notification($learner['user_id'], Notification::TYPE_QUIZ_EVALUATION_SUBMITTED);
        $notifi->sendNotification(['{session}' => strtolower($sessionType)]);

        MyUtility::setSiteLanguage(['language_id' => $userSiteLangId]);
    }
}
