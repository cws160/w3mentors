<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$newsletter = false;
$apikey = FatApp::getConfig("CONF_MAILCHIMP_KEY");
$listId = FatApp::getConfig("CONF_MAILCHIMP_LIST_ID");
$prefix = FatApp::getConfig("CONF_MAILCHIMP_SERVER_PREFIX");
if (!empty($apikey) && !empty($listId) && !empty($prefix) && FatApp::getConfig('CONF_ENABLE_NEWSLETTER_SUBSCRIPTION')) {
    $newsletter = true;
}
if ($newsletter) {
    $form = MyUtility::getNewsLetterForm();
    $form->developerTags['colClassPrefix'] = 'col-sm-';
    $form->developerTags['fld_default_col'] = 12;
    $form->setFormTagAttribute('onsubmit', 'submitNewsletterForm(this); return false;');
    $form->setFormTagAttribute('class', 'form');
    $emailFld = $form->getField('email');
    $emailFld->developerTags['noCaptionTag'] = true;
    $emailFld->addFieldTagAttribute('placeholder', Label::getLabel('LBL_ENTER_YOUR_EMAIL'));
    $emailFld->addFieldTagAttribute('class', 'input-field');
    $submitBtn = $form->getField('btnSubmit');
    $submitBtn->developerTags['noCaptionTag'] = true;
    $submitBtn->addFieldTagAttribute('class', 'btn btn-primary');
}
$sitePhone = FatApp::getConfig('CONF_SITE_PHONE');
$siteEmail = FatApp::getConfig('CONF_CONTACT_EMAIL');
$address = FatApp::getConfig('CONF_ADDRESS_' . $siteLangId, FatUtility::VAR_STRING, '');
?>

</div>

