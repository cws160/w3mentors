<?php

/**
 * A Common Sitemap Utility  
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Sitemap
{

    /**
     * Get URLs
     * 
     * @param int $langId
     * @return array
     */
    public static function getUrls(int $langId)
    {
        $domain = MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONTEND);
        $sitemapUrls = [];
        $srch = new TeacherSearch($langId, 0, User::LEARNER);
        $srch->addMultipleFields(['user_username', 'user_first_name', 'user_last_name']);
        $srch->applyPrimaryConditions();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(2000);
        $resultSet = $srch->getResultSet();
        $urls = [];
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            array_push($urls, [
                'value' => $row['user_first_name'] . ' ' . $row['user_last_name'],
                'frequency' => 'weekly',
                'type' => 'internal',
                'org_url' => MyUtility::makeFullUrl('Teachers', 'view', [$row['user_username']], CONF_WEBROOT_FRONT_URL),
                'url' => MyUtility::generateFullUrl('Teachers', 'view', [$row['user_username']], CONF_WEBROOT_FRONT_URL),
                'urlParams' => ['Teachers', 'view', $row['user_username']],
            ]);
        }
        $sitemapUrls = array_merge($sitemapUrls, [Label::getLabel('LBL_TEACHERS') => $urls]);
        if (GroupClass::isEnabled()) {
            $srch = new GroupClassSearch($langId, 0, User::LEARNER);
            $srch->addMultipleFields(['grpcls_id', 'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title', 'grpcls_slug']);
            $srch->applyPrimaryConditions();
            $srch->applySearchConditions([]);
            $srch->addOrder('grpcls_start_datetime', 'asc');
            $srch->doNotCalculateRecords();
            $srch->setPageSize(2000);
            $resultSet = $srch->getResultSet();
            $urls = [];
            while ($row = FatApp::getDb()->fetch($resultSet)) {
                array_push($urls, [
                    'value' => $row['grpcls_title'],
                    'frequency' => 'weekly',
                    'type' => 'internal',
                    'org_url' => MyUtility::makeFullUrl('GroupClasses', 'view', [$row['grpcls_slug']], CONF_WEBROOT_FRONT_URL),
                    'url' => MyUtility::generateFullUrl('GroupClasses', 'view', [$row['grpcls_slug']], CONF_WEBROOT_FRONT_URL),
                    'urlParams' => ['GroupClasses', 'view', $row['grpcls_slug']],
                ]);
            }
            $sitemapUrls = array_merge($sitemapUrls, [Label::getLabel('LBL_GROUP_CLASSES') => $urls]);
        }
        /* ] */
        if (Course::isEnabled()) {
            /* Courses [ */
            $srch = new CourseSearch($langId, 0, User::LEARNER);
            $srch->addMultipleFields(['course_slug', 'course_title']);
            $srch->applyPrimaryConditions();
            $srch->addCondition('teacher.user_username', '!=', '');
            $srch->addCondition('course_status', '=', Course::PUBLISHED);
            $srch->addCondition('course_active', '=', AppConstant::ACTIVE);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(2000);
            $resultSet = $srch->getResultSet();
            $urls = [];
            while ($row = FatApp::getDb()->fetch($resultSet)) {
                array_push($urls, [
                    'value' => $row['course_title'],
                    'frequency' => 'weekly',
                    'type' => 'internal',
                    'org_url' => MyUtility::makeFullUrl('Courses', 'view', [$row['course_slug']], CONF_WEBROOT_FRONT_URL),
                    'url' => MyUtility::generateFullUrl('Courses', 'view', [$row['course_slug']], CONF_WEBROOT_FRONT_URL),
                    'urlParams' => ['Courses', 'view', $row['course_slug']],
                ]);
            }
            $sitemapUrls = array_merge($sitemapUrls, [Label::getLabel('LBL_COURSES') => $urls]);
            /* ] */
        }
        /* CMS Pages [ */
        $srch = Navigations::getLinkSearchObj($langId);
        $srch->addCondition('nlink_deleted', '=', AppConstant::NO);
        $srch->addCondition('nav_active', '=', AppConstant::ACTIVE);
        $srch->addMultipleFields(['nav_id', 'nlink_type', 'nlink_cpage_id', 'nlink_url', 'IFNULL(nlink_caption, nlink_identifier) as nlink_identifier']);
        $srch->addOrder('nlink_order', 'ASC');
        $srch->addGroupBy('nlink_cpage_id');
        $srch->addGroupBy('nlink_url');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(2000);
        $resultSet = $srch->getResultSet();
        $urls = [];
        while ($link = FatApp::getDb()->fetch($resultSet)) {
            if ($link['nlink_type'] == NavigationLinks::NAVLINK_TYPE_CMS && $link['nlink_cpage_id']) {
                array_push($urls, [
                    'value' => $link['nlink_identifier'],
                    'frequency' => 'monthly',
                    'type' => 'internal',
                    'org_url' => MyUtility::makeFullUrl('Cms', 'view', [$link['nlink_cpage_id']], CONF_WEBROOT_FRONT_URL),
                    'url' => MyUtility::generateFullUrl('Cms', 'view', [$link['nlink_cpage_id']], CONF_WEBROOT_FRONT_URL),
                    'urlParams' => ['Cms', 'view', $link['nlink_cpage_id']],
                ]);
            } elseif ($link['nlink_type'] == NavigationLinks::NAVLINK_TYPE_EXTERNAL_PAGE) {
                $url = $orgurl = $link['nlink_url'];
                if (str_contains(strtolower($link['nlink_url']), '{domain}')) {
                    $baseUrl = MyUtility::generateFullUrl('', '', [], CONF_WEBROOT_FRONT_URL);
                    $baseOrgUrl = MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL);
                    $url = str_replace(['{DOMAIN}', '{domain}'], [$baseUrl, $baseUrl], $link['nlink_url']);
                    $orgurl = str_replace(['{DOMAIN}', '{domain}'], [$baseOrgUrl, $baseOrgUrl], $link['nlink_url']);
                    $url = CommonHelper::processURLString($url);
                }
                array_push($urls, [
                    'url' => $url,
                    'org_url' => $orgurl,
                    'type' => 'external',
                    'value' => $link['nlink_identifier'],
                    'frequency' => 'monthly'
                ]);
            }
        }
        $sitemap = array_merge($sitemapUrls, [Label::getLabel('LBL_CMS_PAGES') => $urls]);
        
        $languages =  Language::getAllNames(false, 0);
        $sitemapUrls = [$sitemap];
        
        if (!CONF_LANGCODE_URL) {
            return $sitemapUrls;
        }
        
        $urls = [];
        unset($languages[$langId]);
        foreach ($languages as $lang) {
            foreach ($sitemap as  $key => $url) {
                $urls[$lang['language_id']][$key] = [];
                foreach ($url as $val) {
                    MyUtility::setSiteLanguage($lang);
                    if ($val['type'] == 'internal' || str_contains($val['org_url'], $domain)) {
                        if(isset($val['urlParams'])) {
                            $val['url'] = MyUtility::generateFullUrl($val['urlParams'][0], $val['urlParams'][1], [$val['urlParams'][2]], CONF_WEBROOT_FRONTEND);
                        } else {
                            $val['url'] = MyUtility::generateFullUrl(str_replace($domain, '', $val['org_url']), '', [], CONF_WEBROOT_FRONTEND);
                        }
                    }
                    $urls[$lang['language_id']][$key][] = $val;
                }
            }
        }
        MyUtility::setSiteLanguage(['language_id' => $langId]);
        foreach ($urls as $key => $data) {
            $sitemapUrls = array_merge($sitemapUrls, [$languages[$key]['language_name'] => $data]);
        }
        return $sitemapUrls;
    }
}
