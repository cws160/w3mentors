<?php

/**
 * Admin Class is used to handle Admin Statistic
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PageLangData extends MyAppModel
{

    const DB_TBL = 'tbl_pages_language_data';
    const DB_TBL_PREFIX = 'plang_';
    const DB_TBL_DATA = 'tbl_pages_data';

    /**
     * Initialize  Pages language data
     * 
     * @param int $pageId
     */
    public function __construct(int $pageId = 0)
    {
        parent::__construct(static::DB_TBL, 'plang_id', $pageId);
    }

    /**
     * Get Search Object
     * 
     * @param bool $isActive
     * @return SearchBase
     */
    public static function getSearchObject(): SearchBase
    {
        return new SearchBase(static::DB_TBL);
    }

    public static function getAttributesByKey($key, $langId)
    {
        $srch = static::getSearchObject();
        $srch->addCondition('plang_key', '=', $key);
        $srch->addCondition('plang_lang_id', '=', $langId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function getDefaultDataByKey($key)
    {
        $srch = new SearchBase(static::DB_TBL_DATA);
        $srch->addCondition('pdata_key', '=', $key);
        $srch->addFld('pdata_helping_text');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function updateKeys($key, $oldKey)
    {
        $record = new TableRecord(PageLangData::DB_TBL);
        $record->setFldValue('plang_key', $key);
        if (!$record->update(['smt' => 'plang_key = ?', 'vals' => [$oldKey]])) {
            return false;
        }
        return true;
    }


}