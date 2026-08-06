<?php

/**
 * This class is used to handle Teachers class Planning
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Plan extends MyAppModel
{

    const DB_TBL = 'tbl_plans';
    const DB_TBL_PREFIX = 'plan_';
    const DB_TBL_LESSON = 'tbl_plan_lessons';
    const DB_TBL_GCLASS = 'tbl_plan_classes';
    /* Levels */
    const LEVEL_BEGINNER = 1;
    const LEVEL_UPPER_BEGINNER = 2;
    const LEVEL_INTERMEDIATE = 3;
    const LEVEL_UPPER_INTERMEDIATE = 4;
    const LEVEL_ADVANCED = 5;
    const PLAN_TYPE_LESSONS = 1;
    const PLAN_TYPE_CLASSES = 2;
    const LISTING_TYPE = 1;

    /**
     * Initialize Plan 
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'plan_id', $id);
    }

    /**
     * Delete Plan
     *
     * @param int $userId
     * @return bool
     */
    public function remove(int $userId): bool
    {
        $stmt = [
            'smt' => 'plan_id = ? AND plan_teacher_id = ?',
            'vals' => [$this->mainTableRecordId, $userId]
        ];
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL, $stmt)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Plan Levels
     * 
     * @param int $key
     * @return string|array
     */
    public static function getLevels(int $key = null)
    {
        $arr = [
            static::LEVEL_BEGINNER => Label::getLabel('LBL_BEGINNER'),
            static::LEVEL_UPPER_BEGINNER => Label::getLabel('LBL_UPPER_BEGINNER'),
            static::LEVEL_INTERMEDIATE => Label::getLabel('LBL_INTERMEDIATE'),
            static::LEVEL_UPPER_INTERMEDIATE => Label::getLabel('LBL_UPPER_INTERMEDIATE'),
            static::LEVEL_ADVANCED => Label::getLabel('LBL_ADVANCED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Lesson Plans
     * 
     * @param array $lessonIds
     * @return array
     */
    public static function getLessonPlans(array $lessonIds): array
    {
        if (count($lessonIds) == 0) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'plan');
        $srch->joinTable(static::DB_TBL_LESSON, 'LEFT JOIN', 'planles.planles_plan_id = plan.plan_id', 'planles');
        $srch->addMultipleFields(['planles_ordles_id', 'plan_id', 'plan_title', 'plan_detail', 'plan_level', 'plan_links', 'planles_id']);
        $srch->addCondition('planles.planles_ordles_id', 'IN', array_unique($lessonIds));
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'planles_ordles_id');
    }

    /**
     * Get Class Plans
     * 
     * @param array $classIds
     * @return array
     */
    public static function getGclassPlans(array $classIds): array
    {
        if (count($classIds) == 0) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'plan');
        $srch->joinTable(static::DB_TBL_GCLASS, 'LEFT JOIN', 'plancls.plancls_plan_id = plan.plan_id', 'plancls');
        $srch->addMultipleFields(['plancls_grpcls_id', 'plan_id', 'plan_title', 'plan_detail', 'plan_level', 'plan_links', 'plancls_id']);
        $srch->addCondition('plancls.plancls_grpcls_id', 'IN', array_unique($classIds));
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'plancls_grpcls_id');
    }

    /**
     * Can Download Plan File
     *
     * @param int $recordId
     * @param int $type
     * @param int $userId
     * @param int $fileId
     * @return bool
     */
    public function canDownload(int $recordId, int $type, int $userId, int $fileId): bool
    {
        $planId = $this->getMainTableRecordId();
        if (empty($planId) || empty($recordId) || empty($userId) || empty($fileId)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }

        $teacherId = Plan::getAttributesById($planId, 'plan_teacher_id');
        if ($teacherId < 1) {
            $this->error = Label::getLabel('LBL_PLAN_NOT_FOUND');
            return false;
        }
        if ($type == -1 && $teacherId == $userId) {
            return true;
        }
        if ($type == AppConstant::LESSON) {
            $srch = new SearchBase(Plan::DB_TBL_LESSON, 'planles');
            $srch->joinTable(Plan::DB_TBL, 'INNER JOIN', 'planles.planles_plan_id = plans.plan_id', 'plans');
            $srch->joinTable(Lesson::DB_TBL, 'INNER JOIN', 'ordles.ordles_id = planles.planles_ordles_id', 'ordles');
            $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
            $srch->addCondition('ordles_status', '!=', Lesson::CANCELLED);
            $srch->addCondition('planles_id', '=', $recordId);
            $srch->addCondition('planles.planles_plan_id', '=', $planId);
            $srch->addfld('orders.order_user_id');
        } else {
            $srch = new SearchBase(Plan::DB_TBL_GCLASS, 'plancls');
            $srch->joinTable(Plan::DB_TBL, 'INNER JOIN', 'plancls.plancls_plan_id = plans.plan_id', 'plans');
            $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'plancls.plancls_grpcls_id = grpcls.grpcls_id', 'grpcls');
            $srch->joinTable(Order::DB_TBL_GCLASS, 'INNER JOIN', 'ordcls.ordcls_grpcls_id = grpcls.grpcls_id', 'ordcls');
            $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
            $srch->addCondition('grpcls_status', '!=', GroupClass::CANCELLED);
            $srch->addCondition('plancls_id', '=', $recordId);
            $srch->addCondition('plancls.plancls_plan_id', '=', $planId);
            $srch->addfld('orders.order_user_id');
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_FILE_NOT_FOUND');
            return false;
        }
        if ($teacherId != $userId && $row['order_user_id'] != $userId) {
            $this->error = Label::getLabel('LBL_INVALID_ACCESS');
            return false;
        }
        return true;
    }

    /**
     * Can view plan file
     *
     * @param int $userId
     * @param int $recordId
     * @param int $type
     * @return bool
     */
    public function canViewPlan(int $userId, int $recordId, int $type): bool
    {
        $planId = $this->getMainTableRecordId();
        if (empty($planId) || empty($userId) || empty($recordId) || empty($type)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $row = [];
        if ($type == AppConstant::LESSON) {
            $srch = new SearchBase(Plan::DB_TBL_LESSON, 'planles');
            $srch->joinTable(Lesson::DB_TBL, 'INNER JOIN', 'planles.planles_ordles_id = ordles.ordles_id', 'ordles');
            $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
            $srch->addCondition('ordles_status', '!=', Lesson::CANCELLED);
            $srch->addCondition('planles.planles_plan_id', '=', $planId);
            $srch->addCondition('planles.planles_id', '=', $recordId);
            $srch->addFld('orders.order_user_id');
            $srch->doNotCalculateRecords();
            $row = FatApp::getDb()->fetch($srch->getResultSet());
        } else {
            $srch = new SearchBase(Plan::DB_TBL_GCLASS, 'plancls');
            $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'plancls.plancls_grpcls_id = grpcls.grpcls_id', 'grpcls');
            $srch->joinTable(Order::DB_TBL_GCLASS, 'INNER JOIN', 'ordcls.ordcls_grpcls_id = grpcls.grpcls_id', 'ordcls');
            $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordcls.ordcls_order_id = orders.order_id', 'orders');
            $srch->addCondition('plancls.plancls_plan_id', '=', $planId);
            $srch->addCondition('plancls.plancls_id', '=', $recordId);
            $srch->addFld('orders.order_user_id');
            $srch->doNotCalculateRecords();
            $row = FatApp::getDb()->fetch($srch->getResultSet());
        }
        if (!empty($row)) {
            $teacherId = Plan::getAttributesById($planId, 'plan_teacher_id');
            if ($teacherId == $userId) {
                return true;
            }
            if ($row['order_user_id'] == $userId) {
                return true;
            }
        }
        $this->error = Label::getLabel('LBL_ACCESS_DENIED');
        return false;
    }
}
