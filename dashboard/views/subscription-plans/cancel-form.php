<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'cancelSetup(this); return(false);');
if ($siteUserType == User::LEARNER && ($lesson['order_discount_value'] > 0 || $lesson['order_reward_value'] > 0)) {
    $noteFld = $frm->getField('comment');
    $noteFld->htmlAfterField = '<span class="-color-primary color-secondary">' . Label::getLabel('LBL_NOTE_CANCEL_LESSON_DISCOUNT_ORDER_TEXT') . '</span>';
}
$fld = $frm->getField('submit');
$fld->setWrapperAttribute('class', 'form-buttons-group');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_CANCEL_SUBSCRIPTION'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button> 
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>