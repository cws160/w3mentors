<?php

/**
 * Mobile Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class MobileController extends MyAppController
{

    /**
     * Initialize
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $this->_template->render(false, false);
    }

}
