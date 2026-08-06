<?php

/**
 * This class is used to handle Course Intended Learners
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class IntendedLearner extends MyAppModel
{
    const DB_TBL = 'tbl_courses_intended_learners';
    const DB_TBL_PREFIX = 'coinle_';

    /* Intended Learners Types */
    const TYPE_LEARNING = 1;
    const TYPE_REQUIREMENTS = 2;
    const TYPE_LEARNERS = 3;

    /**
     * Initialize
     *
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'coinle_id', $id);
    }
    
    /**
     * Get types
     *
     * @param integer|null $key
     * @return string|array
     */
    public static function getTypes(int $key = null)
    {
        $arr = [
            static::TYPE_LEARNING => Label::getLabel('LBL_WHAT_WILL_STUDENT_LEARN'),
            static::TYPE_REQUIREMENTS => Label::getLabel('LBL_WHAT_ARE_THE_REQUIREMENTS'),
            static::TYPE_LEARNERS => Label::getLabel('LBL_WHO_IS_THE_COURSE_FOR'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }
    
    /**
     * Get types sub titles
     *
     * @param integer|null $key
     * @return string|array
     */
    public static function getTypesSubTitles(int $key = null)
    {
        $arr = [
            static::TYPE_LEARNING => Label::getLabel('LBL_WHAT_WILL_STUDENT_LEARN_SUBTITLE'),
            static::TYPE_REQUIREMENTS => Label::getLabel('LBL_WHAT_ARE_THE_REQUIREMENTS_SUBTITLE'),
            static::TYPE_LEARNERS => Label::getLabel('LBL_WHO_IS_THE_COURSE_FOR_SUBTITLE'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Setup Data
     *
     * @param array $data
     * @return bool
     */
    public function setup(array $data)
    {
        if (
            count($data['type_learnings']) < 1 || 
            count($data['type_requirements']) < 1 || 
            count($data['type_learners']) < 1
        ) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $respData = [
            'coinle_course_id' => $data['course_id'],
            'coinle_created' => date('Y-m-d H:i:s'),
        ];
        $db = FatApp::getDb();
        $db->startTransaction();

        /* save learnings */
        $insertIds = [];
        foreach ($data['type_learnings'] as $key => $learnings) {
            $respData['coinle_id'] = 0;
            if (isset($data['type_learnings_ids'][$key])) {
                $respData['coinle_id'] = $data['type_learnings_ids'][$key];
            }
            $respData['coinle_type'] = static::TYPE_LEARNING;
            $respData['coinle_response'] = $learnings;
            if (!$db->insertFromArray(static::DB_TBL, $respData, false, [], $respData)) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
            $insertIds[] = ($respData['coinle_id'] > 0) ? $respData['coinle_id'] : $db->getInsertId();
        }
        /* save requirements */
        foreach ($data['type_requirements'] as $key => $requirement) {
            $respData['coinle_id'] = 0;
            if (isset($data['type_requirements_ids'][$key])) {
                $respData['coinle_id'] = $data['type_requirements_ids'][$key];
            }
            $respData['coinle_type'] = static::TYPE_REQUIREMENTS;
            $respData['coinle_response'] = $requirement;
            if (!$db->insertFromArray(static::DB_TBL, $respData, false, [], $respData)) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
            $insertIds[] = ($respData['coinle_id'] > 0) ? $respData['coinle_id'] : $db->getInsertId();
        }
        /* save learners */
        foreach ($data['type_learners'] as $key => $learner) {
            $respData['coinle_id'] = 0;
            if (isset($data['type_learners_ids'][$key])) {
                $respData['coinle_id'] = $data['type_learners_ids'][$key];
            }
            $respData['coinle_type'] = static::TYPE_LEARNERS;
            $respData['coinle_response'] = $learner;
            if (!$db->insertFromArray(static::DB_TBL, $respData, false, [], $respData)) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
            $insertIds[] = ($respData['coinle_id'] > 0) ? $respData['coinle_id'] : $db->getInsertId();
        }
        if (!$this->updateOrder($insertIds)) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        $course = new Course($data['course_id']);
        if ($course->getEditRequestStatus()) {
            $course->setUpdatedDate();
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Get Intended Learners Formatted Data
     *
     * @param int $courseId
     * @return array
     */
    public function get(int $courseId)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('coinle_course_id', '=', $courseId);
        $srch->addCondition('coinle_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields([
            'coinle_id',
            'coinle_type',
            'coinle_course_id',
            'coinle_response',
        ]);
        $srch->addOrder('coinle_type', 'ASC');
        $srch->addOrder('coinle_order', 'ASC');
        $responses = FatApp::getDb()->fetchAll($srch->getResultSet());
        $responseList = [];
        if ($responses) {
            foreach ($responses as $resp) {
                $responseList[$resp['coinle_type']][] = $resp;
            }
        }
        return $responseList;
    }

    /**
     * Function to remove intended learner
     *
     * @return bool
     */
    public function delete()
    {
        $intendedId = $this->getMainTableRecordId();
        if ($intendedId < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        /*  check if record exists */
        if (!$intendedDeleted = static::getAttributesById($intendedId, ['coinle_deleted'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* check if already deleted */
        if ((int)$intendedDeleted['coinle_deleted'] > 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INTENDED_LEARNER_ALREADY_DELETED'));
        }
        /* mark intended learner record deleted */
        $this->setFldValue('coinle_deleted', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }
}
