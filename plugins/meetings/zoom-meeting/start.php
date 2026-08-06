<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!doctype html>
<html lang="en" dir="<?php echo $siteLanguage['language_direction']; ?>">
    <head>
        <meta charset="utf-8">
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=2,user-scalable=1" />
        <link type="text/css" rel="stylesheet" href="https://source.zoom.us/<?php echo $settings['version']; ?>/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" href="https://source.zoom.us/<?php echo $settings['version']; ?>/css/react-select.css" />
        <script> const ZOOM_VERSION = '<?php echo $settings['version']; ?>';</script>
    </head>
    <body>
        <script src="https://source.zoom.us/<?php echo $settings['version']; ?>/lib/vendor/react.min.js"></script>
        <script src="https://source.zoom.us/<?php echo $settings['version']; ?>/lib/vendor/react-dom.min.js"></script>
        <script src="https://source.zoom.us/<?php echo $settings['version']; ?>/lib/vendor/redux.min.js"></script>
        <script src="https://source.zoom.us/<?php echo $settings['version']; ?>/lib/vendor/redux-thunk.min.js"></script>
        <script src="https://source.zoom.us/<?php echo $settings['version']; ?>/lib/vendor/lodash.min.js"></script>
        <script src="https://source.zoom.us/zoom-meeting-<?php echo $settings['version']; ?>.min.js"></script>
        <?php foreach ($pagejs as $file) { ?>
            <?php echo "<script>" . file_get_contents($file) . "</script>"; ?>
        <?php } ?>
    </body>
</html>