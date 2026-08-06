<?php

/**
 * This class is used to handle Course Progress
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseProgress extends MyAppModel
{

    const DB_TBL = 'tbl_course_progresses';
    const DB_TBL_PREFIX = 'crspro_';

    const PENDING = 1;
    const IN_PROGRESS = 2;
    const COMPLETED = 3;
    const CANCELLED = 4;

    /**
     * Initialize Course
     *
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'crspro_id', $id);
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
     * Begin Course
     *
     * @param int $ordcrsId
     * @return bool
     */
    public function setup(int $ordcrsId)
    {
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        $this->assignValues([
            'crspro_ordcrs_id' => $ordcrsId,
            'crspro_status' => static::PENDING,
        ]);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Set currect lecture
     *
     * @param int $lectureId
     * @return bool
     */
    public function setCurrentLecture(int $lectureId)
    {
        $this->setFldValue('crspro_lecture_id', $lectureId);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Mark/Unmark a lecture as completed
     *
     * @param int $lectureId
     * @param int $markCovered
     * @return bool
     */
    public function setCompletedLectures(int $lectureId, int $markCovered = AppConstant::YES)
    {
        $lectures = static::getAttributesById($this->getMainTableRecordId(), 'crspro_covered');
        $lectures = ($lectures) ? json_decode($lectures) : [];

        /* get lectures excluding deleted/archived */
        if (!empty($lectures)) {
            $srch = new LectureSearch();
            $srch->addCondition('lecture_id', 'IN', array_merge($lectures, [$lectureId]));
            $srch->addFld('lecture_id');
            $srch->applyPrimaryConditions();
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $lectures = FatApp::getDb()->fetchAll($srch->getResultSet(), 'lecture_id');
            if (!isset($lectures[$lectureId])) {
                $this->error = Label::getLabel('LBL_LECTURE_DOES_NOT_EXIST');
                return false;
            }
            $lectures = array_keys($lectures);
        }

        if ($markCovered == AppConstant::YES) {
            $lectures = array_unique(array_merge($lectures, [$lectureId]));
        } else {
            $key = array_search($lectureId, $lectures);
            if ($key !== false) {
                unset($lectures[$key]);
                $lectures = array_values($lectures);
            }
        }
        $this->setFldValue('crspro_covered', json_encode($lectures));
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Get & Format Section's lecture completed stats
     *
     * @param array $sections
     * @return array
     */
    public function getLectureStats(array $sections)
    {
        $lectures = static::getAttributesById($this->getMainTableRecordId(), 'crspro_covered');
        
        $lectures = ($lectures) ? json_decode($lectures) : [];
        $stats = [];
        foreach ($sections as $section) {
            if (!isset($section['lectures']) || count($section['lectures']) < 1) {
                continue;
            }
            $completed = [];
            foreach ($section['lectures'] as $lecture) {
                if (in_array($lecture['lecture_id'], $lectures)) {
                    $completed[] = $lecture['lecture_id'];
                }
            }
            $stats[$section['section_id']] = $completed;
        }
        return $stats;
    }

    /**
     * Update Course progress
     *
     * @param int $courseId
     * @return bool
     */
    public function updateProgress(int $courseId)
    {
        $progress = $this->getAttributesById(
            $this->getMainTableRecordId(),
            ['crspro_covered', 'crspro_completed', 'crspro_ordcrs_id']
        );
        $course = (new Course($courseId, 0, 0, MyUtility::getSiteLangId()))->get();
        $lecturesCovered = ($progress['crspro_covered']) ? count(json_decode($progress['crspro_covered'])) : 0;
        $percent = round(($lecturesCovered * 100) / $course['course_lectures'], 2);
        $this->setFldValue('crspro_progress', $percent);

        $db = FatApp::getDb();
        $db->startTransaction();
        /* completed date will be updated only once(first time completed) */
        if (!$progress['crspro_completed'] && $percent == 100.00) {
            $this->setFldValue('crspro_completed', date('Y-m-d H:i:s'));
            $this->setFldValue('crspro_status', CourseProgress::COMPLETED);

            $ordcrs = new OrderCourse($progress['crspro_ordcrs_id']);
            $ordcrs->setFldValue('ordcrs_status', OrderCourse::COMPLETED);
            if (!$ordcrs->save()) {
                $this->error = $ordcrs->getError();
            }
        }
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Get next & previous lecture id
     *
     * @param array   $data
     * @param int $next
     * @return integer
     */
    public function getLecture($data, int $next = AppConstant::YES)
    {
        $lectureId = 0;
        if ($data['crspro_lecture_id'] < 1 && $data['crspro_progress'] > 0) {
            return $lectureId;
        }
        $lectureOrder = Lecture::getAttributesById($data['crspro_lecture_id'], 'lecture_order');
        $lectureOrder = empty($lectureOrder) ? 0 : $lectureOrder;
        /* get next lecture */
        $srch = new SearchBase(Lecture::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('lecture_id');
        $srch->addCondition('lecture_course_id', '=', $data['crspro_course_id']);
        if ($next == AppConstant::YES) {
            $srch->addCondition('lecture_order', '>', $lectureOrder);
            $srch->addOrder('lecture_order', 'ASC');
        } else {
            $srch->addCondition('lecture_order', '<', $lectureOrder);
            $srch->addOrder('lecture_order', 'DESC');
        }
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        if ($lecture = FatApp::getDb()->fetch($srch->getResultSet())) {
            $lectureId = $lecture['lecture_id'];
        }
        return $lectureId;
    }

    /**
     * Reset course progress to retake
     *
     * @return bool
     */
    public function retake()
    {
        $this->assignValues([
            'crspro_lecture_id' => 0,
            'crspro_progress' => 0,
            'crspro_covered' => NULL,
        ]);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    public function getNextPrevLectures()
    {
        $srch = new SearchBase(CourseProgress::DB_TBL);
        $srch->addCondition('crspro_id', '=', $this->getMainTableRecordId());
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs_id = crspro_ordcrs_id');
        $srch->addMultipleFields(['crspro_lecture_id', 'ordcrs_course_id AS crspro_course_id', 'crspro_progress']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (!$data = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            return false;
        }
        /* get previous and next lectures */
        return [
            'next' => $this->getLecture($data),
            'previous' => $this->getLecture($data, AppConstant::NO),
        ];
    }

    /**
     * Validate Lecture
     * 
     * @param int $lectureId
     * @return bool
     */
    public function isLectureValid(int $lectureId): bool
    {
        if ($lectureId < 1) {
            return true;
        }
        $srch = new SearchBase(CourseProgress::DB_TBL, 'crspro');
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs_id = crspro_ordcrs_id');
        $srch->joinTable(Lecture::DB_TBL, 'INNER JOIN', 'lecture_course_id = ordcrs_course_id ');
        $srch->addCondition('crspro_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('lecture_id', '=', $lectureId);
        $srch->addFld('crspro_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (!FatApp::getDb()->fetch($srch->getResultSet())) {
            return false;
        }
        return true;
    }

    /**
     * Update Covered Lectures On Delete
     *
     * @param int $courseId
     * @param int $lectureId
     * @return bool
     */
    public static function updateCoveredLecturesOnDelete(int $courseId, int $lectureId)
    {
        $srch = new SearchBase(CourseProgress::DB_TBL);
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs_id = crspro_ordcrs_id');
        $srch->addCondition('ordcrs_course_id', '=', $courseId);
        $srch->addMultipleFields(["IFNULL(crspro_covered, '') as crspro_covered", 'crspro_id']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $lectures = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (!empty($lectures)) {
            foreach ($lectures as $covered) {
                $lectureIds = !empty($covered['crspro_covered']) ? json_decode($covered['crspro_covered']) : [];
                $key = array_search($lectureId, $lectureIds);
                if ($key != '') {
                    unset($lectureIds[$key]);
                    FatApp::getDb()->updateFromArray(CourseProgress::DB_TBL, ['crspro_covered' => json_encode(array_values($lectureIds))], ['smt'=>'crspro_id = ?', 'vals' => [$covered['crspro_id']]]);
                }
            }
        }
    }

    /**
     * Update Course progress
     *
     * @param int $courseId
     * @return bool
     */
    public static function updateProgressInBulk(int $courseId)
    {
        $course = (new Course($courseId, 0, 0, MyUtility::getSiteLangId()))->get();
        $totalLectures = $course['course_lectures'];

        $query = FatApp::getDb()->query("UPDATE tbl_course_progresses INNER JOIN tbl_order_courses ON tbl_order_courses.ordcrs_id = tbl_course_progresses.crspro_ordcrs_id SET crspro_progress = ROUND(JSON_LENGTH(tbl_course_progresses.crspro_covered) * 100/$totalLectures, 2) WHERE tbl_order_courses.ordcrs_course_id = $courseId AND tbl_course_progresses.crspro_covered IS NOT NULL");
        if (!$query) {
            return false;
        }

        $query = FatApp::getDb()->query("UPDATE tbl_course_progresses INNER JOIN tbl_order_courses ON tbl_order_courses.ordcrs_id = tbl_course_progresses.crspro_ordcrs_id SET tbl_course_progresses.crspro_status = " . CourseProgress::COMPLETED . " , tbl_course_progresses.crspro_completed = '" . date('Y-m-d H:i:s') . "'  WHERE tbl_order_courses.ordcrs_course_id = $courseId AND tbl_course_progresses.crspro_progress >= 100.00");
        if (!$query) {
            return false;
        }

        $query = FatApp::getDb()->query("UPDATE tbl_order_courses INNER JOIN tbl_course_progresses ON tbl_order_courses.ordcrs_id = tbl_course_progresses.crspro_ordcrs_id SET tbl_order_courses.ordcrs_status = " . OrderCourse::COMPLETED . " WHERE tbl_order_courses.ordcrs_course_id = $courseId AND tbl_course_progresses.crspro_status = " . CourseProgress::COMPLETED);
        if (!$query) {
            return false;
        }
        return true;
    }
}
