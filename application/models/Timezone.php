<?php

/**
 * This class is used to handle Timezone
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Timezone extends MyAppModel
{

    const DB_TBL = 'tbl_timezone';
    const DB_TBL_LANG = 'tbl_timezone_lang';
    const DB_TBL_PREFIX = 'timezone_';

    /**
     * Initialize Timezone
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'timezone_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $active
     * @return SearchBase
     */
    public static function getSearchObject(int $langId): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'tz');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'tz_l.timezonelang_timezone_id '
                . ' = tz.timezone_id AND timezonelang_lang_id = ' . $langId, 'tz_l');
        return $srch;
    }

    /**
     * Get All By Language
     * 
     * @param int $langId
     * @return array
     */
    public static function getAllByLang($langId): array
    {
        $srch = self::getSearchObject($langId);
        $srch->addMultipleFields([
            'timezone_id', 'timezone_offset', 'timezone_identifier',
            'IFNULL(timezonelang_text, timezone_name) as timezone_name'
        ]);
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'timezone_identifier');
    }

}
