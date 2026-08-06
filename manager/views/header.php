<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($includeEditor) && true === $includeEditor) {
    $extendEditorJs = 'true';
} else {
    $extendEditorJs = 'false';
    $includeEditor = false;
}
$commonHeadData = [
    'siteLangId' => $siteLangId,
    'jsVariables' => $jsVariables,
    'extendEditorJs' => $extendEditorJs,
    'includeEditor' => $includeEditor,
    'controllerName' => $controllerName,
    'layoutDirection' => MyUtility::getLayoutDirection()
];
if (!empty($favIconFile)) {
    $commonHeadData['favIconFile'] = $favIconFile;
}
$this->includeTemplate('_partial/header/common-head.php', $commonHeadData, false);
echo $this->writeMetaTags();
echo $this->getJsCssIncludeHtml(false);
$commonHeadHtmlData = ['bodyClass' => $bodyClass, 'includeEditor' => $includeEditor, 'siteLanguage' => $siteLanguage];
$this->includeTemplate('_partial/header/common-header-html.php', $commonHeadHtmlData, false);
if (AdminAuth::isAdminLogged()) {
    $admin = Admin::getAttributesById(AdminAuth::getLoggedAdminId(), ['admin_name','admin_email']);
    $this->includeTemplate('_partial/header/logged-user-header.php', [
        'adminName' => $admin['admin_name'],
        'adminEmail' => $admin['admin_email'],
        'siteLangId' => $siteLangId,
        'siteLanguages' => $siteLanguages,
        'controllerName' => $controllerName,
        'adminLoggedId' => AdminAuth::getLoggedAdminId(),
        'actionName' => $actionName,
        'regendatedtime' => $regendatedtime ?? '',
        'pageText' => $pageText ?? '',
    ]);
}
