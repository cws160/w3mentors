<?php

/**
 * Error Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ErrorController extends MyAppController
{

    /**
     * Initialize Error
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $this->_template->render();
    }

}
