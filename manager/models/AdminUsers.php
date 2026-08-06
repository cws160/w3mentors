<?php
/**
 * Admin Class is used to handle Admin Statistic
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminUsers extends MyAppModel
{

    const DB_TBL = 'tbl_admin';
    const DB_TBL_PREFIX = 'admin_';

    /**
     * Initialize Admin User
     * 
     * @param int $adminId
     */
    public function __construct(int $adminId = 0)
    {
        parent::__construct(static::DB_TBL, 'admin_id', $adminId);
    }

    /**
     * Get Search Object
     * 
     * @param bool $isActive
     * @return SearchBase
     */
    public static function getSearchObject(bool $isActive = true):SearchBase
    {
        $srch = new SearchBased(static::DB_TBL);
        if ($isActive == true) {
            $srch->addCondition('admin_active', '=', 1);
        }
        return $srch;
    }

    /**
     * Get User Permissions
     * 
     * @param int $admperm_admin_id
     * @return bool|array
     */
    public static function getUserPermissions(int $admperm_admin_id = 0)
    {
        $srch = new SearchBase(AdminPrivilege::DB_TBL);
        $srch->addCondition('admperm_admin_id', '=', $admperm_admin_id);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetchAll($rs, 'admperm_section_id');
        if (!empty($row)) {
            return $row;
        }
        return false;
    }

    /**
     * Update Permissions
     * 
     * @param array $assignValues
     * @param bool $updateAll
     * @return bool
     */
    public function updatePermissions(array $assignValues = [], bool $updateAll = false): bool
    {
        if ($updateAll) {
            $permissionModules = AdminPrivilege::getPermissionModules();
            foreach ($permissionModules as $key => $val) {
                $assignValues['admperm_section_id'] = $key;
                if (!FatApp::getDb()->insertFromArray(AdminPrivilege::DB_TBL, $assignValues, false, [], $assignValues)) {
                    return false;
                }
            }
        } else {
            if (!FatApp::getDb()->insertFromArray(AdminPrivilege::DB_TBL, $assignValues, false, [], $assignValues)) {
                return false;
            }
        }
        return true;
    }

}
