<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'cancelSetup(this); return(false);');
if ($course['order_reward_value'] > 0 || $course['ordcrs_discount'] > 0) {
    $frm->getField('comment')->htmlAfterField = '<span class="-color-primary color-secondary">' . Label::getLabel('LBL_COURSE_DISCOUNTS_REFUND_INFO_TEXT') . '</span>';
}
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_CANCEL_COURSE'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>