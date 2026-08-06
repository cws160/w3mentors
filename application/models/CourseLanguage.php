<?php

/**
 * This class is used to handle course Language
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseLanguage extends MyAppModel
{

    const DB_TBL = 'tbl_course_languages';
    const DB_TBL_LANG = 'tbl_course_languages_lang';
    const DB_TBL_PREFIX = 'clang_';

    private $langId;

    /**
     * Initialize Lang
     * 
     * @param int $id
     * @param type $langId
     */
    public function __construct(int $id = 0, $langId = 0)
    {
        $this->langId = $langId;
        parent::__construct(static::DB_TBL, 'clang_id', $id);
    }

    /**
     * Get All Languages
     * 
     * @param int $langId
     * @param bool $active
     * @return array
     */
    public static function getAllLangs(int $langId, bool $active = false): array
    {
        $srch = new SearchBase(static::DB_TBL, 'clang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'clanglang.clanglang_clang_id = clang.clang_id AND clanglang.clanglang_lang_id = ' . $langId, 'clanglang');
        if ($active) {
            $srch->addCondition('clang.clang_active', '=', AppConstant::ACTIVE);
        }
        $srch->addCondition('clang.clang_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addMultiplefields(['clang_id', 'IFNULL(clang_name, clang_identifier) as clang_name']);
        $srch->doNotCalculateRecords();
        $srch->addOrder('clang_order');
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    public function getById()
    {
        $srch = new SearchBase(static::DB_TBL, 'clang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'clanglang.clanglang_clang_id = clang.clang_id AND clanglang.clanglang_lang_id = ' . $this->langId, 'clanglang');
        $srch->addCondition('clang.clang_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('clang.clang_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('clang.clang_id', '=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        $srch->addFld('IFNULL(clang_name, clang_identifier) as clang_name');
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

}
