<?php

class QuizLinked extends MyAppModel
{
    public const DB_TBL = 'tbl_quiz_linked';
    public const DB_TBL_PREFIX = 'quilin_';
    public const DB_TBL_QUIZ_LINKED_QUESTIONS = 'tbl_quiz_linked_questions';

    private $userId;
    private $userType;
    private $langId;
    
    /**
     * Initialize Quiz
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     * @param int $langId
     */
    public function __construct(int $id = 0, int $userId = 0, int $userType = 0, int $langId = 0)
    {
        parent::__construct(static::DB_TBL, 'quilin_id', $id);
        $this->userId = $userId;
        $this->userType = $userType;
        $this->langId = $langId;
    }

    /**
     * Validate lesson, class & course
     *
     * @param int $recordId
     * @param int $recordType
     * @return bool
     */
    public function validateRecord(int $recordId, int $recordType)
    {
        switch ($recordType) {
            case AppConstant::LESSON:
                $srch = new LessonSearch($this->langId, $this->userId, $this->userType);
                $srch->applyPrimaryConditions();
                $srch->addCondition('ordles_id', '=', $recordId);
                $srch->addFld('ordles_id');
                $srch->setPageSize(1);
                $srch->doNotCalculateRecords();
                if (!FatApp::getDb()->fetch($srch->getResultSet())) {
                    $this->error = Label::getLabel('LBL_INVALID_LESSON');
                    return false;
                }
                break;
            case AppConstant::GCLASS:
                $srch = new ClassSearch($this->langId, $this->userId, $this->userType);
                $srch->applyPrimaryConditions();
                $srch->addCondition('grpcls_id', '=', $recordId);
                $srch->addFld('grpcls_id');
                $srch->setPageSize(1);
                if (!FatApp::getDb()->fetch($srch->getResultSet())) {
                    $this->error = Label::getLabel('LBL_INVALID_CLASS');
                    return false;
                }
                break;
            case AppConstant::COURSE:
                $srch = new CourseSearch($this->langId, $this->userId, $this->userType);
                $srch->applyPrimaryConditions();
                $srch->addCondition('course.course_id', '=', $recordId);
                $srch->addFld('course.course_id');
                $srch->setPageSize(1);
                if (!FatApp::getDb()->fetch($srch->getResultSet())) {
                    $this->error = Label::getLabel('LBL_INVALID_COURSE');
                    return false;
                }
                break;
        }
        return true;
    }

