<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'questionFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupQuestion(this); return false;');
$titleFld = $frm->getField('ques_title');
$typeFld = $frm->getField('ques_type');
$typeFld->setFieldTagAttribute('id', 'ques_type');
$typeFld->setFieldTagAttribute('onchange', 'showOptions(this.value);');
$detailFld = $frm->getField('ques_detail');
$catFld = $frm->getField('ques_cate_id');
$catFld->setFieldTagAttribute('onchange', 'getSubcategories(this.value, "#subCateAddQues");');
$subCatFld = $frm->getField('ques_subcate_id');
$subCatFld->setFieldTagAttribute('id', 'subCateAddQues');
$hintFld = $frm->getField('ques_hint');
$marksFld = $frm->getField('ques_marks');
$marksFld->setFieldTagAttribute('oninput', "this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null");
$optionCount = $frm->getField('ques_options_count');
$optionCount->setFieldTagAttribute('oninput', "this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null");
$addOptionsFld = $frm->getField('add_options');
$addOptionsFld->setFieldTagAttribute('onclick', 'addOptions();');
$submitButton = $frm->getField('submit');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_SETUP_QUESTION'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="form-edit-head">

    </div>
    <div class="form-edit-body">
        <?php echo $frm->getFormTag(); ?>
        <?php echo $frm->getFieldHTML('ques_id'); ?>
        <div class="row">
            <div class="col-md-8">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $titleFld->getCaption(); ?>
                            <?php if ($titleFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $titleFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $typeFld->getCaption(); ?>
                            <?php if ($typeFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $typeFld->getHtml(); ?>
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
                            <?php echo $detailFld->getCaption(); ?>
                            <?php if ($detailFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $detailFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $catFld->getCaption(); ?>
                            <?php if ($catFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $catFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $subCatFld->getCaption(); ?>
                            <?php if ($subCatFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $subCatFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $marksFld->getCaption(); ?>
                            <?php if ($marksFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo str_replace('type="text"', 'type="number"', $marksFld->getHtml()); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $hintFld->getCaption(); ?>
                            <?php if ($hintFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $hintFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row options-container" style="<?php echo (empty($typeFld->value) || $typeFld->value == Question::TYPE_TEXT) ? 'display: none;' : ''; ?>">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $optionCount->getCaption(); ?>
                            <?php if ($optionCount->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $optionCount->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">&nbsp;</label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $addOptionsFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="more-container-js">
            <?php
            if (count($options) > 0) {
                $this->includeTemplate(
                    'questions/option-form.php',
                    array(
                        'type' => $typeFld->value,
                        'count' => $optionCount->value,
                        'frm' => $optionsFrm,
                        'options' => $options,
                        'answers' => $answers
                    ),
                    false
                );
            }
            ?>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="field-set mb-0">
                    <div class="field-wraper form-buttons-group">
                        <div class="field_cover">
                            <?php echo $submitButton->getHtml(); ?>
                            <?php
                            if ($quizType > 0) {
                                $btn = $frm->getField('btn_back');
                                $btn->addFieldTagAttribute('onclick', "$('.addQuesJs').click();");
                                echo $btn->getHtml();
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJS(); ?>
    </div>
</div>
<script>
    $(document).ready(function() {
        getSubcategories('<?php echo $catFld->value ?? 0; ?>', '#subCateAddQues', '<?php echo $subCatFld->value ?? 0; ?>');
        addOptions();
    });
</script>