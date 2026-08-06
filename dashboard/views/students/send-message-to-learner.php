<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'bankInfoFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'messageToLearnerSetup(this); return(false);');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_Send_Message'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button> 
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>