    /**
     * Attach quizzes with lessons, classes & courses
     *
     * @param int $recordId
     * @param int $recordType
     * @param array $quizzes
     * @return bool
     */
    public function setup(int $recordId, int $recordType, array $quizzes)
    {
        if ($recordId < 1 || !array_key_exists($recordType, AppConstant::getSessionTypes()) || count($quizzes) < 1) {
            $this->error = Label::getLabel('LBL_INVALID_DATA_SENT');
            return false;
        }

        if (!$this->validateRecord($recordId, $recordType)) {
            return false;
        }

        $srch = new SearchBase(QuizLinked::DB_TBL);
        $srch->addCondition('quilin_record_id', '=', $recordId);
        $srch->addCondition('quilin_record_type', '=', $recordType);
        $srch->addCondition('quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('quilin_quiz_id', 'IN', $quizzes);
        $srch->addFld('quilin_quiz_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_QUIZ_ALREADY_ATTACHED_MESSAGE');
            return false;
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        /* added marks update because there may be marks changes at question level but not updated in quiz */
        if (!(new Quiz(0, $this->userId))->updateMarks($quizzes)) {
            $db->rollbackTransaction();
            return false;
        }

        /* validate quizzes list */
        $srch = new QuizSearch(0, $this->userId, $this->userType);
        $srch->addSearchListingFields();
        $srch->applyPrimaryConditions();
        $srch->addCondition('quiz.quiz_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('quiz.quiz_status', '=', Quiz::STATUS_PUBLISHED);
        $srch->addCondition('quiz.quiz_id', 'IN', $quizzes);
        $srch->addOrder('quiz_id', 'DESC');
        $quizList = FatApp::getDb()->fetchAll($srch->getResultSet(), 'quiz_id');
        if (count($quizList) != count($quizzes)) {
            $this->error = Label::getLabel('LBL_SOME_QUIZZES_ARE_NOT_AVAILABLE._PLEASE_TRY_AGAIN');
            return false;
        }

        $quizzesData = $quizLinkedIds = [];
        foreach ($quizList as $quiz) {
            $quizLink = new TableRecord(static::DB_TBL);
            $data = [
                'quilin_quiz_id' => $quiz['quiz_id'],
                'quilin_type' => $quiz['quiz_type'],
                'quilin_title' => $quiz['quiz_title'],
                'quilin_detail' => $quiz['quiz_detail'],
                'quilin_user_id' => $this->userId,
                'quilin_record_id' => $recordId,
                'quilin_record_type' => $recordType,
                'quilin_duration' => $quiz['quiz_duration'],
                'quilin_attempts' => $quiz['quiz_attempts'],
                'quilin_marks' => $quiz['quiz_marks'],
                'quilin_passmark' => $quiz['quiz_passmark'],
                'quilin_failmsg' => $quiz['quiz_failmsg'],
                'quilin_passmsg' => $quiz['quiz_passmsg'],
                'quilin_validity' => date("Y-m-d H:i:s", strtotime('+' . $quiz['quiz_validity'] . ' hours')),
                'quilin_certificate' => $quiz['quiz_certificate'],
                'quilin_questions' => $quiz['quiz_questions'],
                'quilin_created' => date('Y-m-d H:i:s')
            ];
            $quizLink->assignValues($data);
            if (!$quizLink->addNew()) {
                $db->rollbackTransaction();
                $this->error = $quizLink->getError();
                return false;
            }
            $quizzesData[$quiz['quiz_id']] = $data + ['quilin_id' => $quizLink->getId()];
            $quizLinkedIds[] = $quizLink->getId();

            if ($recordType == AppConstant::COURSE) {
                $course = new Course($recordId);
                $course->setFldValue('course_quilin_id', $quizLink->getId());
                if (!$course->save()) {
                    $db->rollbackTransaction();
                    $this->error = $course->getError();
                    return false;
                }
            }
        }
        /* setup user quizzes */
        if (!$this->setupUserQuizzes($recordId, $recordType, $quizLinkedIds)) {
            $db->rollbackTransaction();
            return false;
        }

        /* attach questions */
        if (!$this->setupQuestions($quizzesData)) {
            $db->rollbackTransaction();
            return false;
        }

        $this->sendQuizAttachedNotification($quizzesData);

        $db->commitTransaction();

        return true;
    }

    /**
     * Get Attached Quiz
     *
     * @param array $recordIds
     * @param int   $type
     * @return array
     */
    public static function getQuizzes(array $recordIds, int $type): array
    {
        if (count($recordIds) == 0) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'quilin');
        $srch->addCondition('quilin.quilin_record_id', 'IN', array_unique($recordIds));
        $srch->addCondition('quilin.quilin_record_type', '=', $type);
        $srch->addCondition('quilin.quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields([
            'quilin_record_id', 'COUNT(*) as quiz_count', 'quilin_id', 'quilin_title', 'quilin_quiz_id'
        ]);
        $srch->addGroupBy('quilin_record_id');
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'quilin_record_id');
    }

    /**
     * Get Attached Quiz
     *
     * @param int $recordIds
     * @param int $type
     * @return array
     */
    public function getAttachedQuizzes(int $recordId, int $type): array
    {
        if ($recordId < 1) {
            return [];
        }

        $srch = new SearchBase(static::DB_TBL, 'quilin');
        $srch->addCondition('quilin.quilin_record_id', '=', $recordId);
        $srch->addCondition('quilin.quilin_record_type', '=', $type);
        $srch->addCondition('quilin.quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addMultipleFields([
            'quilin_record_id', 'quilin_id', 'quilin_title', 'quilin_type', 'quilin_validity', 'quilin_quiz_id'
        ]);
        $srch->doNotCalculateRecords();
        $srch->addOrder('quilin_id', 'DESC');
        $attachedQuizzes = FatApp::getDb()->fetchAll($srch->getResultSet(), 'quilin_id');
        if (empty($attachedQuizzes)) {
            return [];
        }
        $quizLinkedIds = array_column($attachedQuizzes, 'quilin_id');
        $srch = new SearchBase(QuizAttempt::DB_TBL);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = quizat_user_id');
        $srch->addCondition('quizat_quilin_id', 'IN', $quizLinkedIds);
        $srch->addCondition('quizat_active', '=', AppConstant::YES);
        if ($this->userType == User::LEARNER) {
            $srch->addCondition('quizat_user_id', '=', $this->userId);
        }
        $srch->addMultipleFields([
            'user_first_name', 'user_last_name', 'quizat_status', 'quizat_quilin_id', 'quizat_id'
        ]);
        $srch->addOrder('quizat_id');
        $usersList = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($usersList as $user) {
            if ($type == AppConstant::LESSON || $this->userType == User::LEARNER) {
                $attachedQuizzes[$user['quizat_quilin_id']]['users'] = $user;
            } else {
                $attachedQuizzes[$user['quizat_quilin_id']]['users'][] = $user;
            }
        }
        return $attachedQuizzes;
    }

    /**
     * Remove attached quiz
     *
     * @return bool
     */
    public function remove()
    {
        $id = $this->getMainTableRecordId();
        $data = QuizLinked::getAttributesById($id, [
            'quilin_deleted', 'quilin_user_id', 'quilin_record_id', 'quilin_record_type', 'quilin_title', 'quilin_id'
        ]);
        if (!$data || !empty($data['quilin_deleted'])) {
            $this->error = Label::getLabel('LBL_QUIZ_NOT_FOUND');
            return false;
        }
        if ($data['quilin_user_id'] != $this->userId) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }
        /* check if quiz already attended by any user */
        $srch = new SearchBase(QuizAttempt::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('quizat_id');
        $srch->addCondition('quizat_quilin_id', '=', $id);
        $srch->addCondition('quizat_status', '!=', QuizAttempt::STATUS_PENDING);
        $srch->addCondition('quizat_status', '!=', QuizAttempt::STATUS_CANCELED);
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_ATTENDED_QUIZZES_CANNOT_BE_DELETED');
            return false;
        }

        $db = FatApp::getDb();
        $db->startTransaction();
        $this->setFldValue('quilin_deleted', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            return false;
        }

        if (in_array($data['quilin_record_type'], [AppConstant::GCLASS, AppConstant::LESSON])) {
            $this->sendQuizRemovedNotification($data);

            /* delete user quiz */
            if (!$db->deleteRecords(QuizAttempt::DB_TBL, ['smt' => 'quizat_quilin_id = ?', 'vals' => [$id]])) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
        }

        $db->commitTransaction();
        return true;
    }

    /**
     * Get question by id
     *
     * @param int $id
     * @return array
     */
    public static function getQuestionById(int $id)
    {
        $srch = new SearchBase(static::DB_TBL_QUIZ_LINKED_QUESTIONS);
        $srch->addCondition('qulinqu_id', '=', $id);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields([
            'qulinqu_id', 'qulinqu_type', 'qulinqu_ques_id', 'qulinqu_title', 'qulinqu_detail', 'qulinqu_hint',
            'qulinqu_marks', 'qulinqu_options', 'qulinqu_order', 'qulinqu_answer'
        ]);
        $question = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($question)) {
            return [];
        }
        return $question;
    }

    /**
     * Bind quizzes for users
     *
     * @param int $recordId
     * @param int $type
     * @return bool
     */
    public function bindUserQuiz(int $recordId, int $type)
    {
        /* get linked quizzes list */
        $srch = new SearchBase(static::DB_TBL, 'quilin');
        $srch->addCondition('quilin.quilin_record_id', '=', $recordId);
        $srch->addCondition('quilin.quilin_record_type', '=', $type);
        $srch->addCondition('quilin.quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addMultipleFields([
            'quilin_id', 'quilin_record_type', 'quilin_record_id', 'quilin_title', 'quilin_type', 'quilin_validity'
        ]);
        $srch->doNotCalculateRecords();
        $quizzes = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($quizzes)) {
            return true;
        }
        foreach ($quizzes as $quiz) {
            $attempt = new QuizAttempt(0, $this->userId);
            if (!$attempt->setupUserQuiz($quiz['quilin_id'])) {
                $this->error = $attempt->getError();
                return false;
            }
        }
        if($type != AppConstant::COURSE) {
            $this->sendQuizAttachedNotification($quizzes, [$this->userId]);
        }
        return true;
    }

    public function getQuesWithAttemptedAnswers($attemptId, $quizLinkedId)
    {
        $srch = new SearchBase(QuizLinked::DB_TBL_QUIZ_LINKED_QUESTIONS);
        $srch->joinTable(
            QuizAttempt::DB_TBL_QUESTIONS,
            'LEFT JOIN',
            'quatqu_qulinqu_id = qulinqu_id AND quatqu_quizat_id = ' . $attemptId
        );
        $srch->addCondition('qulinqu_quilin_id', '=', $quizLinkedId);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields([
            'quatqu_id', 'quatqu_answer', 'qulinqu_id', 'qulinqu_order', 'qulinqu_answer', 'qulinqu_type',
            'quatqu_scored', 'quatqu_comment'
        ]);
        $srch->addOrder('qulinqu_order', 'ASC');
        $attemptedQues = FatApp::getDb()->fetchAll($srch->getResultSet(), 'qulinqu_id');
        if (empty($attemptedQues)) {
            return [];
        }
        foreach ($attemptedQues as $key => $question) {
            $question['quatqu_answer'] = $question['quatqu_answer'] ? json_decode($question['quatqu_answer'], true) : [];
            $question['is_correct'] = '';
            if ($question['qulinqu_type'] != Question::TYPE_TEXT) {
                $question['qulinqu_answer'] = json_decode($question['qulinqu_answer'], true);

                $answered = array_intersect($question['qulinqu_answer'], $question['quatqu_answer']);
                $wrongAnswered = array_diff($question['quatqu_answer'], $question['qulinqu_answer']);
                if (count($question['qulinqu_answer']) == count($answered) && count($wrongAnswered) < 1) {
                    $question['is_correct'] = AppConstant::YES;
                } else {
                    $question['is_correct'] = AppConstant::NO;
                }
            }

            $attemptedQues[$key] = $question;
        }
        return $attemptedQues;
    }

    public static function getByCourseId(int $courseId)
    {
        $srch = new SearchBase(QuizLinked::DB_TBL, 'quilin');
        $srch->joinTable(Quiz::DB_TBL, 'INNER JOIN', 'quiz_id = quilin_quiz_id');
        $srch->addMultipleFields([
            'quiz_id', 'quilin_title as quiz_title', 'quilin_detail as quiz_detail', 'quilin_type as quiz_type', 'quilin_passmark as quiz_passmark',
            'quilin_questions as quiz_questions', 'quilin_duration as quiz_duration', 'quilin_attempts as quiz_attempts', 'quilin_id'
        ]);
        $srch->addCondition('quilin.quilin_record_id', '=', $courseId);
        $srch->addCondition('quilin.quilin_record_type', '=', AppConstant::COURSE);
        $srch->addCondition('quilin.quilin_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Send quiz removal notification
     *
     * @param array $data
     */
    private function sendQuizRemovedNotification(array $data)
    {
        $siteLangId = MyUtility::getSiteLangId();
        
        /* get users details */
        $srch = new SearchBase(QuizAttempt::DB_TBL);
        $srch->addCondition('quizat_quilin_id', '=', $data['quilin_id']);
        $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quilin.quilin_id = quizat_quilin_id', 'quilin');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = quilin_user_id', 'teacher');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = quizat_user_id', 'learner');
        $srch->addMultipleFields([
            'learner.user_first_name AS learner_first_name', 'learner.user_last_name AS learner_last_name',
            'learner.user_lang_id', 'learner.user_email', 'learner.user_id',
            'teacher.user_first_name as teacher_first_name', 'teacher.user_last_name as teacher_last_name'
        ]);
        $srch->addGroupBy('learner.user_id');
        $users = FatApp::getDb()->fetchAll($srch->getResultSet());

        /* get session details for all languages */
        if ($data['quilin_record_type'] == AppConstant::GCLASS) {
            $sessionData = static::getClassData($data['quilin_record_id']);
        } elseif ($data['quilin_record_type'] == AppConstant::LESSON) {
            $sessionData = static::getLessonData($data['quilin_record_id']);
        }

        foreach ($users as $user) {
            MyUtility::setSiteLanguage(['language_id' => $user['user_lang_id']]);
            
            $sessionTitle = $sessionData[$user['user_lang_id']] ?? current($sessionData);
            if ($data['quilin_record_type'] == AppConstant::LESSON) {
                $sessionTitle = str_replace(
                    ['{teach-lang}', '{n}'],
                    [$sessionTitle['ordles_tlang_name'], $sessionTitle['ordles_duration']],
                    Label::getLabel('LBL_{teach-lang},_{n}_minutes_of_Lesson')
                );
            }
            
            $sessionTypes = AppConstant::getSessionTypes();
            $mail = new FatMailer($user['user_lang_id'], 'quiz_removed_email');
            $vars = [
                '{quiz_title}' => ucfirst($data['quilin_title']),
                '{learner_full_name}' => ucwords($user['learner_first_name'] . ' ' . $user['learner_last_name']),
                '{teacher_full_name}' => ucwords($user['teacher_first_name'] . ' ' . $user['teacher_last_name']),
                '{session_type}' => strtolower($sessionTypes[$data['quilin_record_type']]),
                '{session_title}' => ucwords($sessionTitle),
            ];
            $mail->setVariables($vars);
            $mail->sendMail([$user['user_email']]);
            
            $notifi = new Notification($user['user_id'], Notification::TYPE_QUIZ_REMOVED);
            $notifi->sendNotification(['{session}' => strtolower($sessionTypes[$data['quilin_record_type']])]);
        }
        MyUtility::setSiteLanguage(['language_id' => $siteLangId]);
    }

    /**
     * Send Quiz Attachment Notification
     *
     * @param array $data
     * @param array $userIds
     */
    private function sendQuizAttachedNotification(array $data, array $userIds = [])
    {
        $siteLangId = MyUtility::getSiteLangId();
        $record = current($data);

        /* get users list */
        $srch = new SearchBase(QuizAttempt::DB_TBL);
        $srch->addCondition('quizat_quilin_id', 'IN', array_column($data, 'quilin_id'));
        if (!empty($userIds)) {
            $srch->addCondition('learner.user_id', 'IN', $userIds);
        }
        $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quilin.quilin_id = quizat_quilin_id', 'quilin');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = quilin_user_id', 'teacher');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = quizat_user_id', 'learner');
        $srch->addMultipleFields([
            'learner.user_lang_id', 'learner.user_timezone', 'learner.user_first_name AS learner_first_name', 'learner.user_last_name AS learner_last_name',
            'learner.user_email', 'learner.user_id', 'teacher.user_first_name as teacher_first_name', 'teacher.user_last_name as teacher_last_name',
            'GROUP_CONCAT(CONCAT(quizat_quilin_id, "_", quizat_id) SEPARATOR ",") AS link_id'
        ]);
        $srch->addGroupBy('learner.user_id');
        $users = FatApp::getDb()->fetchAll($srch->getResultSet());

        /* table headings  */
        $html = '<table style="border:1px solid #ddd; border-collapse:collapse; width:100%" cellspacing="0" cellpadding="0" border="0"><thead><tr><td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;">{title}</td><td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd; font-weight:bold;">{type}</td><td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd; font-weight:bold;">{validity}</td><td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd; font-weight:bold;">{action}</td></tr></thead><tbody>';

        /* get session details for all languages */
        if ($record['quilin_record_type'] == AppConstant::GCLASS) {
            $sessionData = static::getClassData($record['quilin_record_id']);
        } elseif ($record['quilin_record_type'] == AppConstant::LESSON) {
            $sessionData = static::getLessonData($record['quilin_record_id']);
        } else {
            $srch = new CourseSearch($this->langId, 0, 0);
            $srch->setPageSize(1);
            $srch->addCondition('course.course_id', '=', $record['quilin_record_id']);
            $srch->addFld('course_title');
            $sessionData = FatApp::getDb()->fetch($srch->getResultSet());
            $sessionTitle = $sessionData['course_title'];
        }

        foreach ($users as $user) {
            $userHtml = $html;
            MyUtility::setSiteLanguage(['language_id' => $user['user_lang_id']]);
            
            /* table headings */
            $find = ['{title}', '{type}', '{validity}', '{action}', '{view}', '{link}'];
            $replacers = [
                Label::getLabel('LBL_TITLE'),
                Label::getLabel('LBL_TYPE'),
                Label::getLabel('LBL_VALIDITY'),
                Label::getLabel('LBL_ACTION'),
                Label::getLabel('LBL_VIEW')
            ];
            $userHtml = str_replace($find, $replacers, $userHtml);

            /* get user quiz attempt ids for links */
            $quizLinkIds = explode(',', $user['link_id']);
            $userLinks = [];
            foreach ($quizLinkIds as $link) {
                $ids = explode('_', $link);
                $userLinks[$ids[0]] = $ids[1];
            }
            
            /* quizzes list */
            $quiztypes = Quiz::getTypes();
            foreach ($data as $quiz) {
                $userHtml .= '<tr>' .
                '<td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333;">' . $quiz['quilin_title'] . '</td>' .
                '<td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;">' . $quiztypes[$quiz['quilin_type']] . '</td>' .
                '<td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;">' . MyDate::showDate(MyDate::formatDate($quiz['quilin_validity'], 'Y-m-d H:i:s', $user['user_timezone']), true, $user['user_lang_id']) . '</td>' .
                '<td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;"><a style="color: {primary-color};" target="_blank" href="' . MyUtility::makeFullUrl('UserQuiz', 'index', [$userLinks[$quiz['quilin_id']]], CONF_WEBROOT_DASHBOARD) . '">' . Label::getLabel('LBL_VIEW') . '</a></td>' .
                '</tr>';
            }

            /* get session types list */
            $sessionType = AppConstant::getSessionTypes($record['quilin_record_type']);

            /* get session title */
            $sessionTitle = $sessionData[$user['user_lang_id']] ?? current($sessionData);
            if ($record['quilin_record_type'] == AppConstant::LESSON) {
                $sessionTitle = str_replace(
                    ['{teach-lang}', '{n}'],
                    [$sessionTitle['ordles_tlang_name'], $sessionTitle['ordles_duration']],
                    Label::getLabel('LBL_{teach-lang},_{n}_minutes_of_Lesson')
                );
            }

            /* send emails */
            $mail = new FatMailer($user['user_lang_id'], 'quiz_attached_email');
            $vars = [
                '{learner_full_name}' => ucwords($user['learner_first_name'] . ' ' . $user['learner_last_name']),
                '{teacher_full_name}' => ucwords($user['teacher_first_name'] . ' ' . $user['teacher_last_name']),
                '{session_type}' => strtolower($sessionType),
                '{session_title}' => ucwords($sessionTitle),
                '{quizzes_list}' => $userHtml,
            ];
            $mail->setVariables($vars);
            $mail->sendMail([$user['user_email']]);

            $notifi = new Notification($user['user_id'], Notification::TYPE_QUIZ_ATTACHED);
            $notifi->sendNotification(['{session}' => strtolower($sessionType)]);

        }
        MyUtility::setSiteLanguage(['language_id' => $siteLangId]);
    }

    /**
     * Setup users quizzes
     *
     * @param int   $recordId
     * @param int   $recordType
     * @param array $data
     * @return bool
     */
    private function setupUserQuizzes(int $recordId, int $recordType, array $data)
    {
        if (empty($data)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }

        if ($recordType == AppConstant::LESSON) {
            $srch = new LessonSearch($this->langId, $this->userId, $this->userType);
            $srch->applyPrimaryConditions();
            $srch->addCondition('ordles_id', '=', $recordId);
            $srch->addCondition('learner.user_id', 'IS NOT', 'mysql_func_NULL', 'AND', true);
            $srch->addCondition('ordles_status', '!=', Lesson::CANCELLED);
            $srch->addFld('learner.user_id');
            $users = FatApp::getDb()->fetchAll($srch->getResultSet());
        } elseif ($recordType == AppConstant::GCLASS) {
            $srch = new ClassSearch($this->langId, $this->userId, $this->userType);
            $srch->applyPrimaryConditions();
            $srch->addCondition('grpcls_id', '=', $recordId);
            $srch->addCondition('learner.user_id', 'IS NOT', 'mysql_func_NULL', 'AND', true);
            $srch->addCondition('ordcls_status', '!=', OrderClass::CANCELLED);
            $srch->addFld('learner.user_id');
            $srch->removGroupBy('grpcls.grpcls_id');
            $users = FatApp::getDb()->fetchAll($srch->getResultSet());
        } elseif ($recordType == AppConstant::COURSE) {
            $users = [];
        }
        if (empty($users)) {
            return true;
        }
        foreach ($users as $user) {
            foreach ($data as $id) {
                $attempt = new QuizAttempt(0, $user['user_id']);
                if (!$attempt->setupUserQuiz($id)) {
                    $this->error = $attempt->getError();
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Setup Questions data
     *
     * @param array $data
     * @return bool
     */
    private function setupQuestions(array $data)
    {
        $srch = new QuizQuestionSearch($this->langId, $this->userId, $this->userType);
        $srch->addCondition('quique_quiz_id', 'IN', array_keys($data));
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('quique_quiz_id', 'ASC');
        $srch->addOrder('quique_order', 'ASC');
        $srch->addCondition('cate.cate_status', '=', AppConstant::ACTIVE);
        $srch->addCondition('cate.cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $questions = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (count($questions) < 1) {
            $this->error = Label::getLabel('LBL_QUESTIONS_NOT_FOUND');
            return false;
        }
        $questionIds = array_column($questions, 'ques_id');

        $srch = new SearchBase(Question::DB_TBL_OPTIONS, 'queopt');
        $srch->addMultipleFields(['queopt_id', 'queopt_title', 'queopt_ques_id']);
        $srch->addCondition('queopt_ques_id', 'IN', $questionIds);
        $srch->doNotCalculateRecords();
        $srch->addOrder('queopt_order', 'ASC');
        $options = FatApp::getDb()->fetchAll($srch->getResultSet());
        $quesOptions = [];
        if (count($options) > 0) {
            foreach ($options as $option) {
                $quesOptions[$option['queopt_ques_id']][] = $option;
            }
        }

        $displayOrder = $quizId = 0;
        foreach ($questions as $question) {
            if ($question['quique_quiz_id'] != $quizId) {
                $displayOrder = 1;
            }
            $opts = $quesOptions[$question['ques_id']] ?? [];
            if ($question['ques_type'] != Question::TYPE_TEXT && count($opts) < 1) {
                $this->error = Label::getLabel('LBL_QUESTION_OPTIONS_ARE_NOT_AVAILABLE');
                return false;
            }

            $linkQues = new TableRecord(static::DB_TBL_QUIZ_LINKED_QUESTIONS);
            $linkQues->assignValues([
                'qulinqu_type' => $question['ques_type'],
                'qulinqu_quilin_id' => $data[$question['quiz_id']]['quilin_id'],
                'qulinqu_ques_id' => $question['ques_id'],
                'qulinqu_title' => $question['ques_title'],
                'qulinqu_detail' => $question['ques_detail'],
                'qulinqu_hint' => $question['ques_hint'],
                'qulinqu_marks' => $question['ques_marks'],
                'qulinqu_answer' => $question['ques_answer'],
                'qulinqu_options' => json_encode($opts),
                'qulinqu_order' => $displayOrder
            ]);
            if (!$linkQues->addNew()) {
                $this->error = $linkQues->getError();
                return false;
            }
            $quizId = $question['quique_quiz_id'];
            $displayOrder++;
        }
        
        return true;
    }

    /**
     * Get class data
     *
     * @param int $id
     * @param int $langId
     * @return array
     */
    public static function getClassData(int $id)
    {
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->joinTable(
            GroupClass::DB_TBL_LANG,
            'LEFT JOIN',
            'gclang.gclang_grpcls_id = grpcls.grpcls_id',
            'gclang'
        );
        $srch->addMultipleFields(['IFNULL(gclang_lang_id, 0) as gclang_lang_id', 'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title']);
        $srch->addCondition('grpcls_id', '=', $id);
        $srch->addOrder('gclang_lang_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get lesson data
     *
     * @param int $id
     * @return array
     */
    public static function getLessonData(int $id)
    {
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'tlang.tlang_id = ordles.ordles_tlang_id', 'tlang');
        $srch->joinTable(
            TeachLanguage::DB_TBL_LANG,
            'LEFT JOIN',
            'tlanglang.tlanglang_tlang_id = tlang.tlang_id',
            'tlanglang'
        );
        $srch->addMultipleFields([
            'ordles_duration', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as ordles_tlang_name',
            'ordles_type', 'IFNULL(tlanglang_lang_id, 0) as tlanglang_lang_id'
        ]);
        $srch->addCondition('ordles_id', '=', $id);
        $srch->addOrder('tlanglang_lang_id');
        $srch->doNotCalculateRecords();
        $lessonData = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlanglang_lang_id');
        $sessionData = [];
        foreach ($lessonData as $data) {
            if ($data['ordles_type'] == Lesson::TYPE_FTRAIL) {
                $data['ordles_tlang_name'] = Label::getLabel('LBL_FREE_TRIAL', $data['tlanglang_lang_id']);
            }
            $sessionData[$data['tlanglang_lang_id']] = [
                'ordles_tlang_name' => $data['ordles_tlang_name'],
                'ordles_duration' => $data['ordles_duration']
            ];
        }
        return $sessionData;
    }
}
