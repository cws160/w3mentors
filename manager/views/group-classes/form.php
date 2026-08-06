<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'groupClassesFrm');
$frm->setFormTagAttribute('enctype', 'multipart/form-data');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$dateformat = FatApp::getConfig('CONF_DATEPICKER_FORMAT', FatUtility::VAR_STRING, 'Y-m-d');
$timeformat = FatApp::getConfig('CONF_DATEPICKER_FORMAT_TIME', FatUtility::VAR_STRING, 'H:i');
$frm->getField('grpcls_start_datetime')->setFieldTagAttribute('data-fatdatetimeformat', $dateformat . ' ' . $timeformat);
$frm->getField('grpcls_end_datetime')->setFieldTagAttribute('data-fatdatetimeformat', $dateformat . ' ' . $timeformat);
?>
<div class="card">
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title">
                <?php echo Label::getLabel('LBL_GROUP_CLASS_SETUP'); ?>
            </h3>
        </div>
    </div>
    <div class="card-body">
        <?php echo $frm->getFormHtml(); ?>
    </div>
</div>