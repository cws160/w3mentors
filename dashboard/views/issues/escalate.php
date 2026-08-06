<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'escalateIssueForm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'escalateSetup(this); return(false);');
$commentFld = $frm->getField('reislo_comment');
$submitBtn = $frm->getField('btn_submit');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_ESCALATE_ISSUE_TO_SUPPORT_TEAM'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormTag(); ?>
    <?php echo $frm->getFieldHtml('reislo_repiss_id'); ?>
    <div class="row">
        <div class="col-md-12">
            <div class="field-set">
                <div class="caption-wraper">
                    <label class="field_label">
                        <?php echo $commentFld->getCaption(); ?>
                        <?php if ($commentFld->requirement->isRequired()) { ?>
                            <span class="spn_must_field">*</span>
                        <?php } ?>
                    </label>
                </div>
                <div class="field-wraper">
                    <div class="field_cover">
                        <?php echo $commentFld->getHtml(); ?>
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
                        <?php echo $submitBtn->getHtml(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
    <?php echo $frm->getExternalJS(); ?>
</div>