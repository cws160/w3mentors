<?php

/**
 * This class is used to handle Url Rewrite
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SeoUrl extends MyAppModel
{

    const DB_TBL = 'tbl_seo_urls';
    const DB_TBL_PREFIX = 'seourl_';
    const HTTP_CODE_301 = 301;
    const HTTP_CODE_302 = 302;

    /**
     * Initialize SEO URL
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'seourl_id', $id);
    }

    /**
     * Get HTTP Codes
     * 
     * @return array
     */
    public static function getHttpCodes(): array
    {
        return [
            static::HTTP_CODE_301 => Label::getLabel('LBL_301_REDIRECT_PERMANENTLY'),
            static::HTTP_CODE_302 => Label::getLabel('LBL_302_REDIRECT_TEMPRARY'),
        ];
    }

    /**
     * Static Controllers
     * 
     * @return array
     */
    public static function staticControllers(): array
    {
        return array_merge(CONF_STATIC_FILE_CONTROLLERS, [
            'checkunique', 'public', 'pwa', 'js-css', 'common-rtl.css.map', 'image',
            'common.css.map', 'frontend.css.map', 'frontend-rtl.css.map'
        ]);
    }

    /**
     * Get Original Url
     * 
     * @param string $customUrl
     * @return null|array
     */
    public static function getOriginalUrl(string $customUrl, int $langId = null)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addFld(['seourl_lang_id', 'seourl_original', 'seourl_httpcode', 'count(*) as totalRecord']);
        $srch->addCondition('seourl_custom', '=', $customUrl);
        if (!empty($langId)) {
            $srch->addCondition('seourl_lang_id', '=', $langId);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $langCodes = Language::getCodes();
        $langId = FatApp::getConfig('CONF_DEFAULT_LANG');
        if (!CONF_LANGCODE_URL && !empty($_COOKIE['CONF_SITE_LANGUAGE'])) {
            $langId = $_COOKIE['CONF_SITE_LANGUAGE'];
        }
        unset($langCodes[$langId]);
        $langIds = array_keys($langCodes);
        $langIds = (count($langIds) > 0) ? ($langId . ',' . implode(",", $langIds)) : $langId;
        $rs = FatApp::getDb()->query($srch->getQuery() . ' ORDER BY FIELD(`seourl_lang_id`, ' . $langIds . ') ASC');
        return FatApp::getDb()->fetch($rs);
    }

    /**
     * Get Custom Url
     * 
     * @param int $langId
     * @param string $originalUrl
     * @return null|array
     */
    public static function getCustomUrl(int $langId, string $originalUrl)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addFld(['seourl_custom', 'seourl_httpcode']);
        $srch->addCondition('seourl_original', '=', $originalUrl);
        $srch->addCondition('seourl_lang_id', '=', $langId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $langCodes = Language::getCodes();
        $langId = FatApp::getConfig('CONF_DEFAULT_LANG');
        if (!CONF_LANGCODE_URL && !empty($_COOKIE['CONF_SITE_LANGUAGE'])) {
            $langId = $_COOKIE['CONF_SITE_LANGUAGE'];
        }
        unset($langCodes[$langId]);
        $langIds = array_keys($langCodes);
        $langIds = (count($langIds) > 0) ? ($langId . ',' . implode(",", $langIds)) : $langId;
        $rs = FatApp::getDb()->query($srch->getQuery() . ' ORDER BY FIELD(`seourl_lang_id`, ' . $langIds . ')');
        return FatApp::getDb()->fetch($rs);
    }

    /**
     * Get Canonical URL
     * 
     * @return type
     */
    public static function getCanonicalUrl()
    {
        return trim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], "/");
    }

}
