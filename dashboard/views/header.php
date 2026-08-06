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
    $mainDashboardClass = (($controllerName == 'Teacher' || $controllerName == 'Learner' || $controllerName == 'Affiliate') && $actionName == "index") ? "main-dashboard" : '';
    ?>

    <script type="text/javascript">
        const LEARNER = <?php echo User::LEARNER; ?>;
        const TEACHER = <?php echo User::TEACHER; ?>;
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
        var tFmt = '<?php echo MyDate::getFormatTime(); ?>';
        var tFmtSec = '<?php echo MyDate::getFormatTime(false, false); ?>';
        var tFmtJs = '<?php echo MyDate::getFormatTime(true); ?>';
        var tFmtSecJs = '<?php echo MyDate::getFormatTime(true, false); ?>';

        var dayShortNames = weekDayNames.shortName.slice(0);
        var lastValue = dayShortNames[6];
        dayShortNames.pop();
        dayShortNames.unshift(lastValue);

        var dayLongNames = weekDayNames.longName.slice(0);
        var lastValueLong = dayLongNames[6];
        dayLongNames.pop();
        dayLongNames.unshift(lastValueLong);

        AM_LABEL = "<?php echo Label::getLabel('LBL_AM'); ?>";
        PM_LABEL = "<?php echo Label::getLabel('LBL_PM'); ?>";
    </script>

    <?php
    echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
    if (isset($includeEditor) && $includeEditor) {
    ?>
        <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/innovaeditor.js"></script>
        <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/common/webfont.js"></script>
    <?php
    }
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
    <?php
    $GA4 = 0;
    echo "<!-- Google tag Manager Head Script -->\r\n";
    if (FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_HEAD_SCRIPT", null, '')) {
        echo FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_HEAD_SCRIPT", null, '');
        $GA4 = 1;
    }
    ?>
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
    if (FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT", null, '')) {
        echo FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT", null, '');
        $GA4 = 1;
    }
    if (MyUtility::isDemoUrl()) {
        include(CONF_INSTALLATION_PATH . 'public/demo-header.php');
    }
    if (isset($_SESSION['preview_theme'])) {
        $this->includeTemplate('header/preview.php', array(), false);
    }
    ?>
    <script>
        var GA4 = <?php echo $GA4; ?>;
    </script>
    <div class="site">
        <!-- [ SIDE BAR ========= -->
        <aside class="sidebar">
            <!-- [ SIDE BAR SECONDARY ========= -->
            <div class="sidebar__secondary">
                <nav class="menu menu--secondary">
                    <ul>
                        <li class="menu__item menu__item-toggle">
                            <a href="#primary-nav" class="menu__item-trigger trigger-js for-responsive" title="<?php echo Label::getLabel('LBL_MENU'); ?>">
                                <span class="icon icon--menu">
                                    <span class="toggle"><span></span></span>
                                </span>
                                <span class="sr-only"><?php echo Label::getLabel('LBL_MENU'); ?></span>
                            </a>
                            <a href="#sidebar__primary" class="menu__item-trigger fullview-js for-desktop" title="<?php echo Label::getLabel('LBL_MENU'); ?>">
                                <span class="icon icon--menu"><span class="toggle"><span></span></span></span>
                                <span class="sr-only"><?php echo Label::getLabel('LBL_MENU'); ?></span>
                            </a>
                        </li>
                        <li class="menu__item menu__item-home">
                            <a href="<?php echo MyUtility::makeUrl('Account'); ?>" class="menu__item-trigger" title="<?php echo Label::getLabel('LBL_HOME'); ?>">
                                <svg class="icon icon--home">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#home'; ?>"></use>
                                </svg>
                                <span class="sr-only"><?php echo Label::getLabel('LBL_HOME'); ?></span>
                            </a>
                        </li>
                        <?php if ($siteUserType != User::AFFILIATE) { ?>
                            <li class="menu__item menu__item-messaging  <?php echo ($controllerName == 'Chats') ? 'is-active' : ''; ?>">
                                <a href="<?php echo MyUtility::makeUrl('Chats'); ?>" class="menu__item-trigger message-badge" title="<?php echo Label::getLabel('LBL_MESSAGING'); ?>">
                                    <!-- add  data-count="{count}" if any unread message -->
                                    <svg class="icon icon--messaging">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#message'; ?>"></use>
                                    </svg>
                                    <span class="sr-only"><?php echo Label::getLabel('LBL_MESSAGING'); ?></span>
                                </a>
                            </li>
                        <?php } ?>
                        <li class="menu__item menu__item-notifications <?php echo ($controllerName == 'Notifications') ? 'is-active' : ''; ?> ">
                            <a href="<?php echo MyUtility::makeUrl('Notifications'); ?>" class="menu__item-trigger notification-badge" title="<?php echo Label::getLabel('LBL_NOTIFICATIONS'); ?>">
                                <!-- add  data-count="{count}" if any unread Notificatons -->
                                <svg class="icon icon--notificatons">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#notification'; ?>"></use>
                                </svg>
                                <span class="sr-only"><?php echo Label::getLabel('LBL_NOTIFICATIONS'); ?></span>
                            </a>
                        </li>
                        <?php if (!empty($siteLanguages) || !empty($siteCurrencies)) { ?>
                            <li class="menu__item menu__item-languages">
                                <a href="#languages-nav" class="menu__item-trigger trigger-js" title="<?php echo Label::getLabel('LBL_LANGUAGES/CURRENCIES'); ?>">
                                    <svg class="icon icon--lang">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#language'; ?>"></use>
                                    </svg>
                                    <span class="sr-only"><?php echo $siteLanguage['language_code'] . ' - ' . $siteCurrency['currency_code']; ?></span>
                                </a>
                                <div id="languages-nav" class="menu__dropdown">
                                    <div class="menu__dropdown-head">
                                        <span class="uppercase small bold-600"><?php echo Label::getLabel('LBL_CHANGE_LANGUAGES'); ?></span>
                                    </div>
                                    <div class="menu__dropdown-body">
                                        <nav class="menu menu--inline">
                                            <ul>
                                                <?php foreach ($siteLanguages as $language) { ?>
                                                    <li class="menu__item <?php echo ($siteLangId == $language['language_id']) ? 'is-active' : ''; ?>">
                                                        <a href="javascript:void(0)" <?php echo ($siteLangId != $language['language_id']) ? 'onclick="setSiteLanguage(' . $language['language_id'] . ')"' : ''; ?>><?php echo $language['language_name']; ?></a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                            <hr />
                                            <ul>
                                                <?php foreach ($siteCurrencies as $currency) { ?>
                                                    <li class="menu__item <?php echo ($siteCurrency['currency_id'] == $currency['currency_id']) ? 'is-active' : ''; ?>">
                                                        <a <?php echo ($siteCurrency['currency_id'] != $currency['currency_id']) ? 'onclick="setSiteCurrency(' . $currency['currency_id'] . ')"' : ''; ?> href="javascript:void(0);"><?php echo $currency['currency_code']; ?></a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </li>
                        <?php } ?>
                        <?php if ($siteUserType == User::LEARNER) { ?>
                            <li class="menu__item menu__item-favorites <?php echo ($controllerName == 'Learner' && $actionName == 'favourites') ? 'is-active' : ''; ?>">
                                <a href="<?php echo MyUtility::makeUrl('Learner', 'favourites'); ?>" class="menu__item-trigger" title="<?php echo Label::getLabel('LBL_FAVORITES'); ?>">
                                    <svg class="icon icon--favorites">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#favorite'; ?>"></use>
                                    </svg>
                                    <span class="sr-only"><?php echo Label::getLabel('LBL_FAVORITES'); ?></span>
                                </a>
                            </li>
                        <?php } ?>
                        <li class="menu__item menu__item-logout">
                            <a href="<?php echo MyUtility::makeUrl('Account', 'logout', [], CONF_WEBROOT_DASHBOARD); ?>" class="menu__item-trigger" title="<?php echo Label::getLabel('LBL_LOGOUT'); ?>">
                                <svg class="icon icon--logout">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#logout'; ?>"></use>
                                </svg>
                                <span class="sr-only"><?php echo Label::getLabel('LBL_LOGOUT'); ?></span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <!-- ] -->
            <!-- [ SIDE BAR PRIMARY ========= -->
            <div id="sidebar__primary" class="sidebar__primary">
                <div class="sidebar__head">
                    <figure class="logo">
                        <a href="<?php echo MyUtility::makeUrl('', '', [], CONF_WEBROOT_FRONT_URL); ?>">
                            <?php echo MyUtility::getLogo(); ?>
                        </a>
                    </figure>
                    <?php if (!isset($flashcardSrchFrm)) { ?>
                        <!-- [ PROFILE ========= -->
                        <div class="profile">
                            <a href="#profile-target" class="trigger-js profile__trigger">
                                <div class="profile__meta d-flex align-items-center">
                                    <div class="profile__media me-3">
                                        <div class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($siteUser['user_first_name']); ?>">
                                            <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $siteUserId, Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '?t=' . time() . '" alt="' . $siteUser['user_first_name'] . '" />'; ?>
                                        </div>
                                    </div>
                                    <div class="profile__details d-none d-md-block">
                                        <h6 class="profile__title"><?php echo $siteUser['user_first_name'] . ' ' . $siteUser['user_last_name']; ?></h6>
                                        <?php $loggedAs = ($siteUserType == User::TEACHER) ? 'LBL_LOGGED_IN_AS_A_TEACHER' : (($siteUserType == User::AFFILIATE) ? 'LBL_LOGGED_IN_AS_A_AFFILIATE' : 'LBL_LOGGED_IN_AS_A_LEARNER'); ?>
                                        <small class="color-black"><?php echo Label::getLabel($loggedAs); ?></small>
                                    </div>
                                </div>
                            </a>
                            <div id="profile-target" class="profile__target">
                                <div class="profile__target-details">
                                    <div class="d-md-none">
                                        <h6 class="profile__title"><?php echo $siteUser['user_first_name'] . ' ' . $siteUser['user_last_name']; ?></h6>
                                        <?php $loggedAs = ($siteUserType == User::TEACHER) ? 'LBL_LOGGED_IN_AS_A_TEACHER' : (($siteUserType == User::AFFILIATE) ? 'LBL_LOGGED_IN_AS_A_AFFILIATE' : 'LBL_LOGGED_IN_AS_A_LEARNER'); ?>
                                        <small class="color-black"><?php echo Label::getLabel($loggedAs); ?></small>
                                    </div>
                                    <table>
                                        <?php if (!empty($siteUser['country_name'])) { ?>
                                            <tr>
                                                <th><?php echo label::getLabel('LBL_TIME_ZONE'); ?></th>
                                                <td>
                                                    <?php echo MyDate::formatDate(date('Y-m-d H:i:s'), MyDate::getFormatTime(), $siteUser['user_timezone']); ?>
                                                    (<?php echo Label::getLabel('LBL_UTC') . " " . (new DateTime("now", new DateTimeZone($siteUser['user_timezone'])))->format('P'); ?>)
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <th><?php echo label::getLabel('LBL_TIME_ZONE'); ?></th>
                                            <td>
                                                <?php echo MyDate::formatDate(date('Y-m-d H:i:s'), 'H:i', $siteUser['user_timezone']); ?>
                                                (<?php echo Label::getLabel('LBL_UTC') . " " . (new DateTime("now", new DateTimeZone($siteUser['user_timezone'])))->format('P'); ?>)
                                            </td>
                                        </tr>
                                    </table>
                                    <span class="-gap-10"></span>
                                    <div class="btns-group">
                                        <?php if ($siteUserType == User::TEACHER) { ?>
                                            <?php if (!empty($profileProgress['isProfileCompleted'])) { ?>
                                                <a href="<?php echo MyUtility::makeFullUrl('teachers', 'view', [$siteUser['user_username']], CONF_WEBROOT_FRONTEND); ?>" class="btn btn--bordered color-third btn--block mt-2"><?php echo label::getLabel('LBL_View_Public_Profile'); ?></a>
                                            <?php } ?>
                                            <a href="javascript:void(0);" onclick="switchProfile('<?php echo User::LEARNER ?>');" class="btn btn--third btn--block mt-3"><?php echo label::getLabel('LBL_Switch_to_Learner_Profile'); ?></a>
                                        <?php
                                        }
                                        if ($siteUserType == User::LEARNER && ($siteUser['user_is_teacher'] == AppConstant::YES || $siteUser['user_registered_as'] == User::TEACHER)) {
                                        ?>
                                            <a href="javascript:void(0);" onclick="switchProfile('<?php echo User::TEACHER ?>');" class="btn btn--third btn--block mt-3"><?php echo label::getLabel('LBL_Switch_to_Teacher_Profile'); ?></a>
                                        <?php }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ] -->
                    <?php }
                    ?>
                </div>
                <div class="sidebar__body">
                    <div class="sidebar__scroll">
                        <div id="primary-nav" class="menu-offset">
                            <!-- Display flashcard list on left sidebar in lesson view page  -->
                            <?php
                            $templateVariable = ['controllerName' => $controllerName, 'action' => $actionName, 'siteUser' => $siteUser, 'siteUserType' => $siteUserType];
                            $sidebarMenuLayout = '_partial/learner-sidebar.php';
                            if ($siteUserType == User::TEACHER) {
                                $templateVariable['tpp'] = $siteUser['profile_progress'];
                                $sidebarMenuLayout = '_partial/teacher-sidebar.php';
                            }
                            if ($siteUserType == User::AFFILIATE) {
                                $sidebarMenuLayout = '_partial/affiliate-sidebar.php';
                            }
                            if (isset($flashcardSrchFrm)) {
                                $templateVariable['flashcardSrchFrm'] = $flashcardSrchFrm;
                                $sidebarMenuLayout = '_partial/flashcard-sidebar.php';
                            }
                            $this->includeTemplate($sidebarMenuLayout, $templateVariable);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] -->
        </aside>
        <!-- ] -->
        <main class="page">