<footer id="footer" class="footer">
    <div class="container container--md">
        <?php if ($newsletter) { ?>
            <div class="footer-upper">
                <div class="site-subscribe">
                    <div class="site-subscribe__head">
                        <h2><?php echo Label::getLabel('LBL_NEWSLETTER_DESCRITPTION'); ?></h2>
                    </div>
                    <div class="site-subscribe__form">
                        <?php echo $form->getFormTag(); ?>
                        <?php echo $emailFld->getHtml(); ?>
                        <?php echo $submitBtn->getHtml(); ?>
                        </form>
                        <?php echo $form->getExternalJs(); ?>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="footer-middle">
            <div class="footer-row">
                <?php if (!empty($footerOneNav)) { ?>
                    <div class="footer-colum toggle-group">
                        <div class="footer-colum__trigger toggle-trigger-js">
                            <h5><?php echo current($footerOneNav)['parent']; ?></h5>
                        </div>
                        <div class="footer-colum__target toggle-target-js">
                            <div class="footer-list">
                                <ul>
                                    <?php foreach ($footerOneNav as $nav) { ?>
                                        <?php if ($nav['pages']) { ?>
                                            <?php foreach ($nav['pages'] as $link) { ?>
                                                <?php
                                                $display = true;
                                                if (($siteUserId < 1 && $link['nlink_login_protected'] == NavigationLinks::NAVLINK_LOGIN_YES) ||
                                                    ($siteUserId > 0 && $link['nlink_login_protected'] == NavigationLinks::NAVLINK_LOGIN_NO)
                                                ) {
                                                    $display = false;
                                                }
                                                if ($display == true) {
                                                    $navUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id']);
                                                ?>
                                                    <li>
                                                        <a target="<?php echo $link['nlink_target']; ?>" href="<?php echo $navUrl; ?>">
                                                            <?php echo $link['nlink_caption']; ?>
                                                        </a>
                                                    </li>
                                                <?php } ?>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if (!empty($footerTwoNav)) { ?>
                    <div class="footer-colum toggle-group">
                        <div class="footer-colum__trigger toggle-trigger-js">
                            <h5><?php echo current($footerTwoNav)['parent']; ?></h5>
                        </div>
                        <div class="footer-colum__target toggle-target-js">
                            <div class="footer-list">
                                <ul>
                                    <?php foreach ($footerTwoNav as $nav) {
                                        if ($nav['pages']) {
                                            foreach ($nav['pages'] as $link) {
                                                $display = true;
                                                if (($siteUserId < 1 && $link['nlink_login_protected'] == NavigationLinks::NAVLINK_LOGIN_YES) ||
                                                    ($siteUserId > 0 && $link['nlink_login_protected'] == NavigationLinks::NAVLINK_LOGIN_NO)
                                                ) {
                                                    $display = false;
                                                }
                                                if ($display == true) {
                                                    $navUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id']); ?>
                                                    <li>
                                                        <a target="<?php echo $link['nlink_target']; ?>" href="<?php echo $navUrl; ?>" class="bullet-list__action"><?php echo $link['nlink_caption']; ?></a>
                                                    </li>
                                                <?php } ?>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if (!empty($address) || !empty($siteEmail) || !empty($sitePhone)) { ?>
                    <div class="footer-colum toggle-group footer-colum--address">
                        <div class="footer-colum__trigger toggle-trigger-js">
                            <h5><?php echo Label::getLabel('LBL_GET_IN_TOUCH'); ?></h5>
                        </div>
                        <div class="footer-colum__target toggle-target-js">
                            <div class="footer-list">
                                <ul>
                                    <?php if (!empty($address)) { ?>
                                        <li>
                                            <div class="contact-meta-info">
                                                <span class="icon icon--18">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                        <path d="M12 20.8995L16.9497 15.9497C19.6834 13.2161 19.6834 8.78392 16.9497 6.05025C14.2161 3.31658 9.78392 3.31658 7.05025 6.05025C4.31658 8.78392 4.31658 13.2161 7.05025 15.9497L12 20.8995ZM12 23.7279L5.63604 17.364C2.12132 13.8492 2.12132 8.15076 5.63604 4.63604C9.15076 1.12132 14.8492 1.12132 18.364 4.63604C21.8787 8.15076 21.8787 13.8492 18.364 17.364L12 23.7279ZM12 13C13.1046 13 14 12.1046 14 11C14 9.89543 13.1046 9 12 9C10.8954 9 10 9.89543 10 11C10 12.1046 10.8954 13 12 13ZM12 15C9.79086 15 8 13.2091 8 11C8 8.79086 9.79086 7 12 7C14.2091 7 16 8.79086 16 11C16 13.2091 14.2091 15 12 15Z"></path>
                                                    </svg>
                                                </span>
                                                <span class="value">
                                                    <?php echo $address; ?>
                                                </span>
                                            </div>
                                        </li>
                                    <?php } ?>
                                    <?php if (!empty($siteEmail)) { ?>
                                        <li>
                                            <div class="contact-meta-info">
                                                <span class="icon icon--18">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20.258" height="20.258" viewBox="0 0 20.258 20.258">
                                                        <rect width="15" height="12" rx="2" transform="translate(2.258 4.258)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                                                        <path d="M3,7l7.183,4.789L17.366,7" transform="translate(-0.415 -0.816)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                                                    </svg>
                                                </span>
                                                <span class="value">
                                                    <a href="mailto:<?php echo $siteEmail; ?>"> <?php echo $siteEmail; ?></a>
                                                </span>
                                            </div>
                                        </li>
                                    <?php } ?>
                                    <?php if (!empty($sitePhone)) { ?>
                                        <li>
                                            <div class="contact-meta-info">
                                                <span class="icon icon--18">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20.258" height="20.258" viewBox="0 0 20.258 20.258">
                                                        <path d="M4.688,4H8.064L9.753,8.22,7.642,9.486a9.285,9.285,0,0,0,4.22,4.22l1.266-2.11,4.22,1.688v3.376a1.688,1.688,0,0,1-1.688,1.688A13.505,13.505,0,0,1,3,5.688,1.688,1.688,0,0,1,4.688,4" transform="translate(-0.468 -0.624)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                                                    </svg>
                                                </span>
                                                <span class="value">
                                                    <a dir="ltr" href="tel:<?php echo $sitePhone; ?>"> <?php echo $sitePhone; ?></a>
                                                </span>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="footer-colum toggle-group">
                    <div class="footer-group py-md-0 py-4">
                        <div class="dropdown footer-dropdown d-md-block d-none">
                            <button class="dropdown-toggle <?php echo (count($siteLanguages) == 0 && count($siteCurrencies) == 0) ? 'no-after-icon' : ''; ?>" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="lang mobile-action-label"><?php echo $siteLanguage['language_name']; ?>, <?php echo $siteCurrency['currency_code']; ?></span>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                <div class="settings-group">
                                    <?php if (count($siteLanguages) > 0) { ?>
                                        <div class="settings toggle-group">
                                            <div class="dropdaown__title"><?php echo Label::getLabel('LBL_Site_Language'); ?></div>
                                            <select name="lang_name" id="lang_name" onchange="setSiteLanguage(this.value);">
                                                <?php foreach ($siteLanguages as $language) { ?>
                                                    <option <?php echo ($siteLangId == $language['language_id']) ? 'selected' : ''; ?> <?php echo ($siteLangId != $language['language_id']) ? '' : 'disabled'; ?> value="<?php echo $language['language_id']; ?>"><?php echo $language['language_name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    <?php } ?>
                                    <?php if (count($siteCurrencies) > 0) { ?>
                                        <div class="settings toggle-group">
                                            <div class="dropdaown__title"><?php echo Label::getLabel('LBL_Site_Currency'); ?></div>
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
                        <?php if (!empty($socialPlatforms)) { ?>
                            <div class="footer-list">
                                <ul class="socials-list">
                                    <?php foreach ($socialPlatforms as $name => $link) {
                                        $name = strtolower($name); ?>
                                        <li>
                                            <a class="social-link" href="<?php echo $link; ?>" aria-label="<?php echo ($name == 'x' ? 'twitter' : $name); ?>" target="_blank">
                                                <svg class="icon icon--<?php echo $name ?>">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#' . ($name == 'x' ? 'twitter' : $name); ?>"></use>
                                                </svg>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        <?php } ?>
                        <div class="payment-cards">
                            <img src="<?php echo CONF_WEBROOT_FRONTEND . 'images/payment-method/payment-1.svg' ?>" width="36" height="23" alt="Payment Options">
                            <img src="<?php echo CONF_WEBROOT_FRONTEND . 'images/payment-method/payment-2.svg' ?>" width="36" height="23" alt="Payment Options">
                            <img src="<?php echo CONF_WEBROOT_FRONTEND . 'images/payment-method/payment-3.svg' ?>" width="36" height="23" alt="Payment Options">
                            <img src="<?php echo CONF_WEBROOT_FRONTEND . 'images/payment-method/payment-4.svg' ?>" width="36" height="23" alt="Payment Options">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (MyUtility::isDemoUrl() || true == WHITE_LABELED) {
            $url = (false == WHITE_LABELED) ? '<a target="_blank"  href="http://w3mentors.com">' . FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors') . '</a>' : FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors');
            $replacements = [
                '{year}' => '&copy; ' . date("Y"),
                '{product}' => '<span class="bold-600">' . $url . '</span>',
                '{owner}' => '<a target="_blank" rel="nofollow" class="bold-600" href="https://www.w3mentors.com">FATbit Technologies</a>',
            ]; ?>
            <div class="footer-lower">
                <div class="row justify-content-between align-items-center">
                    <div class="col-md-auto">
                        <p> <?php echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_COPYRIGHT_TEXT', $siteLangId), $replacements); ?> </p>
                    </div>
                    <?php
                    if (false == WHITE_LABELED) { ?>
                        <div class="col-md-auto">
                            <div class="footer__logo">
                                <?php echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_DEVELOPED_BY_TEXT', $siteLangId), $replacements); ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php } else {
            if (strtolower($controllerName) == 'home' && strtolower($actionName) == 'index') { ?>
                <div class="footer-lower">
                    <div class="row justify-content-between align-items-center">
                        <div class="col-md-auto">
                            <p> Copyright &copy; <?php echo date("Y"); ?>
                                <span class="bold-600">
                                    <?php echo FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, ''); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-auto">
                            <div class="footer__logo">
                                Technology Partner: <a target="_blank" rel="nofollow" class="bold-600" href="https://www.w3mentors.com">FATbit</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</footer>

<a href="#top" class="gototop" title="Back to Top"></a>
<?php if (FatApp::getConfig('CONF_ENABLE_COOKIES', FatUtility::VAR_INT, 1) && empty($cookieConsent)) { ?>
    <div class="cc-window cc-banner cc-type-info cc-theme-block cc-bottom cookie-alert no-print">
        <?php if (FatApp::getConfig('CONF_COOKIES_TEXT_' . $siteLangId, FatUtility::VAR_STRING, '')) { ?>
            <div class="box-cookies">
                <span id="cookieconsent:desc" class="cc-message">
                    <?php echo FatUtility::decodeHtmlEntities(FatApp::getConfig('CONF_COOKIES_TEXT_' . $siteLangId, FatUtility::VAR_STRING, '')); ?>
                    <?php
                    $readMorePage = FatApp::getConfig('CONF_COOKIES_BUTTON_LINK', FatUtility::VAR_INT, 0);
                    if ($readMorePage) {
                    ?>
                        <a href="<?php echo MyUtility::makeUrl('cms', 'view', [$readMorePage]); ?>"><?php echo Label::getLabel('LBL_COOKIES_POLICY'); ?></a></span>
            <?php } ?>
            </span>
            <div class="btns-pair">
                <span class="cc-close btn btn--secondary btn--small" onClick="acceptAllCookies();"><?php echo Label::getLabel('LBL_ACCEPT_COOKIES'); ?></span>
                <span class="cc-close btn btn--secondary btn--small" onClick="cookieConsentForm();"><?php echo Label::getLabel('LBL_CHOOSE_COOKIES'); ?></span>
            </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>
<?php
if (!empty(FatApp::getConfig('CONF_SITE_TRACKER_CODE', FatUtility::VAR_STRING, '')) && !empty($cookieConsent[CookieConsent::STATISTICS])) {
    echo FatApp::getConfig('CONF_SITE_TRACKER_CODE', FatUtility::VAR_STRING, '');
}
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" loading="async" defer>
<script src="/js/aos.js" loading="async"></script>
<script>
    AOS.init();
</script>

</body>

</html>