<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'reportIssueForm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'issueSetup(this); return(false);');
if ($rewardDiscount > 0) {
    $comment = $frm->getField('repiss_comment');
    $comment->htmlAfterField = '<br><spam class="-color-primary color-secondary">' . Label::getLabel('LBL_REWARD_DISCOUNT_REFUND_INFO_TEXT') . '</spam>';
}
$fld = $frm->getField('submit');
$fld->setWrapperAttribute('class', 'form-buttons-group');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_ISSUE_REPORTED'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>
