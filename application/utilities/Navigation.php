<?php

/**
 * A Common Navigation Utility  
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Navigation
{

    /**
     * Get Header Navigation
     * 
     * @return type
     */
    public static function getHeaderNav()
    {
        $langId = MyUtility::getSiteLangId();
        $headerNav = FatCache::get('headerNav' . $langId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if (!empty($headerNav)) {
            return unserialize($headerNav);
        }
        $headerNav = self::getNavigation(Navigations::NAVTYPE_HEADER);
        FatCache::set('headerNav' . $langId, serialize($headerNav), '.txt');
        return $headerNav;
    }

    /**
     * Footer First Navigation
     * 
     * @return type
     */
    public static function footerOneNav()
    {
        $langId = MyUtility::getSiteLangId();
        $footerOneNav = FatCache::get('footerOneNav' . $langId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if (!empty($footerOneNav)) {
            $footerOneNav = unserialize($footerOneNav);
        } else {
            $footerOneNav = self::getNavigation(Navigations::NAVTYPE_FOOTER_ONE);
            FatCache::set('footerOneNav' . $langId, serialize($footerOneNav), '.txt');
        }
        return $footerOneNav;
    }

    /**
     * Footer Two Navigation
     * 
     * @return type
     */
    public static function footerTwoNav()
    {
        $langId = MyUtility::getSiteLangId();
        $footerTwoNav = FatCache::get('footerTwoNav' . $langId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if (!empty($footerTwoNav)) {
            $footerTwoNav = unserialize($footerTwoNav);
        } else {
            $footerTwoNav = self::getNavigation(Navigations::NAVTYPE_FOOTER_TWO);
            FatCache::set('footerTwoNav' . $langId, serialize($footerTwoNav), '.txt');
        }
        return $footerTwoNav;
    }

    /**
     * Footer Two Navigation
     * 
     * @return type
     */
    public static function footerThreeNav()
    {
        $langId = MyUtility::getSiteLangId();
        $footerThreeNav = FatCache::get('footerThreeNav' . $langId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if (!empty($footerThreeNav)) {
            $footerThreeNav = unserialize($footerThreeNav);
        } else {
            $footerThreeNav = self::getNavigation(Navigations::NAVTYPE_FOOTER_THREE);
            FatCache::set('footerThreeNav' . $langId, serialize($footerThreeNav), '.txt');
        }
        return $footerThreeNav;
    }

    /**
     * Get Navigation
     * 
     * @param int $type
     * @return type
     */
    public static function getNavigation(int $type)
    {
        $langId = MyUtility::getSiteLangId();
        $srch = new SearchBase(NavigationLinks::DB_TBL, 'link');
        $srch->joinTable(NavigationLinks::DB_TBL_LANG, 'LEFT OUTER JOIN', 'link.nlink_id = link_l.nlinklang_nlink_id AND nlinklang_lang_id = ' . $langId, 'link_l');
        $srch->joinTable(Navigations::DB_TBL, 'LEFT OUTER JOIN', 'nav.nav_id = link.nlink_nav_id', 'nav');
        $srch->joinTable(Navigations::DB_TBL_LANG, 'LEFT OUTER JOIN', 'nav.nav_id = nav_l.navlang_nav_id AND navlang_lang_id = ' . $langId, 'nav_l');
        $srch->joinTable(ContentPage::DB_TBL, 'LEFT OUTER JOIN', 'cp.cpage_id = link.nlink_cpage_id', 'cp');
        $srch->addCondition('nav_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('nlink_deleted', '=', AppConstant::NO);
        $srch->addCondition('nav_type', '=', $type);
        $srch->addMultipleFields([
            'nav_id', 'IFNULL( nav_name, nav_identifier ) as nav_name',
            'IFNULL( nlink_caption, nlink_identifier ) as nlink_caption', 'nlink_type',
            'nlink_cpage_id', 'IFNULL( cpage_deleted, ' . AppConstant::NO . ' ) as filtered_cpage_deleted',
            'nlink_target', 'nlink_url', 'nlink_login_protected'
        ]);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addOrder('nlink_order', 'ASC');
        $srch->addOrder('nlink_id', 'DESC');
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        $navigation = [];
        $previous_nav_id = 0;
        if ($rows) {
            foreach ($rows as $key => $row) {
                if ($key == 0 || $previous_nav_id != $row['nav_id']) {
                    $previous_nav_id = $row['nav_id'];
                }
                $navigation[$previous_nav_id]['parent'] = $row['nav_name'];
                $navigation[$previous_nav_id]['pages'][$key] = $row;
            }
        }
        return $navigation;
    }

    public static function clearCache()
    {
        FatCache::delete('headerNav');
        FatCache::delete('footerOneNav');
        FatCache::delete('footerTwoNav');
        FatCache::delete('footerThreeNav');
    }

}
