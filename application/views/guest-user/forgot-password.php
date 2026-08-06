<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('autocomplete', 'off');
$frm->setFormTagAttribute('id', 'forgotPasswordFrma');
$frm->setFormTagAttribute('onsubmit', 'forgotPasswordSetup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frmFld = $frm->getField('user_email');
if (!empty($siteKey) && !empty($secretKey)) {
    $htmlNote = $frm->getField('htmlNote');
    $htmlNote->value = '<div class="field-set"><div class="caption-wraper"><label class="field_label"></label></div><div class="field-wraper"><div class="field_cover">
    <div class="g-recaptcha" data-sitekey="' . $siteKey . '" data-callback="captchaValidate" data-expired-callback="captchaValidate"></div></div></div></div>';
}
?>
<section class="section bg-gradiant h-100">
    <div class="container container--fixed">
        <div class="site-form-wrapper">
            <div class="site-form">
                <div class="site-form__header text-center mb-4">
                    <h4><?php echo Label::getLabel('LBL_FORGOT_PASSWORD'); ?></h4>
                </div>
                <div class="site-form__body">
                    <?php echo $frm->getFormHtml(); ?>
                </div>
                <div class="site-form__foot text-center pt-2">
                    <p><a href="<?php echo MyUtility::makeUrl('GuestUser', 'loginForm'); ?>" class="link"><?php echo Label::getLabel('LBL_BACK_TO_LOGIN'); ?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if (!empty($siteKey) && !empty($secretKey)) { ?>
    <script src='//www.google.com/recaptcha/api.js'></script>
<?php } ?>