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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" loading="async" defer>
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>" />
    <!-- FAVICON ================================================== -->
    <link rel="shortcut icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <link rel="apple-touch-icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <!-- CSS/JS ================================================== -->
    <?php
    $jsVariables = CommonHelper::htmlEntitiesDecode($jsVariables);
    $sslUsed = (FatApp::getConfig('CONF_USE_SSL', FatUtility::VAR_BOOLEAN, false)) ? 1 : 0;
    $websiteName = FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '');
    $mainDashboardClass = (($controllerName == 'Teacher' || $controllerName == 'Learner' || $controllerName == 'Affiliate') && $actionName == "index") ? "main-dashboard" : '';
    ?>
    <script type="text/javascript">
        const LEARNER = <?php echo User::LEARNER; ?>;
        const TEACHER = <?php echo User::TEACHER; ?>;
        <?php
        $googleAnalyticsEnabled = false;
        if (FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_HEAD_SCRIPT", null, '')) {
            $googleAnalyticsEnabled = true;
        }
        if (FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT", null, '')) {
            $googleAnalyticsEnabled = true;
        }
        ?>
        var googleAnalyticsEnabled = '<?php echo $googleAnalyticsEnabled; ?>';
        var langLbl = <?php echo json_encode(CommonHelper::htmlEntitiesDecode($jsVariables)) ?>;
        var layoutDirection = '<?php echo MyUtility::getLayoutDirection(); ?>';
        var SslUsed = '<?php echo $sslUsed; ?>';
        var userTimeZone = '<?php echo MyUtility::getSiteTimezone(); ?>';
        var timeZoneOffset = '<?php echo MyDate::getOffset(MyUtility::getSiteTimezone()); ?>';
        var cookieConsent = <?php echo json_encode($cookieConsent); ?>;
        var userType = <?php echo FatUtility::int($siteUserType); ?>;
        var ALERT_CLOSE_TIME = '<?php echo FatApp::getConfig("CONF_AUTO_CLOSE_ALERT_TIME", FatUtility::VAR_INT, 0); ?>';
        var confWebRootUrl = '<?php echo CONF_WEBROOT_URL; ?>';
        var confFrontEndUrl = '<?php echo CONF_WEBROOT_FRONTEND; ?>';
        var monthNames = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::getAllMonthName(false, $siteLangId))); ?>;
        var weekDayNames = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::dayNames(false, $siteLangId))); ?>;
        var meridiems = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::meridiems(false, $siteLangId))); ?>;
    </script>
    <?php
    echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
    if (isset($includeEditor) && $includeEditor) {
    ?>
        <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/innovaeditor.js"></script>
        <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/common/webfont.js"></script>
    <?php
    }
    if (FatApp::getConfig('CONF_ENABLE_PWA', FatUtility::VAR_BOOLEAN, false)) { ?>
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
            <?php if (!empty($messageData['msgs'][0] ?? '')) { ?>
                fcom.success('<?php echo $messageData['msgs'][0]; ?>');
            <?php }
            if (!empty($messageData['dialog'][0] ?? '')) { ?>
                fcom.warning('<?php echo $messageData['dialog'][0]; ?>');
            <?php }
            if (!empty($messageData['errs'][0] ?? '')) { ?>
                fcom.error('<?php echo $messageData['errs'][0]; ?>');
            <?php } ?>
        });
    </script>
</head>

<?php $isPreviewOn = MyUtility::isDemoUrl() ? 'is-preview-on' : ''; ?>
<?php
$dashboadClass = 'dashboard-learner';
if ($siteUserType == User::TEACHER) {
    $dashboadClass = 'dashboard-teacher';
} elseif ($siteUserType == User::AFFILIATE) {
    $dashboadClass = 'dashboard-affiliate';
}
?>

