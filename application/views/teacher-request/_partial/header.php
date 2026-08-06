<?php 
defined('SYSTEM_INIT') or die('Invalid Usage.'); 
CommonHelper::setRefererPageUrl();
?>
<script>
    var ALERT_CLOSE_TIME = <?php echo FatApp::getConfig("CONF_AUTO_CLOSE_ALERT_TIME"); ?>;
</script>
<header class="header">
    <div class="header-primary">
        <div class="container container--narrow">
            <div class="d-flex justify-content-between">
                <div class="header__left">
                    <div class="header__logo">
                        <a href="<?php echo MyUtility::makeUrl(); ?>">
                            <?php echo MyUtility::getLogo(); ?>
                        </a>
                    </div>
                </div>
                <div class="header__right">
                    <div class="head__action">
                        <a class="" href="<?php echo MyUtility::makeUrl('TeacherRequest', 'logoutGuestUser'); ?>">
                            <svg class="icon icon--logout">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#logout'; ?>"></use>
                            </svg>
                            <span><?php echo Label::getLabel('LBL_EXIT'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- ] -->