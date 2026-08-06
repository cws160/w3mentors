<?php

/**
 * This class is used to handle Issue
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Issue extends MyAppModel
{

    const DB_TBL = 'tbl_reported_issues';
    const DB_TBL_PREFIX = 'repiss_';
    const DB_TBL_LOG = 'tbl_reported_issues_log';
    const DB_TBL_LOG_PREFIX = 'reislo_';
    /* Issue Status */
    const STATUS_PROGRESS = 1;
    const STATUS_RESOLVED = 2;
    const STATUS_ESCALATED = 3;
    const STATUS_CLOSED = 4;
    /* Issue Actions */
    const ACTION_RESET_AND_UNSCHEDULED = 1;
    const ACTION_COMPLETE_ZERO_REFUND = 2;
    const ACTION_COMPLETE_HALF_REFUND = 3;
    const ACTION_COMPLETE_FULL_REFUND = 4;
    const ACTION_ESCALATE_TO_ADMIN = 5;
    /* Action User Types */
    const USER_TYPE_TEACHER = 2;
    const USER_TYPE_SUPPORT = 3;
    const ISSUE_REPORTED_NOTIFICATION = 1;
    const ISSUE_RESOLVE_NOTIFICATION = 2;
    /* Issue Types */
    const TYPE_LESSON = 1;
    const TYPE_GCLASS = 2;
    const TYPE_COURSE = 3;

    private $userId;
    private $userType;

    /**
     * Initialize Issue Model
     * 
     * @param int $id
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $id = 0, int $userId = 0, int $userType = 0)
    {
        $this->userId = $userId;
        $this->userType = $userType;
        parent::__construct(static::DB_TBL, 'repiss_id', $id);
        $this->objMainTableRecord->setSensitiveFields(['repiss_status']);
    }

    /**
     * Setup Issue
     * 
     * @param array $post
     * @return bool
     */
    public function setupIssue(int $langId, array $post): bool
    {
        $recordId = FatUtility::int($post['repiss_record_id']);
        $recordType = FatUtility::int($post['repiss_record_type']);
        if (!$this->validateRecord($recordId, $recordType)) {
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
            return false;
        }

        $options = IssueReportOptions::getOptionsArray($this->commonLangId, User::LEARNER);
        $this->setFldValue('repiss_status', static::STATUS_PROGRESS);
        $this->assignValues([
            'repiss_record_id' => $recordId,
            'repiss_record_type' => $recordType,
            'repiss_reported_by' => $this->userId,
            'repiss_reported_on' => date('Y-m-d H:i:s'),
            'repiss_comment' => $post['repiss_comment'],
            'repiss_title' => $options[$post['repiss_title']] ?? 'NA'
        ]);
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }

        if (!$this->sendUserNotification($langId)) {
            $db->rollbackTransaction();
            return false;
        }

        if (!$db->commitTransaction()) {
            $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
            $db->rollbackTransaction();
            return false;
        }
        return true;
    }

    private function sendIssueReportedMailToTeacher(array $data): bool
    {
        $teacherData = User::getAttributesById($data['teacher_id'], ['user_timezone', 'user_email', 'user_lang_id']);
        $sessionType = ($data['repiss_record_type'] == AppConstant::LESSON) ? Label::getLabel('LBL_LESSON', $teacherData['user_lang_id']) : Label::getLabel('LBL_CLASS', $teacherData['user_lang_id']);
        $data['ordles_lesson_starttime'] = MyDate::convert($data['ordles_lesson_starttime'], $teacherData['user_timezone']);
        $data['ordles_lesson_endtime'] = MyDate::convert($data['ordles_lesson_endtime'], $teacherData['user_timezone']);
        $vars = [
            '{teacher_name}' => ucwords($data['teacher_first_name'] . ' ' . $data['teacher_last_name']),
            '{learner_name}' => ucwords($data['learner_full_name']),
            '{session_type}' => $sessionType,
            '{class_lesson_name}' => $data['ordles_title'],
            '{schedule_date}' => MyDate::showDate($data['ordles_lesson_starttime'], false, $teacherData['user_lang_id']),
            '{start_time}' => MyDate::showTime($data['ordles_lesson_starttime'], $teacherData['user_lang_id']),
            '{end_time}' => MyDate::showTime($data['ordles_lesson_endtime'], $teacherData['user_lang_id']),
            '{issue_reason}' => $data['repiss_title'],
            '{learner_comment}' => nl2br($data['repiss_comment']),
        ];

        $mail = new FatMailer($teacherData['user_lang_id'], 'learner_issue_reported_email');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$teacherData['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    private function sendIssueResolvedMailToLearner(array $data): bool
    {
        $learnerData = User::getAttributesById($data['learner_id'], ['user_timezone', 'user_email', 'user_lang_id']);
        $sessionType = ($data['repiss_record_type'] == AppConstant::LESSON) ? Label::getLabel('LBL_LESSON', $learnerData['user_lang_id']) : Label::getLabel('LBL_CLASS', $learnerData['user_lang_id']);
        $data['ordles_lesson_starttime'] = MyDate::convert($data['ordles_lesson_starttime'], $learnerData['user_timezone']);
        $data['ordles_lesson_endtime'] = MyDate::convert($data['ordles_lesson_endtime'], $learnerData['user_timezone']);

        $logs = $this->getLogs();
        $logs = end($logs);

        $vars = [
            '{teacher_name}' => $data['teacher_first_name'] . ' ' . $data['teacher_last_name'],
            '{learner_name}' => $data['learner_full_name'],
            '{session_type}' => $sessionType,
            '{class_lesson_name}' => $data['ordles_title'],
            '{schedule_date}' => MyDate::showDate($data['ordles_lesson_starttime'], false, $learnerData['user_lang_id']),
            '{start_time}' => MyDate::showTime($data['ordles_lesson_starttime'], $learnerData['user_lang_id']),
            '{end_time}' => MyDate::showTime($data['ordles_lesson_endtime'], $learnerData['user_lang_id']),
            '{issue_resolve_type}' => self::getActionsArr($logs['reislo_action'], $learnerData['user_lang_id']),
            '{teacher_comment}' => nl2br($logs['reislo_comment']),
        ];

        $mail = new FatMailer($learnerData['user_lang_id'], 'teacher_issue_resolved_email');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$learnerData['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    private function sendIssueEscalatedMailToAdmin(array $data): bool
    {
        $langId = MyUtility::getSystemLanguage()['language_id'];
        $sessionType = ($data['repiss_record_type'] == AppConstant::LESSON) ? Label::getLabel('LBL_LESSON', $langId) : Label::getLabel('LBL_CLASS', $langId);

        $logs = $this->getLogs();
        $logs = end($logs);
        $scheduleDate = MyDate::formatDate($data['ordles_lesson_starttime'], 'Y-m-d', MyUtility::getSuperAdminTimeZone());
        $scheduleDate = MyDate::showDate($scheduleDate);
        $vars = [
            '{teacher_name}' => ucwords($data['teacher_first_name'] . ' ' . $data['teacher_last_name']),
            '{learner_name}' => ucwords($data['learner_full_name']),
            '{session_type}' => $sessionType,
            '{class_lesson_name}' => $data['ordles_title'],
            '{schedule_date}' =>  $scheduleDate,
            '{start_time}' =>   MyDate::showTime(MyDate::formatDate($data['ordles_lesson_starttime'], 'H:i', MyUtility::getSuperAdminTimeZone())),
            '{end_time}' => MyDate::showTime(MyDate::formatDate($data['ordles_lesson_endtime'], 'H:i', MyUtility::getSuperAdminTimeZone())) . ' (' . (MyUtility::getSuperAdminTimeZone() ?? MyUtility::getSiteTimezone()) . ')',
            '{time_offset}' => "(" . CONF_SERVER_TIMEZONE . " " . MyDate::getOffset(CONF_SERVER_TIMEZONE) . ")",
            '{learner_comment}' => nl2br($logs['reislo_comment']),
        ];
        $mail = new FatMailer($langId, 'issue_escalated_to_admin');
        $mail->setVariables($vars);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    private function sendIssueClosedMail(array $data): bool
    {

        $logs = $this->getLogs();
        $logs = end($logs);

        $learnerData = User::getAttributesById($data['learner_id'], ['user_timezone', 'user_email', 'user_lang_id']);
        $learnerLessonStarttime = MyDate::convert($data['ordles_lesson_starttime'], $learnerData['user_timezone']);
        $learnerLessonEndtime = MyDate::convert($data['ordles_lesson_endtime'], $learnerData['user_timezone']);
        $sessionType = ($data['repiss_record_type'] == AppConstant::LESSON) ? Label::getLabel('LBL_LESSON', $learnerData['user_lang_id']) : Label::getLabel('LBL_CLASS', $learnerData['user_lang_id']);
        $vars = [
            '{session_type}' => $sessionType,
            '{class_lesson_name}' => $data['ordles_title'],
            '{resolution_action}' => Issue::getActionsArr($logs['reislo_action'], $learnerData['user_lang_id']),
            '{admin_comment}' => nl2br($logs['reislo_comment']),
            '{schedule_date}' => MyDate::showDate($learnerLessonStarttime, false, $learnerData['user_lang_id']),
            '{start_time}' => MyDate::showTime($learnerLessonStarttime, $learnerData['user_lang_id']),
            '{end_time}' => MyDate::showTime($learnerLessonEndtime, $learnerData['user_lang_id']),
            '{user_name}' => ucwords($data['learner_full_name']),
        ];

        $mail = new FatMailer($learnerData['user_lang_id'], 'admin_closed_issue');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$learnerData['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }

        $teacherData = User::getAttributesById($data['teacher_id'], ['user_timezone', 'user_email', 'user_lang_id']);

        $teacherLessonStarttime = MyDate::convert($data['ordles_lesson_starttime'], $teacherData['user_timezone']);
        $teacherLessonEndtime = MyDate::convert($data['ordles_lesson_endtime'], $teacherData['user_timezone']);

        $sessionType = ($data['repiss_record_type'] == AppConstant::LESSON) ? Label::getLabel('LBL_LESSON', $teacherData['user_lang_id']) : Label::getLabel('LBL_CLASS', $teacherData['user_lang_id']);
        $vars['{session_type}'] = $sessionType;
        $vars['{resolution_action}'] = Issue::getActionsArr($logs['reislo_action'], $teacherData['user_lang_id']);
        $vars['{schedule_date}'] = MyDate::showDate($teacherLessonStarttime, false, $teacherData['user_lang_id']);
        $vars['{start_time}'] = MyDate::showTime($teacherLessonStarttime, $teacherData['user_lang_id']);
        $vars['{end_time}'] = MyDate::showTime($teacherLessonEndtime, $teacherData['user_lang_id']);
        $vars['{user_name}'] = ucwords($data['teacher_full_name']);

        $mail = new FatMailer($teacherData['user_lang_id'], 'admin_closed_issue');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$teacherData['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Setup Issue Action
     * 
     * Step 1. Add reported issue log 
     * Step 2. Set issue status & refund percentage
     * Step 3. Transaction settlement for refund percentage
     * Step 4. Update issue status and datetime
     * Step 5. Mark lesson detail record as paid
     * 
     * @param int $action
     * @param string $comment
     * @param bool $closed
     * @return bool
     */
    public function setupAction(int $action, string $comment, bool $closed = false): bool
    {
        $issueId = $this->getMainTableRecordId();
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
            return false;
        }
        if ($action === Issue::ACTION_ESCALATE_TO_ADMIN) {
            $logs = $this->getLogs();
            $log = end($logs);
            if ($log['reislo_added_by'] == $this->userId) {
                $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                return false;
            }
            $lastUpdated = static::getAttributesById($issueId, 'repiss_updated_on');
            $escalateHour = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
            $escalateDate = strtotime($lastUpdated . " +" . $escalateHour . " hour");
            if ($escalateDate <= strtotime(date('Y-m-d H:i:s'))) {
                $this->error = Label::getLabel('LBL_ISSUE_ESCALATION_TIME_HAS_PASSED');
                return false;
            }
        }
        /* Add reported issue log */
        $record = new TableRecord(Issue::DB_TBL_LOG);
        $record->assignValues([
            'reislo_action' => $action,
            'reislo_comment' => $comment,
            'reislo_repiss_id' => $issueId,
            'reislo_added_by' => $this->userId,
            'reislo_added_on' => date('Y-m-d H:i:s'),
            'reislo_added_by_type' => $this->userType
        ]);
        if (!$record->addNew()) {
            $this->error = $record->getError();
            $db->rollbackTransaction();
            return false;
        }
        /* Set issue status & refund percentage */
        switch ($action) {
            case static::ACTION_RESET_AND_UNSCHEDULED:
                $refund = 0;
                $teacherPaid = 0;
                $status = static::STATUS_RESOLVED;
                break;
            case static::ACTION_COMPLETE_ZERO_REFUND:
                $refund = 0;
                $teacherPaid = 1;
                $status = static::STATUS_RESOLVED;
                break;
            case static::ACTION_COMPLETE_HALF_REFUND:
                $refund = 50;
                $teacherPaid = 1;
                $status = static::STATUS_RESOLVED;
                break;
            case static::ACTION_COMPLETE_FULL_REFUND:
                $refund = 100;
                $teacherPaid = 1;
                $status = static::STATUS_RESOLVED;
                break;
            case static::ACTION_ESCALATE_TO_ADMIN:
                $refund = 0;
                $teacherPaid = 0;
                $status = static::STATUS_ESCALATED;
                break;
        }
        if ($closed) {
            $status = static::STATUS_CLOSED;
        }
        /* Transaction settlement for refund percentage */
        if ($closed && !$this->executeLessonTransactions($refund, $action, $teacherPaid)) {
            $db->rollbackTransaction();
            return false;
        }
        /* Update issue status and datetime */
        $this->setFldValue('repiss_status', $status);
        $this->setFldValue('repiss_last_action', $action);
        $this->setFldValue('repiss_updated_on', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }
        /* Mark lesson detail record as paid */
        if ($status == static::STATUS_CLOSED) {
            $issue = static::getAttributesById($issueId, ['repiss_record_id', 'repiss_record_type', 'repiss_reported_by']);
            if ($issue['repiss_record_type'] == AppConstant::LESSON) {

                if ($action == static::ACTION_RESET_AND_UNSCHEDULED) {
                    $lessonStartTime = Lesson::getAttributesById($issue['repiss_record_id'], 'ordles_lesson_starttime');
                    $record = new TableRecord(Lesson::DB_TBL);
                    $record->setFlds([
                        'ordles_lesson_starttime' => NULL,
                        'ordles_lesson_endtime' => NULL,
                        'ordles_teacher_starttime' => NULL,
                        'ordles_teacher_endtime' => NULL,
                        'ordles_student_starttime' => NULL,
                        'ordles_student_endtime' => NULL,
                        'ordles_status' => Lesson::UNSCHEDULED,
                        'ordles_updated' => date('Y-m-d H:i:s')
                    ]);
                    if (!$record->update(['smt' => 'ordles_id = ?', 'vals' => [$issue['repiss_record_id']]])) {
                        $db->rollbackTransaction();
                        $this->error = $record->getError();
                        return false;
                    }
                    $where = [
                        'smt' => 'meet_record_id = ? and meet_record_type = ?',
                        'vals' => [$issue['repiss_record_id'], AppConstant::LESSON]
                    ];
                    if (!$db->deleteRecords(Meeting::DB_TBL, $where)) {
                        $db->rollbackTransaction();
                        $this->error = $db->getError();
                        return false;
                    }
                    /* regenerate teacher hourly stats after reset/reschedule  */
                    (new HoursTaughtStat())->regenerate($lessonStartTime);
                } else {
                    $record = new TableRecord(Lesson::DB_TBL);
                    $record->setFldValue('ordles_status', Lesson::COMPLETED);
                    $record->setFldValue('ordles_updated', date('Y-m-d H:i:s'));
                    $stmt = ['smt' => 'ordles_id = ?', 'vals' => [$issue['repiss_record_id']]];
                    if (!$record->update($stmt)) {
                        $db->rollbackTransaction();
                        $this->error = $record->getError();
                        return false;
                    }
                    $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
                    $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
                    $srch->addCondition('ordles.ordles_id', '=', $issue['repiss_record_id']);
                    $srch->doNotCalculateRecords();
                    $srch->setPageSize(1);
                    $srch->addMultipleFields(['ordles_id', 'ordles_type', 'orders.order_user_id', 'ordles_ordsplan_id', 'ordles_status']);
                    $row = FatApp::getDb()->fetch($srch->getResultSet());
                    if (!empty($row['ordles_ordsplan_id'])) {
                        $lesson = new Lesson($row['ordles_id'], $row['order_user_id'], User::SYSTEMS);
                        if (!$lesson->updateUserSubStatus($row['order_user_id'])) {
                            $db->rollbackTransaction();
                            $this->error = $lesson->getError();
                            return false;
                        }
                    }
                }
            } else {
                $grpclsId = OrderClass::getAttributesById($issue['repiss_record_id'], 'ordcls_grpcls_id');
                $record = new TableRecord(GroupClass::DB_TBL);
                $record->setFldValue('grpcls_status', GroupClass::COMPLETED);
                $stmt = ['smt' => 'grpcls_id = ?', 'vals' => [$grpclsId]];
                if (!$record->update($stmt)) {
                    $db->rollbackTransaction();
                    $this->error = $record->getError();
                    return false;
                }
                $record = new TableRecord(OrderClass::DB_TBL);
                $record->setFldValue('ordcls_status', OrderClass::COMPLETED);
                $record->setFldValue('ordcls_updated', date('Y-m-d H:i:s'));
                $stmt = ['smt' => 'ordcls_id = ?', 'vals' => [$issue['repiss_record_id']]];
                if (!$record->update($stmt)) {
                    $db->rollbackTransaction();
                    $this->error = $record->getError();
                    return false;
                }
            }
        }
        if (!$this->sendUserNotification()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->commitTransaction()) {
            $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
            $db->rollbackTransaction();
            return false;
        }
        return true;
    }

    /**
     * Execute Lesson Transactions
     * 
     * @param int $refundPercent
     * @param int $action
     * @param bool $teacherPaid
     * @return bool
     * 
     */
    private function executeLessonTransactions(int $refundPercent, int $action, bool $teacherPaid): bool
    {
        if ($refundPercent == 0 && $action != static::ACTION_COMPLETE_ZERO_REFUND) {
            return true;
        }
        /* Get Issue detail */
        $langId = MyUtility::getSiteLangId();
        $issue = $this->getIssueDetail($langId);
        $issue['refundPercent'] = $refundPercent;
        if (empty($issue)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $teacherLangId = User::getAttributesById($issue['teacher_id'], 'user_lang_id');
        $learnerLangId = User::getAttributesById($issue['repiss_reported_by'], 'user_lang_id');
        if ($issue['repiss_record_type'] == AppConstant::GCLASS) {
            $itemAmount = $issue['ordcls_amount'];
            $paymentComment = Label::getLabel('LBL_PAYMENT_ON_CLASS_{recordid}', $teacherLangId);
            $refundComment = Label::getLabel('LBL_{percent}_REFUND_ON_CLASS_{recordid}', $learnerLangId);
        } else {
            $itemAmount = $issue['ordles_amount'];
            if (!empty($issue['ordles_ordsplan_id'])) {
                $subPlan = OrderSubscriptionPlan::getAttributesById($issue['ordles_ordsplan_id'], ['ordsplan_lesson_amount', 'ordsplan_lessons', 'ordsplan_amount']);
                $issue['ordles_amount'] = $subPlan['ordsplan_lesson_amount'];
                $itemAmount = round($subPlan['ordsplan_amount'] / $subPlan['ordsplan_lessons'], 2);
            }
            $paymentComment = Label::getLabel('LBL_PAYMENT_ON_LESSON_{recordid}', $teacherLangId);
            $refundComment = Label::getLabel('LBL_{percent}_REFUND_ON_LESSON_{recordid}', $learnerLangId);
        }
        /* Refund to Student */
        $price = ($issue['ordles_amount'] - $issue['ordles_discount'] - $issue['ordles_reward_discount']);
        $refund = FatUtility::float(($refundPercent / 100) * $price);
        $issue['ordles_refund'] = $refund;
        if (!$this->upadateRefundAmount($issue)) {
            return false;
        }
        if ($refund > 0) {
            $refundComment = str_replace(['{percent}', '{recordid}'], [$refundPercent, $issue['repiss_record_id']], $refundComment);
            $issue['refundComment'] = $refundComment;
            $txn = new Transaction($issue['repiss_reported_by'], Transaction::TYPE_LEARNER_REFUND);
            if (!$txn->credit($refund, $refundComment)) {
                $this->error = $txn->getError();
                return false;
            }
        }
        $paymentAmount = 0;
        $commissionAmount = 0;
        /* Payment to Teacher */
        $issue['paymentAmount'] = $paymentAmount;
        $issue['commissionAmount'] = $commissionAmount;
        $teacherPercent = (100 - $refundPercent);
        if ($teacherPercent > 0) {
            $paymentAmount = round((($teacherPercent / 100) * $itemAmount), 2);
            $commissionAmount = round((($issue['commission'] / 100) * $paymentAmount), 2);
            $paymentAmount = $paymentAmount - $commissionAmount;
            $sessionId = ($issue['repiss_record_type'] == AppConstant::GCLASS) ? $issue['grpcls_id'] : $issue['repiss_record_id'];
            $paymentComment = str_replace(['{recordid}', '{percent}'], [$sessionId, $teacherPercent], $paymentComment);
            $txn = new Transaction($issue['teacher_id'], Transaction::TYPE_TEACHER_PAYMENT);
            if (!$txn->credit($paymentAmount, $paymentComment)) {
                $this->error = $txn->getError();
                return false;
            }
            $issue['paymentAmount'] = $paymentAmount;
            $issue['paymentComment'] = $paymentComment;
            $issue['commissionAmount'] = $commissionAmount;
        }
        if ($teacherPaid && !$this->updatePaidStatus($issue)) {
            return false;
        }
        if ($refund > 0 || $teacherPercent > 0) {
            $this->sendRefundNotification($issue);
        }
        return true;
    }

    /**
     * Update Refund Amount
     * 
     * @param array $issue
     * @return bool
     */
    private function upadateRefundAmount(array $issue): bool
    {
        if ($issue['repiss_record_type'] == AppConstant::LESSON) {
            $lesson = new Lesson($issue['repiss_record_id'], 0, 0);
            $lesson->setFldValue('ordles_refund', $issue['ordles_refund']);
            $lesson->setFldValue('ordles_updated', date('Y-m-d H:i:s'));
            if (!$lesson->save()) {
                $this->error = $lesson->getError();
                return false;
            }
            return true;
        }
        $record = new TableRecord(OrderClass::DB_TBL);
        $record->setFldValue('ordcls_refund', $issue['ordles_refund']);
        $record->setFldValue('ordcls_updated', date('Y-m-d H:i:s'));
        if (!$record->update(['smt' => 'ordcls_id = ?', 'vals' => [$issue['ordcls_id']]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Paid Status
     * 
     * @param array $issue
     * @return bool
     */
    private function updatePaidStatus(array $issue): bool
    {
        $earnings = $issue['ordles_amount'] - ($issue['ordles_discount'] + $issue['ordles_refund'] + $issue['paymentAmount'] + $issue['ordles_reward_discount']);
        $txn = new AdminTransaction($issue['ordles_id'], $issue['repiss_record_type']);
        if (!$txn->logEarningTxn($earnings)) {
            $this->error = $txn->getError();
            return false;
        }
        if ($issue['repiss_record_type'] == AppConstant::LESSON) {
            $lesson = new Lesson($issue['repiss_record_id'], 0, 0);
            $lesson->setFldValue('ordles_updated', date('Y-m-d H:i:s'));
            $lesson->setFldValue('ordles_earnings', FatUtility::float($earnings));
            $lesson->setFldValue('ordles_teacher_paid', FatUtility::float($issue['paymentAmount']));
            $lesson->setFldValue('ordles_commission_amount', FatUtility::float($issue['commissionAmount']));

            if (!$lesson->save()) {
                $this->error = $lesson->getError();
                return false;
            }
            return true;
        }
        $record = new TableRecord(OrderClass::DB_TBL);
        $record->setFldValue('ordcls_updated', date('Y-m-d H:i:s'));
        $record->setFldValue('ordcls_earnings', FatUtility::float($earnings));
        $record->setFldValue('ordcls_teacher_paid', FatUtility::float($issue['paymentAmount']));
        $record->setFldValue('ordcls_commission_amount', FatUtility::float($issue['commissionAmount']));
        if (!$record->update(['smt' => 'ordcls_id = ?', 'vals' => [$issue['ordcls_id']]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Send Refund Notification
     * 
     * @param array $issue
     * @return bool
     */
    private function sendRefundNotification(array $issue): bool
    {
        $link = ($issue['repiss_record_type'] == AppConstant::GCLASS) ? 'Classes' : 'Lessons';
        if (!empty($issue['paymentAmount']) && $issue['ordles_refund'] > 0) {
            $vars = [
                '{amount}' => MyUtility::formatMoney($issue['ordles_refund']),
                '{reason}' => $issue['refundComment'] ?? '',
                '{link}' => MyUtility::makeUrl($link, 'view', [$issue['repiss_record_id']], CONF_WEBROOT_DASHBOARD),
            ];
            $notifi = new Notification($issue['repiss_reported_by'], Notification::TYPE_WALLET_CREDIT);
            $notifi->sendNotification($vars, User::LEARNER);
        }
        if (!empty($issue['paymentAmount']) && $issue['paymentAmount'] > 0) {
            $classId = ($issue['repiss_record_type'] == AppConstant::GCLASS) ? $issue['grpcls_id'] : $issue['repiss_record_id'];
            $vars = [
                '{amount}' => MyUtility::formatMoney($issue['paymentAmount']),
                '{reason}' => $issue['paymentComment'] ?? '',
                '{link}' => MyUtility::makeUrl($link, 'view', [$classId], CONF_WEBROOT_DASHBOARD),
            ];
            $notifi = new Notification($issue['teacher_id'], Notification::TYPE_WALLET_CREDIT);
            $notifi->sendNotification($vars, User::TEACHER);
        }
        return true;
    }

    /**
     * Send User Notification
     * 
     * @param int $langId
     * @return bool
     */
    private function sendUserNotification(int $langId = 0): bool
    {
        $issue = $this->getIssueDetail($langId);
        $lessonId = $issue['repiss_record_id'];
        $controller = ($issue['repiss_record_type'] == AppConstant::LESSON) ? 'Lessons' : 'Classes';
        $userType = User::LEARNER;
        $lessonLbl = Label::getLabel('LBL_LESSON');
        if ($issue['repiss_record_type'] == AppConstant::CLASS_GROUP) {
            $lessonLbl = Label::getLabel('LBL_GROUP_CLASS');
        }
        switch ($issue['repiss_status']) {
            case static::STATUS_PROGRESS:
                $userId = $issue['teacher_id'];
                $userType = User::TEACHER;
                $notiType = Notification::TYPE_ISSUE_REPORTED;
                if ($issue['repiss_record_type'] == AppConstant::CLASS_GROUP) {
                    $lessonId = $issue['grpcls_id'];
                }
                $this->sendIssueReportedMailToTeacher($issue);
                break;
            case static::STATUS_RESOLVED:
                $userId = $issue['repiss_reported_by'];
                $notiType = Notification::TYPE_ISSUE_RESOLVED;
                $this->sendIssueResolvedMailToLearner($issue);
                break;
            case static::STATUS_ESCALATED:
                $userType = User::TEACHER;
                $userId = $issue['teacher_id'];
                $notiType = Notification::TYPE_ISSUE_ESCALATED;
                if ($issue['repiss_record_type'] == AppConstant::CLASS_GROUP) {
                    $lessonId = $issue['grpcls_id'];
                }
                $this->sendIssueEscalatedMailToAdmin($issue);
                break;
            case static::STATUS_CLOSED:
                $userId = $issue['repiss_reported_by'];
                $notiType = Notification::TYPE_ISSUE_CLOSED;
                $this->sendIssueClosedMail($issue);
                break;
            default:
                $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                return false;
        }



        $link = MyUtility::makeUrl($controller, 'view', [$lessonId], CONF_WEBROOT_DASHBOARD);
        $notifi = new Notification($userId, $notiType);
        if (!$notifi->sendNotification(['{link}' => $link, '{lesson-id}' => $lessonId, '{lesson-type}' => $lessonLbl], $userType)) {
            $this->error = $notifi->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Issue Logs
     * 
     * @return array
     */
    public function getLogs(): array
    {
        $srch = new SearchBase(Issue::DB_TBL_LOG, 'reislo');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'user.user_id = reislo.reislo_added_by and reislo.reislo_added_by_type IN (1,2)', 'user');
        $srch->joinTable('tbl_admin', 'LEFT JOIN', 'admin.admin_id = reislo.reislo_added_by and reislo.reislo_added_by_type IN (3)', 'admin');
        $srch->addCondition('reislo_repiss_id', '=', $this->getMainTableRecordId());
        $srch->addMultipleFields([
            'reislo_repiss_id',
            'reislo_action',
            'reislo_comment',
            'reislo_added_on',
            'reislo_added_by',
            'reislo_added_by_type',
            'CASE WHEN reislo_added_by_type = 3 THEN admin.admin_name ELSE CONCAT(user.user_first_name, " ", user.user_last_name) END as user_fullname'
        ]);
        $srch->addOrder('reislo.reislo_id', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($rows as $key => $row) {
            $row['reislo_added_on'] = MyDate::formatDate($row['reislo_added_on']);
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Validate Record
     * 
     * @param int $recordId
     * @param int $recordType
     * @return bool
     */
    public function validateRecord(int $recordId, int $recordType): bool
    {
        switch ($recordType) {
            case AppConstant::LESSON:
                return $this->validateLessonToReport($recordId);
            case AppConstant::GCLASS:
                return $this->validateClassToReport($recordId);
            default:
                $this->error = Label::getLabel('LBL_INVALID_REQUSET');
                return false;
        }
    }

    /**
     * Validate Lesson To Report
     * 
     * @param int $lessonId
     * @return boolean
     */
    public function validateLessonToReport(int $lessonId)
    {
        $lesson = new Lesson($lessonId, $this->userId, User::LEARNER);
        if (!$lesson->getLessonToReport()) {
            $this->error = $lesson->getError();
            return false;
        }
        return true;
    }

    /**
     * Validate Class To Report
     * 
     * @param int $classId
     * @return bool
     */
    public function validateClassToReport(int $classId): bool
    {
        $class = new OrderClass($classId, $this->userId, User::LEARNER);
        if (!$class->getClassToReport()) {
            $this->error = $class->getError();
            return false;
        }
        return true;
    }

    /**
     * Resolved Issue Transaction Settlement cronjob
     *
     * @return bool
     */
    public static function resolvedIssueSettlement(): bool
    {
        $hours = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
        $srch = new SearchBase(Issue::DB_TBL, 'repiss');
        $srch->joinTable(Order::DB_TBL_LESSON, 'LEFT JOIN', 'repiss.repiss_record_type = ' . Issue::TYPE_LESSON . ' AND repiss.repiss_record_id = ordles.ordles_id AND ordles.ordles_teacher_paid IS NULL ', 'ordles');
        $srch->joinTable(OrderClass::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_type = ' . Issue::TYPE_GCLASS . ' AND repiss.repiss_record_id = ordcls.ordcls_grpcls_id AND ordcls.ordcls_teacher_paid IS NULL ', 'ordcls');
        $srch->addMultipleFields(['repiss.repiss_id', 'ordles.ordles_id', 'ordcls.ordcls_grpcls_id']);
        // $srch->addDirectCondition('DATE_ADD(repiss.repiss_updated_on, INTERVAL ' . $hours . ' HOUR) < NOW()', 'AND');
        $srch->addCondition('repiss.repiss_status', '=', Issue::STATUS_RESOLVED);
        $srch->addOrder('repiss.repiss_id', 'ASC');
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        while ($issue = FatApp::getDb()->fetch($resultSet)) {
            $srch = new SearchBase('tbl_reported_issues_log');
            $srch->addCondition('reislo_repiss_id', '=', $issue['repiss_id']);
            $srch->addOrder('reislo_id', 'DESC');
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $log = FatApp::getDb()->fetch($srch->getResultSet());
            $repIssue = new Issue($issue['repiss_id'], 1, Issue::USER_TYPE_SUPPORT);
            $comment = ($log['reislo_action'] != static::ACTION_RESET_AND_UNSCHEDULED) ? Label::getLabel('RESOLVED_ISSUE_TRANSACTION') : Label::getLabel('LBL_CLASS_UNSCHEDULED');
            if (!$repIssue->setupAction($log['reislo_action'], $comment, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Completed Lesson Transaction Settlement cronjob
     * 
     * Step 1. Get completed lesson without issue
     * Step 2. Add payment to teacher's wallet
     * Step 3. Mark lesson detail record as paid
     * 
     * @return bool
     */
    public function completedLessonSettlement(): bool
    {
        /* Get completed lesson without issue */
        $hours = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION');
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->joinTable(Issue::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_id = ordles.ordles_id AND repiss.repiss_reported_by = orders.order_user_id and repiss_record_type = ' . AppConstant::LESSON, 'repiss');
        $srch->addMultipleFields([
            'ordles.ordles_id',
            'ordles.ordles_teacher_paid',
            'ordles.ordles_order_id',
            'ordles.ordles_teacher_id',
            'ordles.ordles_amount',
            'ordles.ordles_discount',
            'ordles.ordles_commission',
            'ordles.ordles_reward_discount',
            'order_user_id',
            'ordles.ordles_type',
            'repiss.repiss_id',
            'ordles.ordles_ordsplan_id'
        ]);
        $srch->addCondition('ordles.ordles_status', '=', Lesson::COMPLETED);
        $srch->addDirectCondition('DATE_ADD(ordles_lesson_endtime, INTERVAL ' . $hours . ' HOUR) < NOW()', 'AND');
        $srch->addDirectCondition('ordles.ordles_teacher_paid IS NULL');
        $srch->addDirectCondition('((repiss.repiss_id IS NULL) OR (repiss.repiss_status = ' . static::STATUS_CLOSED . ' AND repiss.repiss_last_action = ' . static::ACTION_RESET_AND_UNSCHEDULED . '))', 'AND');
        $srch->addOrder('ordles.ordles_id', 'ASC');
        $srch->doNotCalculateRecords();
        $lessons =  FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($lessons)) {
            return true;
        }
        $affCommissionArr = [];
        if (User::isAffiliateEnabled()) {
            $learnerIds = array_column($lessons, 'order_user_id');
            $teacherIds = array_column($lessons, 'ordles_teacher_id');
            $userIds = array_merge($learnerIds, $teacherIds);
            $affCommissionArr = AffiliateCommission::getCommission($userIds);
            if (!empty($affCommissionArr)) {
                $globalCommission = AffiliateCommission::getGlobalCommission();
            }
        }
        foreach ($lessons as $lesson) {
            $data = [];
            $db = FatApp::getDb();
            if (!$db->startTransaction()) {
                $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
                return false;
            }
            $discounts = $lesson['ordles_discount'] + $lesson['ordles_reward_discount'];
            if (!empty($lesson['ordles_ordsplan_id'])) {
                $subPlan = OrderSubscriptionPlan::getAttributesById($lesson['ordles_ordsplan_id'], ['ordsplan_lesson_amount', 'ordsplan_lessons', 'ordsplan_amount', 'ordsplan_discount', 'ordsplan_reward_discount']);
                $lesson['ordles_amount'] = round($subPlan['ordsplan_amount'] / $subPlan['ordsplan_lessons'], 2);
                $couponDiscount = round($subPlan['ordsplan_discount'] / $subPlan['ordsplan_lessons'], 2);
                $rewardDiscount = round($subPlan['ordsplan_reward_discount'] / $subPlan['ordsplan_lessons'], 2);
                $discounts = $couponDiscount + $rewardDiscount;
            }
            /* Add payment to teacher's wallet */
            $commission = ($lesson['ordles_commission'] / 100) * $lesson['ordles_amount'];
            $teacherAmount = round(($lesson['ordles_amount'] - $commission), 2);
            if ($teacherAmount > 0) {
                $comment = str_replace('{lessonid}', $lesson['ordles_id'], Label::getLabel('LBL_PAYMENT_ON_LESSON_{lessonid}'));
                $txn = new Transaction($lesson['ordles_teacher_id'], Transaction::TYPE_TEACHER_PAYMENT);
                if (!$txn->credit($teacherAmount, $comment)) {
                    $this->error = $txn->getError();
                    $db->rollbackTransaction();
                    return false;
                }
            }

            $affiliateCommission = 0.00;

            /* Credit reward points */
            if (empty($lesson['repiss_id']) && $lesson['ordles_type'] != Lesson::TYPE_FTRAIL) {
                $record = new RewardPoint($lesson['order_user_id']);
                if (!$record->purchaseRewards()) {
                    $this->error = $record->getError();
                    $db->rollbackTransaction();
                    return false;
                }
                /* settle Affiliate Order Commission */
                if (isset($affCommissionArr[$lesson['order_user_id']])) {
                    $data[] = [
                        'affiliate_id' => $affCommissionArr[$lesson['order_user_id']]['affiliate_user_id'],
                        'user_name' => $affCommissionArr[$lesson['order_user_id']]['user_first_name'] . " " . $affCommissionArr[$lesson['order_user_id']]['user_last_name'],
                        'afcomm_commission' => ($affCommissionArr[$lesson['order_user_id']]['afcomm_commission']) ??  $globalCommission,
                    ];
                }
                if (isset($affCommissionArr[$lesson['ordles_teacher_id']])) {
                    $data[] = [
                        'affiliate_id' => $affCommissionArr[$lesson['ordles_teacher_id']]['affiliate_user_id'],
                        'user_name' => $affCommissionArr[$lesson['ordles_teacher_id']]['user_first_name'] . " " . $affCommissionArr[$lesson['ordles_teacher_id']]['user_last_name'],
                        'afcomm_commission' => ($affCommissionArr[$lesson['ordles_teacher_id']]['afcomm_commission']) ??  $globalCommission,
                    ];
                }

                if (!empty($data)) {
                    if (!$this->setupAffiliateSessionCommission($data, $lesson['ordles_amount'], $affiliateCommission)) {
                        $this->error = $this->getError();
                        $db->rollbackTransaction();
                        return false;
                    }
                    $affiliateCommission = round(($affiliateCommission), 2);
                }
            }

            /* Mark lesson detail record as paid */
            $whr = ['smt' => 'ordles_id = ?', 'vals' => [$lesson['ordles_id']]];
            $record = new TableRecord(Lesson::DB_TBL);
            $record->setFldValue('ordles_teacher_paid', $teacherAmount);
            $record->setFldValue('ordles_commission_amount', $commission);
            $earnings = $lesson['ordles_amount'] - ($discounts + $teacherAmount + $affiliateCommission);
            $record->setFldValue('ordles_earnings', FatUtility::float($earnings));
            $record->setFldValue('ordles_affiliate_commission', FatUtility::float($affiliateCommission));
            $record->setFldValue('ordles_updated', date('Y-m-d H:i:s'));
            if (!$record->update($whr)) {
                $this->error = $record->getError();
                $db->rollbackTransaction();
                return false;
            }

            $txn = new AdminTransaction($lesson['ordles_id'], AppConstant::LESSON);
            if (!$txn->logEarningTxn($earnings)) {
                $this->error = $txn->getError();
                return false;
            }
            if (!$db->commitTransaction()) {
                $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
                $db->rollbackTransaction();
                return false;
            }
        }
        return true;
    }

    /**
     * Completed Lesson Transaction Settlement cronjob
     * 
     * Step 1. Get completed lesson without issue
     * Step 2. Add payment to teacher's wallet
     * Step 3. Mark lesson detail record as paid
     * 
     * @return bool
     */
    public function completedClassSettlement(): bool
    {
        /* Get completed lesson without issue */
        $hours = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION');
        $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(Issue::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_id = ordcls.ordcls_id and '
            . ' repiss.repiss_reported_by = orders.order_user_id and repiss_record_type = ' . AppConstant::GCLASS, 'repiss');
        $srch->addMultipleFields([
            'ordcls.ordcls_order_id',
            'ordcls.ordcls_grpcls_id',
            'ordcls.ordcls_id',
            'ordcls.ordcls_discount',
            'ordcls.ordcls_commission',
            'grpcls.grpcls_teacher_id',
            'ordcls.ordcls_amount',
            'ordcls.ordcls_teacher_paid',
            'ordcls.ordcls_reward_discount',
            'order_user_id'
        ]);
        $srch->addDirectCondition('DATE_ADD(grpcls.grpcls_end_datetime, INTERVAL ' . $hours . ' HOUR) < "' . date('Y-m-d H:i:s') . '"', 'AND');
        $srch->addCondition('ordcls.ordcls_status', '=', OrderClass::COMPLETED);
        $srch->addDirectCondition('ordcls.ordcls_teacher_paid IS NULL');
        $srch->addDirectCondition('repiss.repiss_id IS NULL', 'AND');
        $srch->addOrder('ordcls.ordcls_id', 'ASC');
        $srch->doNotCalculateRecords();
        $classes =  FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($classes)) {
            return true;
        }
        $affCommissionArr = [];
        if (User::isAffiliateEnabled()) {
            $learnerIds = array_column($classes, 'order_user_id');
            $teacherIds = array_column($classes, 'grpcls_teacher_id');
            $userIds = array_merge($learnerIds, $teacherIds);
            $affCommissionArr = AffiliateCommission::getCommission($userIds);
            if (!empty($affCommissionArr)) {
                $globalCommission = AffiliateCommission::getGlobalCommission();
            }
        }
        $db = FatApp::getDb();
        foreach ($classes as $class) {
            $data = [];
            if (!$db->startTransaction()) {
                $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
                return false;
            }
            /* Add payment to teacher's wallet */
            $commission = ($class['ordcls_commission'] / 100) * $class['ordcls_amount'];
            $teacherAmount = round(($class['ordcls_amount'] - $commission), 2);
            if ($teacherAmount > 0) {
                $comment = Label::getLabel('LBL_PAYMENT_ON_CLASS_{classid}_{percent}');
                $comment = str_replace(['{classid}', '{percent}'], [$class['ordcls_grpcls_id'], 100], $comment);
                $txn = new Transaction($class['grpcls_teacher_id'], Transaction::TYPE_TEACHER_PAYMENT);
                if (!$txn->credit($teacherAmount, $comment)) {
                    $this->error = $txn->getError();
                    $db->rollbackTransaction();
                    return false;
                }
            }

            /* Credit reward points */
            $record = new RewardPoint($class['order_user_id']);
            if (!$record->purchaseRewards()) {
                $this->error = $record->getError();
                $db->rollbackTransaction();
                return false;
            }

            $affiliateCommission = 0.00;
            /* settle Affiliate Order Commission */
            if (isset($affCommissionArr[$class['order_user_id']])) {
                $data[] = [
                    'affiliate_id' => $affCommissionArr[$class['order_user_id']]['affiliate_user_id'],
                    'user_name' => $affCommissionArr[$class['order_user_id']]['user_first_name'] . " " . $affCommissionArr[$class['order_user_id']]['user_last_name'],
                    'afcomm_commission' => ($affCommissionArr[$class['order_user_id']]['afcomm_commission']) ??  $globalCommission,
                ];
            }
            if (isset($affCommissionArr[$class['grpcls_teacher_id']])) {
                $data[] = [
                    'affiliate_id' => $affCommissionArr[$class['grpcls_teacher_id']]['affiliate_user_id'],
                    'user_name' => $affCommissionArr[$class['grpcls_teacher_id']]['user_first_name'] . " " . $affCommissionArr[$class['grpcls_teacher_id']]['user_last_name'],
                    'afcomm_commission' => ($affCommissionArr[$class['grpcls_teacher_id']]['afcomm_commission']) ??  $globalCommission,
                ];
            }

            if (!empty($data)) {
                if (!$this->setupAffiliateSessionCommission($data, $class['ordcls_amount'], $affiliateCommission)) {
                    $this->error = $this->getError();
                    $db->rollbackTransaction();
                    return false;
                }
                $affiliateCommission = round(($affiliateCommission), 2);
            }
            /* Mark class detail record as paid */
            $whr = ['smt' => 'ordcls_id = ?', 'vals' => [$class['ordcls_id']]];
            $record = new TableRecord(OrderClass::DB_TBL);
            $record->setFldValue('ordcls_teacher_paid', $teacherAmount);
            $record->setFldValue('ordcls_commission_amount', $commission);
            $earnings = $class['ordcls_amount'] - ($class['ordcls_discount'] + $teacherAmount + $class['ordcls_reward_discount'] + $affiliateCommission);
            $record->setFldValue('ordcls_earnings', FatUtility::float($earnings));
            $record->setFldValue('ordcls_affiliate_commission', FatUtility::float($affiliateCommission));
            $record->setFldValue('ordcls_updated', date('Y-m-d H:i:s'));
            if (!$record->update($whr)) {
                $this->error = $record->getError();
                $db->rollbackTransaction();
                return false;
            }
            $txn = new AdminTransaction($class['ordcls_id'], AppConstant::GCLASS);
            if (!$txn->logEarningTxn($earnings)) {
                $this->error = $txn->getError();
                return false;
            }
            if (!$db->commitTransaction()) {
                $this->error = Label::getLabel('LBL_PLEASE_TRY_AGAIN');
                $db->rollbackTransaction();
                return false;
            }
        }
        return true;
    }

    /**
     * Get User Type Array
     * 
     * @param int $key
     * @return string|array
     */
    public static function getUserTypeArr(int $key = null)
    {
        $arr = [
            User::LEARNER => Label::getLabel('USER_LEARNER'),
            User::TEACHER => Label::getLabel('USER_TEACHER'),
            User::SUPPORT => Label::getLabel('USER_SUPPORT')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Status Array
     * 
     * @param int $key
     * @return string|array
     */
    public static function getStatusArr(int $key = null)
    {
        $arr = [
            static::STATUS_PROGRESS => Label::getLabel('STATUS_PROGRESS'),
            static::STATUS_RESOLVED => Label::getLabel('STATUS_RESOLVED'),
            static::STATUS_ESCALATED => Label::getLabel('STATUS_ESCALATED'),
            static::STATUS_CLOSED => Label::getLabel('STATUS_CLOSED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Actions Array
     * 
     * @param int $key
     * @param int $langId
     * @return string|array
     */
    public static function getActionsArr(int $key = null, int $langId = 0)
    {
        $arr = [
            static::ACTION_RESET_AND_UNSCHEDULED => Label::getLabel('LBL_RESET_AND_UNSCHEDULED', $langId),
            static::ACTION_COMPLETE_ZERO_REFUND => Label::getLabel('LBL_COMPLETE_AND_ZERO_REFUND', $langId),
            static::ACTION_COMPLETE_HALF_REFUND => Label::getLabel('LBL_COMPLETE_AND_50%_REFUND', $langId),
            static::ACTION_COMPLETE_FULL_REFUND => Label::getLabel('LBL_COMPLETE_AND_100%_REFUND', $langId),
            static::ACTION_ESCALATE_TO_ADMIN => Label::getLabel('LBL_ESCALATE_TO_SUPPORT_TEAM', $langId)
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Class Issue Ids
     * 
     * @param array $classIds
     * @param int $userType
     * @return array
     */
    public static function getClassIssueIds(array $classIds, int $userType): array
    {
        if (count($classIds) == 0) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'repiss');
        $fld = 'repiss_record_id';
        if ($userType == User::TEACHER) {
            $srch->joinTable(OrderClass::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_type = ' .
                Issue::TYPE_GCLASS . ' AND repiss.repiss_record_id = ordcls.ordcls_id', 'ordcls');
            $fld = 'ordcls_grpcls_id';
        }
        $srch->addCondition($fld, 'IN', array_unique($classIds));
        $srch->addMultipleFields([$fld, 'repiss_id']);
        $srch->addCondition('repiss_record_type', '=', static::TYPE_GCLASS);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Lesson Issue Ids
     * 
     * @param array $lessonIds
     * @return array
     */
    public static function getLessonIssueIds(array $lessonIds): array
    {
        if (count($lessonIds) == 0) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(['repiss_record_id', 'repiss_id']);
        $srch->addCondition('repiss_record_id', 'IN', array_unique($lessonIds));
        $srch->addCondition('repiss_record_type', '=', static::TYPE_LESSON);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Issue Detail
     * 
     * @param int $langId
     * @return null|array
     */
    public function getIssueDetail(int $langId)
    {
        $issueId = $this->getMainTableRecordId();
        $srch = new IssueSearch($langId, $this->userId, $this->userType);
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls_lang.gclang_grpcls_id = '
            . ' grpcls.grpcls_id AND grpcls_lang.gclang_lang_id = ' . $langId, 'grpcls_lang');
        $srch->addFld('IFNULL(grpcls_lang.grpcls_title, grpcls.grpcls_title) as grpcls_title');
        $srch->addCondition('repiss_id', '=', $issueId);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $issues = $srch->fetchAndFormat();
        if (count($issues) == 0) {
            return null;
        }
        return current($issues);
    }

    public function setupAffiliateSessionCommission(array $data, float $amount, float &$affiliateCommission)
    {
        foreach ($data as $data) {
            $langId = User::getAttributesById($data['affiliate_id'], 'user_lang_id');
            $msg = Label::getLabel('LBL_PURCHASE_COMMISSION_RECEIVED_ON_REFERAL_{referral}', $langId);
            $comment = str_replace(['{referral}'], [$data['user_name']], $msg);
            $commission = ($data['afcomm_commission'] / 100) *  $amount;

            $txn = new Transaction($data['affiliate_id'], Transaction::TYPE_REFERRAL_ORDER_COMMISSION);
            if (!$txn->credit($commission, $comment)) {
                $this->error = $txn->getError();
                return false;
            }
            $userObj = new User();
            if (!$userObj->updateAffiliateStats($data['affiliate_id'], Transaction::TYPE_REFERRAL_ORDER_COMMISSION)) {
                return false;
            }
            $notifi = new Notification($data['affiliate_id'], Notification::TYPE_ORDER_COMMISSSION_CREDIT_TO_AFFILIATE);
            $comment = str_replace(['{user}', '{rewards}'], [$data['user_name'], MyUtility::formatMoney($commission)], Label::getLabel('LBL_PURCHASE_COMMISSION_{rewards}_RECEIVED_ON_REFERAL_{user}', $langId));
            $vars = ['{message}' => $comment];
            if (!$notifi->sendNotification($vars)) {
                $this->error = $notifi->getError();
                return false;
            }

            $txn->sendEmail();
            $affiliateCommission += $commission;
        }
        return true;
    }
}
