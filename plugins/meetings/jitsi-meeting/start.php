<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!DOCTYPE html>
<html lang="en" dir="<?php echo $siteLanguage['language_direction']; ?>">
    <head>
        <meta charset="utf-8">
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=2,user-scalable=1" />

        <script src='https://8x8.vc/<?php echo $detail['appID']; ?>/external_api.js' async></script>
        <?php foreach ($pagejs as $file) { ?>
            <?php echo "<script>" . file_get_contents($file) . "</script>"; ?>
        <?php } ?>
        <script type="text/javascript">
            window.onload = () => {
                jitsiAPI = new JitsiMeetExternalAPI("8x8.vc", {
                    parentNode: document.querySelector('#jaas-container'),
                    roomName: "<?php echo $detail['roomName']; ?>",
                    jwt: "<?php echo $detail['jwt']; ?>",
                    configOverwrite: {
                        buttonsWithNotifyClick: ['hangup', 'end-meeting']
                    }
                });
                jitsiAPI.addListener("toolbarButtonClicked", function (e) {
                    if (e.key === 'hangup') {
                        location.reload();
                    } else if (e.key === 'end-meeting') {
                        location.reload();
                    }
                });
            };
        </script>
        <style>
            html, body, #jaas-container {
                margin: 0;
                padding: 0;
                height: 100%;
            }
        </style>
    </head>
    <body>
        <div id="jaas-container"></div>
    </body>
</html>