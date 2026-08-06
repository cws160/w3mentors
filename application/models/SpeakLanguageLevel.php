<?php

/**
 * This class is used to handle Speak Language Level
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SpeakLanguageLevel extends MyAppModel
{
    const DB_TBL = 'tbl_speak_language_levels';
    const DB_TBL_LANG = 'tbl_speak_language_levels_lang';
    const DB_TBL_PREFIX = 'slanglvl_';

     /**
     * Initialize Speak Lang Level
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'slanglvl_id', $id);
    }

    /**
     * Get All Language Levels
     * 
     * @param int $langId
     * @param bool $active
     * @return array
     */
    public static function getAllLangLevels(int $langId, bool $active = false): array
    {
        $srch = new SearchBase(static::DB_TBL, 'slanglvl');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'slevellang.slanglvllang_slanglvl_id = slanglvl.slanglvl_id and slevellang.slanglvllang_lang_id =' . $langId, 'slevellang');
        if ($active) {
            $srch->addCondition('slanglvl.slanglvl_active', '=', AppConstant::ACTIVE);
        }
        $srch->addMultiplefields(['slanglvl_id', 'IFNULL(slanglvl_name, slanglvl_identifier) as slanglvl_name']);
        $srch->doNotCalculateRecords();
        $srch->addOrder('slanglvl_order');
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

}