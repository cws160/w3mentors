<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php require_once(CONF_THEME_PATH . 'page-lang-data/helping-text.php'); ?>

<!--footer start here-->
<footer id="footer" class="footer">
    <div class="container">
        <div class="row justify-content-between">
            <?php if (MyUtility::isDemoUrl() || true == WHITE_LABELED) { ?>
                <?php
                $url = (false == WHITE_LABELED) ? '<a target="_blank"  href="http://w3mentors.com">' . FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors') . '</a>' : FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors');
                $replacements = array(
                    '{year}' => '&copy; ' . date("Y"),
                    '{product}' => '<span class="bold-600">' . $url . '</span>',
                    '{owner}' => '<a target="_blank" rel="nofollow" class="underline color-primary" href="https://www.w3mentors.com">FATbit Technologies</a>',
                );
                ?>
                <div class="col-md-auto">
                    <div class="copyright">
                        <?php
                        echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_COPYRIGHT_TEXT', $siteLangId), $replacements);
                        ?>
                        <?php echo " " . FatApp::getConfig("CONF_W3MENTORS_VERSION", FatUtility::VAR_STRING, 'V1.0') ?>
                    </div>
                </div>
                <div class="col-auto">
                    <?php
                    if (false == WHITE_LABELED) {
                        echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_DEVELOPED_BY_TEXT', $siteLangId), $replacements);
                    }
                    ?>
                </div>
            <?php } else { ?>
                <div class="col-md-auto">
                    <div class="copyright">
                        Copyright &copy; <?php echo date("Y"); ?>
                        <span class="bold-600">
                            <?php echo FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, ''); ?>
                        </span>
                        <?php echo " " . FatApp::getConfig("CONF_W3MENTORS_VERSION", FatUtility::VAR_STRING, 'V1.0') ?>
                    </div>
                </div>
                <div class="col-auto">
                    Technology Partner: <a target="_blank" rel="nofollow" class="bold-600" href="https://www.w3mentors.com">FATbit</a>
                </div>
            <?php } ?>

        </div>
    </div>
</footer>
</div>

<!-- Custom Loader -->
<div id="app-alert" class="alert-position alert-position--bottom-center fadeInDown animated"></div>
<script>
var eMsg = "<?php echo $messageData['errs'][0] ?? ''; ?>";
var sMsg = "<?php echo $messageData['msgs'][0] ?? ''; ?>";
var wMsg = "<?php echo $messageData['dialog'][0] ?? ''; ?>";
$(document).ready(function () {
    if (sMsg != '') {
        fcom.success(sMsg);
    }
    if (eMsg != '') {
        fcom.error(eMsg);
    }
    if (wMsg != '') {
        fcom.warning(wMsg);
    }
});
</script>
<?php
if (AdminAuth::isAdminLogged()) {
?>
<div class="footer-action">
    <div class="footer-action__item">
        <a class="footer-action__trigger" title="<?php echo Label::getLabel('LBL_View_Portal'); ?>" href="<?php echo CONF_WEBROOT_FRONT_URL; ?>" target="_blank">
            <span class="icon">
                <svg class="svg" width="20" height="20">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/retina/sprite.svg#icon-store">
                    </use>
                </svg>
            </span>
        </a>
    </div>
    <div class="footer-action__item">
        <a href="javascript:void(0);" class="footer-action__trigger" data-trigger="sidebar">
            <span class="icon">
                <svg class="svg" width="24" height="24">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/retina/sprite.svg#menu">
                    </use>
                </svg>
            </span>
        </a>
    </div>
    <div class="footer-action__item">
        <a class="footer-action__trigger" title="<?php echo Label::getLabel('LBL_Clear_Cache'); ?>" href="javascript:void(0)" onclick="clearCache()">
            <span class="icon">
                <svg class="svg" width="20" height="20">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/retina/sprite.svg#icon-cache">
                    </use>
                </svg>
            </span>
        </a>
    </div>
    <div class="footer-action__item dropdown">
        <a class="dropdown-toggle footer-action__trigger no-after" data-bs-toggle="dropdown" href="javascript:void(0)">
            <span class="icon">
                <svg class="svg" width="24" height="24">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg#icon-lang"></use>
                </svg>
            </span>
        </a>
        <div class="header-action__target p-0 dropdown-menu dropdown-menu-right dropdown-menu-anim notificationDropMenuJs dropDownMenuBlockClose">
            <div class="pt-3 pb-0 px-4">
                <h6 class="mb-0"><?php echo Label::getLabel('LBL_Select_Site_Language'); ?></h6>
            </div>
            <nav class="nav nav--header-account">
                <?php foreach ($siteLanguages as $langId => $language) { ?>
                    <div <?php echo ($siteLangId == $language['language_id']) ? 'class="is--active"' : ''; ?>><a href="javascript:void(0);" onClick="setSiteDefaultLang(<?php echo $language['language_id']; ?>)"><?php echo CommonHelper::renderHtml($language['language_name']); ?></a></div>
                <?php } ?>
            </nav>
        </div>
    </div>
    <div class="footer-action__item dropdown header-account">
        <div class="dropdown header-account">
            <a class="dropdown-toggle header-action__trigger no-before no-after" data-bs-toggle="dropdown" href="javascript:void(0)">
                <span class="header-account__img">
                    <img id="leftmenuimgtag" alt="" src="<?php echo MyUtility::makeUrl('image', 'show', [Afile::TYPE_ADMIN_PROFILE_IMAGE,  Afile::SIZE_SMALL]); ?>" alt="">
                </span>
            </a>
            <div class="header-action__target dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropDownMenuBlockClose">
                <div class="header-account__avtar">
                    <div class="profile">
                        <div class="profile__img">
                            <img id="leftmenuimgtag" alt="" src="<?php echo MyUtility::makeUrl('image', 'show', [Afile::TYPE_ADMIN_PROFILE_IMAGE, Afile::SIZE_SMALL]); ?>" alt="">
                        </div>
                        <div class="profile__detail">
                            <h6><?php echo Label::getLabel('LBL_HI'); ?>, <?php echo Label::getLabel('Admin name'); ?></h6>
                            <span><a href="mailto:">Email Address</a></span>
                        </div>
                    </div>
                </div>
                <div class="separator m-0"></div>
                <nav class="nav nav--header-account">
                    <a href="<?php echo MyUtility::makeUrl('profile'); ?>"><?php echo Label::getLabel('LBL_View_Profile'); ?></a>
                    <a href="<?php echo MyUtility::makeUrl('profile', 'changePassword'); ?>"><?php echo Label::getLabel('LBL_Change_Password'); ?></a>
                </nav>
                <div class="separator m-0"></div>
                <nav class="nav nav--header-account">
                    <a href="javascript:void(0);" onclick="logout();"><?php echo Label::getLabel('LBL_Logout'); ?></a>
                </nav>
            </div>
        </div>
    </div>
</div>
<?php } ?>
</body>

</html>