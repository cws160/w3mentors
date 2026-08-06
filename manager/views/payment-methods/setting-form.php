<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'settingSetup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$submitField = $frm->getField('btn_submit');
$submitField->htmlBeforeField = "<p>" . CommonHelper::renderHtml($pmethod['pmethod_info']) . "<p><br>";
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_' . $pmethod['pmethod_code']); ?>
            <?php echo Label::getLabel('LBL_Settings'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>