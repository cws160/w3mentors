<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'cancelSetup(this); return(false);');
$fld = $frm->getField('submit');
$fld->setWrapperAttribute('class', 'form-buttons-group');
$noteFld = $frm->getField('note_text');

$noteFld->htmlAfterField = '<span class="-color-primary">' . str_replace('{percent}', $refundPercentage, Label::getLabel('LBL_Refund_Would_Be_{percent}%.')). '</span>';
if ($siteUserType == User::LEARNER && ($lesson['order_discount_value'] > 0 || $lesson['ordles_reward_discount'] > 0)) {
    $noteFld->htmlAfterField .=  '<br><span class="-color-primary color-secondary">' . Label::getLabel('LBL_NOTE_CANCEL_LESSON_DISCOUNT_ORDER_TEXT') . '</span>';
}
if (!empty($lesson['ordles_ordsplan_id'])) {
    $noteFld->htmlAfterField = '<span class="-color-primary">' . Label::getLabel('LBL_LESSON_WILL_BE_RECREDITED_TO_THE_SUBSCRIPTION') . '</span>';
}
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_CANCEL_LESSON'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>