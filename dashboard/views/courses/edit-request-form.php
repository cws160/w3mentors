<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'editRequestSetup(this); return(false);');
if ($frm->getField('coedre_reason')) {
    $frm->getField('coedre_reason')->htmlAfterField = "<br><small>" . Label::getLabel("LBL_PLEASE_ADD_THE_REASON_FOR_THE_EDIT_REQUEST") . ".</small>";
}
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_EDIT_COURSE'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">

    <h6><?php echo Label::getLabel('LBL_IMPORTANT_INSTRUCTIONS:'); ?></h6>
    <div class="check-list mt-4 mb-3">
        <ul class="">
            <li><?php echo Label::getLabel('LBL_COURSE_EDIT_ACCESS_FOR_LIMITED_DURATION'); ?></li>
            <li><?php echo Label::getLabel('LBL_COURSE_CHANGES_WILL_VISIBLE_TO_THE_USERS'); ?></li>
            <li><?php echo Label::getLabel('LBL_AFTER_DURATION_COURSE_WILL_BE_PUBLISHED_AUTOMATICALLY'); ?></li>
        </ul>
    </div>
    <?php
    echo $frm->getFormHtml();
    ?>
</div>