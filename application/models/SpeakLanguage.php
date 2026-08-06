<?php

/**
 * This class is used to handle Speak Language
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SpeakLanguage extends MyAppModel
{

    const DB_TBL = 'tbl_speak_languages';
    const DB_TBL_LANG = 'tbl_speak_languages_lang';
    const DB_TBL_PREFIX = 'slang_';
    /* Proficiencies */
    const PROFICIENCY_TOTAL_BEGINNER = 1;
    const PROFICIENCY_BEGINNER = 2;
    const PROFICIENCY_UPPER_BEGINNER = 3;
    const PROFICIENCY_INTERMEDIATE = 4;
    const PROFICIENCY_UPPER_INTERMEDIATE = 5;
    const PROFICIENCY_ADVANCED = 6;
    const PROFICIENCY_UPPER_ADVANCED = 7;
    const PROFICIENCY_NATIVE = 8;

    /**
     * Initialize Speak Lang
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'slang_id', $id);
    }

    /**
     * Get Proficiencies
     * 
     * @return array
     */
    public static function getProficiencies(): array
    {
        return [
            static::PROFICIENCY_TOTAL_BEGINNER => Label::getLabel('LBL_Total_Beginner'),
            static::PROFICIENCY_BEGINNER => Label::getLabel('LBL_Beginner'),
            static::PROFICIENCY_UPPER_BEGINNER => Label::getLabel('LBL_Upper_Beginner'),
            static::PROFICIENCY_INTERMEDIATE => Label::getLabel('LBL_Intermediate'),
            static::PROFICIENCY_UPPER_INTERMEDIATE => Label::getLabel('LBL_Upper_Intermediate'),
            static::PROFICIENCY_ADVANCED => Label::getLabel('LBL_Advanced'),
            static::PROFICIENCY_UPPER_ADVANCED => Label::getLabel('LBL_Upper_Advanced'),
            static::PROFICIENCY_NATIVE => Label::getLabel('LBL_Native'),
        ];
    }

    /**
     * Get Names
     * 
     * @param int $langId
     * @param array $slangIds
     * @return array
     */
    public static function getNames(int $langId, array $slangIds): array
    {
        $slangIds = array_filter(array_unique($slangIds));
        if ($langId == 0 || empty($slangIds)) {
            return [];
        }
        $srch = new SearchBase(SpeakLanguage::DB_TBL, 'slang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'slanglang.slanglang_slang_id = slang.slang_id and slanglang.slanglang_lang_id =' . $langId, 'slanglang');
        $srch->addMultipleFields(['slang.slang_id', 'IFNULL(slang_name, slang_identifier) as slang_name']);
        $srch->addDirectCondition('slang.slang_id IN (' . implode(',', FatUtility::int($slangIds)) . ')');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
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
        $srch = new SearchBase(SpeakLanguage::DB_TBL, 'slang');
        $srch->joinTable(SpeakLanguage::DB_TBL_LANG, 'LEFT JOIN', 'slanglang.slanglang_slang_id = slang.slang_id AND slanglang.slanglang_lang_id = ' . $langId, 'slanglang');
        if ($active) {
            $srch->addCondition('slang.slang_active', '=', AppConstant::ACTIVE);
            $srch->addMultipleFields(['slang_id', 'IFNULL(slang_name, slang_identifier) as slang_name']);
        }
        $srch->addMultiplefields(['slang_id', 'IFNULL(slang_name, slang_identifier) as slang_name']);
        $srch->doNotCalculateRecords();
        $srch->addOrder('slang_order');
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Options
     * 
     * @param int $langId
     * @param bool $active
     * @return array
     */
    public static function getOptions(int $langId, bool $active = true): array
    {
        $srch = new SearchBase(static::DB_TBL, 'slang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'slanglang.slanglang_slang_id = slang.slang_id AND slanglang.slanglang_lang_id = ' . $langId, 'slanglang');
        $srch->addMultipleFields(['slang.slang_id', 'IFNULL(slanglang.slang_name, slang_identifier) as slang_name']);
        if ($active) {
            $srch->addCondition('slang.slang_active', '=', AppConstant::YES);
        }
        $srch->addOrder('slang.slang_order', 'ASC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

}
