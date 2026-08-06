<?php

class QuizAttempt extends MyAppModel
{
    public const DB_TBL = 'tbl_quiz_attempts';
    public const DB_TBL_PREFIX = 'quizat_';
    public const DB_TBL_QUESTIONS = 'tbl_quiz_attempts_questions';

    const STATUS_PENDING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELED = 3;

    const EVALUATION_PENDING = 0;
    const EVALUATION_PASSED = 1;
    const EVALUATION_FAILED = 2;

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
     * Get Quiz Status
     *
     * @param int $key
     * @return string|array
     */
    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::STATUS_PENDING => Label::getLabel('LBL_PENDING'),
            static::STATUS_IN_PROGRESS => Label::getLabel('LBL_IN_PROGRESS'),
            static::STATUS_COMPLETED => Label::getLabel('LBL_COMPLETED'),
            static::STATUS_CANCELED => Label::getLabel('LBL_CANCELED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Quiz Status
     *
     * @param int $key
     * @return string|array
     */
    public static function getEvaluationStatuses(int $key = null)
    {
        $arr = [
            static::EVALUATION_PENDING => Label::getLabel('LBL_PENDING'),
            static::EVALUATION_PASSED => Label::getLabel('LBL_PASS'),
            static::EVALUATION_FAILED => Label::getLabel('LBL_FAIL')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Start Quiz
     *
     * @return bool
     */
    public function start()
    {
        /* validate */
        if (!$this->validate(QuizAttempt::STATUS_PENDING)) {
            return false;
        }
        if ($this->quiz['quilin_record_type'] != AppConstant::COURSE && strtotime(date('Y-m-d H:i:s')) >= strtotime($this->quiz['quilin_validity'])) {
            $this->error = Label::getLabel('LBL_ACCESS_TO_EXPIRED_QUIZ_IS_NOT_ALLOWED');
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();

        $this->assignValues([
            'quizat_status' => static::STATUS_IN_PROGRESS,
            'quizat_started' => date('Y-m-d H:i:s'),
        ]);
        if (!$this->save()) {
            return false;
        }

        if (!$this->setQuestion()) {
            $db->rollbackTransaction();
            return false;
        }

        $db->commitTransaction();
        return true;
    }

    /**
     * Setup answers submitted by the user
     *
     * @param array $data
     * @param int   $next
     * @return bool
     */
    public function setup(array $data, int $next = AppConstant::NO)
    {
        if (!$this->validate(QuizAttempt::STATUS_IN_PROGRESS)) {
            return false;
        }

        $question = QuizLinked::getQuestionById($data['ques_id']);
        if (empty($question)) {
            $this->error = Label::getLabel('LBL_QUESTION_NOT_FOUND');
            return false;
        }
        if ($question['qulinqu_type'] != $data['ques_type']) {
            $this->error = Label::getLabel('LBL_INVALID_QUESTION_TYPE');
            return false;
        }

        $answer = $data['ques_answer'];
        if ($data['ques_type'] == Question::TYPE_TEXT) {
            $answer = [$data['ques_answer']];
        }
        $assignValues = [
            'quatqu_quizat_id' => $data['ques_attempt_id'],
            'quatqu_id' => $data['quatqu_id'],
            'quatqu_qulinqu_id' => $data['ques_id'],
            'quatqu_answer' => json_encode($answer),
        ];

        $db = FatApp::getDb();
        $db->startTransaction();
        $quesAttempt = new TableRecord(static::DB_TBL_QUESTIONS);
        $quesAttempt->assignValues($assignValues);
        if (!$quesAttempt->addNew([], $assignValues)) {
            $this->error = $quesAttempt->getError();
            return false;
        }

        if ($question['qulinqu_type'] != Question::TYPE_TEXT) {
            $assignValues['quatqu_id'] = empty($data['quatqu_id']) ? $quesAttempt->getId() : $data['quatqu_id'];
            if (!$this->setupQuesScore($assignValues)) {
                $db->rollbackTransaction();
                return false;
            }
        }

        /* calculations */
        if (!$this->setupQuizProgress()) {
            $db->rollbackTransaction();
            return false;
        }

        if ($next == AppConstant::YES) {
            if (!$this->setQuestion(AppConstant::YES)) {
                $db->rollbackTransaction();
                return false;
            }
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Mark quiz complete
     *
     * @param string $endTime
     * @return bool
     */
    public function markComplete(string $endTime = '')
    {
        if (!$this->validate(QuizAttempt::STATUS_IN_PROGRESS)) {
            return false;
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        if (empty($endTime)) {
            $endTime = date('Y-m-d H:i:s');
            $completionTime = strtotime($this->quiz['quizat_started']) + $this->quiz['quilin_duration'];
            if ($this->quiz['quilin_duration'] > 0 && strtotime($endTime) > $completionTime) {
                $endTime = date('Y-m-d H:i:s', $completionTime);
            }
        }
        $this->assignValues([
            'quizat_qulinqu_id' => 0,
            'quizat_status' => QuizAttempt::STATUS_COMPLETED,
            'quizat_updated' => $endTime
        ]);
        if (!$this->save()) {
            return false;
        }

        /* calculations */
        if ($this->quiz['quilin_type'] == Quiz::TYPE_AUTO_GRADED && !$this->setupEvaluation()) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();

        if ($this->quiz['quilin_record_type'] != AppConstant::COURSE) {
            $this->sendQuizCompletionNotification();
        }
        return true;
    }

    /**
     * Setup quiz user entries
     *
     * @param int $id
     * @return bool
     */
    public function setupUserQuiz(int $id)
    {
        $db = FatApp::getDb();
        $where = ['smt' => 'quizat_quilin_id = ? AND quizat_user_id = ?', 'vals' => [$id, $this->userId]];
        if (!$db->updateFromArray(static::DB_TBL, ['quizat_active' => AppConstant::NO], $where)) {
            $this->error = $db->getError();
            return false;
        }
        $this->assignValues([
            'quizat_quilin_id' => $id,
            'quizat_user_id' => $this->userId,
            'quizat_status' => static::STATUS_PENDING,
            'quizat_active' => AppConstant::YES,
            'quizat_created' => date('Y-m-d H:i:s'),
        ]);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Get total attempts count
     *
     * @param int $quizLinkId
     * @return int
     */
    public function getAttemptCount(int $quizLinkId)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('quizat_user_id', '=', $this->userId);
        $srch->addCondition('quizat_quilin_id', '=', $quizLinkId);
        $srch->addCondition('quizat_status', '=', static::STATUS_COMPLETED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('IFNULL(COUNT(quizat_id), 0) as attempts');
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        return $data['attempts'];
    }

    /**
     * Get data by id
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quizat_quilin_id = quilin_id');
        $srch->addCondition('quizat_id', '=', $id);
        $srch->addMultipleFields([
            'quilin_title', 'quilin_detail', 'quilin_type', 'quilin_questions', 'quilin_duration', 'quilin_record_type',
            'quilin_attempts', 'quilin_marks', 'quilin_passmark', 'quilin_validity', 'quilin_certificate',
            'quilin_user_id', 'quizat_status', 'quizat_id', 'quizat_user_id', 'quizat_qulinqu_id', 'quizat_progress',
            'quilin_id', 'quizat_quilin_id', 'quizat_evaluation', 'quilin_passmsg', 'quilin_failmsg', 'quizat_marks',
            'quizat_scored', 'quizat_started', 'quizat_updated', 'quizat_active', 'quizat_certificate_number',
            'quilin_record_id'
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get next & previous question
     *
     * @param int $next
     * @return bool
     */
    public function setQuestion(int $next = AppConstant::YES)
    {
        if (!$this->validate(QuizAttempt::STATUS_IN_PROGRESS)) {
            return false;
        }

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
        $srch->addFld('qulinqu_id as quizat_qulinqu_id');
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($data)) {
            $data = ['quizat_qulinqu_id' => $this->quiz['quizat_qulinqu_id']];
        }

        /* setup question id */
        $this->assignValues($data);
        if (!$this->save()) {
            return false;
        }

        return true;
    }

    /**
     * Validate quiz details
     *
     * @param int $status
     * @return bool
     */
    public function validate(int $status)
    {
        $quiz = static::getById($this->getMainTableRecordId());
        if (empty($quiz)) {
            $this->error = Label::getLabel('LBL_QUIZ_NOT_FOUND');
            return false;
        }
        /* validate logged in user */
        if ($quiz['quizat_user_id'] != $this->userId || $quiz['quizat_active'] == AppConstant::NO) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }
        if ($quiz['quizat_status'] != $status) {
            if ($status == static::STATUS_IN_PROGRESS) {
                $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS_TO_UNATTENDED_OR_COMPLETED_QUIZ');
            } elseif ($status == static::STATUS_PENDING) {
                $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS_TO_IN_PROGRESS_OR_COMPLETED_QUIZ');
            } elseif ($status == static::STATUS_COMPLETED) {
                $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS_TO_IN_PROGRESS_OR_PENDING_QUIZ');
            } elseif ($status == static::STATUS_CANCELED) {
                $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS_TO_CANCELED_QUIZ');
            }
            return false;
        }
        $this->quiz = $quiz;
        return true;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function retake()
    {
        if (!$this->validate(QuizAttempt::STATUS_COMPLETED)) {
            return false;
        }
        if (!$this->canRetake()) {
            return false;
        }
        $this->mainTableRecordId = 0;
        if (!$this->setupUserQuiz($this->quiz['quizat_quilin_id'])) {
            return false;
        }
        return true;
    }

    /**
     * Validate retake permission
     *
     * @return bool
     */
    public function canRetake()
    {
        if (!empty($this->quiz['quizat_certificate_number'])) {
            $this->error = Label::getLabel('LBL_RETAKE_NOT_ALLOWED');
            return false;
        }
        if ($this->quiz['quilin_record_type'] != AppConstant::COURSE && strtotime($this->quiz['quilin_validity']) - strtotime(date('Y-m-d H:i:s')) < 0) {
            $this->error = Label::getLabel('LBL_RETAKE_ON_EXPIRED_QUIZ_IS_NOT_ALLOWED');
            return false;
        }
        if ($this->quiz['quizat_evaluation'] == QuizAttempt::EVALUATION_PENDING) {
            $this->error = Label::getLabel('LBL_RETAKE_IS_NOT_ALLOWED_BEFORE_EVALUATION_IS_COMPLETE');
            return false;
        }
        if ($this->quiz['quilin_attempts'] == $this->getAttemptCount($this->quiz['quizat_quilin_id'])) {
            $this->error = Label::getLabel('LBL_REATTEMPT_LIMIT_HAS_BEEN_EXCEEDED');
            return false;
        }
        return true;
    }

    /**
     * Validate certificate download permission
     *
     * @return bool
     */
    public function canDownloadCertificate()
    {
        if ($this->quiz['quilin_record_type'] == AppConstant::COURSE) {
            $this->error = Label::getLabel('LBL_CERTIFICATE_NOT_AVAILABLE');
            return false;
        }
        if (
            $this->quiz['quilin_certificate'] == AppConstant::NO ||
            $this->quiz['quizat_evaluation'] == static::EVALUATION_PENDING
        ) {
            $this->error = Label::getLabel('LBL_CERTIFICATE_NOT_AVAILABLE');
            return false;
        }
        if ($this->quiz['quizat_evaluation'] == static::EVALUATION_FAILED) {
            $this->error = Label::getLabel('LBL_CERTIFICATE_CANNOT_BE_GENERATED_FOR_FAILED_QUIZ.');
            return false;
        }
        return true;
    }

    /**
     * Get quiz data
     *
     * @return array
     */
    public function getData()
    {
        return $this->quiz;
    }

    /**
     * Cron to cancel incomplete quizzes
     *
     * @return bool
     */
    public function cancelIncompleteQuizzes()
    {
        $srch = new SearchBase(QuizAttempt::DB_TBL);
        $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quilin_id = quizat_quilin_id');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = quizat_user_id');
        $srch->doNotCalculateRecords();
        $cnd = $srch->addCondition('quizat_status', '=', QuizAttempt::STATUS_IN_PROGRESS);
        $cnd->attachCondition('quizat_status', '=', QuizAttempt::STATUS_PENDING);
        $srch->addCondition('quizat_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('quilin_record_type', '!=', AppConstant::COURSE);
        $srch->addCondition('quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $cond1 = 'quilin_duration > 0 AND quizat_started != "0000-00-00 00:00:00" AND DATE_ADD(quizat_started, INTERVAL quilin_duration SECOND) < "' . date('Y-m-d H:i:s') . '" AND quilin_validity < "' . date('Y-m-d H:i:s') . '"';
        $cond2 = 'quilin_duration = 0 AND quizat_started != "0000-00-00 00:00:00" AND quilin_validity < "' . date('Y-m-d H:i:s') . '"';
        $cond3 = 'quizat_started = "0000-00-00 00:00:00" AND quilin_validity < "' . date('Y-m-d H:i:s') . '"';

        $srch->addDirectCondition('((' . $cond1 . ') OR (' . $cond2 . ') OR (' . $cond3 . '))');

        $srch->addMultipleFields([
            'quizat_id',
            'quizat_user_id',
            'user_lang_id',
            'IF(' . $cond3 . ', ' . QuizAttempt::STATUS_CANCELED . ', ' . QuizAttempt::STATUS_COMPLETED . ') as status'
        ]);

        $srch->addOrder('quizat_id');
        $quizzes = FatApp::getDb()->fetchAll($srch->getResultSet(), 'quizat_id');
        foreach ($quizzes as $quiz) {
            if ($quiz['status'] == QuizAttempt::STATUS_COMPLETED) {
                $this->userId = $quiz['quizat_user_id'];
                $this->userType = User::LEARNER;
                $this->langId = $quiz['user_lang_id'];
                $this->mainTableRecordId = $quiz['quizat_id'];
                if (!$this->markComplete()) {
                    return false;
                }
            } else {
                $data = ['quizat_status' => $quiz['status'], 'quizat_updated' => date('Y-m-d H:i:s')];
                $where = ['smt' => 'quizat_id = ?', 'vals' => [$quiz['quizat_id']]];
                $db = FatApp::getDb();
                if (!$db->updateFromArray(QuizAttempt::DB_TBL, $data, $where)) {
                    $this->error = $db->getError();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Cancel quizzes
     *
     * @param int $recordId
     * @param int $recordType
     * @return bool
     */
    public function cancel(int $recordId, int $recordType)
    {
        $srch = new SearchBase(QuizLinked::DB_TBL, 'quilin');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'quilin_id = quizat_quilin_id');

        $srch->addCondition('quilin.quilin_record_id', '=', $recordId);
        $srch->addCondition('quilin.quilin_record_type', '=', $recordType);
        $srch->addCondition('quilin.quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('quizat_active', '=', AppConstant::YES);
        if ($this->userType == User::LEARNER) {
            $srch->addCondition('quizat_user_id', '=', $this->userId);
        }
        if ($this->userType == User::TEACHER) {
            $srch->addCondition('quilin_user_id', '=', $this->userId);
        }
        $srch->addFld('quizat_id');
        $quizIds = FatApp::getDb()->fetchAll($srch->getResultSet(), 'quizat_id');
        if (empty($quizIds)) {
            return true;
        }
        $db = FatApp::getDb();
        $where = [
            'smt' => "quizat_id IN (" . trim(str_repeat('?,', count($quizIds)), ',') . ")",
            'vals' => array_keys($quizIds)
        ];
        if (!$db->updateFromArray(static::DB_TBL, ['quizat_status' => static::STATUS_CANCELED], $where)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Send quiz completion notification to teacher
     */
    private function sendQuizCompletionNotification()
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
        
        MyUtility::setSiteLanguage(['language_id' => $teacher['user_lang_id']]);
        $sessionType = AppConstant::getSessionTypes($data['quilin_record_type']);
        $score = ($data['quizat_scored']) ? $data['quizat_scored'] : 0;
        $duration = strtotime($data['quizat_updated']) - strtotime($data['quizat_started']);
        $duration = CommonHelper::convertDuration($duration, true, true);
        
        /* get session title */
        if ($data['quilin_record_type'] == AppConstant::GCLASS) {
            $sessionData = QuizLinked::getClassData($data['quilin_record_id']);
            $sessionTitle = $sessionData[$teacher['user_lang_id']] ?? current($sessionData);
        } elseif ($data['quilin_record_type'] == AppConstant::LESSON) {
            $sessionData = QuizLinked::getLessonData($data['quilin_record_id']);
            $sessionData = $sessionData[$teacher['user_lang_id']] ?? current($sessionData);
            $sessionTitle = str_replace(
                ['{teach-lang}', '{n}'],
                [$sessionData['ordles_tlang_name'], $sessionData['ordles_duration']],
                Label::getLabel('LBL_{teach-lang},_{n}_minutes_of_Lesson')
            );
        }

        $template = 'graded_quiz_completion_email';
        if ($data['quilin_type'] == Quiz::TYPE_NON_GRADED) {
            $template = 'nongraded_quiz_completion_email';
        }
        $mail = new FatMailer($teacher['user_lang_id'], $template);
        $vars = [
            '{learner_full_name}' => ucwords($learner['user_first_name'] . ' ' . $learner['user_last_name']),
            '{teacher_full_name}' => ucwords($teacher['user_first_name'] . ' ' . $teacher['user_last_name']),
            '{session_type}' => $sessionType,
            '{session_title}' => $sessionTitle,
            '{quiz_title}' => '<a target="_blank" href="' . MyUtility::makeFullUrl('QuizReview', 'index', [$data['quizat_id']]) . '">' . $data['quilin_title'] . '</a>',
            '{progress_percentage}' => MyUtility::formatPercent($data['quizat_progress']),
            '{pass_fail_status}' => static::getEvaluationStatuses($data['quizat_evaluation']),
            '{marks_acheived}' => $data['quizat_marks'],
            '{score_percentage}' => MyUtility::formatPercent($score),
            '{completion_time}' => $duration,
        ];
        $mail->setVariables($vars);
        $mail->sendMail([$teacher['user_email']]);

        $notifi = new Notification($teacher['user_id'], Notification::TYPE_QUIZ_COMPLETED);
        $notifi->sendNotification(['{session}' => strtolower($sessionType)]);
        MyUtility::setSiteLanguage(['language_id' => $userSiteLangId]);
    }

    /**
     * Setup question scores on the basis of correct answers
     *
     * @param array $data
     * @return bool
     */
    private function setupQuesScore(array $data)
    {
        /* get answers */
        $ques = QuizLinked::getQuestionById($data['quatqu_qulinqu_id']);
        if (!$ques) {
            $this->error = Label::getLabel('LBL_AN_ERROR_OCCURRED');
            return false;
        }

        $answers = json_decode($ques['qulinqu_answer'], true);
        $submittedAnswers = json_decode($data['quatqu_answer'], true);
        $wrongAnswers = count(array_diff($submittedAnswers, $answers));
        
        if ($ques['qulinqu_type'] == Question::TYPE_MULTIPLE) {
            $answerOptions = json_decode($ques['qulinqu_options'], true);
            $marksPerAnswer = $ques['qulinqu_marks'] / count($answerOptions);
            $correctAnswers = array_intersect($answers, $submittedAnswers);
            $missedCorrectAnswers = count(array_diff($answers, $correctAnswers));
            $incorrectAnswers = count($answerOptions) - count($answers);

            /*
            * Formula for MCQ score calculation
            * ((Selected Correct Answers - Selected Incorrect Answers - Not Selected Correct Answers) + 
            * (Incorrect Answers - Selected Incorrect Answers)) * Price Per Answer
            */
            $answeredScore = ((count($correctAnswers) - $wrongAnswers - $missedCorrectAnswers) + ($incorrectAnswers - $wrongAnswers)) * $marksPerAnswer;
            $answeredScore = ($answeredScore < 0) ? 0 : $answeredScore;
        } else {
            $answeredScore = ($wrongAnswers > 0) ? 0 : $ques['qulinqu_marks'];
        }

        $quesAttempt = new TableRecord(static::DB_TBL_QUESTIONS);
        $assignValues = [
            'quatqu_id' => $data['quatqu_id'],
            'quatqu_scored' => $answeredScore
        ];
        $quesAttempt->assignValues($assignValues);
        if (!$quesAttempt->addNew([], $assignValues)) {
            $this->error = $quesAttempt->getError();
            return false;
        }
        return true;
    }

    /**
     * Setup quiz progress and scores
     *
     * @return bool
     */
    public function setupQuizProgress()
    {
        $progress = $score = 0;
        $srch = new SearchBase(static::DB_TBL_QUESTIONS);
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'quizat_id = quatqu_quizat_id');
        $srch->addCondition('quatqu_quizat_id', '=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        $srch->addFld('COUNT(quatqu_quizat_id) as attempted_questions');
        $srch->addFld('SUM(quatqu_scored) as total_score');
        $srch->addFld('quizat_quilin_id');
        if ($quesCount = FatApp::getDb()->fetch($srch->getResultSet())) {
            $questions = QuizLinked::getAttributesById($quesCount['quizat_quilin_id'], 'quilin_questions');
            $progress = ($quesCount['attempted_questions'] * 100) / $questions;
            $score = $quesCount['total_score'];
        }

        $this->assignValues([
            'quizat_progress' => $progress,
            'quizat_marks' => $score,
        ]);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Setup final evaluated results
     *
     * @return bool
     */
    public function setupEvaluation()
    {
        $srch = new SearchBase(QuizAttempt::DB_TBL);
        $srch->addCondition('quizat_id', '=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields(['quizat_marks', 'quizat_quilin_id']);
        $data = FatApp::getDb()->fetch($srch->getResultSet());

        $quiz = QuizLinked::getAttributesById($data['quizat_quilin_id'], ['quilin_marks', 'quilin_passmark']);

        $percent = ($data['quizat_marks'] * 100) / $quiz['quilin_marks'];
        $evaluation = static::EVALUATION_PASSED;
        if ($percent < $quiz['quilin_passmark']) {
            $evaluation = static::EVALUATION_FAILED;
        }
        $this->assignValues([
            'quizat_scored' => $percent,
            'quizat_evaluation' => $evaluation,
        ]);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Get Quizzes
     *
     * @param array $quizLinkIds
     * @param int $userId
     * @return array
     */
    public static function getQuizzes(array $quizLinkIds, int $userId)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quizat_quilin_id = quilin_id');
        $srch->addCondition('quizat_quilin_id', 'IN', $quizLinkIds);
        $srch->addCondition('quizat_active', '=', AppConstant::YES);
        $srch->addCondition('quizat_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields([
            'quilin_record_id', 'quizat_evaluation', 'quilin_certificate', 'quizat_status', 'quizat_id',
            'quizat_certificate_number'
        ]);
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'quilin_record_id');
    }
}
