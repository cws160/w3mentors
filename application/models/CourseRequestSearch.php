<?php

class CourseRequestSearch extends W3mentorsSearch
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
        $this->table = Course::DB_TBL_APPROVAL_REQUEST;
        $this->alias = 'coapre';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(Course::DB_TBL, 'INNER JOIN', 'coapre.coapre_course_id = course.course_id', 'course');
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (isset($post['coapre_id'])) {
            $this->addCondition('coapre_id', '=', $post['coapre_id']);
        }
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cnd = $this->addCondition('coapre.coapre_title', 'LIKE', '%' . $keyword . '%');
            $cnd->attachCondition('coapre.coapre_subtitle', 'LIKE', '%' . $keyword . '%', 'OR');
        }
        if (isset($post['teacher_id']) && $post['teacher_id'] > 0) {
            $this->addCondition('course.course_user_id', '=', $post['teacher_id']);
        } elseif (!empty($post['teacher'])) {
            $fullName = 'mysql_func_CONCAT(u.user_first_name, " ", u.user_last_name)';
            $this->addCondition($fullName, 'LIKE', '%' . trim($post['teacher']) . '%', 'AND', true);
        }
        if (isset($post['coapre_status']) && $post['coapre_status'] != '') {
            $this->addCondition('coapre_status', '=', $post['coapre_status']);
        }
        if (isset($post['start_date']) && !empty($post['start_date'])) {
            $this->addCondition('coapre_created', ">=", MyDate::formatToSystemTimezone($post['start_date'] . ' 00:00:00'), 'AND', true);
        }
        if (isset($post['end_date']) && !empty($post['end_date'])) {
            $this->addCondition('coapre_created', "<=", MyDate::formatToSystemTimezone($post['end_date'] . ' 23:59:59'), 'AND', true);
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
            'coapre.coapre_id' => 'coapre_id',
            'coapre.coapre_status' => 'coapre_status',
            'coapre.coapre_remark' => 'coapre_remark',
            'coapre.coapre_created' => 'coapre_created',
            'coapre.coapre_course_id ' => 'coapre_course_id',
            'coapre.coapre_cate_id ' => 'coapre_cate_id',
            'coapre.coapre_subcate_id ' => 'coapre_subcate_id',
            'coapre.coapre_title' => 'coapre_title',
            'coapre.coapre_subtitle' => 'coapre_subtitle',
            'coapre.coapre_price' => 'coapre_price',
            'coapre.coapre_duration' => 'coapre_duration',
            'coapre.coapre_level' => 'coapre_level',
            'coapre.coapre_certificate' => 'coapre_certificate',
            'coapre.coapre_clang_id' => 'coapre_clang_id',
            'coapre.coapre_details' => 'coapre_details',
            'coapre.coapre_learners' => 'coapre_learners',
            'coapre.coapre_learnings' => 'coapre_learnings',
            'coapre.coapre_requirements' => 'coapre_requirements',
            'coapre.coapre_srchtags' => 'coapre_srchtags',
            'coapre.coapre_preview_video' => 'coapre_preview_video',
            'coapre.coapre_quilin_id' => 'coapre_quilin_id',
            'coapre.coapre_certificate_type' => 'coapre_certificate_type',
            'u.user_id' => 'user_id',
            'u.user_first_name' => 'user_first_name',
            'u.user_last_name' => 'user_last_name',
            'u.user_email' => 'user_email',
            'u.user_gender ' => 'user_gender',
        ];
    }

    /**
     * Fetch And Format
     *
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'coapre_id');
        if (count($rows) == 0) {
            return [];
        }
        $categoryIds = [];
        array_map(function ($val) use (&$categoryIds) {
            $categoryIds = array_merge($categoryIds, [$val['coapre_cate_id'], $val['coapre_subcate_id']]);
        }, $rows);
        $categoryIds = array_unique($categoryIds);
        $categories = CourseSearch::getCategoryNames($this->langId, array_unique($categoryIds));
        foreach ($rows as $key => $row) {
            $row['coapre_cate_name'] = array_key_exists($row['coapre_cate_id'], $categories) ? $categories[$row['coapre_cate_id']] : '';
            $row['coapre_subcate_name'] = array_key_exists($row['coapre_subcate_id'], $categories) ? $categories[$row['coapre_subcate_id']] : '';
            $row['coapre_created'] = MyDate::formatDate($row['coapre_created']);
            $rows[$key] = $row;
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
            'course.course_user_id = u.user_id',
            'u'
        );
    }
}
