<?php

/**
 * This class is used to handle Lesson Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class LessonSearch extends W3mentorsSearch
{

    /**
     * Initialize Lesson Search
     *
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = Lesson::DB_TBL;
        $this->alias = 'ordles';

        parent::__construct($langId, $userId, $userType);
        $this->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordles.ordles_teacher_id', 'teacher');
    }

    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        if ($this->userType === User::LEARNER) {
            $this->addCondition('order_user_id', '=', $this->userId);
            $this->addDirectCondition('learner.user_deleted IS NULL');
            $this->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $this->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        } elseif ($this->userType === User::TEACHER) {
            $this->addCondition('ordles_teacher_id', '=', $this->userId);
            $this->addDirectCondition('teacher.user_deleted IS NULL');
            $this->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
            $this->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $this->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        }
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (!empty($post['keyword'])) {
            $keyword = trim($post['keyword']);
            if ($this->userType === User::SUPPORT) {
                $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
                $cond = $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
                $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
                $cond->attachCondition($fullName, 'LIKE', '%' . $keyword . '%', 'OR', true);
            } else {
                if ($this->userType === User::LEARNER) {
                    $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
                    $cond = $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
                } elseif ($this->userType === User::TEACHER) {
                    $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
                    $cond = $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
                }
                $this->addLanguageCondition($keyword, $cond);
            }
            $orderId = FatUtility::int(str_replace('O', '', $keyword));
            if (!empty($orderId)) {
                $cond->attachCondition('ordles.ordles_id', '=', $orderId);
                $cond->attachCondition('ordles.ordles_order_id', '=', $orderId);
            }
        }
        if (!empty($post['ordles_id'])) {
            $this->addCondition('ordles.ordles_id', '=', $post['ordles_id']);
        }
        if (!empty($post['ordles_ordsplan_id'])) {
            $this->addCondition('ordles.ordles_ordsplan_id', '=', $post['ordles_ordsplan_id']);
        }
        if (!empty($post['order_id'])) {
            $this->addCondition('orders.order_id', '=', $post['order_id']);
        }
        if (!empty($post['ordles_status']) && $post['ordles_status'] != -1) {
            $this->addCondition('ordles.ordles_status', '=', $post['ordles_status']);
        }
        if ((!empty($post['ordles_tlang_id'])) || (isset($post['ordles_tlang_id']) && $post['ordles_tlang_id'] == -1)) {
            if ($post['ordles_tlang_id'] > 0) {
                $cond = $this->addCondition('ordles.ordles_tlang_id', '=', $post['ordles_tlang_id']);
                
                $srch = TeachLanguage::getSearchObject($this->langId, false);
                $srch->addDirectCondition('FIND_IN_SET(' . $post['ordles_tlang_id'] . ', tlang_parentids)');
                $srch->addFld('tlang_id');
                $srch->doNotCalculateRecords();
                $srch->doNotLimitRecords();
                $data = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
               if ($data) {
                   $cond->attachCondition('ordles.ordles_tlang_id', 'IN', array_keys($data));
               }
            } elseif ($post['ordles_tlang_id'] == -1) {
                $this->addDirectCondition('ordles.ordles_tlang_id IS NULL');
            }
        } elseif (!empty($post['ordles_tlang'])) {
            $this->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = '
                . ' ordles.ordles_tlang_id AND tlanglang.tlanglang_lang_id = ' . $this->langId, 'tlanglang');
            $cond = $this->addCondition('tlanglang.tlang_name', 'LIKE', '%' . trim($post['ordles_tlang']) . '%');
            $this->addLanguageCondition($post['ordles_tlang'], $cond);
        }
        if (!empty($post['ordles_teacher_id'])) {
            $this->addCondition('ordles.ordles_teacher_id', '=', $post['ordles_teacher_id']);
        }
        if (isset($post['order_payment_status']) && $post['order_payment_status'] !== '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }
        if (isset($post['ordles_offline']) && $post['ordles_offline'] !== '') {
            $this->addCondition('ordles_offline', '=', $post['ordles_offline']);
        }
        if (!empty($post['order_addedon_from'])) {
            $fromDate = MyDate::formatToSystemTimezone($post['order_addedon_from'] . ' 00:00:00');
            $this->addCondition('orders.order_addedon', '>=', $fromDate);
        }
        if (!empty($post['order_addedon_till'])) {
            $tillDate = MyDate::formatToSystemTimezone($post['order_addedon_till'] . ' 23:59:59');
            $this->addCondition('orders.order_addedon', '<=', $tillDate);
        }
        $status = $post['ordles_status'] ?? '';
        if ($status != Lesson::UNSCHEDULED) {
            if (!empty($post['ordles_lesson_starttime'])) {
                $starttime = MyDate::formatToSystemTimezone($post['ordles_lesson_starttime'] . ' 00:00:00');
                if ($status == Lesson::CANCELLED || $status == -1) {
                    $this->addDirectCondition('( ordles.ordles_lesson_starttime IS NULL OR ordles.ordles_lesson_starttime >= "' . $starttime . '")');
                } else {
                    $this->addCondition('ordles.ordles_lesson_starttime', '>=', $starttime);
                }
            }
            if (!empty($post['ordles_lesson_endtime'])) {
                $endtime = MyDate::formatToSystemTimezone($post['ordles_lesson_endtime'] . ' 23:59:59');
                if ($status == Lesson::CANCELLED || $status == -1) {
                    $this->addDirectCondition('( ordles.ordles_lesson_endtime IS NULL OR ordles.ordles_lesson_endtime <= "' . $endtime . '")');
                } else {
                    $this->addCondition('ordles.ordles_lesson_endtime', '<=', $endtime);
                }
            }
        }
        /**
         * Conditions for calendar JSON data
         */
        if (!empty($post['start'])) {
            $post['start'] = MyDate::formatToSystemTimezone($post['start']);
            $this->addCondition('ordles.ordles_lesson_starttime', '< ', $post['end']);
        }
        if (!empty($post['end'])) {
            $post['end'] = MyDate::formatToSystemTimezone($post['end']);
            $this->addCondition('ordles.ordles_lesson_endtime', ' > ', $post['start']);
        }
        if (!empty($post['ordles_type'])) {
            $this->addCondition('ordles.ordles_type', '=', $post['ordles_type']);
        }
        if (isset($post['ordles_offline']) && !empty($post['ordles_offline'])) {
            $this->addCondition('ordles.ordles_offline', '=', AppConstant::YES);
        }
    }

    /**
     * Function to search languages by keyword and prepare condition
     *
     * @param string $keyword
     */
    private function addLanguageCondition(string $keyword, $cond)
    {
        $tlangIds = TeachLanguage::searchByKeyword($keyword, $this->langId);
        $tlangIds = !empty($tlangIds) ? array_keys($tlangIds) : [-1];
        $this->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'ordles.ordles_tlang_id = tlang_id');
        $cond->attachCondition('tlang_id', 'IN', $tlangIds, 'OR', true);
        $qryStr = [];
        foreach ($tlangIds as $id) {
            $qryStr[] = 'FIND_IN_SET(' . $id . ', tlang_parentids)';
        }
        $cond->attachCondition('mysql_func_' . implode(' OR ', $qryStr), '', 'mysql_func_', 'OR', true);
    }

    /**
     * Fetch & Format Lessons
     *
     * @param bool $single
     * @return array
     */
    public function fetchAndFormat(bool $single = false): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($rows) == 0) {
            return [];
        }
        $currentTimeUnix = strtotime(MyDate::formatDate(date('Y-m-d H:i:s')));
        $reportHours = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION', FatUtility::VAR_INT, 0);
        $lessonIds = array_column($rows, 'ordles_id');
        $teachLangIds = array_column($rows, 'ordles_tlang_id');
        $countryIds = array_merge(array_column($rows, 'learner_country_id'), array_column($rows, 'teacher_country_id'));
        $countries = Country::getNames($this->langId, $countryIds);
        $lessonPlans = Plan::getLessonPlans($lessonIds);
        $lessonIssues = Issue::getLessonIssueIds($lessonIds);
        $teachLangNames = TeachLanguage::getNames($this->langId, $teachLangIds, false);
        $teachLangs = TeachLanguage::getAllLangs($this->langId);
        $lessonQuizzes = QuizLinked::getQuizzes($lessonIds, AppConstant::LESSON);
        $title = Label::getLabel('LBL_{teach-lang},_{n}_minutes_of_Lesson');
        $freeTrialLbl = Label::getLabel('LBL_FREE_TRIAL');
        $ongoingLabel = Label::getLabel('LBL_LESSON_IS_ONGOING');
        $passedLabel = Label::getLabel('LBL_LESSON_TIME_HAS_PASSED');
        $needBeScheduled = Label::getLabel('LBL_LESSON_TO_BE_SCHEDULED');
        $playbacks = Meeting::getPlaybacks($this->userId, $lessonIds, AppConstant::LESSON);
        $duration = FatApp::getConfig('CONF_LESSON_CANCEL_DURATION');
        foreach ($rows as $key => $row) {
            $row['playback_url'] = $playbacks[$row['ordles_id']] ?? '';
            $row['repiss_id'] = $lessonIssues[$row['ordles_id']] ?? 0;
            $row['plan'] = $lessonPlans[$row['ordles_id']] ?? ['plan_id' => 0];
            $row['order_user_country'] = $countries[$row['order_user_id']] ?? '';
            $row['ordles_teacher_country'] = $countries[$row['teacher_country_id']] ?? '';
            $row['ordles_learner_country'] = $countries[$row['learner_country_id']] ?? '';
            $row['ordles_tlang_name'] = $teachLangNames[$row['ordles_tlang_id']] ?? '';
            $row['ordles_language_name'] = $teachLangs[$row['ordles_tlang_id']] ?? '';
            if ($row['ordles_type'] == Lesson::TYPE_FTRAIL) {
                $row['ordles_tlang_name'] = $row['ordles_language_name'] = $freeTrialLbl;
            }
            $row = array_merge($row, $lessonPlans[$row['ordles_id']] ?? ['plan_id' => 0]);
            $row['quiz_count'] = $lessonQuizzes[$row['ordles_id']]['quiz_count'] ?? 0;
            $row['lessonTitle'] = str_replace(['{teach-lang}', '{n}'], [$row['ordles_tlang_name'], $row['ordles_duration']], $title);
            $row['ordles_remaining_unix'] = 0;
            $row['ordles_endtime_remaining_unix'] = 0;
            $row['ordles_starttime_unix'] = null;
            $row['ordles_endtime_unix'] = null;
            $row['ordles_currenttime_unix'] = $currentTimeUnix;
            if (!is_null($row['ordles_lesson_starttime'])) {
                $row['ordles_lesson_starttime_utc'] = strtotime($row['ordles_lesson_starttime']);
                $row['ordles_lesson_endtime_utc'] = strtotime($row['ordles_lesson_endtime'] ?? "");
                $row['ordles_lesson_starttime'] = MyDate::formatDate($row['ordles_lesson_starttime']);
                $row['ordles_lesson_endtime'] = MyDate::formatDate($row['ordles_lesson_endtime'] ?? '');
                $row['ordles_starttime_unix'] = strtotime($row['ordles_lesson_starttime']);
                $row['ordles_endtime_unix'] = strtotime($row['ordles_lesson_endtime']);
                $row['ordles_remaining_unix'] = $row['ordles_starttime_unix'] - $currentTimeUnix;
                $row['ordles_endtime_remaining_unix'] = $row['ordles_endtime_unix'] - $currentTimeUnix;
            }
            $row['ordles_lesson_time_info'] = '';
            if (Lesson::SCHEDULED == $row['ordles_status']) {
                if ($row['ordles_currenttime_unix'] > $row['ordles_endtime_unix']) {
                    if ($row['ordles_offline'] == AppConstant::NO) {
                        $row['ordles_lesson_time_info'] = $passedLabel;
                    }
                } elseif ($row['ordles_currenttime_unix'] > $row['ordles_starttime_unix']) {
                    $row['ordles_lesson_time_info'] = $ongoingLabel;
                }
                $row['canCancelTill'] = strtotime(' -' . $duration . ' hours', $row['ordles_starttime_unix']);
            } elseif (Lesson::UNSCHEDULED == $row['ordles_status']) {
                $row['ordles_lesson_time_info'] = $needBeScheduled;
                $row['canCancelTill'] = strtotime('+12 hour', strtotime(MyDate::formatDate(date('Y-m-d H:i:s'))));
            }
            $row = $this->addUserDetails($row);
            $row['canPlaback'] = $this->canPlayback($row);
            $row['canRateLesson'] = $this->canRateLesson($row);
            $row['canReportIssue'] = $this->canReportIssue($row, $reportHours);
            $row['canCancelLesson'] = $this->canCancelLesson($row);
            $row['canScheduleLesson'] = $this->canScheduleLesson($row);
            $row['canRescheduleLesson'] = $this->canRescheduleLesson($row);
            $row['hasValidSubPlan'] = $this->hasValidSubPlan($row);
            $row['canEnd'] = $this->canEnd($row);
            $row['order_addedon'] = MyDate::formatDate($row['order_addedon']);
            $row['teacher_format_starttime'] = MyDate::formatDate($row['ordles_teacher_starttime']);
            $row['teacher_format_endtime'] = MyDate::formatDate($row['ordles_teacher_endtime']);
            $row['student_format_starttime'] = MyDate::formatDate($row['ordles_student_starttime']);
            $row['student_format_endtime'] = MyDate::formatDate($row['ordles_student_endtime']);
            if ($single) {
                $row['canJoin'] = $this->canJoin($row);
                $row['statusInfoLabel'] = $this->statusInfoLabel($row);
            }
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Apply Order By
     *
     * @param array $post
     * @return void
     */
    public function applyOrderBy(array $post): void
    {
        $status = $post['ordles_status'] ?? '-1';
        if ($status == '-1') {
            $this->addOrder('ordles_status');
            $this->addOrder('ordles_lesson_starttime');
        } elseif ($status == Lesson::UNSCHEDULED) {
            $this->addOrder('ordles_id', 'DESC');
        } else {
            $this->addOrder('ordles_lesson_starttime');
        }
    }

    /**
     * Fetch And Format Calendar Data
     *
     * @return array
     */
    public function fetchAndFormatCalendarData(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (empty($rows)) {
            return [];
        }
        $title = Label::getLabel('LBL_{teach-lang},{n}_minutes_of_Lesson');
        $teachLangIds = array_column($rows, 'ordles_tlang_id');
        $freeTrialLbl = Label::getLabel('LBL_FREE_TRIAL');
        $teachLangs = TeachLanguage::getNames($this->langId, $teachLangIds, false);
        foreach ($rows as $lesson) {
            $langName = $teachLangs[$lesson['ordles_tlang_id']] ?? '';
            if ($lesson['ordles_type'] == Lesson::TYPE_FTRAIL) {
                $langName = $freeTrialLbl;
            }
            $lessonData[] = [
                'start' => MyDate::formatDate($lesson['ordles_lesson_starttime']),
                'end' => MyDate::formatDate($lesson['ordles_lesson_endtime']),
                'title' => str_replace(['{teach-lang}', '{n}'], [$langName, $lesson['ordles_duration']], $title),
                'className' => ($lesson['ordles_offline'] == AppConstant::YES) ? 'fc-offline' : 'fc-online'
            ];
        }
        return $lessonData;
    }

    /**
     * Add User Details
     *
     * @param array $row
     * @return array
     */
    private function addUserDetails(array $row): array
    {
        $row['first_name'] = $row['teacher_first_name'];
        $row['last_name'] = $row['teacher_last_name'];
        $row['country_name'] = $row['ordles_teacher_country'];
        $row['user_id'] = $row['ordles_teacher_id'];
        if ($this->userType == User::TEACHER) {
            $row['first_name'] = $row['learner_first_name'];
            $row['last_name'] = $row['learner_last_name'];
            $row['country_name'] = $row['ordles_learner_country'];
            $row['user_id'] = $row['order_user_id'];
        }
        return $row;
    }

    /**
     * Can Playback Recording
     *
     * @param array $lesson
     * @return bool
     */
    private function canPlayback(array $lesson): bool
    {
        return (!empty($lesson['playback_url']) &&
            $lesson['ordles_status'] == Lesson::COMPLETED &&
            $lesson['ordles_lesson_endtime_utc'] < strtotime(date('Y-m-d H:i:s')));
    }

    /**
     * Can Rate Lesson
     *
     * @param array $lesson
     * @return bool
     */
    private function canRateLesson(array $lesson): bool
    {
        return (User::LEARNER == $this->userType &&
            Lesson::COMPLETED == $lesson['ordles_status'] &&
            AppConstant::NO == $lesson['ordles_reviewed'] &&
            FatApp::getConfig('CONF_ALLOW_REVIEWS'));
    }

    /**
     * Can Report Issue
     *
     * @param array $lesson
     * @param int $reportHours
     * @return bool
     */
    private function canReportIssue(array $lesson, int $reportHours): bool
    {
        if ($lesson['ordles_type'] == Lesson::TYPE_FTRAIL || !is_null($lesson['ordles_teacher_paid']) || $reportHours <= 0 || $this->userType != User::LEARNER || $lesson['repiss_id'] > 0) {
            return false;
        }
        $reportTime = strtotime(" +" . $reportHours . " hour", $lesson['ordles_endtime_unix']);
        return (
            ($lesson['ordles_status'] == Lesson::COMPLETED ||
                ($lesson['ordles_status'] == Lesson::SCHEDULED &&
                    empty($lesson['ordles_teacher_starttime']) && $lesson['ordles_currenttime_unix'] > $lesson['ordles_endtime_unix']
                )
            ) &&
            $reportTime > $lesson['ordles_currenttime_unix']);
    }

    /**
     * Can Cancel Lesson
     *
     * @param array $lesson
     * @return bool
     */
    private function canCancelLesson(array $lesson): bool
    {
        if (!in_array($lesson['ordles_status'], [Lesson::UNSCHEDULED, Lesson::SCHEDULED])) {
            return false;
        }
        return ((Lesson::UNSCHEDULED == $lesson['ordles_status'] ||
            (Lesson::SCHEDULED == $lesson['ordles_status'] &&
                $lesson['ordles_currenttime_unix'] < $lesson['canCancelTill']))) &&  $this->hasValidSubPlan($lesson);
    }

    /**
     * Can Schedule Lesson
     *
     * @param array $lesson
     * @return bool
     */
    private function canScheduleLesson(array $lesson): bool
    {
        return (User::LEARNER == $this->userType && Lesson::UNSCHEDULED == $lesson['ordles_status']) && $this->hasValidSubPlan($lesson);
    }

    /**
     * Can Reschedule Lesson
     *
     * @param array $lesson
     * @return bool
     */
    private function canRescheduleLesson(array $lesson): bool
    {
        $duration = FatApp::getConfig('CONF_LESSON_RESCHEDULE_DURATION');
        $startTime = strtotime(' -' . $duration . ' hours', $lesson['ordles_starttime_unix']);
        return (Lesson::SCHEDULED == $lesson['ordles_status'] && $lesson['ordles_currenttime_unix'] < $startTime) && $this->hasValidSubPlan($lesson);
    }

    /**
     * Can End Lesson
     *
     * @param array $lesson
     * @return bool
     */
    private function canEnd(array $lesson): bool
    {
        if ($lesson['ordles_status'] != Lesson::SCHEDULED) {
            return false;
        }
        if ($this->userType == User::LEARNER) {
            if ($lesson['ordles_offline'] == AppConstant::YES) {
                return false;
            }
            return (!empty($lesson['ordles_student_starttime']) && empty($lesson['ordles_student_endtime']));
        }
        if ($lesson['ordles_offline'] == AppConstant::NO && !empty($lesson['ordles_teacher_starttime']) && empty($lesson['ordles_teacher_endtime'])) {
            return true;
        }
        if ($lesson['ordles_offline'] == AppConstant::YES && empty($lesson['ordles_teacher_endtime'])) {
            $currentTime = strtotime(MyDate::formatDate(date('Y-m-d H:i:s')));
            $endTime = strtotime($lesson['ordles_lesson_endtime']);
            $duration = FatApp::getConfig('CONF_TEACHER_END_SESSION_DURATION');
            $endTimeWithDuration = strtotime('+' . $duration . ' hour', strtotime($lesson['ordles_lesson_endtime']));
            if ($endTime <= $currentTime && $currentTime <= $endTimeWithDuration) {
                return true;
            }
        }
        return false;
    }

    /**
     * Can Join Lesson
     *
     * @param array $lesson
     * @return bool
     */
    private function canJoin(array $lesson): bool
    {
        return ($lesson['ordles_status'] == Lesson::SCHEDULED &&
            $lesson['ordles_endtime_unix'] > $lesson['ordles_currenttime_unix'] &&
            $lesson['ordles_starttime_unix'] <= $lesson['ordles_currenttime_unix']);
    }

    /**
     * Status Info FLabel
     * @param array $lesson
     * @return string
     */
    private function statusInfoLabel(array $lesson): string
    {
        if (!$this->hasValidSubPlan($lesson)) {
            return Label::getLabel('LBL_LESSON_SUBSCRIPTION_NO_LONGER_ACTIVE');
        }
        if ($lesson['repiss_id'] > 0) {
            return Label::getLabel('LBL_ISSUE_IS_REPORTED_ON_THIS_LESSON');
        }
        $label = '';
        switch ($lesson['ordles_status']) {
            case Lesson::UNSCHEDULED:
                $label = 'LBL_LESSON_IS_UNSCHEDULED_ENCOURAGE_YOUR_STUDENT_TO_SCHEDULE_IT';
                if ($this->userType == User::LEARNER) {
                    $label = 'LBL_LESSON_IS_UNSCHEDULED_PLEASE_SCHEDULE_IT';
                }
                break;
            case Lesson::SCHEDULED:
                if ($lesson['ordles_endtime_unix'] < $lesson['ordles_currenttime_unix']) {
                    if ($this->userType == User::LEARNER) {
                        $label = 'LBL_THIS_LESSON_HAS_BEEN_PASSED_SCHEDULE_MORE_LESSONS';
                    } else {
                        $label = 'LBL_THIS_LESSON_HAS_BEEN_PASSED_TEACHER_MSG';
                    }
                }
                break;
            case Lesson::COMPLETED:
                $label = 'LBL_LESSON_IS_COMPLETED';
                if ($lesson['canRateLesson']) {
                    $label = 'LBL_LESSON_IS_COMPLETED_ENCOURAGE_YOUR_STUDENT_TO_RATE_IT';
                    if ($this->userType == User::LEARNER) {
                        $label = 'LBL_LESSON_IS_COMPLETED_YOU_CAN_SHARE_YOUR_FEEDBACK';
                    }
                }
                break;
            case Lesson::CANCELLED:
                $label = 'LBL_LESSON_HAS_BEEN_CANCELLED_SCHEDULE_MORE_LESSONS';
                break;
        }
        return empty($label) ? '' : Label::getLabel($label);
    }

    /**
     * Get Search Form
     *
     * @return Form
     */
    public static function getSearchForm(bool $forCalendar = false): Form
    {
        $status = ['-1' => Label::getLabel('LBL_ALL_LESSONS')] + Lesson::getStatuses();
        $frm = new Form('frmLessonSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword');
        $frm->addDateField(Label::getLabel('LBL_LESSON_STARTDATE'), 'ordles_lesson_starttime', MyDate::formatDate(date('Y-m-d')), ['readonly' => 'readonly']);
        $frm->addDateField(Label::getLabel('LBL_LESSON_ENDDATE'), 'ordles_lesson_endtime', '', ['readonly' => 'readonly']);
        $frm->addRadioButtons(Label::getLabel('LBL_VIEW'), 'view', AppConstant::getDisplayViews(), AppConstant::VIEW_LISTING);
        $frm->addRadioButtons(Label::getLabel('LBL_STATUS'), 'ordles_status', $status, '-1');
        $frm->addSelectBox(Label::getLabel('LBL_SERVICE_TYPE'), 'ordles_offline', AppConstant::getServiceType(), '', [], Label::getLabel('LBL_SELECT'));
        if ($forCalendar) {
            $frm->addRequiredField(Label::getLabel('LBL_START'), 'start');
            $frm->addRequiredField(Label::getLabel('LBL_END'), 'end');
        }
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', AppConstant::PAGESIZE)->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addHiddenField('', 'ordles_id')->requirements()->setInt();
        $frm->addHiddenField('', 'order_id')->requirements()->setInt();
        $frm->addHiddenField('', 'ordles_ordsplan_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Get Detail Fields
     *
     * @return array
     */
    public static function getDetailFields(): array
    {
        return static::getListingFields() + [
            'learner.user_deleted' => 'user_deleted',
            'teacher.user_deleted' => 'teacher_deleted',
            'ordles.ordles_ended_by' => 'ordles_ended_by',
        ];
    }

    /**
     * Get Listing Fields
     *
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'orders.order_id' => 'order_id',
            'orders.order_type' => 'order_type',
            'orders.order_user_id' => 'order_user_id',
            'orders.order_pmethod_id' => 'order_pmethod_id',
            'orders.order_discount_value' => 'order_discount_value',
            'orders.order_currency_code' => 'order_currency_code',
            'orders.order_currency_value' => 'order_currency_value',
            'orders.order_payment_status' => 'order_payment_status',
            'orders.order_total_amount' => 'order_total_amount',
            'orders.order_addedon' => 'order_addedon',
            'ordles.ordles_id' => 'ordles_id',
            'ordles.ordles_type' => 'ordles_type',
            'ordles.ordles_order_id' => 'ordles_order_id',
            'ordles.ordles_teacher_id' => 'ordles_teacher_id',
            'ordles.ordles_tlang_id' => 'ordles_tlang_id',
            'ordles.ordles_lesson_starttime' => 'ordles_lesson_starttime',
            'ordles.ordles_lesson_endtime' => 'ordles_lesson_endtime',
            'ordles.ordles_teacher_starttime' => 'ordles_teacher_starttime',
            'ordles.ordles_teacher_endtime' => 'ordles_teacher_endtime',
            'ordles.ordles_student_starttime' => 'ordles_student_starttime',
            'ordles.ordles_student_endtime' => 'ordles_student_endtime',
            'ordles.ordles_commission' => 'ordles_commission',
            'ordles.ordles_commission_amount' => 'ordles_commission_amount',
            'ordles.ordles_affiliate_commission' => 'ordles_affiliate_commission',
            'ordles.ordles_duration' => 'ordles_duration',
            'ordles.ordles_amount' => 'ordles_amount',
            'ordles.ordles_discount' => 'ordles_discount',
            'ordles.ordles_reward_discount' => 'ordles_reward_discount',
            'ordles.ordles_refund' => 'ordles_refund',
            'ordles.ordles_teacher_paid' => 'ordles_teacher_paid',
            'ordles.ordles_status' => 'ordles_status',
            'ordles.ordles_reviewed' => 'ordles_reviewed',
            'ordles.ordles_metool_id' => 'ordles_metool_id',
            'ordles.ordles_offline' => 'ordles_offline',
            'ordles.ordles_address' => 'ordles_address',
            'ordles.ordles_ordsplan_id' => 'ordles_ordsplan_id',
            'teacher.user_country_id' => 'teacher_country_id',
            'teacher.user_username' => 'teacher_username',
            'teacher.user_first_name' => 'teacher_first_name',
            'teacher.user_last_name' => 'teacher_last_name',
            'learner.user_country_id' => 'learner_country_id',
            'learner.user_username' => 'learner_username',
            'learner.user_first_name' => 'learner_first_name',
            'learner.user_last_name' => 'learner_last_name',
            'orders.order_reward_value' => 'order_reward_value',
            'orders.order_net_amount' => 'order_net_amount',
        ];
    }

    /**
     * Group Dates
     *
     * @param array $rows
     * @return array
     */
    public function groupDates(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }
        $classes = [];
        foreach ($rows as $row) {
            $key = Lesson::getStatuses($row['ordles_status']);
            if (!empty($row['ordles_starttime_unix'])) {
                $key = date('Y-m-d', $row['ordles_starttime_unix']);
            }
            if (isset($classes[$key])) {
                array_push($classes[$key], $row);
            } else {
                $classes[$key] = [$row];
            }
        }
        return $classes;
    }

    /**
     * is Valid Subscription Plan Lesson
     * 
     * @param array $lesson
     * @return bool
     */
    private function hasValidSubPlan(array $lesson): bool
    {
        if (empty($lesson['ordles_ordsplan_id'])) {
            return true;
        }
        $orderSubPlan =  OrderSubscriptionPlan::getActivePlan($lesson['order_user_id']);
        if (!empty($orderSubPlan)  &&  $orderSubPlan['ordsplan_id'] == $lesson['ordles_ordsplan_id']) {
            return true;
        }
        return false;
    }
}
