<?php

/**
 * This class is used to handle Group Class Order
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderClass extends MyAppModel
{

    const DB_TBL = 'tbl_order_classes';
    const DB_TBL_PREFIX = 'ordcls_';
    const SCHEDULED = 1;
    const COMPLETED = 2;
    const CANCELLED = 3;

    private $userId;
    private $userType;

    /**
     * Initialize Order Class
     * 
     * @param int $id
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $id = 0, int $userId = 0, int $userType = 0)
    {
        $this->userId = $userId;
        $this->userType = $userType;
        parent::__construct(static::DB_TBL, 'ordcls_id', $id);
    }

    /**
     * Get Statuses
     * 
     * @param int $key
     * @return string|array
     */
    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::SCHEDULED => Label::getLabel('LBL_SCHEDULED'),
            static::COMPLETED => Label::getLabel('LBL_COMPLETED'),
            static::CANCELLED => Label::getLabel('LBL_CANCELLED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Booked Seat Counts
     * 
     * @param array $classIds
     * @return array
     */
    public static function getbookedSeatCounts(array $classIds): array
    {
        if (empty($classIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->addMultipleFields(['grpcls_id', 'count(*) as totalSeats']);
        $srch->addCondition('grpcls.grpcls_id', 'IN', $classIds);
        $srch->addCondition('ordcls_status', '!=', static::CANCELLED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addGroupBy('grpcls_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Search Object
     * 
     * @return SearchBase
     */
    public function getSearchObject(): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
        if ($this->mainTableRecordId > 0) {
            $srch->addCondition('ordcls.ordcls_id', '=', $this->mainTableRecordId);
        }
        if ($this->userType === User::TEACHER) {
            $srch->addDirectCondition('teacher.user_deleted IS NULL');
            $srch->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
            if ($this->userId > 0) {
                $srch->addCondition('grpcls.grpcls_teacher_id', '=', $this->userId);
            }
        } elseif ($this->userType === User::LEARNER) {
            $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $srch->addDirectCondition('learner.user_deleted IS NULL');
            if ($this->userId > 0) {
                $srch->addCondition('orders.order_user_id', '=', $this->userId);
            }
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return $srch;
    }

    /**
     * Start Class
     * 
     * @param array $class
     * @return bool|array
     */
    public function start(array $class)
    {
        if (empty($class['grpcls_teacher_starttime'])) {
            $this->error = Label::getLabel('LBL_PLEASE_WAIT_LET_TEACHER_JOIN');
            return false;
        }
        if (empty($class['ordcls_starttime'])) {
            $this->assignValues(['ordcls_starttime' => date('Y-m-d H:i:s'), 'ordcls_updated' => date('Y-m-d H:i:s')]);
            if (!$this->save()) {
                return false;
            }
        }
        return $class;
    }

    /**
     * Complete Class
     * 
     * @param array $class
     * @return bool
     */
    public function complete(array $class): bool
    {
        if ($class['ordcls_status'] == static::COMPLETED) {
            return true;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $this->assignValues([
            'ordcls_endtime' => date('Y-m-d H:i:s'),
            'ordcls_updated' => date('Y-m-d H:i:s'),
            'ordcls_status' => static::COMPLETED,
            'ordcls_ended_by' => User::LEARNER,
        ]);
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }
        if ($class['grpcls_parent'] > 0) {
            $packageClsStats = static::getPackageClsStats($class['grpcls_parent'], $this->userId);
            if (0 >= $packageClsStats['schClassCount']) {
                $tableRecord = new TableRecord(OrderPackage::DB_TBL);
                $tableRecord->assignValues(['ordpkg_status' => OrderPackage::COMPLETED]);
                if (!$tableRecord->update([
                            'smt' => 'ordpkg_package_id = ? and ordpkg_status = ? and ordpkg_order_id = ?',
                            'vals' => [$class['grpcls_parent'], OrderPackage::SCHEDULED, $class['order_id']]
                        ])) {
                    $this->error = $tableRecord->getError();
                    $db->rollbackTransaction();
                    return false;
                }
            }
        }
        $sessionLog = new SessionLog($this->getMainTableRecordId(), AppConstant::GCLASS);
        if (!$sessionLog->addCompletedClassLog($this->userId, User::LEARNER)) {
            $db->rollbackTransaction();
            $this->error = $sessionLog->getError();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Cancel Class
     * 
     * @param string $comment
     * @param int $langId
     * @return bool
     */
    public function cancel(string $comment = '', int $langId = 0): bool
    {
        if (!$class = $this->getClassToCancel($langId)) {
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $this->setFldValue('ordcls_status', static::CANCELLED);
        $this->setFldValue('ordcls_updated', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            return false;
        }
        $quiz = new QuizAttempt(0, $this->userId, $this->userType);
        if (!$quiz->cancel($class['grpcls_id'], AppConstant::GCLASS)) {
            $this->error = $quiz->getError();
            $db->rollbackTransaction();
            return false;
        }
        if (FatUtility::float($class['order_net_amount']) > 0) {
            $refundPercent = static::getRefundPercentage(User::LEARNER, $class['grpcls_start_datetime']);
            if (!$this->refundToLearner([$class], $refundPercent)) {
                $db->rollbackTransaction();
                return false;
            }
            $remainingPercent = (100 - $refundPercent);
            if ($remainingPercent > 0) {
                if (!$this->paidToTeacher($class, $remainingPercent)) {
                    $db->rollbackTransaction();
                    return false;
                }
            }
        }
        $sessionLog = new SessionLog($this->getMainTableRecordId(), AppConstant::GCLASS);
        if (!$sessionLog->addCanceledClassLog($this->userId, User::LEARNER, $comment)) {
            $db->rollbackTransaction();
            $this->error = $sessionLog->getError();
            return false;
        }
        $groupClass = new GroupClass($class['grpcls_id']);
        if (!$groupClass->updateBookedSeatsCount(-1)) {
            $db->rollbackTransaction();
            return false;
        }
        $thread = new Thread(0);
        if ($thread->groupThreadExist($class['grpcls_id'])) {
            if (!$thread->deleteThreadUser($this->userId)) {
                $this->error = $thread->getError();
                $db->rollbackTransaction();
                return false;
            }
        }
        $db->commitTransaction();
        $class['comment'] = $comment;
        $this->removeGoogleEvent($class['grpcls_id']);
        $this->sendCancelClassNotification($class);
        return true;
    }

    /**
     * Remove Google Event
     * 
     * @param int $classId
     */
    public function removeGoogleEvent(int $classId)
    {
        $token = (new UserSetting($this->userId))->getGoogleToken();
        if (!empty($token)) {
            $googleCalendar = new GoogleCalendarEvent($this->userId, $classId, AppConstant::GCLASS);
            $event = $googleCalendar->getGroupClassEvent();
            if (!empty($event['gocaev_event_id'])) {
                $googleCalendar->deletEvent($token, $event['gocaev_event_id']);
            }
        }
    }

    /**
     * Send Cancel Class FNotification
     * 
     * @param array $class
     */
    private function sendCancelClassNotification(array $class)
    {
        $url = MyUtility::makeUrl('Classes', 'view', [$class['grpcls_id']]);
        if (isset($class['grpcls_offline']) && $class['grpcls_offline'] == AppConstant::YES) {
            $url = MyUtility::makeUrl('Classes');
        }
        if (($class['grpcls_booked_seats'] - 1) <= 0) {
            $url = MyUtility::makeUrl('Classes', 'index') . '?grpcls_id=' . $class['grpcls_id'];
        }
        $noti = new Notification($class['teacher_id'], Notification::TYPE_CLASS_CANCELLED);
        $noti->sendNotification(['{link}' => $url, '{class_name}' => $class['grpcls_title']], User::TEACHER);
        $mail = new FatMailer($class['teacher_lang_id'], 'learner_cancelled_class_email');
        $vars = [
            '{class_name}' => $class['grpcls_title'],
            '{learner_comment}' => nl2br($class['comment']),
            '{learner_name}' => $class['learner_first_name'] . ' ' . $class['learner_last_name'],
            '{teacher_name}' => $class['teacher_first_name'] . ' ' . $class['teacher_last_name'],
        ];
        $mail->setVariables($vars);
        if (!$mail->sendMail([$class['teacher_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Refund To Learner
     * 
     * @param array $classes
     * @param float $refundPercent
     * @return boolean
     */
    public function refundToLearner(array $classes, float $refundPercent)
    {
        foreach ($classes as $value) {
            $price = $value['ordcls_amount'] - $value['ordcls_discount'] - $value['ordcls_reward_discount'];
            $refund = FatUtility::float(($refundPercent / 100) * $price);
            $earnings = FatUtility::float($price - $refund);
            $record = new TableRecord(static::DB_TBL);
            $record->setFldValue('ordcls_refund', $refund);
            $record->setFldValue('ordcls_earnings', $earnings);
            $record->setFldValue('ordcls_updated', date('Y-m-d H:i:s'));
            if (!$record->update(['smt' => 'ordcls_id = ?', 'vals' => [$value['ordcls_id']]])) {
                $this->error = $record->getError();
                return false;
            }
            if ($refund > 0) {
                $txn = new Transaction($value['order_user_id'], Transaction::TYPE_LEARNER_REFUND);
                $comment = Label::getLabel('LBL_{refund}%_CANCEL_REFUND_ON_CLASS_{classid}', $value['learner_lang_id']);
                $comment = str_replace(['{refund}', '{classid}'], [$refundPercent, $value['ordcls_id']], $comment);
                if (!$txn->credit($refund, $comment)) {
                    $this->error = $txn->getError();
                    return false;
                }
                $txn->sendEmail();
                $notifiVar = ['{amount}' => MyUtility::formatMoney(abs($refund)), '{reason}' => Label::getLabel('LBL_WALLET_ORDER_PAYMENT', $value['learner_lang_id'])];
                $notifi = new Notification($value['order_user_id'], Notification::TYPE_WALLET_CREDIT);
                $notifi->sendNotification($notifiVar);
            }
            if ($this->userType != User::LEARNER && $value['ordcls_reward_discount'] > 0) {
                $record = new RewardPoint($value['order_user_id']);
                $rewardPoints = RewardPoint::convertToPoints($value['ordcls_reward_discount']);
                if (!$record->refundRewards($value['order_id'], $rewardPoints)) {
                    $this->error = $record->getError();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get Refund Percentage
     * 
     * Pass the lesson start time in system timezone  
     * 
     * @param int $userType
     * @param int $status
     * @param type $lessonStartTime
     * Pass the lesson status 
     * 
     * @return float
     */
    public static function getRefundPercentage(int $userType, $lessonStartTime): float
    {
        /**
         * Set refund percentage 100 if user type is teacher else depend on configuration 
         */
        $refundPercent = 100;
        if ($userType == User::LEARNER) {
            $refundDuration = FatApp::getConfig('CONF_CLASS_REFUND_DURATION', FatUtility::VAR_FLOAT, 24);
            $refundPercent = FatApp::getConfig('CONF_CLASS_REFUND_PERCENTAGE_AFTER_DURATION', FatUtility::VAR_FLOAT, 50);
            if (MyDate::hoursDiff($lessonStartTime) > $refundDuration) {
                $refundPercent = FatApp::getConfig('CONF_CLASS_REFUND_PERCENTAGE_BEFORE_DURATION', FatUtility::VAR_FLOAT, 100);
            }
        }
        return FatUtility::float($refundPercent);
    }

    /**
     * Feedback Class
     * 
     * @param array $post
     * @return bool
     */
    public function feedback(array $post): bool
    {
        if (!$class = $this->getClassToFeedback()) {
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $ratingReview = new RatingReview($class['grpcls_teacher_id'], $this->userId);
        if (!$ratingReview->addReview(AppConstant::GCLASS, $class['grpcls_id'], $post)) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        $this->assignValues(['ordcls_reviewed' => AppConstant::YES]);
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        if (FatApp::getConfig('CONF_DEFAULT_REVIEW_STATUS') == RatingReview::STATUS_APPROVED) {
            $ratingReview->sendMailToTeacher($class);
        } else {
            $ratingReview->sendMailToAdmin($class);
        }
        return true;
    }

    /**
     * Get Class To Start
     * 
     * @param int $langId
     * @return bool|array
     */
    public function getClassToStart(int $langId)
    {
        $currentDate = date('Y-m-d H:i:s');
        $srch = $this->getSearchObject();
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id and gclang_lang_id = ' . $langId, 'gclang');
        $srch->addCondition('grpcls_status', '=', GroupClass::SCHEDULED);
        $srch->addCondition('ordcls_status', '=', static::SCHEDULED);
        $srch->addCondition('grpcls_start_datetime', '<=', $currentDate);
        $srch->addCondition('grpcls_end_datetime', '>', $currentDate);
        $srch->addMultipleFields([
            'ordcls_id', 'grpcls.grpcls_id', 'grpcls.grpcls_start_datetime', 'grpcls.grpcls_end_datetime',
            'grpcls.grpcls_teacher_starttime', 'grpcls.grpcls_teacher_endtime', 'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title',
            'teacher.user_id as teacher_id', 'grpcls_duration', 'grpcls.grpcls_metool_id',
            'teacher.user_first_name as teacher_first_name', 'teacher.user_last_name as teacher_last_name',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) as teacher_full_name',
            'teacher.user_email as teacher_email', 'teacher.user_timezone as teacher_timezone',
            'learner.user_first_name as learner_first_name', 'learner.user_last_name as learner_last_name',
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) as learner_full_name',
            'learner.user_timezone as learner_timezone', 'learner.user_email as learner_email', 'learner.user_id as learner_id'
        ]);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_CLASS_NOT_FOUND');
            return false;
        }
        return $row;
    }

    /**
     * Get Class To Complete
     * 
     * @return bool|array
     */
    public function getClassToComplete()
    {
        $srch = $this->getSearchObject();
        $srch->addCondition('grpcls.grpcls_status', 'IN', [GroupClass::SCHEDULED, GroupClass::COMPLETED]);
        $srch->addCondition('grpcls.grpcls_start_datetime', '<', date('Y-m-d H:i:s'));
        $srch->addDirectCondition('((grpcls_offline = ' . AppConstant::NO . ' && ordcls_starttime IS NOT NULL) || grpcls_offline = ' . AppConstant::YES . ')');
        $srch->addCondition('ordcls_status', 'IN', [static::SCHEDULED, static::COMPLETED]);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_CLASS_NOT_FOUND');
            return false;
        }
        return $row;
    }

    /**
     * Get Class To Cancel
     * 
     * @param int $langId
     * @return bool|array
     */
    public function getClassToCancel(int $langId = 0)
    {
        $srch = $this->getSearchObject();
        if ($langId > 0) {
            $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id and gclang_lang_id = ' . $langId, 'gclang');
            $srch->addFld('IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title');
        }
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('grpcls.grpcls_status', '=', GroupClass::SCHEDULED);
        $srch->addCondition('grpcls.grpcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->addCondition('ordcls.ordcls_status', '=', static::SCHEDULED);
        $srch->addCondition('grpcls.grpcls_parent', '=', 0);
        $srch->addMultipleFields([
            'orders.order_id', 'ordcls.ordcls_status', 'ordcls.ordcls_amount',
            'ordcls.ordcls_discount', 'ordcls.ordcls_reward_discount',
            'orders.order_net_amount', 'orders.order_user_id',
            'grpcls.grpcls_id', 'teacher.user_id as teacher_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'learner.user_lang_id as learner_lang_id',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'teacher.user_email as teacher_email',
            'teacher.user_lang_id as teacher_lang_id',
            'grpcls_start_datetime', 'ordcls.ordcls_id',
            'grpcls_booked_seats', 'ordcls_commission', 'grpcls_offline'
        ]);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_CLASS_NOT_FOUND');
            return false;
        }
        $duration = FatApp::getConfig('CONF_CLASS_CANCEL_DURATION', FatUtility::VAR_INT, 24);
        $startTime = strtotime($row['grpcls_start_datetime'] . ' -' . $duration . ' hours');
        if (time() >= $startTime) {
            $this->error = Label::getLabel('LBL_TIME_TO_CANCEL_CLASS_PASSED');
            return false;
        }
        return $row;
    }

    /**
     * Get Class To Feedback
     * 
     * @return bool|array
     */
    public function getClassToFeedback()
    {
        if ($this->userType != User::LEARNER) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if (FatApp::getConfig('CONF_ALLOW_REVIEWS') != AppConstant::YES) {
            $this->error = Label::getLabel('LBL_REVIEW_NOT_ALLOWED');
            return false;
        }
        $srch = $this->getSearchObject();
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordcls_status', '=', static::COMPLETED);
        $srch->addMultipleFields(['grpcls.grpcls_id', 'grpcls.grpcls_teacher_id',
            'ordcls.ordcls_reviewed', 'ordcls.ordcls_id']);
        $srch->addMultipleFields([
            'grpcls.grpcls_id',
            'grpcls.grpcls_teacher_id',
            'ordcls.ordcls_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'teacher.user_email as teacher_email',
            'teacher.user_lang_id as teacher_lang_id'
        ]);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_CLASS_NOT_FOUND');
            return false;
        }
        if ($row['ordcls_reviewed'] == AppConstant::YES) {
            $this->error = Label::getLabel('LBL_FEEDBACK_ALREADY_SUBMITTED');
            return false;
        }
        return $row;
    }

    /**
     * Get Class To Report
     * 
     * @return bool|array
     */
    public function getClassToReport()
    {
        $reportHours = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION', FatUtility::VAR_INT, 0);
        $srch = $this->getSearchObject();
        $srch->joinTable(Issue::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_id = ordcls_id AND repiss.repiss_record_type = ' . AppConstant::GCLASS . ' AND repiss.repiss_reported_by = orders.order_user_id', 'repiss');
        $srch->addCondition('ordcls_status', 'IN', [static::COMPLETED, static::SCHEDULED]);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addDirectCondition('ordcls_teacher_paid IS NULL');
        $srch->addDirectCondition('repiss_record_id IS NULL');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_CLASS_NOT_FOUND');
            return false;
        }
        $endTimeUnix = strtotime($row['grpcls_end_datetime']);
        $reportTime = strtotime(" +" . $reportHours . " hour", $endTimeUnix);
        if (
                ($row['ordcls_status'] == static::COMPLETED ||
                ($row['ordcls_status'] == static::SCHEDULED &&
                empty($row['grpcls_teacher_starttime']) &&
                time() > $endTimeUnix)) && $reportTime >= time()
        ) {
            return $row;
        }
        $this->error = Label::getLabel('LBL_INVALID_REQUEST');
        return false;
    }

    /**
     * Can Playback Class
     * 
     * @return bool
     */
    public function canPlaybackClass(): bool
    {
        $srch = $this->getSearchObject();
        $srch->addFld('COUNT(*) AS records');
        $srch->addCondition('ordcls.ordcls_status', '=', static::COMPLETED);
        $srch->addCondition('grpcls.grpcls_status', '=', GroupClass::COMPLETED);
        $srch->addCondition('grpcls.grpcls_end_datetime', '<', date('Y-m-d H:i:s'));
        $respose = FatApp::getDb()->fetch($srch->getResultSet())['records'] ?? 0;
        return FatUtility::int($respose) > 0 ? true : false;
    }

    /**
     * Get Order Class By Group Id
     * 
     * @param int $groupId
     * @param array $classIds
     * @param array $ordclsStatus
     * @return array
     */
    public static function getOrdClsByGroupId(int $groupId, array $classIds = [], array $ordclsStatus = []): array
    {
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        if (!empty($ordclsStatus)) {
            $srch->addCondition('ordcls.ordcls_status', 'IN', $ordclsStatus);
        }
        $srch->addCondition('grpcls.grpcls_id', '=', $groupId);
        if (!empty($classIds)) {
            $srch->addCondition('ordcls.ordcls_id', 'IN', $classIds);
        }
        $srch->addMultipleFields([
            'learner.user_id as user_id',
            'learner.user_email as learner_email',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'learner.user_lang_id as learner_lang_id',
            'orders.order_net_amount', 'orders.order_id',
            'orders.order_user_id', 'grpcls.grpcls_id',
            'ordcls.ordcls_id', 'ordcls.ordcls_amount',
            'ordcls.ordcls_discount',
            'ordcls.ordcls_reward_discount',
        ]);
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Order Class By Package Id
     * 
     * @param int $groupId
     * @return array
     */
    public static function getOrdClsByPackageId(int $packageId, int $userId = 0): array
    {
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        if ($userId > 0) {
            $srch->addCondition('orders.order_user_id', '=', $userId);
        }
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordcls.ordcls_status', '=', OrderClass::SCHEDULED);
        $srch->addCondition('grpcls.grpcls_parent', '=', $packageId);
        $srch->addMultipleFields(['orders.order_user_id', 'grpcls.grpcls_id']);
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Order Class count by PackageId
     * 
     * @param int $packageId
     * @param array $status
     * @return int
     */
    public static function getClsCountByPackageId(int $packageId, $status = []): int
    {
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        if (!empty($status)) {
            $srch->addCondition('ordcls.ordcls_status', 'IN', $status);
        }
        $srch->addCondition('grpcls.grpcls_parent', '=', $packageId);
        $srch->addMultipleFields("count('ordcls.ordcls_id') as totalCls");
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return $record['totalCls'] ?? 0;
    }

    /**
     * Get Class Stats
     * 
     * @return array
     */
    public function getSchedClassStats(): array
    {
        $srch = $this->getSearchObject();
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addMultipleFields([
            'count(ordcls_id) as totalClasses',
            'count(IF(ordcls.ordcls_status = ' . static::SCHEDULED . ', 1, null)) as schClassCount',
            'count(IF(ordcls.ordcls_status = ' . static::SCHEDULED . ' and grpcls.grpcls_start_datetime >= "' . date('Y-m-d H:i:s') . '", 1, null)) as upcomingClass'
        ]);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        return [
            'totalClasses' => FatUtility::int($data['totalClasses'] ?? 0),
            'schClassCount' => FatUtility::int($data['schClassCount'] ?? 0),
            'upcomingClass' => FatUtility::int($data['upcomingClass'] ?? 0)
        ];
    }

    /**
     * Already Booked Class
     * 
     * @param int $userId
     * @param array $classIds
     * @return array
     */
    public static function userBooked(int $userId, array $classIds): array
    {
        if (empty($userId) || empty($classIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->addFld('ordcls.ordcls_grpcls_id AS grpcls_id');
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addCondition('ordcls.ordcls_grpcls_id', 'IN', $classIds);
        $srch->addCondition('ordcls.ordcls_status', '=', static::SCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordcls.ordcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        return array_column($records, 'grpcls_id', 'grpcls_id');
    }

    /**
     * Get Learners
     * 
     * @return array
     */
    public static function getLearners(int $classId): array
    {
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->addMultipleFields(['user_first_name', 'user_last_name', 'user_email', 'user_gender', 'learner.user_id']);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordcls.ordcls_status', '!=', static::CANCELLED);
        $srch->addCondition('ordcls.ordcls_grpcls_id', '=', $classId);
        $srch->doNotCalculateRecords();
        $srch->addGroupBy('learner.user_id');
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Unpaid Seats
     * 
     * @param array $classIds
     * @return array
     */
    public static function getUnpaidSeats(array $classIds): array
    {
        if (empty($classIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->addMultipleFields(['ordcls_grpcls_id', 'count(*) as totalSeats']);
        $srch->addCondition('ordcls.ordcls_grpcls_id', 'IN', $classIds);
        $srch->addCondition('ordcls_status', '=', static::SCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_INPROCESS);
        $srch->addGroupBy('ordcls_grpcls_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Unpaid Class
     * 
     * @param int $userId
     * @param int $classId
     * @return null|array
     */
    public static function getUnpaidClass(int $userId, int $classId)
    {
        $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(Coupon::DB_TBL_HISTORY, 'LEFT JOIN', 'orders.order_id = couhis.couhis_order_id', 'couhis');
        $srch->addMultipleFields(['order_id', 'couhis_id', 'order_type', 'couhis_coupon_id', 'order_user_id', 'order_reward_value']);
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addCondition('ordcls.ordcls_grpcls_id', '=', $classId);
        $srch->addCondition('ordcls.ordcls_status', '=', OrderClass::SCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_INPROCESS);
        $srch->addCondition('ordcls.ordcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->addOrder('order_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function getPackageClsStats(int $packageId, int $userId): array
    {
        $srch = new SearchBase(static::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        if ($userId > 0) {
            $srch->addCondition('orders.order_user_id', '=', $userId);
        }
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('grpcls.grpcls_parent', '=', $packageId);
        $srch->addMultipleFields([
            'count(ordcls_id) as totalClasses',
            'count(IF(ordcls.ordcls_status = ' . static::SCHEDULED . ', 1, null)) as schClassCount',
            'count(IF(ordcls.ordcls_status = ' . static::COMPLETED . ', 1, null)) as completedClass',
            'count(IF(ordcls.ordcls_status = ' . static::CANCELLED . ', 1, null)) as cancelledClass',
        ]);
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function getUpComingClesss(int $langId, int $pageSize = 2): array
    {
        $srch = new ClassSearch($langId, $this->userId, $this->userType);
        $srch->applySearchConditions([
            'ordcls_status' => OrderClass::SCHEDULED,
            'grpcls_start_datetime' => MyDate::formatDate(date('Y-m-d H:i:s'))
        ]);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('grpcls_start_datetime');
        $srch->setPageSize($pageSize);
        return $srch->fetchAndFormat();
    }

    /**
     * Paid To Teacher
     * 
     * @param array $class
     * @param float $percentage
     * @return bool
     */
    private function paidToTeacher(array $class, float $percentage): bool
    {
        $price = $class['ordcls_amount'];
        $remainingAmount = FatUtility::float(($percentage / 100) * $price);
        $commission = FatUtility::float(($class['ordcls_commission'] / 100) * $remainingAmount);
        $teacherAmount = FatUtility::float($remainingAmount - $commission);

        $refundAmt  = OrderClass::getAttributesById($class['ordcls_id'], 'ordcls_refund');
        $earnings = $class['ordcls_amount'] - ($class['ordcls_discount'] + $refundAmt + $teacherAmount + $class['ordcls_reward_discount']);

        if ($teacherAmount > 0) {
            $this->setFldValue('ordcls_teacher_paid', $teacherAmount);
            $this->setFldValue('ordcls_commission_amount', $commission);
            $this->setFldValue('ordcls_earnings', $earnings);
            $this->setFldValue('ordcls_updated', date('Y-m-d H:i:s'));
            if (!$this->save()) {
                return false;
            }
            $comment = str_replace('{classid}', $class['grpcls_id'], Label::getLabel('LBL_PAYMENT_ON_CANCELLED_CLASS_{classid}',$class['teacher_lang_id']));
            $txn = new Transaction($class['teacher_id'], Transaction::TYPE_TEACHER_PAYMENT);
            if (!$txn->credit($teacherAmount, $comment)) {
                $this->error = $txn->getError();
                return false;
            }
            $txn->sendEmail();
            $notifiVar = ['{amount}' => MyUtility::formatMoney(abs($teacherAmount)), '{reason}' => Label::getLabel('LBL_WALLET_ORDER_PAYMENT', $class["teacher_lang_id"] ?? 0)];
            $notifi = new Notification($class['teacher_id'], Notification::TYPE_WALLET_CREDIT);
            $notifi->sendNotification($notifiVar);
        }
        
        
        $txn = new AdminTransaction($class['ordcls_id'], AppConstant::GCLASS);
        $comment = Label::getLabel('LBL_EARNINGS_ON_CANCELED_CLASS_ID_:_') . $class['ordcls_id'];
        if (!$txn->logEarningTxn($earnings, $comment)) {
            $this->error = $txn->getError();
            return false;
        }
        return true;
    }

}
