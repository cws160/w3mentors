<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$stickyDemoHeader = MyUtility::isDemoUrl() ? 'sticky-demo-header' : '';
CommonHelper::setRefererPageUrl();
?>
<!doctype html>
<html lang="en" dir="<?php echo $siteLanguage['language_direction']; ?>" theme="<?php echo $activeTheme ?? ''; ?>" class="<?php echo $stickyDemoHeader; ?>">

<head>
    <!-- Basic Page Needs ======================== -->
    <meta charset="utf-8">
    <?php echo $this->writeMetaTags(); ?>
    <!-- MOBILE SPECIFIC METAS ===================== -->
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=2,user-scalable=1" />
    <!-- FONTS ================================================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>" />
    <!-- FAVICON ================================================== -->
    <link rel="shortcut icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <link rel="apple-touch-icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <!-- CSS/JS ================================================== -->
    <?php
    $jsVariables = CommonHelper::htmlEntitiesDecode($jsVariables);
    $sslUsed = (FatApp::getConfig('CONF_USE_SSL', FatUtility::VAR_BOOLEAN, false)) ? 1 : 0;
    $mainDashboardClass = (($controllerName == 'Teacher' || $controllerName == 'Learner') && $actionName == "index") ? "main-dashboard" : '';
    ?>
    <script type="text/javascript">
        var langLbl = <?php echo json_encode(CommonHelper::htmlEntitiesDecode($jsVariables)) ?>;
        var layoutDirection = '<?php echo MyUtility::getLayoutDirection(); ?>';
        var SslUsed = '<?php echo $sslUsed; ?>';
        var userTimeZone = '<?php echo MyUtility::getSiteTimezone(); ?>';
        var timeZoneOffset = '<?php echo MyDate::getOffset(MyUtility::getSiteTimezone()); ?>';
        var cookieConsent = <?php echo json_encode($cookieConsent); ?>;
        var userType = <?php echo FatUtility::int($siteUserType); ?>;
        const LEARNER = <?php echo User::LEARNER; ?>;
        const TEACHER = <?php echo User::TEACHER; ?>;
        const ALERT_CLOSE_TIME = '<?php echo FatApp::getConfig("CONF_AUTO_CLOSE_ALERT_TIME", FatUtility::VAR_INT, 0); ?>';
        const confWebRootUrl = '<?php echo CONF_WEBROOT_URL; ?>';
        const confFrontEndUrl = '<?php echo CONF_WEBROOT_FRONTEND; ?>';
        var monthNames = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::getAllMonthName(false, $siteLangId))); ?>;
        var weekDayNames = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::dayNames(false, $siteLangId))); ?>;
        var meridiems = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::meridiems(false, $siteLangId))); ?>;
    </script>
    <?php
    echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
    if (FatApp::getConfig('CONF_ENABLE_PWA', FatUtility::VAR_BOOLEAN, false)) {
    ?>
        <link rel="manifest" href="<?php echo MyUtility::makeUrl('Pwa', '', [], CONF_WEBROOT_FRONTEND); ?>">
        <script>
            if ("serviceWorker" in navigator) {
                navigator.serviceWorker.register("<?php echo CONF_WEBROOT_FRONTEND; ?>sw.js");
            }
        </script>
    <?php } ?>
    <?php echo Common::setThemeColorStyle(true); ?>
    <script>
        $(document).ready(function() {
            <?php if ($siteUserId > 0) { ?>
                setTimeout(getBadgeCount(), 1000);
            <?php }
            if (!empty($messageData['msgs'][0] ?? '')) {
            ?>
                fcom.success('<?php echo $messageData['msgs'][0]; ?>');
            <?php }
            if (!empty($messageData['dialog'][0] ?? '')) {
            ?>
                fcom.warning('<?php echo $messageData['dialog'][0]; ?>');
            <?php }
            if (!empty($messageData['errs'][0] ?? '')) {
            ?>
                fcom.error('<?php echo $messageData['errs'][0]; ?>');
            <?php } ?>
        });
    </script>
</head>
<?php $isPreviewOn = MyUtility::isDemoUrl() ? 'is-preview-on' : ''; ?>

<body class="course-leaner <?php echo $isPreviewOn; ?>">
    <?php
    if (MyUtility::isDemoUrl()) {
        include(CONF_INSTALLATION_PATH . 'public/demo-header.php');
    }
    if (isset($_SESSION['preview_theme'])) {
        $this->includeTemplate('header/preview.php', array(), false);
    }
    ?>
    <page class="page">
        <main class="page-container">