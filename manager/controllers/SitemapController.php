<?php

/**
 * Sitemap Controller is used to handle Sitemaps
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SitemapController extends AdminBaseController
{

    /**
     * Initialize Sitemap
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSiteMap();
    }

    /**
     * Generate Sitemap
     */
    public function generate()
    {
        define('SYSTEM_FRONT', true);
        $this->objPrivilege->canEditSiteMap();
        $this->startSitemapXml();
        $urls = Sitemap::getUrls($this->siteLangId);
        foreach ($urls as $languageUrls) {
            foreach ($languageUrls as $url) {
                foreach ($url as $val) {
                    $this->writeSitemapUrl($val['url'], $val['frequency']);
                }
            }
        }
        $this->endSitemapXml();
        $this->writeSitemapIndex();
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SITEMAP_HAS_BEEN_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Start Sitemap XML
     */
    private function startSitemapXml()
    {
        ob_start();
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    }

    /**
     * Write Sitemap Url
     * 
     * @staticvar int $sitemap_i
     * 
     * @param type $url
     * @param type $freq
     */
    private function writeSitemapUrl($url, $freq)
    {
        static $sitemap_i;
        $sitemap_i++;
        if ($sitemap_i > 2000) {
            $sitemap_i = 1;
            $this->endSitemapXml();
            $this->startSitemapXml();
        }
        echo "<url>
                <loc>" . $url . "</loc>
                <lastmod>" . date('Y-m-d') . "</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.8</priority>
            </url>";
        echo "\n";
    }

    /**
     * End Sitemap XML
     * 
     * @global type $sitemapListInc
     */
    private function endSitemapXml()
    {
        global $sitemapListInc;
        $sitemapListInc++;
        echo '</urlset>' . "\n";
        $contents = ob_get_clean();
        $rs = '';
        MyUtility::writeFile('sitemap/list_' . $sitemapListInc . '.xml', $contents, $rs);
    }

    /**
     * Write Sitemap Index
     * 
     * @global type $sitemapListInc
     */
    private function writeSitemapIndex()
    {
        global $sitemapListInc;
        ob_start();
        echo "<?xml version='1.0' encoding='utf-8' standalone='no' ?>
		<sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
        for ($i = 1; $i <= $sitemapListInc; $i++) {
            echo "<sitemap><loc>" . MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL) . "sitemap/list_" . $i . ".xml</loc></sitemap>\n";
        }
        echo "</sitemapindex>";
        $contents = ob_get_clean();
        $rs = '';
        MyUtility::writeFile('sitemap.xml', $contents, $rs);
    }

}
