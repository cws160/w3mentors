<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$recaptchaKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
if (!empty($recaptchaKey)) {
    $htmlNote = $frm->getField('htmlNote');
    $htmlNote->value = '<div class="field-set"><div class="caption-wraper"><label class="field_label"></label></div><div class="field-wraper"><div class="field_cover">
    <div class="g-recaptcha" data-sitekey="' . $siteKey . '" data-callback="captchaValidate" data-expired-callback="captchaValidate"></div></div></div></div>';
}
$privacyPolicyLink = empty($privacyPolicyLink) ? 'javascript:void();' : $privacyPolicyLink;
$termsConditionsLink = empty($termsConditionsLink) ? 'javascript:void();' : $termsConditionsLink;
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-sm-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'signupSetup(this); return(false);');
$fldFirstName = $frm->getField('user_first_name');
$fldFirstName->developerTags['col'] = 6;
$fldLastName = $frm->getField('user_last_name');
$fldLastName->developerTags['col'] = 6;
$fldPassword = $frm->getField('user_password');
$fldPassword->changeCaption('');
$fldPassword->captionWrapper = (array(Label::getLabel('LBL_Password') . '<span class="spn_must_field">*</span><a onClick="togglePassword(this)" href="javascript:void(0)" class="link" data-show-caption="' . Label::getLabel('LBL_Show_Password') . '" data-hide-caption="' . Label::getLabel('LBL_Hide_Password') . '">' . Label::getLabel('LBL_Show_Password'), '</a>'));
$termLink = ' <a target="_blank" class = "link" href="' . $termsConditionsLink . '">' . Label::getLabel('LBL_TERMS_AND_CONDITION') . '</a> ' . Label::getLabel('LBL_AND') . ' <a href="' . $privacyPolicyLink . '" target="_blank" class = "link" >' . Label::getLabel('LBL_Privacy_Policy') . '</a>';
$terms_caption = '<span>' . $termLink . '</span>';
$frm->getField('agree')->addWrapperAttribute('class', 'terms_wrap set-remember');
$frm->getField('agree')->htmlAfterField = $terms_caption;
?>
<section class="section bg-gradiant">
    <div class="container container--fixed">
        <div class="site-form-wrapper">
            <div class="site-form">
                <div class="site-form__header text-center mb-4">
                    <h4><?php echo Label::getLabel('LBL_REGISTER'); ?></h4>
                </div>
                <div class="ite-form__body">
                    <?php $this->includeTemplate('guest-user/_partial/learner-social-media-signup.php'); ?>
                    <?php echo $frm->getFormHtml(); ?>
                    <div class="text-center">
                        <p><?php echo Label::getLabel('LBL_ALREADY_HAVE_AN_ACCOUNT?'); ?> <a href="<?php echo MyUtility::makeUrl('GuestUser', 'LoginForm'); ?>" class="link"><?php echo Label::getLabel('LBL_Sign_In'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if (!empty($siteKey) && !empty($secretKey)) { ?>
    <script src='//www.google.com/recaptcha/api.js'></script>
<?php } ?>
<script>
    $(document).ready(function() {
        $('#termLabelWrapper label').addClass('field_resp_block');
        $('#termLabelWrapper label').append('<?php echo $termLink; ?>');
    })
</script>