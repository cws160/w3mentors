<?php

/**
 * Home Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class HomeController extends AccountController
{

    /**
     * Initialize Home
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        parent::index();
    }

    public function slug()
    {
        $slug = FatApp::getPostedData('slug');
        $slug = MyUtility::createSlug($slug);
        FatUtility::dieJsonSuccess(['slug' => $slug]);
    }

}
