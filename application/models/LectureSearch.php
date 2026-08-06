<?php

/**
 * This class is used to handle lectures
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class LectureSearch extends W3mentorsSearch
{

    /**
     * Initialize Lecture Search
     *
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId = 0, int $userId = 0, int $userType = 0)
    {
        $this->table = Lecture::DB_TBL;
        $this->alias = 'lecture';
        parent::__construct($langId, $userId, $userType);
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
            'lecture.lecture_id' => 'lecture_id',
            'lecture.lecture_order' => 'lecture_order',
            'lecture.lecture_section_id' => 'lecture_section_id',
            'lecture.lecture_course_id' => 'lecture_course_id',
            'lecture.lecture_is_trial' => 'lecture_is_trial',
            'lecture.lecture_duration' => 'lecture_duration',
            'lecture.lecture_title' => 'lecture_title',
            'lecture.lecture_details' => 'lecture_details',
            'lecture.lecture_archived' => 'lecture_archived',
            'lecture.lecture_updated' => 'lecture_updated',
        ];
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (isset($post['section_id']) && $post['section_id'] > 0) {
            $this->addCondition('lecture.lecture_section_id', '=', $post['section_id']);
        }

        if (isset($post['lecture_id']) && $post['lecture_id'] > 0) {
            $this->addCondition('lecture.lecture_id', '=', $post['lecture_id']);
        }
    }

    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('lecture.lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
    }

    /**
     * Fetch And Format
     *
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'lecture_id');
        if (count($rows) == 0) {
            return [];
        }
        return $rows;
    }

    /**
     * Function to get lecture data by id
     *
     * @param int $lectureId
     * @param array   $flds
     * @return array
     */
    public function getById(int $lectureId, array $flds = [])
    {
        $this->applyPrimaryConditions();
        $this->applySearchConditions(['lecture_id' => $lectureId]);
        if (count($flds) > 0) {
            $this->addMultipleFields($flds);
        } else {
            $this->addSearchListingFields();
        }
        return FatApp::getDb()->fetch($this->getResultSet());
    }

}
