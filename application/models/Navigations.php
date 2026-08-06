<?php

/**
 * This class is used to handle Navigation
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Navigations extends MyAppModel
{

    const DB_TBL = 'tbl_navigations';
    const DB_TBL_PREFIX = 'nav_';
    const DB_TBL_LANG = 'tbl_navigations_lang';
    const DB_TBL_LANG_PREFIX = 'navlang_';
    const NAVTYPE_HEADER = 1;
    const NAVTYPE_FOOTER_ONE = 2;
    const NAVTYPE_FOOTER_TWO = 3;
    const NAVTYPE_FOOTER_THREE = 4;

    /**
     * Initialize Navigation
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'nav_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $isActive
     * @param bool $isDeleted
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0, bool $isActive = true, bool $isDeleted = false): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'nav');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'nav_l.navlang_nav_id = nav.nav_id AND nav_l.navlang_lang_id = ' . $langId, 'nav_l');
        }
        if ($isActive == true) {
            $srch->addCondition('nav.nav_active', '=', 1);
        }
        if ($isDeleted == false) {
            $srch->addCondition('nav.nav_deleted', '=', 0);
        }
        return $srch;
    }

    /**
     * Get Listing Obj
     * 
     * @param int $langId
     * @param string|array $attr
     * @return SearchBase
     */
    public static function getListingObj(int $langId, $attr = null): SearchBase
    {
        $srch = self::getSearchObject($langId);
        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $srch->addMultipleFields(['IFNULL(nav_l.nav_name,nav.nav_identifier) as nav_name']);
        return $srch;
    }

    /**
     * Update Content
     * 
     * @param array $data
     * @return bool
     */
    public function updateContent(array $data = []): bool
    {
        if (!($this->mainTableRecordId > 0)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $nav_id = FatUtility::int($data['nav_id']);
        $assignValues = ['nav_identifier' => $data['nav_identifier'], 'nav_active' => $data['nav_active']];
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, $assignValues, ['smt' => 'nav_id = ? ', 'vals' => [$nav_id]])) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Link Search Obj
     * 
     * @param int $langId
     * @return SearchBase
     */
    public static function getLinkSearchObj(int $langId): SearchBase
    {
        $srch = new SearchBase(NavigationLinks::DB_TBL, 'link');
        $srch->joinTable(Navigations::DB_TBL, 'LEFT OUTER JOIN', 'nav.nav_id = link.nlink_nav_id', 'nav');
        if ($langId > 0) {
            $srch->joinTable(NavigationLinks::DB_TBL_LANG, 'LEFT OUTER JOIN', 'link.nlink_id = link_l.nlinklang_nlink_id AND nlinklang_lang_id = ' . $langId, 'link_l');
            $srch->joinTable(Navigations::DB_TBL_LANG, 'LEFT OUTER JOIN', 'nav.nav_id = nav_l.navlang_nav_id AND navlang_lang_id = ' . $langId, 'nav_l');
        }
        return $srch;
    }

}
