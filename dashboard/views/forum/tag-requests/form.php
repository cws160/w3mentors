<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->addFormTagAttribute('onsubmit', 'setupApprovalRequest(this); return false;');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$fld = $frm->getField('btn_submit');
$fld->setWrapperAttribute('class', 'form-buttons-group');

?>
<div class="modal-header">
    <h4><?php echo Label::getLabel('LBL_REQUEST_NEW_TAG'); ?></h4>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>