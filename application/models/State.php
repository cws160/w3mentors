<?php
/**
 * This class is used to handle States
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class State extends MyAppModel
{

    const DB_TBL = 'tbl_states';
    const DB_TBL_PREFIX = 'state_';
    const DB_TBL_LANG = 'tbl_states_lang';
    const DB_TBL_LANG_PREFIX = 'stlang_';

    /**
     * Initialize state
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'state_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param bool $isActive
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObject(bool $isActive = true, int $langId = 0): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'st');
        if ($isActive == true) {
            $srch->addCondition('st.state_active', '=', AppConstant::YES);
        }
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = st.state_id and stlang.stlang_lang_id = ' . $langId, 'stlang');
        }
        return $srch;
    }

    /**
     * Get states Names
     * 
     * @param int $langId
     * @return array
     */
    public static function getNames(int $langId, int $countryId): array
    {
        $srch = new SearchBase(static::DB_TBL, 'st');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = '
            . 'st.state_id AND stlang.stlang_lang_id = ' . $langId, 'stlang');            
        $srch->addCondition('st.state_country_id', '=', $countryId);
        $srch->addCondition('st.state_active', '=', AppConstant::YES);
        $srch->addMultipleFields(['st.state_id', 'IFNULL(stlang.state_name, st.state_identifier) AS state_name']);
        $srch->addOrder('state_name', 'ASC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }


    /**
     * Get All states
     * 
     * @param int $langId
     * @return array
     */
    public static function getAll(int $langId = 0): array
    {
        $srch = new SearchBase(static::DB_TBL, 'st');
        $srch->addMultipleFields(['st.*', 'st.state_identifier as state_name']);
        if ($langId > 0) {
            $srch->addFld('IFNULL(stlang.state_name, st.state_identifier) AS state_name');
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = st.state_id AND stlang.stlang_lang_id = ' . $langId, 'stlang');
        }
        $srch->addCondition('st.state_active', '=', AppConstant::YES);
        $srch->addOrder('state_name', 'ASC');
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $states = [];
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            $states[$row['state_id']] = $row;
            $states[$row['state_id']]['phone_code'] = $row['state_name'] . ' (' . $row['state_code'] . ')';
        }
        return $states;
    }

    /**
     * Get states Names
     * 
     * @param int $langId
     * @param array $stateIds
     * @return array
     */
    public static function getNameAndCode(int $langId, array $stateIds): array
    {
        if ($langId == 0 || count($stateIds) == 0) {
            return [];
        }
        $srch = new SearchBase(state::DB_TBL, 'st');
        $on = 'stlang.stlang_state_id = st.state_id AND stlang.stlang_lang_id = ' . $langId;
        $srch->joinTable(state::DB_TBL_LANG, 'INNER JOIN', $on, 'stlang');
        $srch->addMultipleFields(['state_id', 'state_code', 'state_name']);
        $srch->addCondition('st.state_id', 'IN', $stateIds);
        $srch->doNotCalculateRecords();
        $result = $srch->getResultSet();
        $rows = FatApp::getDb()->fetchAll($result);
        $states = [];
        foreach ($rows as $row) {
            $states[$row['state_id']] = ['code' => $row['state_code'], 'name' => $row['state_name']];
        }
        return $states;
    }

    /**
     * Add/Edit States Lang Data
     *
     * @param array $data
     * @return bool
     */
    public function addUpdateLangData($data): bool
    {
        $assignValues = [
            'stlang_state_id' => $this->getMainTableRecordId(),
            'stlang_lang_id' => $data['stlang_lang_id'],
            'state_name' => $data['state_name']
        ];
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_LANG, $assignValues, false, [], $assignValues)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }
}
