<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'giftcardRedeem(this); return(false);');
$code = $frm->getField('giftcard_code');
$btnSubmit = $frm->getField('btn_submit');
$btnCancel = $frm->getField('btn_cancel');
$btnCancel->addFieldTagAttribute('onclick', 'cancel();');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_REDEEM_GIFTCARD'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormTag(); ?>
    <form class="form">
        <div class="row">
            <div class="col-md-12">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $code->getCaption(); ?>
                            <?php if ($code->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $code->getHtml(); ?>
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