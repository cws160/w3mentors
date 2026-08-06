<?php

/**
 * This class is used to handle Languages
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Language extends MyAppModel
{

    const DB_TBL = 'tbl_languages';
    const DB_TBL_PREFIX = 'language_';

    /**
     * Initialize Language
     *
     * @param int $langId
     */
    public function __construct(int $langId = 0)
    {
        parent::__construct(static::DB_TBL, 'language_id', $langId);
        $this->objMainTableRecord->setSensitiveFields([]);
    }

    /**
     * Get Search Object
     * 
     * @param bool $isActive
     * @return SearchBase
     */
    public static function getSearchObject(bool $isActive = true): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'l');
        if ($isActive == true) {
            $srch->addCondition('l.language_active', '=', AppConstant::ACTIVE);
        }
        return $srch;
    }

    /**
     * Get All Names
     * 
     * @param bool $assoc
     * @param int $recordId
     * @param int $active
     * @return array
     */
    public static function getAllNames(bool $assoc = true, int $recordId = 0, int $active = null): array
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addOrder('language_id');
        if (is_null($active)) {
            $srch->addCondition('language_active', '=', AppConstant::ACTIVE);
        }
        if ($recordId > 0) {
            $srch->addCondition('language_id', '=', FatUtility::int($recordId));
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if ($assoc) {
            $srch->addMultipleFields(array('language_id', 'language_name'));
            return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        } else {
            return FatApp::getDb()->fetchAll($srch->getResultSet(), 'language_id');
        }
    }

    /**
     * Get All Codes Assoc
     * 
     * @param int $key
     */
    public static function getCodes(int $key = null)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(['language_id', 'lower(language_code)']);
        $srch->addCondition('language_active', '=', AppConstant::ACTIVE);
        $srch->addOrder('language_id');
        $srch->doNotCalculateRecords();
        $arr = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        return ($key === null) ? $arr : ($arr[$key] ?? '');
    }

    /**
     * Get Layout Direction
     * 
     * @param int $langId
     * @return type
     */
    public static function getLayoutDirection(int $langId)
    {
        $langData = self::getAttributesById($langId, ['language_direction']);
        if (false != $langData) {
            return $langData['language_direction'];
        }
    }

    /**
     * Get Data
     * 
     * @param int $languageId
     * @return array
     */
    public static function getData(int $languageId)
    {
        $srch = new SearchBase(static::DB_TBL, 'language');
        $srch->addCondition('language_id', '=', $languageId);
        $srch->addCondition('language_active', '=', AppConstant::YES);
        $srch->addMultipleFields(['language_id', 'language_code', 'language_name', 'language_direction']);
        $srch->doNotCalculateRecords();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function getDefaultLang()
    {
        return FatApp::getConfig('CONF_DEFAULT_LANG');
    }
}
