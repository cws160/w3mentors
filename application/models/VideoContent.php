<?php

/**
 * This class is used to handle Video Content
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class VideoContent extends MyAppModel
{

    const DB_TBL = "tbl_bible_content";
    const DB_TBL_PREFIX = "biblecontent_";
    const DB_TBL_LANG = "tbl_bible_content_lang";
    const DB_TBL_LANG_PREFIX = "biblecontentlang_";

    /**
     * Initialize Video Content
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(self::DB_TBL, "biblecontent_id", $id);
    }

    /**
     * Get List
     * 
     * @param int $langId
     * @return SearchBase
     */
    public function getList(int $langId = 0): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition("biblecontent_active", '=', 1);
        if ($langId) {
            $srch->joinTable(self::DB_TBL_LANG, 'LEFT JOIN', 'biblecontent_id = biblecontentlang_biblecontent_id AND biblecontentlang_lang_id=' . $langId);
        }
        return $srch;
    }

    /**
     * Get Video Content By Id
     * 
     * @param int $id
     * @return null|array
     */
    public static function getBibleContentById(int $id)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition("biblecontent_id", '=', $id);
        $rs = $srch->getResultSet();
        if ($srch->recordCount() < 1) {
            return [];
        }
        return FatApp::getDb()->fetch($rs);
    }

}
