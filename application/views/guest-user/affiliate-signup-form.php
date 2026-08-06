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
$frm->setFormTagAttribute('name', 'affiliateSignupFrm');
$frm->setFormTagAttribute('id', 'affiliateSignupFrm');
$frm->developerTags['colClassPrefix'] = 'col-sm-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'signupAffiliateSetup(this); return(false);');
$fldFirstName = $frm->getField('user_first_name');
$fldFirstName->developerTags['col'] = 6;
$fldLastName = $frm->getField('user_last_name');
$fldLastName->developerTags['col'] = 6;
$fldPassword = $frm->getField('user_password');
$getFieldHTML = $frm->getField('btn_submit');
$getFieldHTML->addFieldTagAttribute('class', 'btn--block');

$fldPassword->changeCaption('');
$fldPassword->captionWrapper = (array(Label::getLabel('LBL_Password') . '<span class="spn_must_field">*</span><a onClick="togglePassword(this)" href="javascript:void(0)" class="link" data-show-caption="' . Label::getLabel('LBL_Show_Password') . '" data-hide-caption="' . Label::getLabel('LBL_Hide_Password') . '">' . Label::getLabel('LBL_Show_Password'), '</a>'));
$termLink = ' <a target="_blank" class = "link" href="' . $termsConditionsLink . '">' . Label::getLabel('LBL_TERMS_AND_CONDITION') . '</a> ' . Label::getLabel('LBL_AND') . ' <a href="' . $privacyPolicyLink . '" target="_blank" class = "link" >' . Label::getLabel('LBL_Privacy_Policy') . '</a>';
$terms_caption = '<span>' . $termLink . '</span>';
$frm->getField('agree')->addWrapperAttribute('class', 'terms_wrap set-remember');
$frm->getField('agree')->htmlAfterField = $terms_caption;
$addClass = 'mx-auto';
?>

<section class="section" style="background:url('<?php echo FatCache::getCachedUrl(MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_AFFILIATE_REGISTRATION_BANNER, 0, Afile::SIZE_LARGE, $siteLangId], CONF_WEBROOT_URL), CONF_DEF_CACHE_TIME, '.jpg'); ?>');background-size:cover;background-position:center;">
    <div class="container container--xxl">
        <div class="row gap-5 align-items-center justify-content-between">
            <?php if (!empty($contentBlocks &&  isset($contentBlocks[ExtraPage::BLOCK_AFFILIATE_REGISTRATION_BANNER]))) {
                $addClass = '';
            ?>
                <div class="col-lg-5 col-xxl-5 order-2">
                    <div class="cms-form">
                        <div class="editor-content">
                            <?php echo FatUtility::decodeHtmlEntities($contentBlocks[ExtraPage::BLOCK_AFFILIATE_REGISTRATION_BANNER]['epage_content']); ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="col-lg-6 col-xxl-4 order-1 <?php echo $addClass; ?>">
                <div class="card-form">
                    <div class="card-form__head">
                        <h2><?php echo Label::getLabel('LBL_REGISTER_AS_AFFILIATE'); ?></h2>
                    </div>
                    <div class="card-form__body">
                        <?php
                        echo $frm->getFormHtml();
                        ?>
                    </div>
                    <div class="card-form__footer text-center">
                        <p><?php echo Label::getLabel('LBL_ALREADY_HAVE_AN_ACCOUNT?'); ?> <a href="javascript:void(0);" onClick="signinForm()" class="link"><?php echo Label::getLabel('LBL_Sign_In'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    $(document).ready(function() {
        $('#termLabelWrapper label').addClass('field_resp_block');
        $('#termLabelWrapper label').append('<?php echo $termLink; ?>');
    });
</script>
<?php if (!empty($siteKey) && !empty($secretKey)) { ?>
    <script src='//www.google.com/recaptcha/api.js'></script>
<?php } ?>