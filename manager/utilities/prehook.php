<?php

define('CONF_FORM_ERROR_DISPLAY_TYPE', Form::FORM_ERROR_TYPE_AFTER_FIELD);
define('CONF_FORM_REQUIRED_STAR_WITH', Form::FORM_REQUIRED_STAR_WITH_CAPTION);
define('CONF_FORM_REQUIRED_STAR_POSITION', Form::FORM_REQUIRED_STAR_POSITION_AFTER);
define('CONF_STATIC_FILE_CONTROLLERS', ['fonts', 'images', 'js', 'img', 'innovas', 'assetmanager', 'cache']);
FatApplication::getInstance()->setControllersForStaticFileServer(CONF_STATIC_FILE_CONTROLLERS);
$innova_settings = [
    'width' => '"100%"', 'height' => '"400px"', 'arrStyle' => '[["body",false,"","min-height:250px;"]]',
    'groups' => ' [
        ["group1", "", ["Bold", "Italic", "Underline", "Strikethrough", "ForeColor", "TextDialog", "FontSize","RemoveFormat"]],
        ["group2", "", ["JustifyLeft", "JustifyCenter", "JustifyRight", "Bullets", "Numbering"]],
        ["group3", "", ["LinkDialog", "TableDialog", "ImageDialog"]],
        ["group4", "", ["Undo", "Redo", "SearchDialog", "SourceDialog"]],
    ]',
    'fileBrowser' => '"' . CONF_WEBROOT_URL . 'innova/assetmanager/asset.php"',
    'css' => '"' . CONF_WEBROOT_URL . 'innovas/styles/default.css"'
];
FatApp::setViewDataProvider('_partial/header/left-navigation.php', ['Common', 'setLeftNavigationVals']);
FatApp::setViewDataProvider('_partial/header/logged-user-header.php', ['Common', 'setLeftNavigationVals']);
FatApp::setViewDataProvider('_partial/header/header-breadcrumb.php', ['Common', 'setHeaderBreadCrumb']);
