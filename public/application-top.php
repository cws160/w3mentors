<?php

/* Turn on output buffering */
if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip')) {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

/* Set Error reporting */
ini_set('display_errors', CONF_DEVELOPMENT_MODE);
error_reporting(CONF_DEVELOPMENT_MODE ? E_ALL : E_ALL & ~E_NOTICE & ~E_WARNING);

/* Load Fatbit & Vendor Packages */
require_once CONF_INSTALLATION_PATH . 'library/autoloader.php';
require_once CONF_INSTALLATION_PATH . 'vendor/autoload.php';

/* Register S3 Client Streaming */
Afile::registerS3ClientStream();

/* Check and Redirect to https */
if ((($_SERVER['HTTPS'] ?? '') != 'on' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') != 'https') && (FatApp::getConfig('CONF_USE_SSL') == 1)) {
    FatApp::redirectUser('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
}

/* Set timezone of SERVER & MySql Database */
date_default_timezone_set('UTC');
$query = "SET NAMES utf8mb4; SET time_zone = '" . date('P') . "';";
$dbCon = FatApp::getDb()->getConnectionObject();
if ($dbCon->multi_query($query)) {
    while ($dbCon->next_result()) {
        if (!$dbCon->more_results()) {
            break;
        }
    }
}
/* Session Cookies and Start session */
CommonHelper::setSeesionCookieParams();
session_start();
define('SYSTEM_INIT', true);
define('CONF_DEFAULT_LANG', FatApp::getConfig('CONF_DEFAULT_LANG'));
define('CONF_LANGCODE_URL', FatApp::getConfig('CONF_LANGCODE_URL'));
define('WHITE_LABELED', false);