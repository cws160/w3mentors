<?php

/**
 * This class is used to handle Courses Order
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderCourse extends MyAppModel
{

    const DB_TBL = 'tbl_order_courses';
    const DB_TBL_PREFIX = 'ordcrs_';

    /* course statuses */
    const PENDING = 1;
    const IN_PROGRESS = 2;
    const COMPLETED = 3;
    const CANCELLED = 4;

    private $userId;
    private $userType;
    private $langId;

    /**
     * Initialize Order Class
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     * @param int $langId
     */
    public function __construct(int $id = 0, int $userId = 0, int $userType = 0, int $langId = 0)
    {
        $this->userId = $userId;
        $this->userType = $userType;
        $this->langId = $langId;
        parent::__construct(static::DB_TBL, 'ordcrs_id', $id);
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
            static::PENDING => Label::getLabel('LBL_PENDING'),
            static::IN_PROGRESS => Label::getLabel('LBL_IN_PROGRESS'),
            static::COMPLETED => Label::getLabel('LBL_COMPLETED'),
            static::CANCELLED => Label::getLabel('LBL_CANCELLED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get course details for feedback
     *
     * @return array|bool
     */
    public function getCourseToFeedback()
    {
        if ($this->userType != User::LEARNER) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if (FatApp::getConfig('CONF_ALLOW_REVIEWS') != AppConstant::YES) {
            $this->error = Label::getLabel('LBL_REVIEW_NOT_ALLOWED');
            return false;
        }
        /* validate course, order & user */
        $srch = new OrderCourseSearch($this->langId, $this->userId, 0);
        $srch->addCondition('ordcrs.ordcrs_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ordcrs.ordcrs_status', '!=', static::CANCELLED);
        $srch->addCondition('orders.order_user_id', '=', $this->userId);
        $srch->addCondition('crspro.crspro_completed', 'IS NOT', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields([
            'course_user_id',
            'ordcrs.ordcrs_id',
            'learner.user_first_name AS learner_first_name',
            'learner.user_last_name AS learner_last_name',
            'teacher.user_first_name AS teacher_first_name',
            'teacher.user_last_name AS teacher_last_name',
            'teacher.user_lang_id AS teacher_lang_id',
            'teacher.user_email AS teacher_email',
            'ordcrs.ordcrs_reviewed',
            'ordcrs.ordcrs_course_id'
        ]);
        if (!$course = FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_COURSE_NOT_FOUND');
            return false;
        }
        if ($course['ordcrs_reviewed'] == AppConstant::YES) {
            $this->error = Label::getLabel('LBL_COURSE_REVIEW_ALREADY_SUBMITTED');
            return false;
        }
        return $course;
    }

    /**
     * Get course details to cancel
     *
     * @return array|bool
     */
    public function getCourseToCancel()
    {
        /* validate course, order & user */
        $srch = new OrderCourseSearch($this->langId, $this->userId, 0);
        $srch->addCondition('ordcrs_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('orders.order_user_id', '=', $this->userId);
        $srch->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields([
            'orders.order_addedon',
            'orders.order_status',
            'ordcrs.ordcrs_amount',
            'ordcrs.ordcrs_id',
            'ordcrs.ordcrs_discount',
            'ordcrs.ordcrs_teacher_paid',
            'course.course_id',
            'crsdetail.course_title',
            'orders.order_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'teacher.user_lang_id as teacher_lang_id',
            'learner.user_lang_id as learner_lang_id',
            'crspro_progress',
            'crspro_status',
            'orders.order_reward_value',
            'orders.order_user_id'
        ]);
        if (!$course = FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_COURSE_NOT_FOUND');
            return false;
        }
        if ($this->userType != User::SUPPORT) {
            $duration = FatApp::getConfig('CONF_COURSE_CANCEL_DURATION', FatUtility::VAR_INT, 7);
            $date = strtotime($course['order_addedon'] . ' +' . $duration . ' days');
            $currentDate = strtotime(date('Y-m-d H:i:s'));
            if ($currentDate >= $date) {
                $this->error = Label::getLabel('LBL_ALLOWED_CANCELLATION_DURATION_HAS_PASSED');
                return false;
            }
            if (!is_null($course['ordcrs_teacher_paid'])) {
                $this->error = Label::getLabel('LBL_ALLOWED_CANCELLATION_DURATION_HAS_PASSED');
                return false;
            }
        }
        if ($course['order_status'] == Order::STATUS_CANCELLED) {
            $this->error = Label::getLabel('LBL_COURSE_ALREADY_CANCELLED');
            return false;
        }
        return $course;
    }

    /**
     * Save cancellation request
     *
     * @param string $comment
     * @return bool
     */
    public function cancel(string $comment)
    {
        if (!$data = $this->getCourseToCancel()) {
            return false;
        }
        $db = FatApp::getDb();
        $srch = new SearchBase(Course::DB_TBL_REFUND_REQUEST);
        $srch->addCondition('corere_ordcrs_id', '=', $data['ordcrs_id']);
        $srch->addCondition('corere_user_id', '=', $this->userId);
        $srch->addCondition('corere_status', '=', Course::REFUND_PENDING);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if ($db->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_CANCELLATION_REQUEST_ALREADY_PLACED_ON_THIS_COURSE');
            return false;
        }
        $db->startTransaction();
        $defaultStatus = FatApp::getConfig('CONF_COURSE_DEFAULT_CANCELLATION_STATUS');
        $record = new TableRecord(Course::DB_TBL_REFUND_REQUEST);
        $record->assignValues([
            'corere_ordcrs_id' => $data['ordcrs_id'],
            'corere_user_id' => $this->userId,
            'corere_status' => $defaultStatus,
            'corere_remark' => $comment,
            'corere_created' => date('Y-m-d H:i:s'),
            'corere_updated' => ($defaultStatus == Course::REFUND_APPROVED) ? date('Y-m-d H:i:s') : NULL
        ]);
        if (!$record->addNew()) {
            $this->error = Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED');
            return false;
        }
        if ($defaultStatus == Course::REFUND_APPROVED) {
            $this->setFldValue('ordcrs_status', static::CANCELLED);
            $this->setFldValue('ordcrs_updated', date('Y-m-d H:i:s'));
            if (!$this->save()) {
                $db->rollbackTransaction();
                $this->error = $this->getError();
                return false;
            }
            /* Update course progress status */
            $stmt = ['smt' => 'crspro_ordcrs_id = ?', 'vals' => [$this->getMainTableRecordId()]];
            if (!FatApp::getDb()->updateFromArray(CourseProgress::DB_TBL, ['crspro_status' => CourseProgress::CANCELLED], $stmt)) {
                $this->error = Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED');
                $db->rollbackTransaction();
                return false;
            }
            if (!$this->refundToLearner($data)) {
                $db->rollbackTransaction();
                return false;
            }
            $course = new Course($data['course_id']);
            if (!$course->setStudentCount()) {
                $this->error = $course->getError();
                $db->rollbackTransaction();
                return false;
            }
            $request = ['corere_remark' => '', 'corere_status' => Course::REFUND_APPROVED, 'course_title' => $data['course_title']];
            $user = User::getAttributesById($this->userId, ['user_lang_id', 'user_first_name', 'user_last_name', 'user_email']);
            Course::sendRefundStatusMailToLearner(array_merge($request, $user));
        } else {
            $data['comment'] = $comment;
            $this->sendCancelRequestToAdmin($data);
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Course Cancellation Request Email to Admin
     *
     * @param array $data
     * @return bool
     */
    public function sendCancelRequestToAdmin($data)
    {
        $mail = new FatMailer(MyUtility::getSiteLangId(), 'course_cancellation_request_email_to_admin');
        $vars = [
            '{course_title}' => ucwords($data['course_title']),
            '{course_price}' => MyUtility::formatMoney($data['ordcrs_amount'] - $data['ordcrs_discount']),
            '{teacher_full_name}' => ucwords($data['teacher_first_name'] . ' ' . $data['teacher_last_name']),
            '{learner_full_name}' => ucwords($data['learner_first_name'] . ' ' . $data['learner_last_name']),
            '{progress_percent}' => ($data['crspro_status'] == CourseProgress::COMPLETED) ? 100 : $data['crspro_progress'],
            '{learner_comment}' => nl2br($data['comment']),
        ];
        $mail->setVariables($vars);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);
    }

    /**
     * Refund amount to learner
     *
     * @param array $course
     * @return bool
     */
    public function refundToLearner(array $course)
    {
        if (!$course) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $refundAmt = $course['ordcrs_amount'] - ($course['ordcrs_discount'] + $course['order_reward_value']);
        $this->assignValues([
            'ordcrs_refund' => $refundAmt,
            'ordcrs_teacher_paid' => 0,
            'ordcrs_updated' => date('Y-m-d H:i:s')
        ]);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        if ($refundAmt > 0) {
            $txn = new Transaction($this->userId, Transaction::TYPE_LEARNER_REFUND);
            $comment = Label::getLabel('LBL_COURSE_REFUNDED_{order-id}', $course['learner_lang_id']);
            $comment = str_replace('{order-id}', Order::formatOrderId($course['order_id']), $comment);
            if (!$txn->credit($refundAmt, $comment)) {
                $this->error = $txn->getError();
                return false;
            }
            $txn->sendEmail();
            $notifiVar = ['{amount}' => MyUtility::formatMoney(abs($refundAmt)), '{reason}' => Label::getLabel('LBL_WALLET_ORDER_PAYMENT', $course['learner_lang_id'])];
            $notifi = new Notification($this->userId, Notification::TYPE_WALLET_CREDIT);
            $notifi->sendNotification($notifiVar);
        }
        return true;
    }

    /**
     * Get order course by id
     *
     * @return array
     */
    public function getOrderCourseById()
    {
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_order_id = orders.order_id', 'orders');
        $srch->addCondition('ordcrs.ordcrs_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ordcrs.ordcrs_status', '!=', static::CANCELLED);
        $srch->addCondition('orders.order_user_id', '=', $this->userId);
        $srch->addMultipleFields([
            'ordcrs.ordcrs_id',
            'ordcrs.ordcrs_course_id',
            'ordcrs.ordcrs_certificate_number',
            'orders.order_user_id'
        ]);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Course Stats
     * 
     * @return array
     */
    public function getCourseStats(): array
    {
        $srch = new OrderCourseSearch($this->langId, $this->userId, $this->userType);
        $srch->applyPrimaryConditions();
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordcrs.ordcrs_status', 'IN', [static::COMPLETED, static::IN_PROGRESS]);
        $srch->addMultipleFields([
            'count(ordcrs_id) as totalCourses',
        ]);
        if ($this->userType == User::TEACHER) {
            $srch->addCondition('course.course_user_id', '=', $this->userId);
        }
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        return [
            'totalCourses' => FatUtility::int($data['totalCourses'] ?? 0)
        ];
    }

    /**
     * Get Unpaid Courses
     * 
     * @param int $userId
     * @param int $courseId
     * @return null|array
     */
    public static function getUnpaidCourses(int $userId, int $courseId)
    {
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->joinTable(CourseProgress::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_id = crspro.crspro_ordcrs_id', 'crspro');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_order_id = orders.order_id', 'orders');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'course');
        $srch->joinTable(Coupon::DB_TBL_HISTORY, 'LEFT JOIN', 'orders.order_id = couhis.couhis_order_id', 'couhis');
        $srch->addMultipleFields(['order_id', 'couhis_id', 'order_type', 'couhis_coupon_id', 'crspro_id', 'order_user_id', 'order_reward_value']);
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addCondition('ordcrs.ordcrs_course_id', '=', $courseId);
        $srch->addCondition('ordcrs.ordcrs_status', '=', OrderCourse::IN_PROGRESS);
        $srch->addCondition('orders.order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('orders.order_type', '=', Order::TYPE_COURSE);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_INPROCESS);
        $srch->addOrder('order_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }
}