<body class="<?php echo $dashboadClass . ' ' . strtolower($controllerName) . ' ' . $mainDashboardClass . ' ' . $isPreviewOn; ?>">
    <?php
    if (MyUtility::isDemoUrl()) {
        include(CONF_INSTALLATION_PATH . 'public/demo-header.php');
    }
    if (isset($_SESSION['preview_theme'])) {
        $this->includeTemplate('header/preview.php', array(), false);
    }
    ?>
    <page class="page">
        <!-- [ HEADER ========= -->
        <?php if (!$courseQuiz) { ?>
            <header class="header nav-down">
                <div class="header-primary d-sm-flex justify-content-sm-between align-items-sm-center">
                    <div class="header-primary__right order-sm-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- [ USER ACCOUNT ========= -->
                            <div class="account">
                                <a href="#accout-target" class="avtar avtar--small avtar--round account__trigger trigger-js" data-title="<?php echo CommonHelper::getFirstChar($siteUser['user_first_name']); ?>">
                                    <img src="<?php echo MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $siteUserId, Afile::SIZE_SMALL), CONF_WEBROOT_FRONTEND) . '?' . time() ?>" alt="">
                                </a>
                                <div id="accout-target" class="account__target">
                                    <nav class="menu-vertical">
                                        <ul>
                                            <?php if ($siteUserType == User::TEACHER) { ?>
                                                <li class="menu__item <?php echo ("Teacher" == $controllerName) ? 'is-active' : ''; ?>"><a href="<?php echo MyUtility::makeUrl('Teacher', '', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_Dashboard'); ?></a></li>
                                                <li class="menu__item <?php echo ("Students" == $controllerName) ? 'is-active' : ''; ?>"><a href="<?php echo MyUtility::makeUrl('Students', '', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_My_Students'); ?></a></li>
                                                <li class="menu__item <?php echo ("Lessons" == $controllerName) ? 'is-active' : ''; ?>"><a href="<?php echo MyUtility::makeUrl('Lessons', '', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_Lessons'); ?></a></li>
                                            <?php
                                            }
                                            if ($siteUserType == User::LEARNER) {
                                            ?>
                                                <li class="menu__item <?php echo ("Learner" == $controllerName) ? 'is-active' : ''; ?>"><a href="<?php echo MyUtility::makeUrl('Learner', '', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_Dashboard'); ?></a></li>
                                                <li class="menu__item <?php echo ("Teachers" == $controllerName) ? 'is-active' : ''; ?>"><a href="<?php echo MyUtility::makeUrl('Teachers', '', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_My_Teachers'); ?></a></li>
                                                <li class="menu__item <?php echo ("Lessons" == $controllerName) ? 'is-active' : ''; ?>"><a href="<?php echo MyUtility::makeUrl('Lessons', '', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_Lessons'); ?></a></li>
                                            <?php }
                                            ?>
                                            <li class="menu__item <?php echo ("Account" == $controllerName && "profileInfo" == $action) ? 'is-active' : ''; ?>">
                                                <a href="<?php echo MyUtility::makeUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_Settings'); ?></a>
                                            </li>
                                            <li class="menu__item border-top mt-3">
                                                <a href="<?php echo MyUtility::makeUrl('Account', 'logout', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Label::getLabel('LBL_Logout'); ?></a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <!-- ] -->
                        </div>
                    </div>
                    <div class="header-primary__left order-sm-1">
                        <div class="d-sm-flex justify-content-sm-between align-items-sm-center">
                            <figure class="header-logo">
                                <a href="<?php echo MyUtility::makeUrl('', '', [], CONF_WEBROOT_FRONT_URL); ?>">
                                    <?php if (MyUtility::isDemoUrl()) { ?>
                                        <img src="<?php echo CONF_WEBROOT_FRONTEND . 'images/logo.svg'; ?>" alt="" />
                                    <?php } else { ?>
                                        <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', array(Afile::TYPE_FRONT_LOGO, 0, Afile::SIZE_LARGE), CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $websiteName; ?>">
                                    <?php } ?>
                                </a>
                            </figure>
                            <h1 class="page-title">
                                <?php echo $data['quilin_title'] ?>
                            </h1>
                        </div>
                    </div>

                </div>
            </header>
        <?php } ?>
        <!-- ] -->