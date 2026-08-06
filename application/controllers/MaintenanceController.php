<?php

/**
 * Maintenance Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class MaintenanceController extends MyAppController
{

    /**
     * Initialize Maintenance
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Maintenance Page 
     */
    public function index()
    {
        if (FatApp::getConfig("CONF_MAINTENANCE", FatUtility::VAR_INT, 0) == 0) {
            FatApp::redirectUser(MyUtility::makeUrl('home'));
        }
        $this->set('maintenanceText', FatApp::getConfig("CONF_MAINTENANCE_TEXT_" . $this->siteLangId, FatUtility::VAR_STRING, ''));
        $this->_template->render();
    }

}
