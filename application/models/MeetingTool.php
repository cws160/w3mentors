<?php

/**
 * This class is used to handle Meeting Tool
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class MeetingTool extends MyAppModel
{

    const DB_TBL = 'tbl_meeting_tools';
    const DB_TBL_PREFIX = 'metool_';

    /**
     * Initialize Meeting Tool
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'metool_id', $id);
    }

    /**
     * Get Statues
     * 
     * @param int $key
     * @return string|array
     */
    public static function getStatues(int $key = null)
    {
        $arr = [
            AppConstant::ACTIVE => Label::getLabel('LBL_ACTIVE'),
            AppConstant::INACTIVE => Label::getLabel('LBL_INACTIVE'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Setup Tool
     * 
     * @param array $post
     * @return bool
     */
    public function setup(array $settings): bool
    {
        $tool = static::getAttributesById($this->getMainTableRecordId());
        $toolSettings = json_decode($tool['metool_settings'], true);
        foreach ($toolSettings as &$row) {
            foreach ($row as $name => &$field) {
                $field['value'] = trim($settings[$name]);
            }
        }
        $this->assignValues(['metool_settings' => json_encode($toolSettings)]);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Update Status
     * 
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $status): bool
    {
        $this->setFldValue('metool_status', $status);
        return $this->save();
    }

    /**
     * Get By Code
     * 
     * @param string $code
     * @return null|array
     */
    public static function getByCode(string $code)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('metool_code', '=', $code);
        $srch->addMultipleFields(['metool_id', 'metool_settings']);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Detail
     * 
     * @param int $toolId
     * @return type
     */
    public static function getDetail(int $toolId)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(['metool_id', 'metool_code', 'metool_iframe', 'metool_settings']);
        if ($toolId > 0) {
            $srch->addCondition('metool_id', '=', $toolId);
        } else {
            $srch->addCondition('metool_status', '=', AppConstant::ACTIVE);
        }
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Can Join From App
     * 
     * @param int $toolId
     * @return int
     */
    public static function canJoinFromApp(int $toolId): int
    {
        $sets = [];
        $tool = MeetingTool::getDetail($toolId);
        $settings = json_decode($tool['metool_settings'], true);
        foreach ($settings as $value) {
            foreach ($value as $key => $val) {
                $sets[$key] = $val;
            }
        }
        return FatUtility::int($sets['join_from_app']['value'] ?? 0);
    }

}
