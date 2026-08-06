<?php

/**
 * This class is used to handle Course sections
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class Section extends MyAppModel
{

    const DB_TBL = 'tbl_sections';
    const DB_TBL_PREFIX = 'section_';

    private $userId;

    /**
     * Initialize Section
     *
     * @param int $id
     */
    public function __construct(int $id = 0, $userId = 0)
    {
        parent::__construct(static::DB_TBL, 'section_id', $id);
        $this->userId = $userId;
    }

    /**
     * Setup section data
     *
     * @param array $data
     * @return bool
     */
    public function setup(array $data)
    {
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        $this->assignValues([
            'section_course_id' => $data['section_course_id'],
            'section_title' => $data['section_title'],
            'section_details' => $data['section_details'],
        ]);
        if ($data['section_id'] < 1) {
            $this->setFldValue('section_created', date('Y-m-d H:i:s'));
        }
        if (!$this->save($data)) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        /* reset section order */
        if (!$this->resetOrder($data['section_course_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->setCourseSectionCount($data['section_course_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->setUpdatedDate($data['section_course_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Function to remove Section
     *
     * @return bool
     */
    public function delete()
    {
        $sectionId = $this->getMainTableRecordId();
        if (!$courseId = Section::getAttributesById($sectionId, 'section_course_id')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if (Course::getAttributesById($courseId, 'course_user_id') != $this->userId) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }
        $course = new Course($courseId, $this->userId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        $this->setFldValue('section_deleted', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        /* delete associated lectures data */
        if (!$this->deleteAssociatedData($sectionId)) {
            $db->rollbackTransaction();
            return false;
        }
        /* reset section order */
        if (!$this->resetOrder($courseId)) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->setCourseSectionCount($courseId)) {
            $db->rollbackTransaction();
            return false;
        }
        $lecture = new Lecture();
        if (!$lecture->setLectureCount($sectionId)) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to removal all associated lecture and resources
     *
     * @param int $sectionId
     * @return bool
     */
    public function deleteAssociatedData(int $sectionId)
    {
        /* get lectures */
        $srch = new LectureSearch(0);
        $srch->addMultipleFields(['lecture_course_id', 'lecture_id']);
        $srch->applySearchConditions(['section_id' => $sectionId]);
        $srch->applyPrimaryConditions();
        $lectures = $srch->fetchAndFormat();

        /* return if no lecture avaiable */
        if (empty($lectures)) {
            return true;
        }
        $db = FatApp::getDb();
        if (!$db->updateFromArray(Lecture::DB_TBL, ['lecture_deleted' => date('Y-m-d H:i:s')], ['smt' => 'lecture_section_id = ?', 'vals' => [$sectionId]])) {
            $this->error = $db->getError();
            return false;
        }

        $lectureIds = array_keys($lectures);
        $lecture = new Lecture();
        if (!$lecture->bulkRemoveResources($lectureIds)) {
            $this->error = $lecture->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to reset course sections display order
     *
     * @param int $courseId
     * @return bool
     */
    public function resetOrder(int $courseId)
    {
        /* reset section order */
        $srch = new SearchBase(static::DB_TBL);
        $srch->addFld('section_id');
        $srch->addCondition('section_course_id', '=', $courseId);
        $srch->addCondition('section_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addOrder('section_order = 0', 'ASC');
        $srch->addOrder('section_order', 'ASC');
        $sectionIds = FatApp::getDb()->fetchAll($srch->getResultSet(), 'section_id');
        $sectionIds = array_keys($sectionIds);
        array_unshift($sectionIds, "");
        unset($sectionIds[0]);
        /* return if no record avaiable for ordering */
        if (count($sectionIds) < 1) {
            return true;
        }
        if (!$this->updateOrder($sectionIds)) {
            $this->error = $this->getError();
            return false;
        }
        $lecture = new Lecture();
        if (!$lecture->resetOrder($courseId)) {
            $this->error = $lecture->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to set sections count in courses
     *
     * @param int $courseId
     * @return bool
     */
    private function setCourseSectionCount(int $courseId)
    {
        /* get count */
        $srch = new SearchBase(static::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('COUNT(section_id) AS course_sections');
        $srch->addCondition('section_course_id', '=', $courseId);
        $srch->addCondition('section_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        /* update section count */
        $course = new Course($courseId);
        $course->assignValues($row);
        if (!$course->save()) {
            $this->error = $course->getError();
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
        $srch = new SearchBase(Lecture::DB_TBL);
        $srch->addCondition('lecture_section_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('IFNULL(SUM(lecture_duration), 0) AS section_duration');
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


    public function setUpdatedDate($courseId)
    {
        $courseStatus =  Course::getAttributesById($courseId, 'course_status');
        if ($courseStatus != Course::PUBLISHED) {
            return true;
        }

        /* update section date */
        $this->assignValues(['section_updated' => date('Y-m-d H:i:s')]);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        /* update course date */
        $course = new Course($courseId);
        if (!$course->setUpdatedDate()) {
            $this->error = $course->getError();
            return false;
        }
        return true;
    }
}
