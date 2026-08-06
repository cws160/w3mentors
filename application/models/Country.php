<?php

/**
 * This class is used to handle Country
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Country extends MyAppModel
{

    const DB_TBL = 'tbl_countries';
    const DB_TBL_PREFIX = 'country_';
    const DB_TBL_LANG = 'tbl_countries_lang';
    const DB_TBL_LANG_PREFIX = 'countrylang_';

    /**
     * Initialize Country
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'country_id', $id);
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
        $srch = new SearchBase(static::DB_TBL, 'c');
        if ($isActive == true) {
            $srch->addCondition('c.country_active', '=', AppConstant::YES);
        }
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'c_l.countrylang_country_id = c.country_id and c_l.countrylang_lang_id = ' . $langId, 'c_l');
        }
        return $srch;
    }

    /**
     * Get Countries Names
     * 
     * @param int $langId
     * @param array $countryIds
     * @return array
     */
    public static function getNames(int $langId, array $countryIds = [], bool $isActive = true): array
    {
        $countries = array_filter(array_unique($countryIds));
        $srch = new SearchBase(static::DB_TBL, 'country');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'lang.countrylang_country_id = '
                . 'country.country_id AND lang.countrylang_lang_id = ' . $langId, 'lang');
        if (count($countries) > 0) {
            $srch->addDirectCondition('lang.countrylang_country_id IN (' . implode(',', $countries) . ')');
        }
        if ($isActive) {
            $srch->addCondition('country.country_active', '=', AppConstant::YES);
        }
        $srch->addMultipleFields(['country.country_id', 'IFNULL(lang.country_name, country.country_identifier) AS country_name']);
        $srch->addOrder('lang.country_name', 'ASC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

     /**
     * Get Countries Options
     * 
     * @return array
     */
    public static function getOptions($langId = 0): array
    {
        $srch = new SearchBase(static::DB_TBL, 'country');
        $srch->addCondition('country.country_active', '=', AppConstant::YES);
        $srch->addMultipleFields(['country.country_id', 'IFNULL(clang.country_name, country.country_identifier) as country_identifier']);
        $srch->joinTable(Country::DB_TBL_LANG, 'LEFT JOIN', 'clang.countrylang_country_id  = country.country_id  and clang.countrylang_lang_id   = ' . $langId, 'clang');
        $srch->addOrder('country_identifier', 'ASC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }


    /**
     * Get Countries Dial Codes
     * 
     * @param array $countryIds
     * @return array
     */
    public static function getDialCodes(array $countryIds = []): array
    {
        $countries = array_filter(array_unique($countryIds));
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(['country_id', 'country_dial_code']);
        if (count($countries) > 0) {
            $srch->addDirectCondition('country_id IN (' . implode(',', $countries) . ')');
        }
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get All Countries
     * 
     * @param int $langId
     * @return array
     */
    public static function getAll(int $langId = 0): array
    {
        $srch = new SearchBase(static::DB_TBL, 'country');
        $srch->addMultipleFields(['country.*', 'country.country_identifier as country_name']);
        if ($langId > 0) {
            $srch->addFld('IFNULL(lang.country_name, country.country_identifier) AS country_name');
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'lang.countrylang_country_id = country.country_id AND lang.countrylang_lang_id = ' . $langId, 'lang');
        }
        $srch->addCondition('country.country_active', '=', AppConstant::YES);
        $srch->addOrder('country_name', 'ASC');
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $countries = [];
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            $countries[$row['country_id']] = $row;
            $countries[$row['country_id']]['phone_code'] = $row['country_name'] . ' (' . $row['country_dial_code'] . ')';
        }
        return $countries;
    }

    /**
     * Get Countries Names
     * 
     * @param int $langId
     * @param array $countryIds
     * @return array
     */
    public static function getNameAndCode(int $langId, array $countryIds): array
    {
        if ($langId == 0 || count($countryIds) == 0) {
            return [];
        }
        $srch = new SearchBase(Country::DB_TBL, 'country');
        $on = 'clang.countrylang_country_id = country.country_id AND clang.countrylang_lang_id = ' . $langId;
        $srch->joinTable(Country::DB_TBL_LANG, 'INNER JOIN', $on, 'clang');
        $srch->addMultipleFields(['country_id', 'country_code', 'country_name']);
        $srch->addCondition('country.country_id', 'IN', $countryIds);
        $srch->doNotCalculateRecords();
        $result = $srch->getResultSet();
        $rows = FatApp::getDb()->fetchAll($result);
        $countries = [];
        foreach ($rows as $row) {
            $countries[$row['country_id']] = ['code' => $row['country_code'], 'name' => $row['country_name']];
        }
        return $countries;
    }

    /**
     * can Inactive a country
     * 
     * @param int $countryId
     * @return bool
     */
    public static function canInactive($countryId) : bool {
        $srch = new SearchBase(UserAddresses::DB_TBL);
        $srch->addCondition('usradd_country_id', '=', $countryId);
        $srch->addDirectCondition('usradd_deleted IS NULL');
        $srch->getResultSet();
        $belongsToAddr = $srch->recordCount();
        $srch = new SearchBase(User::DB_TBL);
        $srch->addCondition('user_country_id', '=', $countryId);
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->getResultSet();
        $belongsToUser = $srch->recordCount();
        $siteCountry = FatApp::getConfig('CONF_COUNTRY');
        $srch = new SearchBase(State::DB_TBL);
        $srch->addCondition('state_country_id', '=', $countryId);
        $srch->addCondition('state_active', '=', AppConstant::YES);
        $srch->getResultSet();
        $belongsToState = $srch->recordCount();
        if($belongsToState == 0 && $siteCountry != $countryId &&  $belongsToAddr == 0 && $belongsToUser == 0) {
            return true;
        }
        return false;
    }

}
