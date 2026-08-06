<?php

class CourseEditRequestSearch extends W3mentorsSearch
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
        $this->table = Course::DB_TBL_EDIT_REQUEST;
        $this->alias = 'coedre';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(Course::DB_TBL, 'INNER JOIN', 'coedre.coedre_course_id = course.course_id', 'course');
        $this->joinTable(Course::DB_TBL_LANG, 'INNER JOIN', 'crsdetails.course_id = course.course_id', 'crsdetails');
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (isset($post['coedre_id'])) {
            $this->addCondition('coedre_id', '=', $post['coedre_id']);
        }
        if (!empty($post['keyword'])) {
            $this->addCondition('crsdetails.course_title', 'LIKE', '%' .  trim($post['keyword']) . '%');
        }
        if (isset($post['teacher_id']) && $post['teacher_id'] > 0) {
            $this->addCondition('course.course_user_id', '=', $post['teacher_id']);
        } elseif (!empty($post['teacher'])) {
            $fullName = 'mysql_func_CONCAT(u.user_first_name, " ", u.user_last_name)';
            $this->addCondition($fullName, 'LIKE', '%' . trim($post['teacher']) . '%', 'AND', true);
        }
        if (isset($post['coedre_status']) && $post['coedre_status'] != '') {
            $this->addCondition('coedre_status', '=', $post['coedre_status']);
        }
        if (isset($post['start_date']) && !empty($post['start_date'])) {
            $this->addCondition('coedre_created', ">=", MyDate::formatToSystemTimezone($post['start_date'] . ' 00:00:00'), 'AND', true);
        }
        if (isset($post['end_date']) && !empty($post['end_date'])) {
            $this->addCondition('coedre_created', "<=", MyDate::formatToSystemTimezone($post['end_date'] . ' 23:59:59'), 'AND', true);
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
            'coedre.coedre_id' => 'coedre_id',
            'coedre.coedre_status' => 'coedre_status',
            'coedre.coedre_reason' => 'coedre_reason',
            'coedre.coedre_created' => 'coedre_created',
            'coedre.coedre_course_id' => 'coedre_course_id',
            'crsdetails.course_title' => 'course_title',
            'coedre.coedre_updated' => 'coedre_updated',
            'coedre.coedre_duration' => 'coedre_duration',
            'u.user_id' => 'user_id',
            'u.user_first_name' => 'user_first_name',
            'u.user_last_name' => 'user_last_name',
            'u.user_email' => 'user_email',
            'u.user_gender' => 'user_gender',
            'u.user_timezone' => 'user_timezone',
        ];
    }

    /**
     * Fetch And Format
     *
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'coedre_id');
        if (count($rows) == 0) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['coedre_created'] = MyDate::formatDate($row['coedre_created']);
            $row['coedre_expired'] = '';
            if ($row['coedre_status'] == Course::EDIT_REQUEST_APPROVED) {
                $row['coedre_expired'] = MyDate::formatDate(date('Y-m-d H:i:s', strtotime($row['coedre_updated'] . ' + ' . $row['coedre_duration'] . ' days')));
            }
            $row['coedre_updated'] = MyDate::formatDate($row['coedre_updated']);
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
