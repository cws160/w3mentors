<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupContribution(this);return false;');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = '12';
$bcontributions_author_first_name = $frm->getField('bcontributions_author_first_name');
$bcontributions_author_first_name->developerTags['col'] = 6;
$bcontributions_author_last_name = $frm->getField('bcontributions_author_last_name');
$bcontributions_author_last_name->developerTags['col'] = 6;
$bcontributions_author_email = $frm->getField('bcontributions_author_email');
$bcontributions_author_email->developerTags['col'] = 6;
$bcontributions_author_phone = $frm->getField('bcontributions_author_phone');
$bcontributions_author_phone->developerTags['col'] = 6;
$btn_submit = $frm->getField('btn_submit');
$btn_submit->setFieldTagAttribute('class ', 'btn btn--primary btn--block btn--large');
$btn_submit->developerTags['col'] = 12;

$fileFld = $frm->getField('file');
$ext = Afile::getAllowedExts(Afile::TYPE_BLOG_CONTRIBUTION);
$size = MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_BLOG_CONTRIBUTION)) . ' MB';
$fileFld->htmlAfterField = '<small>' . str_replace(['{ext}', '{size}'], [implode(", ", $ext), $size], Label::getLabel('LBL_ALLOWED_EXTS_{ext}_AND_MAX_SIZE_{size}')) . '</small>';
$verifyRecaptcha = (FatApp::getConfig('CONF_RECAPTCHA_SITEKEY  ', FatUtility::VAR_STRING, '') != '' && FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY  ', FatUtility::VAR_STRING, '') != '');
if ($verifyRecaptcha) {
    $captchaFld = $frm->getField('htmlNote');
    $captchaFld->htmlBeforeField = '<div class = "field-set"><div class = "caption-wraper"><label class = "field_label"></label></div><div class = "field-wraper"><div class = "field_cover">';
    $captchaFld->htmlAfterField = '</div></div></div>';
}
?>
<section class="section bg-gradiant section--page-header text-center">
    <div class="container container--xl">
        <h1><?php echo Label::getLabel('LBL_WRITE_FOR_US'); ?></h1>
        <p class="p-large"><?php echo Label::getLabel('LBL_WRITE_FOR_US_TAG_LINE'); ?></p>
    </div>
</section>
<section class="section">
    <div class="container container--fixed">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8 col-md-10">
                <div class="box -padding-40 -skin">
                    <?php echo $frm->getFormHtml(); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<script src='https://www.google.com/recaptcha/api.js'></script>
<script>
    (function() {
        setupContribution = function(frm) {
            if (!$(frm).validate()) {
                return;
            }
            fcom.process();
            let formData = new FormData(frm);
            fcom.ajaxMultipart(fcom.makeUrl('Blog', 'setupContribution'), formData, function(ans) {
                <?php if ($verifyRecaptcha) { ?>
                    grecaptcha.reset();
                <?php } ?>
                if (ans.status == 1) {
                    frm.reset();
                }
            }, {
                fOutMode: 'json',
                failed: true
            });
        };
    })();
</script>