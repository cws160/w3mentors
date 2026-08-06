<?php
defined('SYSTEM_INIT') or exit('Invalid Usage.');
$recaptchaKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
$contactFrm->setFormTagAttribute('class', 'form form--normal');
if (!empty($recaptchaKey)) {
    $captchaFld = $contactFrm->getField('htmlNote');
    $captchaFld->htmlBeforeField = '<div class="field-set"><div class="caption-wraper"><label class="field_label"></label></div><div class="field-wraper"><div class="field_cover">';
    $captchaFld->htmlAfterField = '</div></div></div>';
}

$contactFrm->setFormTagAttribute('onsubmit', 'contactSetup(this); return(false);');
$contactFrm->developerTags['colClassPrefix'] = 'col-md-';
$contactFrm->developerTags['fld_default_col'] = 12;
$nameFld = $contactFrm->getField('name');
$phoneFld = $contactFrm->getField('phone');
$emailFld = $contactFrm->getField('email');
$messageFld = $contactFrm->getField('message');
?>
<section class="section bg-grey">
    <div class="container container--fixed">
        <?php echo FatUtility::decodeHtmlEntities($contactBanner); ?>
        <div class="contact-wrapper">
            <div class="row g-5 justify-content-around">
                <?php echo FatUtility::decodeHtmlEntities($contactLeftSection); ?>
                <div class="col-md-7 col-lg-6">
                    <div class="contact-form">
                        <?php echo $contactFrm->getFormTag() ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"><?php echo Label::getLabel('LBL_Name') ?>
                                            <?php if ($nameFld->requirement->isRequired()) { ?>
                                                <span class="spn_must_field">*</span>
                                            <?php } ?>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $contactFrm->getFieldHTML('name'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"><?php echo Label::getLabel('LBL_Phone_no') ?>
                                            <?php if ($phoneFld->requirement->isRequired()) { ?>
                                                <span class="spn_must_field">*</span></label>
                                    <?php } ?>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $contactFrm->getFieldHTML('phone'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"><?php echo Label::getLabel('LBL_Email'); ?>
                                            <?php if ($emailFld->requirement->isRequired()) { ?>
                                                <span class="spn_must_field">*</span></label>
                                    <?php } ?>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $contactFrm->getFieldHTML('email'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"><?php echo Label::getLabel('LBL_Message'); ?>
                                            <?php if ($messageFld->requirement->isRequired()) { ?>
                                                <span class="spn_must_field">*</span></label>
                                    <?php } ?>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $contactFrm->getFieldHTML('message'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($siteKey) && !empty($secretKey)) { ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="field-set">
                                        <div class="field-wraper">
                                            <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-set">
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $contactFrm->getFieldHTML('btn_submit'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                        <?php echo $contactFrm->getExternalJS(); ?>
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
    /* global fcom */
    (function() {
        contactSetup = function(frm) {
            if (!$(frm).validate()) {
                return;
            }
            fcom.process();
            fcom.updateWithAjax(fcom.makeUrl('Contact', 'contactSubmit'), fcom.frmData(frm), function(res) {
                frm.reset();
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
            });
        };
    })();
</script>