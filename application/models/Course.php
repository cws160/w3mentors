<?php

/**
 * This class is used to handle Course
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class Course extends MyAppModel
{

    const DB_TBL = 'tbl_courses';
    const DB_TBL_PREFIX = 'course_';
    const DB_TBL_LANG = 'tbl_course_details';
    const DB_TBL_APPROVAL_REQUEST = 'tbl_course_approval_requests';
    const DB_TBL_REFUND_REQUEST = 'tbl_course_refund_requests';
    const DB_TBL_INTENDED_LEARNERS = 'tbl_courses_intended_learners';
    const DB_TBL_EDIT_REQUEST = 'tbl_course_edit_requests';

    /* Course Status */
    const DRAFTED = 1;
    const SUBMITTED = 2;
    const PUBLISHED = 3;

    /* Course Request Status */
    const REQUEST_PENDING = 0;
    const REQUEST_APPROVED = 1;
    const REQUEST_DECLINED = 2;

    /* Course Edit Request Status */
    const EDIT_REQUEST_PENDING = 0;
    const EDIT_REQUEST_APPROVED = 1;
    const EDIT_REQUEST_DECLINED = 2;

    /* Course Updates Status */
    const EDIT_UPDATES_INPROGRESS = 0;
    const EDIT_UPDATES_COMPLETE = 1;

    /* Course Refund Status */
    const REFUND_PENDING = 0;
    const REFUND_APPROVED = 1;
    const REFUND_DECLINED = 2;

    /* Course Price Type */
    const TYPE_FREE = 1;
    const TYPE_PAID = 2;

    /* Filter Types */
    const FILTER_COURSE = 1;
    const FILTER_TEACHER = 2;
    const FILTER_TAGS = 3;

    private $userId;
    private $userType;
    private $langId;

    /**
     * Initialize Course
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $id = 0, int $userId = 0, int $userType = 0, int $langId = 0)
    {
        $this->userId = $userId;
        $this->userType = $userType;
        $this->langId = $langId;
        parent::__construct(static::DB_TBL, 'course_id', $id);
    }

    /**
     * Return course enabled/disabled status
     *
     * @return boolean
     */
    public static function isEnabled(int $isAdmin = 0)
    {
        $status = FatApp::getConfig('CONF_ENABLE_COURSES');
        if ($isAdmin == 0) {
            return (bool) $status;
        }

        if ($status == AppConstant::YES) {
            return true;
        }
        $srch = new SearchBase(Order::DB_TBL);
        $srch->addFld('order_id');
        $srch->addCondition('order_type', '=', Order::TYPE_COURSE);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        if ($status == AppConstant::NO && FatApp::getDb()->fetch($srch->getResultSet())) {
            return false;
        }
        return true;
    }

    /**
     * Return list of course email templates
     *
     * @return array
     */
    public static function getEmailTemplates()
    {
        return [
            "completed_course_settlement_email_to_teacher",
            "course_approval_request_email_to_admin",
            "course_booking_email_to_admin",
            "course_booking_email_to_learner",
            "course_cancellation_request_email_to_admin",
            "course_refund_update_email_to_learner",
            "course_request_update_email_to_teacher",
            "course_edit_request_update_email_to_teacher",
            "course_edit_request_email_to_admin",
            "course_modifications_completed_email"
        ];
    }

    /**
     * Function to check if course is not sent for the approval
     *
     * @return boolean
     */
    public function canEditCourse()
    {
        if ($this->getMainTableRecordId() > 0) {
            $course = static::getAttributesById($this->getMainTableRecordId(), ['course_status', 'course_active', 'course_user_id','course_deleted']);
            if (!$course) {
                $this->error = Label::getLabel('LBL_COURSE_NOT_FOUND');
                return false;
            }
            if ($course['course_active'] == AppConstant::INACTIVE) {
                $this->error = Label::getLabel('LBL_COURSE_IS_IN_INACTIVE_STATE');
                return false;
            }
            if ($course['course_deleted']) {
                $this->error = Label::getLabel('LBL_COURSE_IS_DELETED');
                return false;
            }
            if ($course['course_user_id'] != $this->userId) {
                $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
                return false;
            }
            if ($course['course_status'] == static::PUBLISHED && $this->getEditRequestStatus()) {
                return true;
            }
            if (!$course['course_status']) {
                $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                return false;
            }
            if (static::SUBMITTED == $course['course_status']) {
                $this->error = Label::getLabel('LBL_ACTION_NOT_ALLOWED._COURSE_APPROVAL_REQUEST_IS_IN_PROCESS');
                return false;
            }
            if (static::PUBLISHED == $course['course_status']) {
                $this->error = Label::getLabel('LBL_ACTION_NOT_ALLOWED._COURSE_IS_ALREADY_PUBLISHED');
                return false;
            }
        }
        return true;
    }

    /**
     * GetCourse Status List
     *
     * @param integer $key
     * @return string|array
     */
    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::DRAFTED => Label::getLabel('LBL_DRAFTED'),
            static::SUBMITTED => Label::getLabel('LBL_SUBMITTED_FOR_APPROVAL'),
            static::PUBLISHED => Label::getLabel('LBL_PUBLISHED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Approval Requests Status List
     *
     * @param integer $key
     * @param integer $langId
     * @return string|array
     */
    public static function getRequestStatuses(int $key = null, int $langId = 0)
    {
        $arr = [
            static::REQUEST_PENDING => Label::getLabel('LBL_PENDING', $langId),
            static::REQUEST_APPROVED => Label::getLabel('LBL_APPROVED', $langId),
            static::REQUEST_DECLINED => Label::getLabel('LBL_DECLINED', $langId)
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Edit Requests Status List
     *
     * @param integer $key
     * @param integer $langId
     * @return string|array
     */
    public static function getEditRequestStatuses(int $key = null, int $langId = 0)
    {
        $arr = [
            static::EDIT_REQUEST_PENDING => Label::getLabel('LBL_EDIT_REQUEST_PENDING', $langId),
            static::EDIT_REQUEST_APPROVED => Label::getLabel('LBL_EDIT_REQUEST_APPROVED', $langId),
            static::EDIT_REQUEST_DECLINED => Label::getLabel('LBL_EDIT_REQUEST_DECLINED', $langId)
        ];
        return AppConstant::returArrValue($arr, $key);
    }
    /**
     * Get Refund Requests Status List
     *
     * @param integer $key
     * @param integer $langId
     * @return string|array
     */
    public static function getRefundStatuses(int $key = null, int $langId = 0)
    {
        $arr = [
            static::REFUND_PENDING => Label::getLabel('LBL_REFUND_PENDING', $langId),
            static::REFUND_APPROVED => Label::getLabel('LBL_REFUND_APPROVED', $langId),
            static::REFUND_DECLINED => Label::getLabel('LBL_REFUND_DECLINED', $langId)
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Types List
     *
     * @param integer $key
     * @return string|array
     */
    public static function getTypes(int $key = null)
    {
        $arr = [
            static::TYPE_FREE => Label::getLabel('LBL_FREE'),
            static::TYPE_PAID => Label::getLabel('LBL_PAID')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get course levels list or value
     *
     * @param integer $key
     * @return string|array
     */
    public static function getCourseLevels(int $key = null)
    {
        $levelList = json_decode(FatApp::getConfig('CONF_COURSE_LEVELS'));
        $arr = [];
        foreach ($levelList as $level) {
            $arr[$level->id] = Label::getLabel('LBL_' . $level->name);
        }
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Course Filter Types
     *
     * @param integer $key
     * @return string|array
     */
    public static function getFilterTypes(int $key = null)
    {
        $arr = [
            static::FILTER_COURSE => Label::getLabel('LBL_COURSES'),
            static::FILTER_TEACHER => Label::getLabel('LBL_TEACHERS'),
            static::FILTER_TAGS => Label::getLabel('LBL_TAGS')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Course Rating Filters
     *
     * @return array
     */
    public static function getRatingFilters()
    {
        return [
            '4.5' => Label::getLabel('LBL_4.5_&_UP'),
            '4.0' => Label::getLabel('LBL_4.0_&_UP'),
            '3.5' => Label::getLabel('LBL_3.5_&_UP'),
            '3.0' => Label::getLabel('LBL_3.0_&_UP'),
        ];
    }

    /**
     * Update course requests status
     *
     * @param array $data
     * @return bool
     */
    public function updateRequestStatus(array $data)
    {
        $requestId = FatUtility::int($data['coapre_id']);
        $status = FatUtility::int($data['coapre_status']);
        if ($requestId < 1 || $status < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $eligibility = $this->isEligibleForApproval();
        if ($eligibility['course_is_eligible'] == false && $status != static::REQUEST_DECLINED) {
            $this->error = Label::getLabel('LBL_COURSE_DETAILS_ARE_INCOMPLETE._CATEGORY,_SUBCATEGORY_OR_LANGUAGE_NOT_AVAILABLE.');
            return false;
        }
        $srch = new SearchBase(self::DB_TBL_APPROVAL_REQUEST);
        $srch->addCondition('coapre_id', '=', $requestId);
        $srch->addCondition('coapre_status', '=', static::REQUEST_PENDING);
        $srch->doNotCalculateRecords();
        if (!FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        if (!$this->setupRequest($requestId, $data)) {
            $this->error = $this->getError();
            return false;
        }
        /* update course status */
        $courseStatus = ($status == static::REQUEST_APPROVED) ? static::PUBLISHED : static::DRAFTED;
        if (!$this->updateStatus($courseStatus)) {
            $db->rollbackTransaction();
            return false;
        }
        if ($courseStatus == static::PUBLISHED && !$this->setStatsCount()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        $this->sendRequestUpdateMailToTeacher($data);
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    private function updateStatus($status)
    {
        $this->setFldValue('course_status', $status);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * function to send request status update email to teacher
     *
     * @param array $data
     * @return bool
     */
    private function sendRequestUpdateMailToTeacher(array $data)
    {
        $usrDtl = User::getAttributesById($data['user_id'], ['user_lang_id']);
        $mail = new FatMailer($usrDtl['user_lang_id'], 'course_request_update_email_to_teacher');
        $vars = [
            '{username}' => ucwords($data['user_first_name'] . ' ' . $data['user_last_name']),
            '{course_title}' => ucwords($data['coapre_title']),
            '{request_status}' => static::getRequestStatuses($data['coapre_status'], $usrDtl['user_lang_id']),
            '{admin_comment}' => empty($data['coapre_remark']) ? Label::getLabel('LBL_NA', $usrDtl['user_lang_id']) : nl2br($data['coapre_remark']),
        ];
        $mail->setVariables($vars);
        if (!$mail->sendMail([$data['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * function to send edit request status update email to teacher
     *
     * @param array $data
     * @return bool
     */
    private function sendEditRequestUpdateMailToTeacher(array $data)
    {
        $status = static::getEditRequestStatuses($data['coedre_status'], $data['user_lang_id']);
        $mail = new FatMailer($data['user_lang_id'], 'course_edit_request_update_email_to_teacher');
        $vars = [
            '{username}' => ucwords($data['user_first_name'] . ' ' . $data['user_last_name']),
            '{course_title}' => ucwords($data['course_title']),
            '{admin_comment}' => empty($data['coedre_comment']) ? Label::getLabel('LBL_NA', $data['user_lang_id']) : nl2br($data['coedre_comment']),
            '{request_status}' => $status,
            '{expiry_date}' => !empty($data['coedre_expired']) ? MyDate::showDate($data['coedre_expired'], true) : Label::getLabel('LBL_NA', $data['user_lang_id']),
        ];
        $mail->setVariables($vars);
        if (!$mail->sendMail([$data['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        $noti = new Notification($data['user_id'], Notification::TYPE_COURSE_EDIT_REQUEST_STATUS_UPDATE);
        $noti->sendNotification(['{request_status}' => $status], User::TEACHER);

        return true;
    }

    /**
     * Update course refund requests status
     *
     * @param array $data
     * @return bool
     */
    public function updateRefundRequestStatus(array $data)
    {
        $requestId = FatUtility::int($data['corere_id']);
        $status = FatUtility::int($data['corere_status']);
        if ($requestId < 1 || $status < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $srch = new CourseRefundRequestSearch($this->langId, 0, 0);
        $srch->joinUser();
        $srch->applySearchConditions(['corere_id' => $requestId]);
        $srch->addSearchListingFields();
        $srch->addFld('user_lang_id');
        $srch->addCondition('corere.corere_status', '=', self::REFUND_PENDING);
        if (!$request = FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $order = new OrderCourse($request['ordcrs_id'], $request['user_id'], User::SUPPORT, $request['user_lang_id']);
        if (!$orderData = $order->getCourseToCancel()) {
            $this->error = $order->getError();
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $refundData = [
            'corere_status' => $status,
            'corere_comment' => $data['corere_comment'],
            'corere_updated' => date('Y-m-d H:i:s')
        ];
        $where = ['smt' => 'corere_id = ?', 'vals' => [$requestId]];
        if (!$db->updateFromArray(self::DB_TBL_REFUND_REQUEST, $refundData, $where)) {
            $this->error = $db->getError();
            return false;
        }
        if ($status == static::REFUND_APPROVED) {
            $order->setFldValue('ordcrs_status', OrderCourse::CANCELLED);
            $order->setFldValue('ordcrs_updated', date('Y-m-d H:i:s'));
            if (!$order->save()) {
                $db->rollbackTransaction();
                $order->error = $order->getError();
                return false;
            }
            /* update course progress status */
            if (!FatApp::getDb()->updateFromArray(
                CourseProgress::DB_TBL,
                ['crspro_status' => CourseProgress::CANCELLED],
                ['smt' => 'crspro_ordcrs_id = ?', 'vals' => [$request['ordcrs_id']]]
            )) {
                $this->error = Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED');
                $db->rollbackTransaction();
                return false;
            }
            if (!$order->refundToLearner($orderData)) {
                $db->rollbackTransaction();
                return false;
            }
            $course = new Course($orderData['course_id']);
            if (!$course->setStudentCount()) {
                $this->error = $course->getError();
                $db->rollbackTransaction();
                return false;
            }
        }
        $request['corere_remark'] = $data['corere_comment'];
        $request = array_merge($request, $refundData);
        static::sendRefundStatusMailToLearner($request);
        $db->commitTransaction();
        return true;
    }

    /**
     * function to send refund request status update email to learner
     *
     * @param array $data
     * @return void
     */
    public static function sendRefundStatusMailToLearner(array $data)
    {
        $mail = new FatMailer($data['user_lang_id'], 'course_refund_update_email_to_learner');
        $vars = [
            '{username}' => ucwords($data['user_first_name'] . ' ' . $data['user_last_name']),
            '{course_title}' => ucwords($data['course_title']),
            '{request_status}' => static::getRefundStatuses($data['corere_status'], $data['user_lang_id']),
            '{admin_comment}' => empty($data['corere_remark']) ? Label::getLabel('LBL_NA') : nl2br($data['corere_remark']),
        ];
        $mail->setVariables($vars);
        $mail->sendMail([$data['user_email']]);
    }

    /**
     * Setup basic details
     *
     * @param array $data
     * @return bool
     */
    public function setupGeneralData(array $data)
    {
        if (!$this->canEditCourse()) {
            return false;
        }
        /* validate data */
        if (!$this->validate($data)) {
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }

        if ($data['course_id'] < 1) {
            $this->setFldValue('course_created', date('Y-m-d H:i:s'));
        }
        $courseStatus = Course::DRAFTED;
        if ($data['course_id'] > 1) {
            $status =  Course::getAttributesById($data['course_id'], 'course_status');
            if ($status == Course::PUBLISHED) {
                /* update course date */
                if (!$this->setUpdatedDate()) {
                    return false;
                }
                $courseStatus = Course::PUBLISHED;
            }
        }
        $this->setFldValue('course_user_id', $this->userId);
        $this->setFldValue('course_status', $courseStatus);
        $this->setFldValue('course_active', AppConstant::ACTIVE);
        $this->assignValues($data);
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        $this->setFldValue('course_id', $this->getMainTableRecordId());

        $this->setFldValue('course_slug', $this->getSlug($data['course_title'], $this->getMainTableRecordId()));
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }

        $assignValues = [
            'course_id' => $this->getMainTableRecordId(),
            'course_title' => $data['course_title'],
            'course_subtitle' => $data['course_subtitle'],
            'course_details' => $data['course_details'],
        ];
        if (!$this->setupLangData($assignValues)) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }

        /* Update Categories Record Count if Course Edited */
        if($this->getEditRequestStatus()) {
            if(!$this->setStatsCount()) {
                $db->rollbackTransaction();
                $this->error = $this->getError();
                return false;
            }
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Function to validate & create unique slug
     *
     * @param string $title
     * @param int    $id
     * @return string
     */
    private function getSlug(string $title, int $id = 0)
    {
        $title = MyUtility::createSlug($title);
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('course_slug', '=', $title);
        if ($id > 0) {
            $srch->addCondition('course_id', '!=', $id);
        }
        $srch->doNotCalculateRecords();
        $srch->addFld('course_slug');
        $srch->setPageSize(1);
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            return CommonHelper::seoUrl($title) . '-' . $this->getMainTableRecordId();
        }
        return CommonHelper::seoUrl($title);
    }

    /**
     * Validate form values
     *
     * @param array $data
     * @return bool
     */
    private function validate(array $data)
    {
        /* validate categories */
        $categories = [$data['course_cate_id'], $data['course_subcate_id']];
        $srch = Category::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->addFld('cate_id');
        $srch->addCondition('cate_id', 'IN', $categories);
        $srch->addCondition('cate_status', '=', AppConstant::ACTIVE);
        $srch->addCondition('cate_type', '=', Category::TYPE_COURSE);
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $categories = FatApp::getDb()->fetchAll($srch->getResultSet(), 'cate_id');
        if (!array_key_exists($data['course_cate_id'], $categories)) {
            $this->error = Label::getLabel('LBL_CATEGORY_NOT_AVAILABLE');
            return false;
        }
        if ($data['course_subcate_id'] > 0 && !array_key_exists($data['course_subcate_id'], $categories)) {
            $this->error = Label::getLabel('LBL_SUBCATEGORY_NOT_AVAILABLE');
            return false;
        }
        $courseLang = CourseLanguage::getAttributesById($data['course_clang_id'], ['clang_active', 'clang_deleted']);
        if ($courseLang['clang_active'] == AppConstant::INACTIVE || !empty($courseLang['clang_deleted'])) {
            $this->error = Label::getLabel('LBL_LANGUAGE_NOT_AVAILABLE');
            return false;
        }
        return true;
    }

    /**
     * Setup Course Basic Lang Data
     *
     * @param array $data
     * @return bool
     */
    private function setupLangData(array $data)
    {
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_LANG, $data, false, [], $data)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Setup basic details
     *
     * @param array $data
     * @return bool
     */
    public function setupSettings(array $data)
    {
        if (!$this->canEditCourse()) {
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        $editReqStatus = $this->getEditRequestStatus();
        if ($editReqStatus) {
            $this->setFldValue('course_updated', date('Y-m-d H:i:s'));
        } else {
            /* certificate settings updates not allowed if course is in edit mode */
            $this->setFldValue('course_certificate', $data['course_certificate']);
            $this->setFldValue('course_certificate_type', ($data['course_certificate_type'] ?? 0));
        }
        
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        $tagsList = explode('||', $data['course_tags']);
        $langData = [
            'course_id' => $this->getMainTableRecordId(),
            'course_srchtags' => json_encode($tagsList),
        ];
        if (!$this->setupLangData($langData)) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        if (!$editReqStatus) {
            if (!$this->setupQuiz($data)) {
                $db->rollbackTransaction();
                return false;
            }
        }

        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Setup quiz if attached
     *
     * @param array $data
     * @return bool
     */
    private function setupQuiz(array $data)
    {
        $quizLinked = QuizLinked::getQuizzes([$this->getMainTableRecordId()], AppConstant::COURSE);
        $quizLinked = current($quizLinked);
        /**
         * Delete if quiz is attached but certificate type is changed OR
         * if quiz is attached & type is same but new quiz is selected
         */
        if (!empty($quizLinked)) {
            if ((!isset($data['course_certificate_type'])) || ($data['course_certificate_type'] != Certificate::TYPE_COURSE_EVALUATION) || ($data['course_certificate_type'] == Certificate::TYPE_COURSE_EVALUATION && $data['course_quilin_id'] != $quizLinked['quilin_id'])) {
                $quiz = new QuizLinked($quizLinked['quilin_id'], $this->userId, $this->userType, $this->langId);
                if (!$quiz->remove()) {
                    $this->error = $quiz->getError();
                    return false;
                }

                $this->setFldValue('course_quilin_id', 0);
                if (!$this->save()) {
                    $this->error = $this->getError();
                    return false;
                }
            }
        }
        /* attach quiz if not attached yet or received different quiz */
        if (isset($data['course_certificate_type']) && ($data['course_certificate_type'] == Certificate::TYPE_COURSE_EVALUATION) && (empty($quizLinked) || $data['course_quilin_id'] != $quizLinked['quilin_id'])) {
            $quiz = new QuizLinked(0, $this->userId, $this->userType, $this->langId);
            if (!$quiz->setup($this->getMainTableRecordId(), AppConstant::COURSE, [$data['course_quilin_id']])) {
                $this->error = $quiz->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Remove attached quiz
     *
     * @param int $quilinId
     * @return bool
     */
    public function removeQuiz(int $quilinId)
    {
        if (!$this->canEditCourse()) {
            return false;
        }
        if (static::getAttributesById($this->getMainTableRecordId(), 'course_status') == static::PUBLISHED) {
            $this->error = Label::getLabel('LBL_QUIZ_CANNOT_BE_REMOVED_FROM_PUBLISHED_COURSES');
            return false;
        }
        if ($this->getMainTableRecordId() != QuizLinked::getAttributesById($quilinId, 'quilin_record_id')) {
            $this->error = Label::getLabel('LBL_INVALID_QUIZ');
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $quiz = new QuizLinked($quilinId, $this->userId, $this->userType, $this->langId);
        if (!$quiz->remove()) {
            $this->error = $quiz->getError();
            return false;
        }
        $this->setFldValue('course_quilin_id', 0);
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Function to remove course
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->getMainTableRecordId() < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if (!$this->canEditCourse() || !$this->canDeleteCourse()) {
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        /* mark course deleted */
        $this->setFldValue('course_deleted', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        if (!$this->deleteMedia()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->deleteSections()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->deleteLectures()) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    private function canDeleteCourse()
    {
        $courseId = $this->getMainTableRecordId();
        $courseDeleted = static::getAttributesById($courseId, ['course_deleted']);
        if ((int) $courseDeleted['course_deleted'] > 0) {
            $this->error = Label::getLabel('LBL_COURSE_ALREADY_DELETED');
            return false;
        }
        /* check if course order is in progress */
        $srch = new SearchBase(OrderCourse::DB_TBL);
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'order_id = ordcrs_order_id');
        $srch->addCondition('ordcrs_course_id', '=', $courseId);
        $srch->addCondition('ordcrs_status', '!=', OrderCourse::COMPLETED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_CANNOT_DELETE_AS_THE_COURSE_IS_ASSOCIATED_WITH_ORDERS');
            return false;
        }
        return true;
    }

    private function deleteMedia()
    {
        $courseId = $this->getMainTableRecordId();
        /* delete course images */
        $files = new Afile(Afile::TYPE_COURSE_IMAGE);
        $filesList = $files->getFilesByType($courseId);
        if ($filesList) {
            foreach ($filesList as $file) {
                if (!$files->removeById($file['file_id'], true)) {
                    $this->error = $files->getError();
                    return false;
                }
            }
        }
        /* delete course preview video */
        $videoId = $this->getAttributesById($courseId, 'course_preview_video');
        if (!empty($videoId)) {
            $video = new VideoStreamer();
            if (!$video->remove($videoId)) {
                $this->error = $video->getError();
                return false;
            }
        }
        return true;
    }

    private function deleteSections()
    {
        $db = FatApp::getDb();
        $where = ['smt' => 'section_course_id = ?', 'vals' => [$this->getMainTableRecordId()]];
        if (!$db->updateFromArray(Section::DB_TBL, ['section_deleted' => date('Y-m-d H:i:s')], $where)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    private function deleteLectures()
    {
        $courseId = $this->getMainTableRecordId();
        $db = FatApp::getDb();
        $where = ['smt' => 'lecture_course_id = ?', 'vals' => [$courseId]];
        if (!$db->updateFromArray(Lecture::DB_TBL, ['lecture_deleted' => date('Y-m-d H:i:s')], $where)) {
            $this->error = $db->getError();
            return false;
        }

        /* get lecture resources */
        $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
        $srch->addMultipleFields(['lecsrc_id', 'lecsrc_link']);
        $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('lecsrc.lecsrc_course_id', '=', $courseId);
        $resources = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($resources)) {
            return true;
        }
        $where = ['smt' => 'lecsrc_course_id = ?', 'vals' => [$courseId]];
        if (!$db->updateFromArray(Lecture::DB_TBL_LECTURE_RESOURCE, ['lecsrc_deleted' => date('Y-m-d H:i:s')], $where)) {
            $this->error = $db->getError();
            return false;
        }
        $data = [];
        foreach ($resources as $resource) {
            if (!empty($resource['lecsrc_link'])) {
                $data[] = $resource['lecsrc_link'];
            }
        }
        $video = new VideoStreamer();
        if (!$video->bulkRemove($data)) {
            $this->error = $video->getError();
            return false;
        }
        return true;
    }

    /**
     * Check and send eligibility status for approval
     *
     * @return array
     */
    public function isEligibleForApproval()
    {
        $courseId = $this->getMainTableRecordId();
        $criteria = json_decode(FatApp::getConfig('CONF_COURSE_APPROVAL_ELIGIBILITY_CRITERIA'));
        $criteria = array_fill_keys($criteria, 0);
        /* get course curriculum and price tabs data */
        $srch = new CourseSearch(0, $this->userId, $this->userType);
        $srch->joinTable(Category::DB_TBL, 'LEFT JOIN', 'subcate.cate_id = course.course_subcate_id', 'subcate');
        $srch->applyPrimaryConditions();
        $srch->addMultipleFields([
            'IF(course_sections > 0, 1, 0) as course_sections',
            'IF(course_lectures > 0, 1, 0) as course_lectures',
            'IF(course_type = ' . Course::TYPE_FREE . ' OR course_currency_id > 0, 1, 0) as course_currency_id',
            'IF(course_type = ' . Course::TYPE_FREE . ' OR course_price > 0, 1, 0) as course_price',
            'IF(cate.cate_deleted IS NULL AND cate.cate_status = ' . AppConstant::ACTIVE . ', 1, 0) course_cate',
            'IF(course.course_subcate_id > 0 AND (subcate.cate_deleted IS NOT NULL OR subcate.cate_status = ' . AppConstant::INACTIVE . '), 0, 1) course_subcate',
            'IF(clang.clang_deleted IS NULL AND clang.clang_active = ' . AppConstant::ACTIVE . ', 1, 0) course_clang',
            'IF(
                (course_certificate =' . AppConstant::YES . ' AND course_certificate_type = ' . Certificate::TYPE_COURSE_EVALUATION . ' AND course_quilin_id < 1) OR 
                (course_certificate =' . AppConstant::YES . ' AND course_certificate_type = 0)
                , 0, 1
            ) AS course_quiz',
            "course_video_ready as course_preview_video",
        ]);
        $srch->addCondition('course.course_id', '=', $courseId);
        $srch->setPageSize(1);
        $courseData = FatApp::getDb()->fetch($srch->getResultSet());

        if ($courseData) {
            $criteria = array_merge($criteria, $courseData);
        }


        /* check lecture resource with video link */
        $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('lecsrc_course_id', '=', $courseId);
        $srch->addCondition('lecsrc_type', '=', Lecture::TYPE_RESOURCE_EXTERNAL_URL);
        $srch->addCondition('lecsrc_duration', '=', 0);
        $srch->addDirectCondition('lecsrc_deleted IS NULL');
        $lectureResource = FatApp::getDb()->fetch($srch->getResultSet());
        if (($courseData && $courseData['course_sections'] > 0) && ($lectureResource && $lectureResource['lecsrc_duration'] == 0)) {

            $criteria['course_lectures'] = 0;
        }
        /*  check lecture resource with video link */

        /* check sections without lectures */
        $srch = new SearchBase(Section::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('section_course_id', '=', $courseId);
        $srch->addCondition('section_lectures', '=', 0);
        $srch->addDirectCondition('section_deleted IS NULL');
        $srch->addFld('COUNT(section_id) as sections_count');
        $sections = FatApp::getDb()->fetch($srch->getResultSet());
        if (($courseData && $courseData['course_sections'] > 0) && ($sections && $sections['sections_count'] > 0)) {
            $criteria['course_sections'] = 0;
        }
        /* get course lang data */
        $srch = new SearchBase(Course::DB_TBL_LANG);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('course_id', '=', $courseId);
        $srch->addMultipleFields([
            'IF(course_id IS NULL, 0, 1) as course_lang',
            '1 as course_welcome',
            '1 as course_congrats',
            'IF(course_srchtags IS NULL, 0, 1) as course_tags',
        ]);
        if ($courseLang = FatApp::getDb()->fetch($srch->getResultSet())) {
            $criteria = array_merge($criteria, $courseLang);
        }
        /* get course intended learners data */
        $srch = new SearchBase(IntendedLearner::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('coinle_course_id', '=', $courseId);
        $srch->addFld('coinle_id');
        $criteria['courses_intended_learners'] = (FatApp::getDb()->fetch($srch->getResultSet())) ? 1 : 0;
        /* get course image and video */
        $afile = new Afile(Afile::TYPE_COURSE_IMAGE);
        $criteria['course_image'] = ($afile->getFilesByType($courseId)) ? 1 : 0;
        $criteria['course_is_eligible'] = true;

        if (!empty(array_search(0, $criteria))) {
            $criteria['course_is_eligible'] = false;
        }
        return $criteria;
    }

    /**
     * Submit course for approval from admin
     *
     * @return bool
     */
    public function submitApprovalRequest()
    {
        if (!$this->canEditCourse()) {
            return false;
        }
        $eligibility = $this->isEligibleForApproval();
        if ($eligibility['course_is_eligible'] == false) {
            $this->error = Label::getLabel('LBL_COURSE_IS_NOT_ELIGIBILE_FOR_REVIEW._PLEASE_COMPLETE_THE_DETAILS');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        if (!$this->setupRequest(0)) {
            $this->error = $this->getError();
            return false;
        }
        /* update course status */
        if (!$this->updateStatus(static::SUBMITTED)) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        if (!$this->setupMetaTags()) {
            $db->rollbackTransaction();
            return false;
        }
        $this->sendApprovalRequestEmailToAdmin();

        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Add/Edit Approval Request
     *
     * @param int   $requestId
     * @param array $data
     * @return bool
     */
    private function setupRequest(int $requestId, array $data = [])
    {
        $db = FatApp::getDb();
        if ($requestId < 1) {
            $course = $this->get();
            $intendedLearner = new IntendedLearner();
            $intendedLearnerData = $intendedLearner->get($this->getMainTableRecordId());
            $data = [
                'coapre_course_id' => $this->getMainTableRecordId(),
                'coapre_cate_id' => $course['course_cate_id'],
                'coapre_subcate_id' => $course['course_subcate_id'],
                'coapre_clang_id' => $course['course_clang_id'],
                'coapre_level' => $course['course_level'],
                'coapre_certificate' => $course['course_certificate'],
                'coapre_certificate_type' => $course['course_certificate_type'],
                'coapre_quilin_id' => $course['course_quilin_id'],
                'coapre_status' => static::REQUEST_PENDING,
                'coapre_created' => date('Y-m-d H:i:s'),
                'coapre_title' => $course['course_title'],
                'coapre_subtitle' => $course['course_subtitle'],
                'coapre_details' => $course['course_details'],
                'coapre_price' => $course['course_price'],
                'coapre_duration' => $course['course_duration'],
                'coapre_srchtags' => $course['course_srchtags'],
                'coapre_learners' => json_encode($intendedLearnerData[IntendedLearner::TYPE_LEARNERS]),
                'coapre_learnings' => json_encode($intendedLearnerData[IntendedLearner::TYPE_LEARNING]),
                'coapre_requirements' => json_encode($intendedLearnerData[IntendedLearner::TYPE_REQUIREMENTS]),
                'coapre_preview_video' => $course['course_preview_video'],
            ];
        } else {
            $data = [
                'coapre_id' => $requestId,
                'coapre_status' => $data['coapre_status'],
                'coapre_remark' => $data['coapre_remark'],
                'coapre_updated' => date('Y-m-d H:i:s')
            ];
        }
        if (!$db->insertFromArray(static::DB_TBL_APPROVAL_REQUEST, $data, false, [], $data)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * function to send approval request email to admin
     *
     * @return bool
     */
    private function sendApprovalRequestEmailToAdmin()
    {
        $srch = new SearchBase(static::DB_TBL, 'course');
        $srch->addCondition('course.course_id', '=', $this->getMainTableRecordId());
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'crsdetail.course_id = course.course_id', 'crsdetail');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = teacher.user_id', 'teacher');
        $srch->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('course.course_user_id', '=', $this->userId);
        $srch->addMultipleFields([
            'teacher.user_first_name AS teacher_first_name',
            'teacher.user_last_name AS teacher_last_name',
            'crsdetail.course_title AS course_title',
        ]);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        $mail = new FatMailer(FatApp::getConfig('CONF_DEFAULT_LANG'), 'course_approval_request_email_to_admin');
        $vars = [
            '{username}' => ucwords($data['teacher_first_name'] . ' ' . $data['teacher_last_name']),
            '{course_title}' => ucwords($data['course_title']),
            '{course_link}' => MyUtility::makeFullUrl('CourseRequests', '', [], CONF_WEBROOT_BACKEND),
        ];
        $mail->setVariables($vars);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Update duration
     *
     * @return bool
     */
    public function setDuration()
    {
        $srch = new SearchBase(Section::DB_TBL);
        $srch->addCondition('section_course_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('section_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('IFNULL(SUM(section_duration), 0) AS course_duration');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        /* update duration */
        $this->assignValues($row);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to set students count in courses
     *
     * @param int $courseId
     * @return bool
     */
    public function setStudentCount()
    {
        /* get count */
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcrs.ordcrs_order_id', 'orders');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('COUNT(ordcrs_order_id) AS course_students');
        $srch->addCondition('ordcrs_course_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordcrs_status', '!=', OrderCourse::CANCELLED);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        /* update student count */
        $this->assignValues($row);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Get course data by id
     *
     * @return array|false
     */
    public function get()
    {
        $srch = new SearchBase(static::DB_TBL, 'course');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'crsdetail.course_id = course.course_id', 'crsdetail');
        $srch->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('course.course_id', '=', $this->getMainTableRecordId());
        $srch->addMultipleFields([
            'crsdetail.course_title',
            'crsdetail.course_subtitle',
            'crsdetail.course_details',
            'course.course_id',
            'course.course_cate_id',
            'course.course_subcate_id',
            'course.course_level',
            'course.course_certificate',
            'course.course_clang_id',
            'course.course_lectures',
            'course.course_user_id',
            'course.course_reviews',
            'course.course_ratings',
            'course.course_certificate',
            'course.course_currency_id',
            'course.course_price',
            'course.course_status',
            'course_duration',
            'course_srchtags',
            'course_quilin_id',
            'course_certificate_type',
            'course_preview_video',
            'course_slug',
            'course.course_updated'
        ]);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Feedback Course
     * 
     * @param array $post
     * @return bool
     */
    public function feedback(array $post): bool
    {
        $ordcrs = new OrderCourse($post['ordcrs_id'], $this->userId, $this->userType, $this->langId);
        if (!$course = $ordcrs->getCourseToFeedback()) {
            $this->error = $ordcrs->getError();
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $ratingReview = new CourseRatingReview($course['course_user_id'], $this->userId);
        $post['ratrev_lang_id'] = $this->langId;
        if (!$ratingReview->addReview(AppConstant::COURSE, $this->getMainTableRecordId(), $post)) {
            $db->rollbackTransaction();
            $this->error = $ratingReview->getError();
            return false;
        }
        $record = new OrderCourse($course['ordcrs_id']);
        $record->assignValues(['ordcrs_reviewed' => AppConstant::YES, 'ordcrs_updated' => date('Y-m-d H:i:s')]);
        if (!$record->save()) {
            $this->error = $record->getError();
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        if (FatApp::getConfig('CONF_DEFAULT_REVIEW_STATUS') == CourseRatingReview::STATUS_APPROVED) {
            $ratingReview->sendMailToTeacher($course);
        } else {
            $ratingReview->sendMailToAdmin($course);
        }
        return true;
    }

    /**
     * Set rating & reviews count
     *
     * @return bool
     */
    public function setRatingReviewCount()
    {
        $srch = new SearchBase(CourseRatingReview::DB_TBL, 'ratrev');
        $srch->addMultipleFields([
            'COUNT(*) as course_reviews',
            'IFNULL(ROUND(AVG(ratrev.ratrev_overall), 2), 0) as course_ratings'
        ]);
        $srch->addCondition('ratrev.ratrev_status', '=', CourseRatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_type_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ratrev.ratrev_type', '=', AppConstant::COURSE);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (!$data = FatApp::getDb()->fetch($srch->getResultSet())) {
            $data = ['course_ratings' => 0, 'course_reviews' => 0];
        }
        $this->assignValues($data);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Update stats for category and students
     *
     * @return bool
     */
    public function setStatsCount()
    {
        /* update category course count */
        $courseData = static::getAttributesById($this->getMainTableRecordId(), ['course_cate_id', 'course_subcate_id']);
        $srch = new SearchBase(Course::DB_TBL);
        $srch->joinTable(Category::DB_TBL, 'INNER JOIN', 'c.cate_id = course_cate_id OR c.cate_id = course_subcate_id', 'c');
        $srch->addCondition('course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('course_status', '=', static::PUBLISHED);
        $srch->addCondition('course_active', '=', AppConstant::YES);
        $srch->addCondition('c.cate_id', 'IN', $courseData);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['c.cate_id, COUNT(*)']);
        $srch->addGroupBy('cate_id');
        $rows = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        if (count($rows) > 0) {
            foreach ($rows as $id => $count) {
                $category = new Category($id);
                $category->assignValues(['cate_records' => $count]);
                if (!$category->save()) {
                    $this->error = $category->getError();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Settle payment for completed courses
     *
     * @return void
     */
    public function completedCourseSettlement()
    {
        $days = FatApp::getConfig('CONF_COURSE_CANCEL_DURATION');
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_order_id = orders.order_id', 'orders');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'course');
        $srch->joinTable(Course::DB_TBL_LANG, 'LEFT JOIN', 'crsdetail.course_id = course.course_id', 'crsdetail');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = teacher.user_id', 'teacher');
        $srch->joinTable(Course::DB_TBL_REFUND_REQUEST, 'LEFT JOIN', 'corere.corere_ordcrs_id = ordcrs.ordcrs_id', 'corere');
        $srch->addDirectCondition('DATE_ADD(orders.order_addedon, INTERVAL ' . $days . ' DAY) < "' . date('Y-m-d H:i:s') . '"', 'AND');
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addDirectCondition('ordcrs.ordcrs_teacher_paid IS NULL');
        $cnd = $srch->addCondition('corere.corere_id', 'IS', 'mysql_func_NULL', 'AND', true);
        $cnd->attachCondition('corere.corere_status', '=', static::REFUND_DECLINED);
        $srch->addOrder('ordcrs.ordcrs_id', 'ASC');
        $srch->addMultipleFields([
            'ordcrs_commission',
            'ordcrs_amount',
            'ordcrs_discount',
            'ordcrs_id',
            'order_id',
            'teacher.user_id as teacher_id',
            'teacher.user_email as teacher_email',
            'teacher.user_first_name',
            'teacher.user_last_name',
            'crsdetail.course_title',
            'teacher.user_lang_id',
            'orders.order_reward_value',
            'order_user_id'
        ]);
        $srch->doNotCalculateRecords();
        $courses =  FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($courses)) {
            return true;
        }
        $affCommissionArr = [];
        if (User::isAffiliateEnabled()) {
            $learnerIds = array_column($courses, 'order_user_id');
            $teacherIds = array_column($courses, 'teacher_id');
            $userIds = array_merge($learnerIds, $teacherIds);
            $affCommissionArr = AffiliateCommission::getCommission($userIds);
            if (!empty($affCommissionArr)) {
                $globalCommission = AffiliateCommission::getGlobalCommission();
            }
        }
        $db = FatApp::getDb();
        foreach ($courses as $course) {
            $data = [];
            $db->startTransaction();
            $commission = ($course['ordcrs_commission'] / 100) * $course['ordcrs_amount'];
            $teacherAmount = round(($course['ordcrs_amount'] - $commission), 2);
            if ($teacherAmount > 0) {
                $comment = Label::getLabel('LBL_PAYMENT_ON_COURSE_"{course-name}"', $course['user_lang_id']);
                $comment = str_replace('{course-name}', $course['course_title'], $comment);
                $txn = new Transaction($course['teacher_id'], Transaction::TYPE_TEACHER_PAYMENT);
                if (!$txn->credit($teacherAmount, $comment)) {
                    $this->error = $txn->getError();
                    $db->rollbackTransaction();
                    return false;
                }
                $txn->sendEmail();
                $course['amount'] = $teacherAmount;
                $this->sendPaymentMailToTeacher($course);
            }
            /* Credit reward points */
            $record = new RewardPoint($course['order_user_id']);
            if (!$record->purchaseRewards()) {
                $this->error = $record->getError();
                $db->rollbackTransaction();
                return false;
            }
            $affiliateCommission = 0.00;
            /* settle Affiliate Order Commission */
            if (isset($affCommissionArr[$course['order_user_id']])) {
                $data[] = [
                    'affiliate_id' => $affCommissionArr[$course['order_user_id']]['affiliate_user_id'],
                    'user_name' => $affCommissionArr[$course['order_user_id']]['user_first_name'] . " " . $affCommissionArr[$course['order_user_id']]['user_last_name'],
                    'afcomm_commission' => ($affCommissionArr[$course['order_user_id']]['afcomm_commission']) ??  $globalCommission,
                ];
            }
            if (isset($affCommissionArr[$course['teacher_id']])) {
                $data[] = [
                    'affiliate_id' => $affCommissionArr[$course['teacher_id']]['affiliate_user_id'],
                    'user_name' => $affCommissionArr[$course['teacher_id']]['user_first_name'] . " " . $affCommissionArr[$course['teacher_id']]['user_last_name'],
                    'afcomm_commission' => ($affCommissionArr[$course['teacher_id']]['afcomm_commission']) ??  $globalCommission,
                ];
            }

            if (!empty($data)) {
                $issueObj = new Issue();
                if (!$issueObj->setupAffiliateSessionCommission($data, $course['ordcrs_amount'], $affiliateCommission)) {
                    $this->error = $issueObj->getError();
                    $db->rollbackTransaction();
                    return false;
                }
                $affiliateCommission = round(($affiliateCommission), 2);
            }

            $orderCourse = new OrderCourse($course['ordcrs_id']);
            $earnings = $course['ordcrs_amount'] - ($course['ordcrs_discount'] + $teacherAmount + $course['order_reward_value'] + $affiliateCommission);
            $orderCourse->assignValues([
                'ordcrs_teacher_paid' => $teacherAmount,
                'ordcrs_commission_amount' => $commission,
                'ordcrs_earnings' => FatUtility::float($earnings),
                'ordcrs_affiliate_commission' => FatUtility::float($affiliateCommission),
                'ordcrs_updated' => date('Y-m-d H:i:s')
            ]);
            if (!$orderCourse->save()) {
                $this->error = $orderCourse->getError();
                $db->rollbackTransaction();
                return false;
            }
            $txn = new AdminTransaction($course['order_id'], AppConstant::COURSE);
            $comment = Label::getLabel('LBL_EARNINGS_ON_COURSE_ORDER_ID_:_') . $course['order_id'];
            if (!$txn->logEarningTxn($earnings, $comment)) {
                $this->error = $txn->getError();
                return false;
            }
            $db->commitTransaction();
        }
        return true;
    }

    /**
     * function to send course payment settlement email to teacher
     *
     * @param array $data
     * @return void
     */
    public function sendPaymentMailToTeacher(array $data)
    {
        $mail = new FatMailer($data['user_lang_id'], 'completed_course_settlement_email_to_teacher');
        $vars = [
            '{username}' => ucwords($data['user_first_name'] . ' ' . $data['user_last_name']),
            '{course_title}' => ucwords($data['course_title']),
            '{amount}' => MyUtility::formatMoney($data['amount']),
        ];
        $mail->setVariables($vars);
        $mail->sendMail([$data['teacher_email']]);
    }

    /**
     * Get course data by slug
     *
     * @param string $slug
     * @return array|null
     */
    public static function getCourseBySlug(string $slug)
    {
        $srch = new SearchBase(Course::DB_TBL, 'crs');
        $srch->addMultipleFields(['course_id', 'crs.course_slug']);
        $srch->addCondition('course_slug', '=', $slug);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Setup meta tags for courses
     *
     * @return void
     */
    private function setupMetaTags()
    {
        /* Setup meta tags */
        $data = $this->get();
        /* Get meta tag */
        $metaData = MetaTag::getMetaTag(MetaTag::META_GROUP_COURSE, $data['course_slug']);
        $meta = new MetaTag(($metaData['meta_id']) ?? 0);
        if (!$meta->addMetaTag(MetaTag::META_GROUP_COURSE, $data['course_slug'], $data['course_slug'] . '_' . Label::getLabel('LBL_META_COURSE'))) {
            $this->error = $meta->getError();
            return false;
        }
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $value) {
            if (!$meta->updateCoursesData($langId, $data)) {
                $this->error = $meta->getError();
                return false;
            }
        }
        return true;
    }

    public function getQuiz(int $quilinId)
    {
        $srch = new SearchBase(QuizAttempt::DB_TBL);
        $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quizat_quilin_id = quilin_id');
        $srch->addCondition('quizat_quilin_id', '=', $quilinId);
        $srch->addCondition('quizat_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('quizat_user_id', '=', $this->userId);
        $srch->addMultipleFields(['quizat_id', 'quilin_title', 'quilin_detail', 'quilin_record_id']);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Add/Edit Course Edit Request
     *
     * @param string $details
     * @return bool
     */
    public function setupEditRequest(string $details)
    {
        if (!$course = $this->get()) {
            $this->error = Label::getLabel('LBL_COURSE_NOT_FOUND');
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $courseId = $this->getMainTableRecordId();
        if (!$db->updateFromArray(Course::DB_TBL_EDIT_REQUEST, ['coedre_recent' => AppConstant::NO], ['smt'=>'coedre_course_id = ?', 'vals' => [$courseId]])) {
            $this->error = $db->getError();
            return false;
        }
        $data = [
            'coedre_course_id' => $courseId,
            'coedre_reason' => $details,
            'coedre_status' => Course::EDIT_UPDATES_INPROGRESS,
            'coedre_created' => date('Y-m-d H:i:s'),
            'coedre_recent' => AppConstant::YES,
        ];
        $record = new TableRecord(Course::DB_TBL_EDIT_REQUEST);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $db->rollbackTransaction();
            return false;
        }
        $data['course_title'] = $course['course_title'];
        $this->sendEditRequestEmailToAdmin($data);
        $db->commitTransaction();
        return true;
    }

    /**
     * Add/Edit Course Edit Request
     *
     * @param int   $requestId
     * @param array $data
     * @return bool
     */
    public function updateEditRequest(array $data = [])
    {
        $requestId = FatUtility::int($data['coedre_id']);
        $status = FatUtility::int($data['coedre_status']);
        $updatedDate = date('Y-m-d H:i:s');
        $duration = FatApp::getConfig('CONF_COURSE_EDIT_DURATION');
        $record = new TableRecord(Course::DB_TBL_EDIT_REQUEST);
        $record->assignValues([
            'coedre_status' => $status,
            'coedre_updated' => $updatedDate,
            'coedre_comment' => $data['coedre_comment'],
            'coedre_duration' => $duration,
        ]);
        if (!$record->update(['smt' => 'coedre_id = ?', 'vals' => [$requestId]])) {
            $this->error = $record->getError();
            return false;
        }
        $data['coedre_expired'] = ($status == Course::EDIT_REQUEST_APPROVED) ? MyDate::formatDate(date('Y-m-d H:i:s', strtotime($updatedDate . ' + ' . $duration . ' days')), 'Y-m-d H:i:s', $data['user_timezone']) : '';
        $this->sendEditRequestUpdateMailToTeacher($data);
        return true;
    }


    /**
     * Get Latest Edit Request
     * @return array
     */
    public function getLatestEditRequest(): array
    {
        $srch = new SearchBase(static::DB_TBL_EDIT_REQUEST);
        $srch->addCondition('coedre_course_id', '=', $this->getMainTableRecordId());
        $srch->addMultipleFields(['coedre_id', 'coedre_updated', 'coedre_status', 'coedre_updates_done', 'coedre_duration']);
        $srch->setPageSize(1);
        $srch->addOrder('coedre_id', 'DESC');
        return FatApp::getDb()->fetch($srch->getResultSet()) ?? [];
    }

    /**
     * Get Edit Request Course
     * @return bool
     */
    public function getEditRequestStatus(): bool
    {
        $editRequest = $this->getLatestEditRequest();
        if (empty($editRequest)) {
            return false;
        }
        if (self::checkIfEditRequestValid($editRequest)) {
            return true;
        }
        return false;
    }

    public static function checkIfEditRequestValid($editRequest)
    {
        $allowEditDate = date('Y-m-d H:i:s', strtotime($editRequest['coedre_updated'] . ' + ' . $editRequest['coedre_duration'] . ' days'));
        if ($editRequest['coedre_status'] == Course::EDIT_REQUEST_APPROVED && $allowEditDate > date('Y-m-d H:i:s') && $editRequest['coedre_updates_done'] == Course::EDIT_UPDATES_INPROGRESS) {
            return true;
        }
        return false;
    }

    /**
     * Can send edit course request
     * @return bool
     */
    public static function canSendEditRequest($editRequest): bool
    {
        if (empty($editRequest)) {
            return true;
        }
        if ($editRequest['coedre_status'] ==  course::EDIT_REQUEST_PENDING) {
            return false;
        }
        if (self::checkIfEditRequestValid($editRequest)) {
            return false;
        }
        return true;
    }


    /**
     * function to send approval request email to admin
     *
     * @return bool
     */
    private function sendEditRequestEmailToAdmin($data)
    {
        $user = User::getAttributesById($this->userId, ['user_first_name', 'user_last_name']);
        $mail = new FatMailer(FatApp::getConfig('CONF_DEFAULT_LANG'), 'course_edit_request_email_to_admin');
        $vars = [
            '{username}' => ucwords($user['user_first_name'] . ' ' . $user['user_last_name']),
            '{course_title}' => ucwords($data['course_title']),
            '{course_link}' => MyUtility::makeFullUrl('courseEditRequests', '', [], CONF_WEBROOT_BACKEND),
        ];
        $mail->setVariables($vars);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Set course updated date
     *
     * @return bool
     */
    public function setUpdatedDate()
    {
        /* update course duration */
        $this->assignValues(['course_updated' => date('Y-m-d H:i:s')]);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Set course updates done
     *
     * @return bool
     */
    public function markUpdatesComplete()
    {
        $latestRequest = $this->getLatestEditRequest();
        if (!$latestRequest) {
            $this->error = Label::getLabel('LBL_REQUEST_NOT_FOUND');
            return false;
        }
        if ($latestRequest['coedre_updates_done'] == Course::EDIT_UPDATES_COMPLETE) {
            $this->error = Label::getLabel('LBL_UPDATES_ARE_ALREADY_MARKED_COMPLETED');
            return false;
        }
        if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($latestRequest['coedre_updated'] . ' + ' . $latestRequest['coedre_duration'] . ' days'))) {
            $this->error = Label::getLabel('LBL_ALLOWED_DURATION_FOR_UPDATES_IS_OVER');
            return false;
        }
        if (!$this->completeEditRequest($latestRequest['coedre_id'])) {
            return false;
        }
        return true;
    }

    /**
     * Mark edit request status as complete
     *
     * @param int $editReqId
     * @return bool
     */
    public function completeEditRequest(int $editReqId)
    {
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        if (!CourseProgress::updateProgressInBulk($this->getMainTableRecordId())) {
            $this->error = $db->getError();
            $db->rollbackTransaction();
            return false;
        }
        /* update course duration */
        $record = new TableRecord(Course::DB_TBL_EDIT_REQUEST);
        $record->assignValues(['coedre_updates_done' => Course::EDIT_UPDATES_COMPLETE]);
        if (!$record->update(['smt' => 'coedre_id = ?', 'vals' => [$editReqId]])) {
            $db->rollbackTransaction();
            $this->error = $record->getError();
            return false;
        }
        /* send email to learner who has purchsed course */
        $this->sendCompletionEmailToUsers();

        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Send notifications to all users who has purchased course
     *
     * @return bool
     */
    public function sendCompletionEmailToUsers()
    {
        /* get users with course purchased */
        $srch = new OrderCourseSearch($this->langId, 0, 0);
        $srch->addDirectCondition('teacher.user_deleted IS NULL');
        $srch->addDirectCondition('teacher.user_verified IS NOT NULL');
        $srch->addCondition('teacher.user_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        $srch->addDirectCondition('learner.user_deleted IS NULL');
        $srch->addDirectCondition('learner.user_verified IS NOT NULL');
        $srch->addCondition('learner.user_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('ordcrs_course_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ordcrs_status', '!=', OrderCourse::CANCELLED);
        $srch->addMultipleFields(['learner.user_id', 'learner.user_email', 'learner.user_lang_id', 'learner.user_first_name', 'learner.user_last_name', 'course_title']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $users = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($users)) {
            return true;
        }
        $sendEmail = FatApp::getConfig('CONF_SEND_COURSE_EDIT_EMAILS', FatUtility::VAR_INT, AppConstant::NO);
        foreach ($users as $user) {
            $vars = [
                '{username}' => ucwords($user['user_first_name'] . ' ' . $user['user_last_name']),
                '{course_title}' => ucfirst($user['course_title']),
            ];
            if ($sendEmail) {
                $mail = new FatMailer($user['user_lang_id'], 'course_modifications_completed_email');
                $mail->setVariables($vars);
                if (!$mail->sendMail([$user['user_email']])) {
                    $this->error = $mail->getError();
                    return false;
                }
            }
            $noti = new Notification($user['user_id'], Notification::TYPE_COURSE_MODIFICATIONS_COMPLETED);
            $noti->sendNotification(['{course_title}' => ucfirst($user['course_title'])], User::LEARNER);
        }
        return true;
    }
}
