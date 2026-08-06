<?php

require_once dirname(__DIR__) . '/conf/conf-admin.php';
require_once dirname(__FILE__) . '/application-top.php';
FatApp::unregisterGlobals();
if (file_exists(CONF_APPLICATION_PATH . 'utilities/prehook.php')) {
    require_once CONF_APPLICATION_PATH . 'utilities/prehook.php';
}
FatApplication::getInstance()->callHook();
