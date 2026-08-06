<?php

/**
 * Mobile Apps Controller is used for Mobile Apps
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AppPackagesController extends AdminBaseController
{

    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAppPackages();
    }

    public function index()
    {
        $this->set('rows', MyUtility::getApps());
        $this->_template->render();
    }

}
