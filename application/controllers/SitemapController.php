<?php
/**
 * Sitemap Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SitemapController extends MyAppController
{

    /**
     * Initialize Sitemap
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Sitemap
     */
    public function index()
    {
        $this->set('urls', Sitemap::getUrls($this->siteLangId));
        $this->_template->render();
    }

}
