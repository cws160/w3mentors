<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'conf-common.php';

define('CONF_APPLICATION_PATH', CONF_INSTALLATION_PATH . 'manager/');
define('CONF_FRONT_END_APPLICATION_PATH', CONF_INSTALLATION_PATH . CONF_FRONT_END_APPLICATION_DIR);
define('CONF_FRONT_END_THEME_PATH', CONF_FRONT_END_APPLICATION_PATH . 'views/');
$conf_webroot_frontend = CONF_WEBROOT_FRONTEND;
$conf_webroot_url_dir = 'admin/';
define('CONF_WEBROOT_URL', CONF_WEBROOT_BACKEND);
define('CONF_WEBROOT_FRONT_URL', CONF_WEBROOT_FRONTEND);
define('CONF_WEBROOT_URL_TRADITIONAL', $conf_webroot_frontend . 'public/admin.php?url=');
define('CONF_HTML_EDITOR', 'innova');
define('CONF_FAT_CACHE_DIR', CONF_INSTALLATION_PATH . 'public/cache/');
define('CONF_FAT_CACHE_URL', CONF_WEBROOT_FRONTEND . 'cache/');
