<?php

/**
 * This class is used to handle Courses Order
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderCourseSearch extends W3mentorsSearch
{
    /**
     * Initialize Order Course
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = OrderCourse::DB_TBL;
        $this->alias = 'ordcrs';
        
        parent::__construct($langId, $userId, $userType);

        $this->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_order_id = orders.order_id', 'orders');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'orders.order_user_id = learner.user_id', 'learner');
        $this->joinTable(Course::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'course');
        $this->joinTable(Course::DB_TBL_LANG, 'INNER JOIN', 'crsdetail.course_id = course.course_id', 'crsdetail');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = teacher.user_id', 'teacher');
        $this->joinTable(CourseProgress::DB_TBL, 'LEFT JOIN', 'crspro.crspro_ordcrs_id = ordcrs.ordcrs_id', 'crspro');
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

            $cond = $this->addCondition('crsdetail.course_title', 'LIKE', '%' . $keyword . '%');
            if ($this->userType === User::SUPPORT) {
                $cond->attachCondition('mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)', 'LIKE', '%' . $keyword . '%', 'OR', true);
                $cond->attachCondition('mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)', 'LIKE', '%' . $keyword . '%', 'OR', true);
            } else {
                $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
                if ($this->userType === User::LEARNER) {
                    $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
                }
                $cond->attachCondition($fullName, 'LIKE', '%' . $keyword . '%', 'OR', true);
            }

            $orderId = FatUtility::int(str_replace("O", '', strtoupper($keyword)));
            $cond->attachCondition('ordcrs.ordcrs_order_id', '=', $orderId);
            $cond->attachCondition('ordcrs.ordcrs_id', '=', $orderId);
        }

        if (isset($post['order_payment_status']) && $post['order_payment_status'] !== '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }

        if (!empty($post['ordcrs_status'])) {
            $this->addCondition('ordcrs.ordcrs_status', '=', $post['ordcrs_status']);
        }
        if (!empty($post['course_cateid'])) {
            $this->addCondition('course.course_cate_id', '=', $post['course_cateid']);
        }
        if (!empty($post['course_subcateid'])) {
            $this->addCondition('course.course_subcate_id', '=', $post['course_subcateid']);
        }
        if (!empty($post['order_addedon_from'])) {
            $start = $post['order_addedon_from'] . ' 00:00:00';
            $this->addCondition('orders.order_addedon', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['order_addedon_till'])) {
            $end = $post['order_addedon_till'] . ' 23:59:59';
            $this->addCondition('orders.order_addedon', '<=', MyDate::formatToSystemTimezone($end));
        }
        if (isset($post['course_type']) && $post['course_type'] > 0) {
            $this->addCondition('course.course_type', '=', $post['course_type']);
        }
        if (isset($post['crspro_status']) && !empty($post['crspro_status'])) {
            $this->addCondition('crspro.crspro_status', '=', $post['crspro_status']);
        }
    }

    /**
     * Add Search Listing Fields
     *
     * @return void
     */
    public function addSearchListingFields(): void
    {
        $fields = static::getListingFields();
        foreach ($fields as $field => $alias) {
            $this->addFld($field . ' AS ' . $alias);
        }
    }

    /**
     * Get Listing FFields
     *
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'ordcrs.ordcrs_id' => 'ordcrs_id',
            'ordcrs.ordcrs_order_id' => 'order_id',
            'ordcrs.ordcrs_amount' => 'ordcrs_amount',
            'ordcrs.ordcrs_discount' => 'ordcrs_discount',
            'ordcrs.ordcrs_status' => 'ordcrs_status',
            'ordcrs.ordcrs_reviewed' => 'ordcrs_reviewed',
            'ordcrs.ordcrs_course_type' => 'ordcrs_course_type',
            'teacher.user_first_name' => 'teacher_first_name',
            'teacher.user_last_name' => 'teacher_last_name',
            'teacher.user_username' => 'user_username',
            'learner.user_first_name' => 'learner_first_name',
            'learner.user_last_name' => 'learner_last_name',
            'course.course_id' => 'course_id',
            'course.course_ratings' => 'course_ratings',
            'course.course_reviews' => 'course_reviews',
            'course.course_lectures' => 'course_lectures',
            'course.course_duration' => 'course_duration',
            'course.course_certificate' => 'course_certificate',
            'course.course_certificate_type' => 'course_certificate_type',
            'course.course_quilin_id' => 'course_quilin_id',
            'course.course_cate_id' => 'course_cate_id',
            'course.course_subcate_id' => 'course_subcate_id',
            'course.course_slug' => 'course_slug',
            'crsdetail.course_title' => 'course_title',
            'orders.order_addedon' => 'order_addedon',
            'orders.order_pmethod_id' => 'order_pmethod_id',
            'orders.order_addedon' => 'order_created',
            'orders.order_payment_status' => 'order_payment_status',
            'orders.order_reward_value' => 'order_reward_value',
            'crspro.crspro_completed' => 'crspro_completed',
            'crspro.crspro_progress' => 'crspro_progress',
            'crspro.crspro_ordcrs_id' => 'crspro_ordcrs_id',
            'crspro.crspro_id' => 'crspro_id',
            'IFNULL(crspro.crspro_status, ' . CourseProgress::PENDING . ')' => 'crspro_status',
            'course.course_price' => 'course_price',
            'course.course_currency_id' => 'course_currency_id',
            'course.course_type' => 'course_type',
            'course.course_students' => 'course_students',
            'crsdetail.course_subtitle' => 'course_subtitle',
            'ordcrs.ordcrs_teacher_paid' => 'ordcrs_teacher_paid',
            'course.course_status' => 'course_status',
            'course.course_updated' => 'course_updated'
        ];
    }

    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        if ($this->userType === User::LEARNER) {
            $this->addCondition('orders.order_user_id', '=', $this->userId);
            $this->addDirectCondition('learner.user_deleted IS NULL');
        } elseif ($this->userType === User::TEACHER) {
            $this->addDirectCondition('teacher.user_deleted IS NULL');
            $this->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        }
        $this->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
    }

    /**
     * Fetch And Format
     *
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'ordcrs_id');
        if (count($rows) == 0) {
            return [];
        }
        $ordcrsIds = array_keys($rows);
        $cancelReqs = [];
        if (count($ordcrsIds) > 0) {
            /* get cancellation request data */
            $srch = new SearchBase(Course::DB_TBL_REFUND_REQUEST);
            $srch->addDirectCondition('corere_ordcrs_id IN (' . implode(', ', $ordcrsIds) . ')');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addMultipleFields(['corere_ordcrs_id', 'corere_id', 'corere_status']);
            $cancelReqs = FatApp::getDb()->fetchAll($srch->getResultSet(), 'corere_ordcrs_id');
        }
        $categoryIds = array_merge(array_column($rows, 'course_cate_id'), array_column($rows, 'course_subcate_id'));
        $categories = CourseSearch::getCategoryNames($this->langId, array_unique($categoryIds));

        /* get quizzes details */
        $quizLinkIds = array_column($rows, 'course_quilin_id');
        $quizzes = QuizAttempt::getQuizzes(array_unique($quizLinkIds), $this->userId);
        foreach ($rows as $key => $row) {
            $row['cate_name'] = array_key_exists($row['course_cate_id'], $categories) ? $categories[$row['course_cate_id']] : '';
            $row['subcate_name'] = array_key_exists($row['course_subcate_id'], $categories) ? $categories[$row['course_subcate_id']] : '';
            if (isset($cancelReqs[$row['ordcrs_id']])) {
                $row['corere_status'] = $cancelReqs[$row['ordcrs_id']]['corere_status'];
            }
            $row['can_view_course'] = $this->canView($row);
            $row['can_edit_course'] = false;
            $row['can_delete_course'] = false;
            $row['can_cancel_course'] = $this->canCancel($row);
            $row['can_rate_course'] = $this->canRate($row, $this->userType);
            $row['can_retake_course'] = $this->canRetake($row);
            if (isset($quizzes[$row['course_id']])) {
                $row = array_merge($row, $quizzes[$row['course_id']]);
            }
            $row['can_download_certificate'] = $this->canDownloadCertificate($row);
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Can View Course
     * 
     * @param array $course
     * @return bool
     */
    private function canView(array $course)
    {
        if ($course['ordcrs_status'] == OrderCourse::CANCELLED) {
            return false;
        }
        return true;
    }

    /**
     * Can Cancel Course
     * 
     * @param array $course
     * @return bool
     */
    private function canCancel(array $course)
    {
        if (!isset($course['order_created'])) {
            return false;
        }
        if ($course['ordcrs_status'] == OrderCourse::CANCELLED) {
            return false;
        }
        $duration = FatApp::getConfig('CONF_COURSE_CANCEL_DURATION');
        $orderDate = strtotime(' +' . $duration . ' day', strtotime($course['order_created']));
        $date = strtotime(date('Y-m-d H:i:s'));
        if ($this->userType == User::LEARNER && $date > $orderDate) {
            return false;
        }
        if (!is_null($course['ordcrs_teacher_paid'])) {
            return false;
        }   
        if (isset($course['corere_status'])) {
            return false;
        }
        return true;
    }

    /**
     * Can Rate Course
     * 
     * @param array $course
     * @param int   $userType
     * @return bool
     */
    public static function canRate(array $course, int $userType)
    {
        if ($userType == User::TEACHER) {
            return false;
        }
        if (FatApp::getConfig('CONF_ALLOW_REVIEWS') != AppConstant::YES) {
            return false;
        }
        if ($course['ordcrs_status'] == OrderCourse::CANCELLED) {
            return false;
        }
        if (empty($course['crspro_completed']) || $course['ordcrs_reviewed'] == AppConstant::YES) {
            return false;
        }
        return true;
    }

    /**
     * Can Retake Course
     * 
     * @param array $ordcrs
     * @return bool
     */
    private function canRetake(array $ordcrs)
    {
        if ($this->userType == User::TEACHER) {
            return false;
        }
        if (!isset($ordcrs['crspro_completed']) || !$ordcrs['crspro_completed']) {
            return false;
        }
        if ($ordcrs['crspro_completed'] && $ordcrs['crspro_progress'] < 1) {
            return false;
        }
        if ($ordcrs['ordcrs_status'] == OrderCourse::CANCELLED) {
            return false;
        }
        return true;
    }

    /**
     * Can Download Course Certificate
     * 
     * @param array $certificate
     * @return bool
     */
    private function canDownloadCertificate(array $certificate)
    {
        if ($this->userType == User::TEACHER) {
            return false;
        }
        if ($certificate['course_certificate'] == AppConstant::NO) {
            return false;
        }
        if ($certificate['course_certificate_type'] == Certificate::TYPE_COURSE_EVALUATION) {
            if ($certificate['course_quilin_id'] == 0) {
                return false;
            }
            if ($certificate['course_quilin_id'] > 0 && !isset($certificate['quizat_status'])) {
                return false;
            }
            if ($certificate['quizat_status'] != QuizAttempt::STATUS_COMPLETED) {
                return false;
            }
            if (isset($certificate['quizat_evaluation']) && $certificate['quizat_evaluation'] != QuizAttempt::EVALUATION_PASSED) {
                return false;
            }
        }
        if ($certificate['ordcrs_status'] != OrderCourse::COMPLETED) {
            return false;
        }
        if (!$certificate['crspro_completed']) {
            return false;
        }
        return true;
    }
}
