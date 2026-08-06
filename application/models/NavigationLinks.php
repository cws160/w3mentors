<?php

/**
 * This class is used to handle Navigation Links
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class NavigationLinks extends MyAppModel
{

    const DB_TBL = 'tbl_navigation_links';
    const DB_TBL_PREFIX = 'nlink_';
    const DB_TBL_LANG = 'tbl_navigation_links_lang';
    const DB_TBL_LANG_PREFIX = 'nlinkslang_';
    const NAVLINK_TYPE_CMS = 0;
    //const NAVLINK_TYPE_CUSTOM_HTML = 1;
    const NAVLINK_TYPE_EXTERNAL_PAGE = 2;
    const NAVLINK_TYPE_CATEGORY_PAGE = 3;
    const NAVLINK_LOGIN_BOTH = 0;
    const NAVLINK_LOGIN_YES = 1;
    const NAVLINK_LOGIN_NO = 2;

    /**
     * Initialize Navigation
     *
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'nlink_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $isDeleted
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0, bool $isDeleted = false): SearchBase
    {
        $langId = FatUtility::int($langId);
        $srch = new SearchBase(static::DB_TBL, 'link');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN',
                    'link_l.nlinkslang_nlink_id = link.nlink_id AND link_l.nlinkslang_lang_id = ' . $langId, 'link_l');
        }
        if ($isDeleted == false) {
            $srch->addCondition('link.nlink_deleted', '=', AppConstant::NO);
        }
        return $srch;
    }

    /**
     * Get Link Types
     * 
     * @return array
     */
    public static function getLinkTypeArr(): array
    {
        return [
            static::NAVLINK_TYPE_CMS => Label::getLabel('LBL_CMS_Page'),
            static::NAVLINK_TYPE_EXTERNAL_PAGE => Label::getLabel('LBL_External_Page')
        ];
    }

    /**
     * Get Link Login Types
     * 
     * @return array
     */
    public static function getLinkLoginTypeArr(): array
    {
        return [
            static::NAVLINK_LOGIN_BOTH => Label::getLabel('LBL_Both'),
            static::NAVLINK_LOGIN_YES => Label::getLabel('LBL_Yes'),
            static::NAVLINK_LOGIN_NO => Label::getLabel('LBL_No'),
        ];
    }

}
