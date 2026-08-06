<?php

/**
 * This class is used to handle Course sections
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class SectionSearch extends W3mentorsSearch
{

    /**
     * Initialize Section Search
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = Section::DB_TBL;
        $this->alias = 'section';
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
            'section.section_id' => 'section_id',
            'section.section_order' => 'section_order',
            'section.section_lectures' => 'section_lectures',
            'section.section_duration' => 'section_duration',
            'section.section_title' => 'section_title',
            'section.section_details' => 'section_details',
            'section.section_updated' => 'section_updated',
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
        if (isset($post['course_id']) && $post['course_id'] > 0) {
            $this->addCondition('section.section_course_id', '=', $post['course_id']);
        }
        if (isset($post['section_id']) && $post['section_id'] > 0) {
            $this->addCondition('section.section_id', '=', $post['section_id']);
        }
    }

    /**
     * Apply Primary Conditions
     *
     * @param int $courseId
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('section.section_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
    }

    /**
     * Fetch And Format
     *
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'section_id');
        if (count($rows) == 0) {
            return [];
        }
        $sectionIds = array_keys($rows);
        /* get lectures list */
        $lectureIds = $lectures = $resources = [];
        if (!empty($sectionIds)) {
            $srch = new LectureSearch();
            $srch->applyPrimaryConditions();
            $srch->addSearchListingFields();
            $srch->addFld('0 AS total_resources');
            $srch->addDirectCondition('lecture_section_id IN (' . implode(',', $sectionIds) . ')');
            $srch->addOrder('lecture_order', 'ASC');
            $srch->doNotCalculateRecords();
            $lectures = $srch->fetchAndFormat();
            $lectureIds = array_keys($lectures);
        }
        /* get lecture resources */
        if (!empty($lectureIds)) {
            $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
            $srch->joinTable(Resource::DB_TBL, 'INNER JOIN', 'resrc.resrc_id = lecsrc.lecsrc_resrc_id', 'resrc');
            $srch->addMultipleFields(['resrc_name', 'resrc_size', 'lecsrc_id', 'lecsrc_lecture_id']);
            $srch->addCondition('resrc.resrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
            $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addDirectCondition('lecsrc.lecsrc_lecture_id IN (' . implode(',', $lectureIds) . ')');
            $srch->addOrder('lecsrc_id', 'ASC');
            $resources = FatApp::getDb()->fetchAll($srch->getResultSet());
        }
        if (count($resources) > 0) {
            foreach ($resources as $resource) {
                $lectures[$resource['lecsrc_lecture_id']]['resources'][] = $resource;
                $lectures[$resource['lecsrc_lecture_id']]['total_resources'] += 1;
            }
        }
        foreach ($lectures as $lecture) {
            $rows[$lecture['lecture_section_id']]['lectures'][] = $lecture;
            if (isset($rows[$lecture['lecture_section_id']]['total_resources'])) {
                $rows[$lecture['lecture_section_id']]['total_resources'] += $lecture['total_resources'];
            } else {
                $rows[$lecture['lecture_section_id']]['total_resources'] = $lecture['total_resources'];
            }
        }
        return $rows;
    }

}
