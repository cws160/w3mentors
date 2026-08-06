<?php

/**
 * A Common Utility Class 
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Common
{

    public static function setHeaderBreadCrumb($template)
    {
        $controllerName = FatApp::getController();
        $controller = new $controllerName('');
        $template->set('nodes', $controller->getBreadcrumbNodes(FatApp::getAction()));
    }

    public static function setLeftNavigationVals($template)
    {
        $name = Admin::getAttributesById(AdminAuth::getLoggedAdminId(), 'admin_name');
        $template->set('objPrivilege', AdminPrivilege::getInstance());
        $template->set('adminName', $name ?? '');
    }

    public static function isSetCookie($cookieName)
    {
        if (isset($_COOKIE[$cookieName])) {
            return true;
        }
        return false;
    }


}
