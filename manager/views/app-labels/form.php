<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupLabels(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$fld = $frm->getField('applbl_key_label');
$fld->setFieldTagAttribute('disabled', 'disabled');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Setup_App_Labels'); ?></h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>