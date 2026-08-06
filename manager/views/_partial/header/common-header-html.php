<?php if (isset($includeEditor) && $includeEditor) { ?>
    <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/innovaeditor.js"></script>
    <script src="<?php echo CONF_WEBROOT_URL; ?>innovas/scripts/common/webfont.js"></script>
<?php } ?>
<script>
    var ALERT_CLOSE_TIME = <?php echo FatApp::getConfig("CONF_AUTO_CLOSE_ALERT_TIME"); ?>;
    var timeFormat = '<?php echo MyDate::getFormatTime(); ?>';
    var timeFormatSec = '<?php echo MyDate::getFormatTime(false, false); ?>';
    var timeFormatJs = '<?php echo MyDate::getFormatTime(true); ?>';
    var timeFormatSecJs = '<?php echo MyDate::getFormatTime(true, false); ?>';
</script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<?php $isPreviewOn = MyUtility::isDemoUrl() ? 'is-preview-on' : ''; ?>

<body class="<?php echo $bodyClass . ' ' . $isPreviewOn; ?>" dir="<?php echo $siteLanguage['language_direction']; ?>">
    <?php
    if (MyUtility::isDemoUrl()) {
        $isAdminPanel = 1;
        include(CONF_INSTALLATION_PATH . 'public/demo-header.php');
    }
    ?>