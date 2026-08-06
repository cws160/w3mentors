<?php

/**
 * This class is used to handle lectures
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class Lecture extends MyAppModel
{

    const DB_TBL = 'tbl_lectures';
    const DB_TBL_PREFIX = 'lecture_';
    const DB_TBL_LECTURE_RESOURCE = 'tbl_lectures_resources';
    const DB_TBL_LECTURE_RESOURCE_PREFIX = 'lecsrc_';
    const TYPE_RESOURCE_EXTERNAL_URL = 1;
    const TYPE_RESOURCE_UPLOAD_FILE = 2;
    const TYPE_RESOURCE_LIBRARY = 3;

    private $userId;

    /**
     * Initialize Lecture
     *
     * @param int $id
     */
    public function __construct(int $id = 0, $userId = 0)
    {
        parent::__construct(static::DB_TBL, 'lecture_id', $id);
        $this->userId = $userId;
    }

    /**
     * Get Resource Types List
     *
     * @param int $key
     * @return string|array
     */
    public static function getTypes(int $key = null)
    {
        $arr = [
            static::TYPE_RESOURCE_EXTERNAL_URL => Label::getLabel('LBL_EXTERNAL_URL'),
            static::TYPE_RESOURCE_UPLOAD_FILE => Label::getLabel('LBL_UPLOAD_FILE'),
            static::TYPE_RESOURCE_LIBRARY => Label::getLabel('LBL_RESOURCE_LIBRARY')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Setup data
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
            'lecture_section_id' => $data['lecture_section_id'],
            'lecture_course_id' => $data['lecture_course_id'],
            'lecture_is_trial' => $data['lecture_is_trial'],
            'lecture_title' => $data['lecture_title'],
            'lecture_details' => $data['lecture_details'],
        ]);
        if ($data['lecture_id'] < 1) {
            $this->setFldValue('lecture_created', date('Y-m-d H:i:s'));
        }
        if (!$this->save($data)) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }


        /* update lecture duration */
        if (!$this->setDuration()) {
            return false;
        }
        /* update section duration */
        $section = new Section($data['lecture_section_id']);
        if (!$section->setDuration()) {
            $db->rollbackTransaction();
            $this->error = $section->getError();
            return false;
        }
        /* update course duration */
        $course = new Course($data['lecture_course_id']);
        if (!$course->setDuration()) {
            $this->error = $course->getError();
            return false;
        }
        /* reset section order */
        if (!$this->resetOrder($data['lecture_course_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->setLectureCount($data['lecture_section_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        /*set update date */
        if (!$this->setUpdatedDate($data['lecture_course_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Function to setup resources
     *
     * @param int $lecSrcId
     * @param int $type
     * @param int $srcId
     * @param int $courseId
     * @return bool
     */
    public function setupResources(int $lecSrcId, int $type, int $srcId, int $courseId)
    {
        /* bind added resource with lecture */
        $obj = new TableRecord(static::DB_TBL_LECTURE_RESOURCE);
        $obj->assignValues([
            'lecsrc_id' => $lecSrcId,
            'lecsrc_type' => $type,
            'lecsrc_resrc_id' => $srcId,
            'lecsrc_lecture_id' => $this->getMainTableRecordId(),
            'lecsrc_course_id' => $courseId,
            'lecsrc_created' => date('Y-m-d H:i:s')
        ]);
        if (!$obj->addNew()) {
            $this->error = $obj->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to remove course
     *
     * @return bool
     */
    public function delete()
    {
        $lectureId = $this->getMainTableRecordId();
        if (!$data = static::getAttributesById($lectureId, ['lecture_section_id', 'lecture_course_id'])) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $course =  Course::getAttributesById($data['lecture_course_id'], ['course_user_id', 'course_status']);
        if ($course['course_user_id'] != $this->userId) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }
        $courseObj = new Course($data['lecture_course_id'], $this->userId);
        if (!$courseObj->canEditCourse()) {
            FatUtility::dieJsonError($courseObj->getError());
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        $this->setFldValue('lecture_deleted', date('Y-m-d H:i:s'));
        if ($course['course_status'] == Course::PUBLISHED) {
            $this->setFldValue('lecture_archived', AppConstant::YES);
            $this->setFldValue('lecture_updated', date('Y-m-d H:i:s'));
        } 
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        if ($course['course_status'] != Course::PUBLISHED) {
            if (!$this->bulkRemoveResources([$lectureId])) {
                $db->rollbackTransaction();
                return false;
            }
        }

        /* update lecture duration */
        if (!$this->setDuration()) {
            $db->rollbackTransaction();
            return false;
        }
        /* update section duration */
        $section = new Section($data['lecture_section_id']);
        if (!$section->setDuration()) {
            $db->rollbackTransaction();
            $this->error = $section->getError();
            return false;
        }
        /* update course duration */
        $course = new Course($data['lecture_course_id']);
        if (!$course->setDuration()) {
            $db->rollbackTransaction();
            $this->error = $course->getError();
            return false;
        }
        if (!$this->setUpdatedDate($data['lecture_course_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        /* reset lectures order */
        if (!$this->resetOrder($data['lecture_course_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->setLectureCount($data['lecture_section_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public function bulkRemoveResources(array $lectureIds)
    {
        $db = FatApp::getDb();
        $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
        $srch->addMultipleFields(['lecsrc_id', 'lecsrc_link']);
        $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('lecsrc.lecsrc_lecture_id', 'IN', $lectureIds);
        $resources = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        if (empty($resources)) {
            return true;
        }
        /* delete binded resources */
        $smt = trim(str_repeat('?,', count($lectureIds)), ',');
        if (
            !$db->updateFromArray(
                Lecture::DB_TBL_LECTURE_RESOURCE,
                ['lecsrc_deleted' => date('Y-m-d H:i:s')],
                ['smt' => 'lecsrc_lecture_id IN (' . $smt . ')', 'vals' => $lectureIds]
            )
        ) {
            $this->error = $db->getError();
            return false;
        }
        $video = new VideoStreamer();
        if (!$video->bulkRemove($resources)) {
            $this->error = $video->getError();
            return false;
        }
        return true;
    }

    /**
     * Reset Order
     *
     * @param int $courseId
     * @return bool
     */
    public function resetOrder(int $courseId)
    {
        /* reset section order */
        $srch = new SearchBase(static::DB_TBL);
        $srch->joinTable(Section::DB_TBL, 'INNER JOIN', 'section_id = lecture_section_id');
        $srch->addFld('lecture_id');
        $srch->addCondition('lecture_course_id', '=', $courseId);
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('section_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addOrder('section_order', 'ASC');
        $srch->addOrder('lecture_order = 0', 'ASC');
        $srch->addOrder('lecture_order', 'ASC');
        $lectureIds = FatApp::getDb()->fetchAll($srch->getResultSet(), 'lecture_id');
        $lectureIds = array_keys($lectureIds);
        array_unshift($lectureIds, "");
        unset($lectureIds[0]);
        /* return if no record avaiable for ordering */
        if (count($lectureIds) < 1) {
            return true;
        }
        if (!$this->updateOrder($lectureIds)) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to get lecture media by id and type
     *
     * @param int $type
     * @return array
     */
    public function getMedia(int $type)
    {
        $srch = new SearchBase(static::DB_TBL_LECTURE_RESOURCE);
        $srch->addMultipleFields(['lecsrc_id', 'lecsrc_link', 'lecsrc_lecture_id']);
        $srch->addCondition('lecsrc_lecture_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('lecsrc_type', '=', $type);
        $srch->addMultipleFields([
            'lecsrc_id',
            'lecsrc_link',
            'lecsrc_lecture_id',
            'lecsrc_link_name',
            'lecsrc_duration'
        ]);
        $srch->doNotCalculateRecords();
        if ($type == Lecture::TYPE_RESOURCE_EXTERNAL_URL) {
            $srch->setPageSize(1);
            return FatApp::getDb()->fetch($srch->getResultSet());
        } else {
            $srch->doNotLimitRecords();
            return FatApp::getDb()->fetchAll($srch->getResultSet());
        }
    }

    /**
     * Get lecture resources
     *
     * @return array|bool
     */
    public function getResources()
    {
        $srch = new SearchBase(static::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
        $srch->addCondition('lecsrc.lecsrc_lecture_id', '=', $this->mainTableRecordId);
        $srch->joinTable(Resource::DB_TBL, 'INNER JOIN', 'resrc.resrc_id = lecsrc.lecsrc_resrc_id', 'resrc');
        $srch->addMultipleFields([
            'resrc_name',
            'resrc_size',
            'resrc_type',
            'lecsrc_id',
            'lecsrc_lecture_id',
            'lecsrc_created',
            'resrc_id'
        ]);
        $srch->addCondition('resrc.resrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addOrder('lecsrc_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get lecture resources array
     *
     * @return bool
     */
    public static function validateResource($resrcId, $courseId)
    {
        $srch = new SearchBase(static::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
        $srch->addCondition('lecsrc.lecsrc_course_id', '=', $courseId);
        $srch->addCondition('lecsrc.lecsrc_id', '=', $resrcId);
        $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->setPageSize(1);
        $srch->getResultSet();
        return $srch->recordCount() > 0 ? 1 : 0;
    }

    /**
     * Function to set lectures count in sections
     *
     * @param int $sectionId
     * @return bool
     */
    public function setLectureCount(int $sectionId)
    {
        /* get count */
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('lecture_section_id', '=', $sectionId);
        $srch->addFld('COUNT(lecture_id) AS section_lectures');
        $srch->addFld('lecture_course_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        /* update lectures count */
        $section = new Section($sectionId);
        $section->setFldValue('section_lectures', $row['section_lectures']);
        if (!$section->save()) {
            $this->error = $section->getError();
            return false;
        }
        /* update course lectures */
        if (!$this->setCourseLectureCount($sectionId)) {
            $this->error = $section->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to set course lectures count in sections
     *
     * @param int $sectionId
     * @return bool
     */
    private function setCourseLectureCount(int $sectionId)
    {
        /* get course id to update course lectures count */
        $courseId = Section::getAttributesById($sectionId, 'section_course_id');
        $srch = new SearchBase(static::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('COUNT(lecture_id) AS course_lectures');
        $srch->addCondition('lecture_course_id', '=', $courseId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $course = new Course($courseId);
        $course->assignValues($row);
        if (!$course->save()) {
            $this->error = $course->getError();
            return false;
        }
        return true;
    }

    /**
     * Setup External Url Media
     *
     * @param array $post
     * @return bool
     */
    public function setupMedia(array $post)
    {
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        $video = new VideoStreamer();

        /* Upload Video */
        if (!$video->upload($post['lecsrc_link'], Afile::TYPE_COURSE_LECTURE_VIDEO)) {
            FatUtility::dieJsonError($video->getError());
        }

        /* remove previous Lecture Content if exists */
        if ($post['lecsrc_id'] > 0) {
            if (!$this->removeMedia($post['lecsrc_id'])) {
                return false;
            }
        }
        $videoId = $video->getVideoId();

        $data = [
            'lecsrc_id' => $post['lecsrc_id'],
            'lecsrc_type' => static::TYPE_RESOURCE_EXTERNAL_URL,
            'lecsrc_link' => $videoId,
            'lecsrc_lecture_id' => $this->getMainTableRecordId(),
            'lecsrc_course_id' => $post['lecsrc_course_id'],
        ];
        if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_MUX) {
            $videoData = $video->getDetails($videoId);
            $data['lecsrc_duration'] = $videoData['data']['duration'] ?? 0;
        }
        if ($post['lecsrc_id'] < 1) {
            $data['lecsrc_created'] = date('Y-m-d H:i:s');
        } else {
            $data['lecsrc_updated'] = date('Y-m-d H:i:s');
        }

        $fileName = $post['lecsrc_link']['name'];
        if ($fileName) {
            $data['lecsrc_link_name'] = $fileName;
        }
        /* save external url media */
        if (!$db->insertFromArray(static::DB_TBL_LECTURE_RESOURCE, $data, true, [], $data)) {
            $this->error = $db->getError();
            return false;
        }

        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove lecture video
     *
     * @param int $lecResourceId
     * @param string $videoId
     * @return bool
     */
    public function removeMedia(int $lecResourceId)
    {
        $media = $this->getMediaById($lecResourceId);
        if (empty($media)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->deleteRecords(Lecture::DB_TBL_LECTURE_RESOURCE, ['smt' => 'lecsrc_id = ?', 'vals' => [$lecResourceId]])) {
            $this->error = $db->getError();
            return false;
        }

        /* remove video from Video Tool */
        $video = new VideoStreamer();
        if (!$video->remove($media['lecsrc_link'])) {
            $this->error = Label::getLabel('LBL_ERROR_REMOVING_FILE');
            return false;
        }
        return true;
    }

    /**
     * Set lecture video duration
     *
     * @return bool
     */
    public function setDuration()
    {
        $srch = new SearchBase(static::DB_TBL_LECTURE_RESOURCE);
        $srch->addCondition('lecsrc_lecture_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('lecsrc_type', '=', static::TYPE_RESOURCE_EXTERNAL_URL);
        $srch->addCondition('lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('SUM(lecsrc_duration) AS lecture_duration');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $duration = 0;
        if ($row) {
            $duration = $row['lecture_duration'];
        }

        /* get lecture content duration */
        $content = static::getAttributesById($this->getMainTableRecordId(), 'lecture_details');
        $content = strip_tags($content);
        $content = count(explode(' ', $content));
        $row['lecture_duration'] = (ceil($content / 100) * 60) + $duration;

        /* update lecture duration */
        $this->assignValues($row);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Get lecture videos
     *
     * @param array $lectureIds
     * @return array
     */
    public static function getVideos(array $lectureIds)
    {
        $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
        $srch->addDirectCondition('lecsrc.lecsrc_lecture_id IN (' . implode(',', $lectureIds) . ')');
        $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('lecsrc.lecsrc_type', '=', Lecture::TYPE_RESOURCE_EXTERNAL_URL);
        $srch->addMultipleFields(['lecsrc_id', 'lecsrc_lecture_id']);
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Extract lectures ids and free trials data from sections array
     *
     * @param array $sections
     * @return array
     */
    public static function getIds(array $sections)
    {
        $lectureIds = [];
        foreach ($sections as $val) {
            if (isset($val['lectures']) && count($val['lectures']) > 0) {
                foreach ($val['lectures'] as $lecture) {
                    if ($lecture['lecture_is_trial'] == AppConstant::NO) {
                        continue;
                    }
                    $lectureIds[] = $lecture['lecture_id'];
                }
            }
        }
        return $lectureIds;
    }

    /**
     * Get lecture by id
     *
     * @return array
     */
    public function getByCourseId(int $courseId)
    {
        $srch = new SearchBase(static::DB_TBL, 'lecture');
        $srch->addCondition('lecture_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('lecture_course_id', '=', $courseId);
        $srch->addMultipleFields([
            'lecture.lecture_id',
            'lecture.lecture_course_id',
            'lecture.lecture_section_id',
        ]);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function getMediaById(int $id)
    {
        $srch = new SearchBase(static::DB_TBL_LECTURE_RESOURCE);
        $srch->addCondition('lecsrc_id', '=', $id);
        $srch->addDirectCondition('lecsrc_deleted IS NULL');
        $srch->addMultipleFields(['lecsrc_id', 'lecsrc_link', 'lecsrc_lecture_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function getArchivedByCourseId($courseId)
    {
        $srch = new LectureSearch();
        $srch->joinTable(Section::DB_TBL, 'INNER JOIN', 'lecture.lecture_section_id = section.section_id', 'section');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'lecture.lecture_course_id = course.course_id', 'course');
        $srch->addCondition('lecture.lecture_archived', '=', AppConstant::YES);
        $srch->addCondition('course.course_id', '=', $courseId);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['section.section_title', 'section.section_order', 'section.section_id']);
        $srch->addSearchListingFields();
        $srch->addOrder('section.section_order', 'ASC');
        $srch->addOrder('lecture.lecture_order', 'ASC');
        return $srch->fetchAndFormat();
    }


    public function setUpdatedDate($courseId)
    {
        /* update lecture date */
        $lectureId = $this->getMainTableRecordId();
        $courseStatus =  Course::getAttributesById($courseId, 'course_status');
        if ($courseStatus != Course::PUBLISHED) {
            return true;
        }

        $this->assignValues(['lecture_updated' => date('Y-m-d H:i:s')]);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }

        /* update section duration */
        $sectionId = Lecture::getAttributesById($lectureId, 'lecture_section_id');
        $section = new Section($sectionId);
        if (!$section->setUpdatedDate($courseId)) {
            $this->error = $section->getError();
            return false;
        }

        /* update course duration */
        $course = new Course($courseId);
        if (!$course->setUpdatedDate()) {
            $this->error = $course->getError();
            return false;
        }
        return true;
    }
}
