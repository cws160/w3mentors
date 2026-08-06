<?php

class CourseRefundRequestSearch extends W3mentorsSearch
{

    /**
     * Initialize Course Requests Search
     *
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = Course::DB_TBL_REFUND_REQUEST;
        $this->alias = 'corere';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_id = corere.corere_ordcrs_id', 'ordcrs');
        $this->joinTable(Course::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'course');
        $this->joinTable(Course::DB_TBL_LANG, 'INNER JOIN', 'crsdetail.course_id = course.course_id', 'crsdetail');
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (isset($post['corere_id'])) {
            $this->addCondition('corere_id', '=', $post['corere_id']);
        }
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cnd = $this->addCondition('crsdetail.course_title', 'LIKE', '%' . $keyword . '%');
            $cnd->attachCondition('crsdetail.course_subtitle', 'LIKE', '%' . $keyword . '%', 'OR');
        }
        if (isset($post['learner_id']) && $post['learner_id'] > 0) {
            $this->addCondition('corere_user_id', '=', $post['learner_id']);
        } elseif (!empty($post['learner'])) {
            $fullName = 'mysql_func_CONCAT(u.user_first_name, " ", u.user_last_name)';
            $this->addCondition($fullName, 'LIKE', '%' . trim($post['learner']) . '%', 'AND', true);
        }
        if (isset($post['corere_status']) && $post['corere_status'] != '') {
            $this->addCondition('corere_status', '=', $post['corere_status']);
        }
        if (isset($post['start_date']) && !empty($post['start_date'])) {
            $this->addCondition('corere_created', ">=", MyDate::formatToSystemTimezone($post['start_date'] . ' 00:00:00'), 'AND', true);
        }
        if (isset($post['end_date']) && !empty($post['end_date'])) {
            $this->addCondition('corere_created', "<=", MyDate::formatToSystemTimezone($post['end_date'] . ' 23:59:59'), 'AND', true);
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
            'corere.corere_id' => 'corere_id',
            'corere.corere_status' => 'corere_status',
            'corere.corere_remark' => 'corere_remark',
            'corere.corere_comment' => 'corere_comment',
            'corere.corere_created' => 'corere_created',
            'ordcrs.ordcrs_id ' => 'ordcrs_id',
            'ordcrs.ordcrs_course_id ' => 'ordcrs_course_id',
            'ordcrs.ordcrs_amount ' => 'ordcrs_amount',
            'crsdetail.course_title' => 'course_title',
            'crsdetail.course_subtitle' => 'course_subtitle',
            'course.course_id' => 'course_id',
            'course.course_price' => 'course_price',
            'course.course_currency_id' => 'course_currency_id',
            'course.course_duration' => 'course_duration',
            'course.course_status' => 'course_status',
            'crsdetail.course_details' => 'course_details',
            'u.user_id' => 'user_id',
            'u.user_first_name' => 'user_first_name',
            'u.user_last_name' => 'user_last_name',
            'u.user_email' => 'user_email',
            'u.user_gender ' => 'user_gender',
            'u.user_lang_id ' => 'user_lang_id',
        ];
    }

    /**
     * Fetch And Format
     *
     * @return array
     */
    public function fetchAndFormat(bool $single = false): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'corere_id');
        if (count($rows) == 0) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['corere_created'] = MyDate::formatDate($row['corere_created']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }

    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        
    }

    /**
     * Join user table
     *
     * @return void
     */
    public function joinUser()
    {
        $this->joinTable(
            User::DB_TBL,
            'INNER JOIN',
            'corere.corere_user_id = u.user_id',
            'u'
        );
    }

}
