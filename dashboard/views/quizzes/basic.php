<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'questionFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setup(); return false;');
$title = $frm->getField('quiz_title');
$type = $frm->getField('quiz_type');
$type->addFieldTagAttribute('id', 'quizTypeJs');
$typeId = $frm->getField('quiz_type_id');
if ($quizId > 0) {
    $typeId->addFieldTagAttribute('disabled', 'disabled');
} else {
    $typeId->addFieldTagAttribute('onchange', 'setType(this.value)');
}
$detail = $frm->getField('quiz_detail');
$submit = $frm->getField('btn_submit');
?>
<?php echo $frm->getFormTag(); ?>
<div class="page-layout">
    <div class="page-layout__small">
        <?php
        echo $this->includeTemplate('quizzes/navigation.php', [
            'quizId' => $quizId, 'active' => 1, 'frm' => $frm
        ])
        ?>
    </div>
    <div class="page-layout__large">
        <div class="box-panel">
            <div class="box-panel__head  border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4><?php echo Label::getLabel('LBL_SETUP_QUIZ'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="box-panel__body">
                <div class="box-panel__container">
                    <?php echo $frm->getFieldHTML('quiz_id'); ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $title->getCaption(); ?>
                                        <?php if ($title->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $title->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $typeId->getCaption(); ?>
                                        <?php if ($typeId->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $typeId->getHtml(); ?>
                                        <?php echo $type->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $detail->getCaption(); ?>
                                        <?php if ($detail->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $detail->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</form>
<?php echo $frm->getExternalJS(); ?>