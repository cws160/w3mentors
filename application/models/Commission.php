<?php

/**
 * This class is used to handle Admin Commission 
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Commission extends MyAppModel
{

    const DB_TBL = 'tbl_admin_commissions';
    const DB_TBL_PREFIX = 'comm_';
    const DB_TBL_HISTORY = 'tbl_commission_history';

    /**
     * Initialize Commission
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'comm_id', $id);
    }

    /**
     * Add Update Data
     * 
     * @param array $data
     * @return bool
     */
    public function addUpdateData(array $data): bool
    {
        $db = FatApp::getDb();
        $db->startTransaction();
        $this->assignValues($data);
        $this->setFldValue('comm_created', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->addHistory($data)) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Add History
     * 
     * @param array $data
     * @return bool
     */
    private function addHistory(array $data): bool
    {
        $assignValues = [
            'comhis_user_id' => $data['comm_user_id'],
            'comhis_lessons' => $data['comm_lessons'],
            'comhis_classes' => ($data['comm_classes']) ?? '',
            'comhis_courses' => ($data['comm_courses']) ?? '',
            'comhis_created' => date('Y-m-d H:i:s'),
        ];
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_HISTORY, $assignValues)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Commission
     * 
     * @param int $teacherId
     * @return null|array
     */
    public static function getCommission(int $teacherId)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(['comm_lessons', 'comm_classes', 'comm_courses', 'comm_id']);
        $srch->addDirectCondition('comm_user_id = ' . $teacherId . ' OR comm_user_id IS NULL');
        $srch->addOrder('comm_user_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

}
