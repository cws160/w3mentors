<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php $headerClasses = strtolower($controllerName) . ' ' . strtolower($controllerName) . '-' . strtolower($actionName); ?>
<?php
CommonHelper::setRefererPageUrl();
$siteLangCode = $siteLanguages[MyUtility::getSiteLangId()]['lower_language_code'];
?>
<!DOCTYPE html>
<html lang="<?php echo $siteLangCode ?>" theme="<?php echo $activeTheme ?? ''; ?>" prefix="og: http://ogp.me/ns#" class="<?php echo MyUtility::isDemoUrl() ? 'sticky-demo-header' : ''; ?>" data-kit="F!YC">

<head>
    <meta charset="utf-8">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=2,user-scalable=1" />
    <?php echo $this->writeMetaTags(); ?>
    <?php
    if (strtolower($controllerName) == 'subscriptionplans') {
        echo '<meta name="robots" content="noindex, nofollow">';
    }
    ?>
    <link rel="shortcut icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <link rel="apple-touch-icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <?php if (!empty($canonicalUrl)) { ?>
        <link rel="canonical" href="<?php echo $canonicalUrl; ?>" />
    <?php } ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" loading="async" defer>
    <?php
    foreach ($siteLanguages as $lang) {
        $requestUrl = !empty($_REQUEST['url']) ? str_replace($siteLangCode, '', $_REQUEST['url'] ?? '') : '';
        if ($lang['language_id'] == MyUtility::getSiteLangId()) {
            continue;
        }
        $hrefLangUrl = MyUtility::makeFullUrl();
        if (Language::getDefaultLang() != $lang['language_id']) {
            $hrefLangUrl .=  $lang['lower_language_code'] . '/';
        }
        $hrefLangUrl .=  trim($requestUrl, '/');
    ?>
        <link rel="alternate" hreflang="<?php echo $lang['lower_language_code'] ?>" href="<?php echo $hrefLangUrl ?>">
    <?php } ?>
    <script type="text/javascript">
        const confWebRootUrl = '<?php echo CONF_WEBROOT_URL; ?>';
        const confFrontEndUrl = '<?php echo CONF_WEBROOT_URL; ?>';
        const confWebDashUrl = '<?php echo CONF_WEBROOT_DASHBOARD; ?>';
        const FTRAIL_TYPE = '<?php echo Lesson::TYPE_FTRAIL; ?>';
        var langLbl = <?php echo json_encode(CommonHelper::htmlEntitiesDecode($jsVariables)); ?>;
        var timeZoneOffset = '<?php echo MyDate::getOffset($siteTimezone); ?>';
        var layoutDirection = '<?php echo $siteLanguage['language_direction']; ?>';
        var SslUsed = '<?php echo FatApp::getConfig('CONF_USE_SSL'); ?>';
        var cookieConsent = <?php echo json_encode($cookieConsent); ?>;
        var ALERT_CLOSE_TIME = <?php echo FatApp::getConfig("CONF_AUTO_CLOSE_ALERT_TIME"); ?>;
        var monthNames = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::getAllMonthName(false, $siteLangId))); ?>;
        var weekDayNames = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::dayNames(false, $siteLangId))); ?>;
        var meridiems = <?php echo json_encode(CommonHelper::htmlEntitiesDecode(MyDate::meridiems(false, $siteLangId))); ?>;
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
    <?php if (!empty($includeEditor)) { ?>
        <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/innovaeditor.js"></script>
        <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/common/webfont.js"></script>
    <?php } ?>
    <?php if (FatApp::getConfig('CONF_ENABLE_PWA')) { ?>
        <link rel="manifest" href="<?php echo MyUtility::makeUrl('Pwa'); ?>">
        <script>
            if ("serviceWorker" in navigator) {
                navigator.serviceWorker.register("<?php echo CONF_WEBROOT_FRONTEND; ?>sw.js");
            }
        </script>
    <?php } ?>
    <?php
    echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
    echo Common::setThemeColorStyle();
    ?>
    <script>
        $(document).ready(function() {
            <?php if ($siteUserId > 0) { ?>
                setTimeout(getBadgeCount(), 1000);
            <?php } ?>
            <?php if (!empty($messageData['msgs'][0] ?? '')) { ?>
                fcom.success('<?php echo $messageData['msgs'][0]; ?>');
            <?php } ?>
            <?php if (!empty($messageData['dialog'][0] ?? '')) { ?>
                fcom.warning('<?php echo $messageData['dialog'][0]; ?>');
            <?php } ?>
            <?php if (!empty($messageData['errs'][0] ?? '')) { ?>
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
    <!-- w3mentors -->
    <!-- F!YC -->
</head>
<?php $isPreviewOn = MyUtility::isDemoUrl() ? 'is-preview-on' : '';
?>


<body class="<?php echo $headerClasses . ' ' . $isPreviewOn; ?>" dir="<?php echo $siteLanguage['language_direction']; ?>">
    <!-- Custom Loader -->
    <div id="app-alert" class="alert-position alert-position--top-right fadeInDown animated"></div>
    <?php
    echo "<!-- Google tag Manager Body Script -->\r\n";
    if (FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT", null, '')) {
        echo FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT", null, '');
        $GA4 = 1;
    }
    if (isset($_SESSION['preview_theme'])) {
        $this->includeTemplate('_partial/preview.php', array(), false);
    }
    if (!isset($exculdeMainHeaderDiv)) {
    ?>
        <script>
            GA4 = <?php echo $GA4; ?>;
        </script>
        <header class="header">
            <?php
            if (MyUtility::isDemoUrl()) {
                include(CONF_INSTALLATION_PATH . 'public/demo-header.php');
            }
            ?>
            <div class="header-primary">
                <div class="container">
                    <div class="header-flex d-flex justify-content-between align-items-center">
                        <div class="header__left">
                            <?php if (!empty($headerNav)) { ?>
                                <span class="cursor-pointer toggle toggle--nav toggle--nav-js">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 4H21V6H3V4ZM3 11H21V13H3V11ZM3 18H21V20H3V18Z"></path>
                                    </svg>
                                </span>
                            <?php } else { ?>
                                <a class="toggle toggle--nav"></a>
                            <?php } ?>
                            <div class="header__logo">
                                <a href="<?php echo MyUtility::makeUrl(); ?>">
                                    <?php echo MyUtility::getLogo(); ?>
                                </a>
                            </div>
                        </div>
                        <div class="header__middle">
                            <?php if (!empty($headerNav)) { ?>
                                <span class="overlay overlay--nav toggle--nav-js is-active"></span>
                                <nav class="menu nav--primary-offset">
                                    <ul>
                                        <?php foreach ($headerNav as $nav) { ?>
                                            <?php if ($nav['pages']) {
                                                foreach ($nav['pages'] as $link) {
                                                    $display = true;
                                                    $controller = FatUtility::camel2Dashed($controllerName);
                                                    $activeClass = '';
                                                    $linkController = str_replace('{domain}', '', strtolower($link['nlink_url']));
                                                    if (($siteUserId < 1 && $link['nlink_login_protected'] == NavigationLinks::NAVLINK_LOGIN_YES) ||
                                                        ($siteUserId > 0 && $link['nlink_login_protected'] == NavigationLinks::NAVLINK_LOGIN_NO)
                                                    ) {
                                                        $display = false;
                                                    }
                                                    if ($display == true) {
                                                        if ($link['nlink_type'] == NavigationLinks::NAVLINK_TYPE_CMS) {
                                                            $linkController = 'cms/' . $link['nlink_cpage_id'];
                                                            $params = FatApp::getParameters()[0] ?? '';
                                                            $controller .= !empty($params) ? '/' . $params : '';
                                                        }
                                                        if ($linkController == $controller) {
                                                            $activeClass = 'has-current';
                                                        }
                                                        $navUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id']);
                                            ?>
                                                        <li class="menu__item <?php echo $activeClass; ?>">
                                                            <a target="<?php echo $link['nlink_target']; ?>" href="<?php echo $navUrl; ?>">
                                                                <?php echo CommonHelper::renderHtml($link['nlink_caption']); ?>
                                                            </a>
                                                        </li>
                                        <?php }
                                                }
                                            }
                                        } ?>
                                    </ul>
                                </nav>
                            <?php } ?>
                        </div>
                        <div class="header__right">
                            <div class="header-controls">
                                <div class="header-controls__item">
                                    <a href="<?php echo MyUtility::makeUrl('', '', [], CONF_WEBROOT_FRONTEND); ?>" class="header-controls__action mobile-action">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                            <path d="M13 19h6V9.978l-7-5.444-7 5.444V19h6v-6h2v6zm8 1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.49a1 1 0 0 1 .386-.79l8-6.222a1 1 0 0 1 1.228 0l8 6.222a1 1 0 0 1 .386.79V20z"></path>
                                        </svg>
                                        <span class="mobile-action-label"><?php echo Label::getLabel('LBL_HOME') ?></span>
                                    </a>
                                </div>
                                <div class="header-controls__item header-dropdown header-dropdown--arrow">
                                    <?php if (count($siteLanguages) > 0 || count($siteCurrencies) > 0) { ?>
                                        <a class="header-controls__action header-dropdown__trigger trigger-js mobile-action" href="#languages-nav">
                                            <svg class="icon icon--globe">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#globe-icon'; ?>"></use>
                                            </svg>
                                            <span class="lang mobile-action-label"><?php echo $siteLanguage['language_code'] . ' - ' . $siteCurrency['currency_code']; ?></span>
                                            <svg class="icon icon--arrow">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#arrow-black' ?>"></use>
                                            </svg>
                                        </a>
                                        <div id="languages-nav" class="header-dropdown__target">
                                            <div class="dropdown__cover">
                                                <div class="settings-group">
                                                    <?php if (count($siteLanguages) > 0) { ?>
                                                        <div class="settings toggle-group">
                                                            <div class="dropdaown__title"><?php echo Label::getLabel('LBL_SITE_LANGUAGE') ?></div>
                                                            <select name="lang_name" id="lang_name" onchange="setSiteLanguage(this.value);">
                                                                <?php foreach ($siteLanguages as $language) { ?>
                                                                    <option <?php echo ($siteLangId == $language['language_id']) ? 'selected' : ''; ?> <?php echo ($siteLangId != $language['language_id']) ? '' : 'disabled'; ?> value="<?php echo $language['language_id']; ?>"><?php echo $language['language_name'] ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if (count($siteCurrencies) > 0) { ?>
                                                        <div class="settings toggle-group">
                                                            <div class="dropdaown__title"><?php echo Label::getLabel('LBL_SITE_CURRENCY'); ?></div>
                                                            <select name="currency_name" id="currency_name" onchange="setSiteCurrency(this.value);">
                                                                <?php foreach ($siteCurrencies as $currency) { ?>
                                                                    <option <?php echo ($siteCurrency['currency_id'] == $currency['currency_id']) ? 'selected' : ''; ?> <?php echo ($siteCurrency['currency_id'] != $currency['currency_id']) ? '' : 'disabled'; ?> value="<?php echo $currency['currency_id']; ?>"><?php echo $currency['currency_code']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php if ($siteUserId > 0) { ?>
                                    <div class="header-controls__item header--notification d-none d-md-block">
                                        <a href="<?php echo MyUtility::makeUrl('Notifications', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="header-controls__action btn btn--equal btn-round mobile-action" title="<?php echo Label::getLabel('LBL_NOTIFICATIONS'); ?>">
                                            <span class="notification-count-js"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                                                <path d="M20 17h2v2H2v-2h2v-7a8 8 0 1 1 16 0v7zm-2 0v-7a6 6 0 1 0-12 0v7h12zm-9 4h6v2H9v-2z"></path>
                                            </svg>
                                            <span class="mobile-action-label d-md-none d-block"><?php echo Label::getLabel('LBL_NOTIFICATIONS'); ?></span>
                                        </a>
                                    </div>
                                    <div class="header-controls__item header--message d-md-block">
                                        <a href="<?php echo MyUtility::makeUrl('Chats', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="header-controls__action btn btn--equal btn-round mobile-action" title="<?php echo Label::getLabel('LBL_MESSAGES'); ?>">
                                            <span class="message-count-js"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24.139" height="19.182" viewBox="0 0 24.139 19.182">
                                                <g>
                                                    <path d="M12.082,19.181q-4.232,0-8.464,0A3.382,3.382,0,0,1,.005,15.567q-.011-5.976,0-11.952A3.372,3.372,0,0,1,3.618,0Q12.11,0,20.6,0a3.359,3.359,0,0,1,3.525,3.469q.024,6.119,0,12.238A3.36,3.36,0,0,1,20.6,19.18q-4.26.007-8.521,0M22.239,3.309c-.235.033-.343.231-.484.373q-3.562,3.553-7.117,7.113a3.406,3.406,0,0,1-5.155-.013Q5.948,7.243,2.407,3.71c-.151-.151-.273-.355-.551-.414-.013.2-.034.367-.034.535q0,5.8,0,11.605c0,1.363.581,1.928,1.961,1.928q8.261,0,16.522,0c1.446,0,2-.55,2-2q0-5.688,0-11.377a1.3,1.3,0,0,0-.07-.676M3.169,1.847c.217.231.342.369.473.5q3.495,3.5,6.991,6.993c1.062,1.063,1.8,1.069,2.862.01q3.517-3.515,7.028-7.036c.122-.123.323-.208.311-.467Z" transform="translate(0 0)" />
                                                </g>
                                            </svg>
                                            <span class="mobile-action-label d-md-none d-block"><?php echo Label::getLabel('LBL_MESSAGES'); ?></span>
                                        </a>
                                    </div>

                                    <div class="header-controls__item header-action">
                                        <div class="header__action">
                                            <a href="#HEADER-SEARCH" onclick="" class="btn btn--equal btn-round btn--search search-trigger trigger-js">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="19.247" height="19.247" viewBox="0 0 19.247 19.247">
                                                    <path d="M18.98,17.848l-4.78-4.78A8.022,8.022,0,1,0,13.067,14.2l4.78,4.78a.8.8,0,1,0,1.133-1.133ZM8,14.409A6.407,6.407,0,1,1,14.409,8,6.407,6.407,0,0,1,8,14.409Z" transform="translate(0.032 0.032)" />
                                                </svg>
                                                <span class="mobile-action-label d-md-none"><?php echo Label::getLabel('LBL_HEADER_SEARCH'); ?></span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="header-dropdown header-dropwown--profile">
                                        <a class="header-dropdown__trigger trigger-js mobile-action" href="#profile-nav">
                                            <div class="teacher-profile">
                                                <div class="teacher__media">
                                                    <div class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($siteUser['user_first_name']); ?>">
                                                        <?php echo '<img src="' . MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $siteUserId, Afile::SIZE_SMALL)) . '?' . time() . '" alt="" />'; ?>
                                                    </div>
                                                </div>
                                                <div class="mobile-action-label d-md-none d-block"><?php echo $siteUser['user_first_name']; ?></div>
                                            </div>
                                        </a>
                                        <div id="profile-nav" class="header-dropdown__target">
                                            <div class="dropdown__cover">
                                                <div class="avtar-meta mb-3">
                                                    <div class="avtar avtar--small avtar--round" data-title="<?php echo CommonHelper::getFirstChar($siteUser['user_first_name']); ?>">
                                                        <?php echo '<img src="' . MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $siteUserId, Afile::SIZE_SMALL)) . '?' . time() . '" alt="" />'; ?>
                                                    </div>
                                                    <div class="avtar-meta__name">
                                                        <?php echo $siteUser['user_first_name']; ?>
                                                    </div>
                                                </div>
                                                <nav class="menu--inline">
                                                    <ul>
                                                        <?php if ($siteUserType == User::TEACHER) { ?>
                                                            <li class="menu__item">
                                                                <a href="<?php echo MyUtility::makeUrl('Teacher', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                    <svg class="icon">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#dashboard"></use>
                                                                    </svg>
                                                                    <?php echo Label::getLabel('LBL_Dashboard'); ?>
                                                                </a>
                                                            </li>
                                                            <li class="menu__item">
                                                                <a href="<?php echo MyUtility::makeUrl('Students', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                    <svg class="icon">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#students"></use>
                                                                    </svg>
                                                                    <?php echo Label::getLabel('LBL_My_Students'); ?>
                                                                </a>
                                                            </li>
                                                        <?php
                                                        }
                                                        if ($siteUserType == User::LEARNER) {
                                                        ?>
                                                            <li class="menu__item">
                                                                <a href="<?php echo MyUtility::makeUrl('Learner', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                    <svg class="icon">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#dashboard"></use>
                                                                    </svg>
                                                                    <?php echo Label::getLabel('LBL_Dashboard'); ?>
                                                                </a>
                                                            </li>
                                                            <li class="menu__item">
                                                                <a href="<?php echo MyUtility::makeUrl('Teachers', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                    <svg class="icon">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#students"></use>
                                                                    </svg>
                                                                    <?php echo Label::getLabel('LBL_My_Teachers'); ?>
                                                                </a>
                                                            </li>
                                                        <?php }
                                                        ?>
                                                        <?php if ($siteUserType == User::LEARNER || $siteUserType == User::TEACHER) { ?>
                                                            <li class="menu__item">
                                                                <a href="<?php echo MyUtility::makeUrl('Lessons', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                    <svg class="icon">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#lessons"></use>
                                                                    </svg>
                                                                    <?php echo Label::getLabel('LBL_Lessons'); ?>
                                                                </a>
                                                            </li>
                                                            <li class="menu__item">
                                                                <a href="<?php echo MyUtility::makeUrl('Classes', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                    <svg class="icon">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#group-classes"></use>
                                                                    </svg>
                                                                    <?php echo Label::getLabel('LBL_Classes'); ?>
                                                                </a>
                                                            </li>
                                                            <?php if (Course::isEnabled()) { ?>
                                                                <li class="menu__item">
                                                                    <a href="<?php echo MyUtility::makeUrl('Courses', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                        <svg class="icon">
                                                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#all-courses"></use>
                                                                        </svg>
                                                                        <?php echo Label::getLabel('LBL_Courses'); ?>
                                                                    </a>
                                                                </li>
                                                            <?php } ?>
                                                        <?php } ?>
                                                        <?php if ($siteUserType == User::AFFILIATE) { ?>
                                                            <li class="menu__item">
                                                                <a href="<?php echo MyUtility::makeUrl('Affiliate', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                    <svg class="icon">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#dashboard"></use>
                                                                    </svg>
                                                                    <?php echo Label::getLabel('LBL_Dashboard'); ?>
                                                                </a>
                                                            </li>
                                                        <?php
                                                        } ?>
                                                        <li class="menu__item">
                                                            <a href="<?php echo MyUtility::makeUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                <svg class="icon">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#settings"></use>
                                                                </svg>
                                                                <?php echo Label::getLabel('LBL_Settings'); ?>
                                                            </a>
                                                        </li>
                                                        <li class="menu__item">
                                                            <a href="<?php echo MyUtility::makeUrl('Account', 'logout', [], CONF_WEBROOT_DASHBOARD); ?>">
                                                                <svg class="icon" width="24" height="24" viewBox="0 0 24 24">
                                                                    <path d="M7.68421 19C7.30633 19 7 18.6866 7 18.3V5.7C7 5.3134 7.30633 5 7.68421 5H17.2632C17.641 5 17.9474 5.3134 17.9474 5.7V7.8H16.5789V6.4H8.36842V17.6H16.5789V16.2H17.9474V18.3C17.9474 18.6866 17.641 19 17.2632 19H7.68421ZM16.5789 14.8V12.7H11.7895V11.3H16.5789V9.2L20 12L16.5789 14.8Z" fill="black" />
                                                                </svg>
                                                                <?php echo Label::getLabel('LBL_Logout'); ?>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="header-controls__item header-action">
                                        <div class="header__action">
                                            <button onclick="signinForm();" class="header-controls__action btn btn--transparent user-click mobile-action">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                                    <path d="M10 11V8l5 4-5 4v-3H1v-2h9zm-7.542 4h2.124A8.003 8.003 0 0 0 20 12 8 8 0 0 0 4.582 9H2.458C3.732 4.943 7.522 2 12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10c-4.478 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                <span class="mobile-action-label">
                                                    <?php echo Label::getLabel('LBL_Login'); ?>
                                                </span>
                                            </button>
                                            <button onclick="signupForm();" class="btn btn--primary user-click mobile-action">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                                    <path d="M14 14.252v2.09A6 6 0 0 0 6 22l-2-.001a8 8 0 0 1 10-7.748zM12 13c-3.315 0-6-2.685-6-6s2.685-6 6-6 6 2.685 6 6-2.685 6-6 6zm0-2c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm6 6v-3h2v3h3v2h-3v3h-2v-3h-3v-2h3z"></path>
                                                </svg>
                                                <span class="mobile-action-label">
                                                    <?php echo Label::getLabel('LBL_SIGN_UP'); ?>
                                                </span>
                                            </button>
                                            <button href="#HEADER-SEARCH" title="<?php echo Label::getLabel('LBL_HEADER_SEARCH'); ?>" class="btn btn--equal btn-round btn--search search-trigger trigger-js">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="19.247" height="19.247" viewBox="0 0 19.247 19.247">
                                                    <path d="M18.98,17.848l-4.78-4.78A8.022,8.022,0,1,0,13.067,14.2l4.78,4.78a.8.8,0,1,0,1.133-1.133ZM8,14.409A6.407,6.407,0,1,1,14.409,8,6.407,6.407,0,0,1,8,14.409Z" transform="translate(0.032 0.032)" />
                                                </svg>
                                                <span class="mobile-action-label d-md-none">
                                                    <?php echo Label::getLabel('LBL_HEADER_SEARCH'); ?>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="header-search" id="HEADER-SEARCH">
                        <?php $this->includeTemplate('_partial/headerSearch.php'); ?>
                    </div>
                </div>
            </div>
        </header>
        <div id="body" class="body">
        <?php } ?>