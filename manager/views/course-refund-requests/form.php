<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'updateStatus(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;

$fld = $frm->getField('corere_status');
$fld->setFieldTagAttribute('onChange', 'showHideCommentBox(this.value);');
$label = Label::getLabel('LBL_NOTE:_LEARNER_HAS_ALREADY_COMPLETED_{percent}%_OF_THE_COURSE');
if ($data['crspro_status'] == CourseProgress::COMPLETED) {
    $label =  str_replace('{percent}', 100, $label);
} else {
    $label =  str_replace('{percent}', $data['crspro_progress'], $label);
}
$fld->htmlAfterField = '<small>' . $label . '</small>';

$fld = $frm->getField('corere_comment');
$fld->setWrapperAttribute('id', 'remarkField');
$fld = $frm->getField('btn_submit');

?>
<div class="card">
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title">
                <?php echo Label::getLabel('LBL_UPDATE_STATUS'); ?>
            </h3>
        </div>
    </div>
    <div class="card-body">
        <?php echo $frm->getFormHtml(); ?>
    </div>
</div>