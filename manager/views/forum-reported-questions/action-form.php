<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('id', 'actionForm');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupAction(this); return(false);');
?>

<div class="form-edit-body">
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title">
                <?php echo Label::getLabel('LBL_ACTION_FORM'); ?>
            </h3>
        </div>
    </div>
    <?php echo $frm->getFormHtml(); ?>
</div>