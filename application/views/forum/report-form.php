<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form ');
$frm->setFormTagAttribute('onsubmit', 'reportSetup(this); return(false);');
$fld = $frm->getField('btn_submit');
$fld->setFieldTagAttribute('class', 'btn btn-primary');
$fld = $frm->getField('rep_reason');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_Forum_Report_Question_As_Spam'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>