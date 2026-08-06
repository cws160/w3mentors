<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('autocomplete', 'off');
$frm->setFormTagAttribute('onsubmit', 'updateEmail(this); return(false);');
$fld = $frm->getField('new_email');
$fld->developerTags['col'] = 12;
$fld = $frm->getField('conf_new_email');
$fld->developerTags['col'] = 12;
$fld = $frm->getField('btn_submit');
$fld->developerTags['col'] = 12;
?>

<section class="section bg-gradiant">
    <div class="container container--fixed">
        <div class="mb-5 text-center">
            <?php echo Label::getLabel('LBL_PLEASE_CONTACT_WEMASTER'); ?> <a class="link" href="mailto:<?php echo FatApp::getConfig('conf_site_owner_email') ?>"><?php echo FatApp::getConfig('conf_site_owner_email') ?></a>
        </div>
        <div class="site-form-wrapper">
            <div class="site-form">
                <div class="site-form__header text-center mb-4">
                    <h4><?php echo Label::getLabel('LBL_UPDATE_EMAIL'); ?></h4>
                </div>
                <div class="site-form__body"><?php echo $frm->getFormHtml(); ?></div>
            </div>
        </div>
    </div>
</section>