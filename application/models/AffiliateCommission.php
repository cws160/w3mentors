<?php

/**
 * This class is used to handle Affiliate Commission 
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class AffiliateCommission extends MyAppModel
{

    const DB_TBL = 'tbl_affiliate_commissions';
    const DB_TBL_PREFIX = 'afcomm_';
    const DB_TBL_HISTORY = 'tbl_affiliate_commission_history';


    /**
     * Initialize Commission
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'afcomm_id', $id);
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
        $this->setFldValue('afcomm_created', date('Y-m-d H:i:s'));
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
            'afcomhis_user_id' => $data['afcomm_user_id'],
            'afcomhis_commission' => $data['afcomm_commission'],
            'afcomhis_created' => date('Y-m-d H:i:s'),
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
     * @param array $userIds
     * @return array
     */
    public static function getCommission(array $userIds)
    {
        if(empty($userIds)){
            return [];
        }
        $srch = new SearchBase(User::DB_TBL,'user');  
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'affiliate.user_id= uset.user_referred_by  AND  affiliate.user_active = '.AppConstant::YES.' AND affiliate.user_is_affiliate ='.AppConstant::YES, 'affiliate');
        $srch->joinTable(static::DB_TBL, 'LEFT JOIN', 'affiliate.user_id= afcomm.afcomm_user_id', 'afcomm');
        $srch->addMultipleFields([
            'user.user_id as user_id', 'affiliate.user_id as affiliate_user_id', 'afcomm_commission','user.user_first_name','user.user_last_name',

        ]);
        $srch->addCondition('user.user_id', 'IN', $userIds);
        $srch->addCondition('user.user_active', '=', AppConstant::YES);
        $srch->addCondition('user.user_is_affiliate', '=', AppConstant::NO); 
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->addDirectCondition('affiliate.user_deleted IS NULL');
        $srch->addOrder('afcomm_user_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
       

    }

    /**
     * Remove affiliate commission and history
     *
     * @return bool
     */
    public function remove()
    {
        $commissionId = $this->getMainTableRecordId();
        $row = AffiliateCommission::getAttributesById($commissionId, ['afcomm_id', 'afcomm_user_id']);
        if (!$row) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if (empty($row['afcomm_user_id'])) {
            $this->error = Label::getLabel('LBL_CANNOT_DELETE_GLOBAL_COMMISSION');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        /* delete commission */
        $whr = ['smt' => 'afcomm_id = ?', 'vals' => [$commissionId]];
        if (!$db->deleteRecords(AffiliateCommission::DB_TBL, $whr)) {
            $db->rollbackTransaction();
            $this->error = $db->getError();
            return false;
        }

        /* delete commission history */
        $whr = ['smt' => 'afcomhis_user_id = ?', 'vals' => [$row['afcomm_user_id']]];
        if (!$db->deleteRecords(AffiliateCommission::DB_TBL_HISTORY, $whr)) {
            $db->rollbackTransaction();
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
     * get Global Commsision
     *
     * @return float
     */

    public static function getGlobalCommission()
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(['afcomm_commission', 'afcomm_user_id', 'afcomm_id']);
        $srch->addDirectCondition('afcomm_user_id IS NULL');
        $srch->addOrder('afcomm_user_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet())['afcomm_commission'];
    }
}
