<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'cancelSetup(this); return(false);');
$fld = $frm->getField('comment');
if($class['grpcls_booked_seats'] > 0){
    $html = '<span class="-color-primary">' . sprintf(Label::getLabel('LBL_NOTE:_REFUND_WOULD_BE_%s_PERCENT'), $refundPercentage) . '</span>';
    if ($siteUserType == User::LEARNER && ($class['ordcls_discount'] > 0 || $class['ordcls_reward_discount'] > 0)) {
        $html .= '<br><span class="-color-primary color-secondary">' . Label::getLabel('LBL_NOTE_CANCEL_CLASS_DISCOUNT_ORDER_TEXT') . '</span>';
    }
    $fld->htmlAfterField = $html;
}
?>

<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_CANCEL_CLASS'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>
