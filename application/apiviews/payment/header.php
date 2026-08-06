<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" class="" theme="<?php echo $activeTheme ?? ''; ?>">

<head>
    <meta charset="utf-8">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=2,user-scalable=1" />
    <?php echo $this->writeMetaTags(); ?>
    <link rel="shortcut icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <link rel="apple-touch-icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    <?php if (!empty($canonicalUrl)) { ?>
        <link rel="canonical" href="<?php echo $canonicalUrl; ?>" />
    <?php } ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script type="text/javascript">
        var API_CALL = true;
        var langLbl = <?php echo json_encode(CommonHelper::htmlEntitiesDecode($jsVariables)); ?>;
        var timeZoneOffset = '<?php echo MyDate::getOffset($siteTimezone); ?>';
        var layoutDirection = '<?php echo $siteLanguage['language_direction']; ?>';
        var SslUsed = '<?php echo FatApp::getConfig('CONF_USE_SSL'); ?>';
        var cookieConsent = <?php echo json_encode($cookieConsent); ?>;
        const confWebRootUrl = '<?php echo CONF_WEBROOT_URL; ?>';
        const confFrontEndUrl = '<?php echo CONF_WEBROOT_URL; ?>';
        const confWebDashUrl = '<?php echo CONF_WEBROOT_DASHBOARD; ?>';
        const FTRAIL_TYPE = '<?php echo Lesson::TYPE_FTRAIL; ?>';
        var ALERT_CLOSE_TIME = <?php echo FatApp::getConfig("CONF_AUTO_CLOSE_ALERT_TIME"); ?>;
    </script>
    <?php
    echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
    echo Common::setThemeColorStyle();
    ?>
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

<body dir="<?php echo $siteLanguage['language_direction']; ?>">
    <div id="app-alert" class="alert-position alert-position--top-right fadeInDown animated"></div>
    <div id="body" class="body">