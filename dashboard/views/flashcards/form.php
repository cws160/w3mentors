<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$tlangId = $frm->getField('flashcard_tlang_id');
$title = $frm->getField('flashcard_title');
$detail = $frm->getField('flashcard_detail');
$btnSubmit = $frm->getField('btn_submit');
$btnCancel = $frm->getField('btn_cancel');
$btnCancel->addFieldTagAttribute('onclick', 'cancel();');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_SETUP_FLASHCARD'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormTag(); ?>
    <?php echo $frm->getFieldHTML('flashcard_id'); ?>
    <?php echo $frm->getFieldHTML('flashcard_type'); ?>
    <?php echo $frm->getFieldHTML('flashcard_type_id'); ?>
    <div class="row">
        <div class="col-md-6">
            <div class="field-set">
                <div class="caption-wraper">
                    <label class="field_label">
                        <?php echo $tlangId->getCaption(); ?>
                        <?php if ($tlangId->requirement->isRequired()) { ?>
                            <span class="spn_must_field">*</span>
                        <?php } ?>
                    </label>
                </div>
                <div class="field-wraper">
                    <div class="field_cover">
                        <?php echo $tlangId->getHtml(); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
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
    <div class="row">
        <div class="col-sm-12">
            <div class="field-set mb-0">
                <div class="field-wraper form-buttons-group">
                    <div class="field_cover">
                        <?php echo $btnSubmit->getHtml(); ?>
                        <?php echo $btnCancel->getHtml(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
    <?php echo $frm->getExternalJS(); ?>
</div>