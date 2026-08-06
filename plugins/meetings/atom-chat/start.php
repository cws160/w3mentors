<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<style> html,body{height: 100%;margin: 0;padding: 0;} </style>
<!doctype html>
<html lang="en" dir="<?php echo $siteLanguage['language_direction']; ?>">
    <head>
        <meta charset="utf-8">
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=2,user-scalable=1" />
        <?php foreach ($pagejs as $file) { ?>
            <?php echo "<script>" . file_get_contents($file) . "</script>"; ?>
        <?php } ?>
        <script>
            $(document).ready(function () {
                var meeting = <?php echo html_entity_decode($meet['meet_details']); ?>;
                loadChatBox(meeting, "#chatBox");
            });
        </script>
    </head>
    <body>
        <div id="chatBox" style="height:calc(100% - 2px);width: calc(100% - 2px);"></div>
    </body>
</html>