<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');

$fld = $frm->getField('resource_files[]');
$fld->htmlAfterField = "<small>" . str_replace(['{filesize}', '{extension}'], [$filesize, $allowedExtensions], Label::getLabel('LBL_NOTE:_ALLOWED_SIZE_{filesize}_MB._SUPPORTED_FILE_FORMATS_{extension}')) . "</small>";
$cancelBtn = $frm->getField('btn_cancel');
$cancelBtn->addFieldTagAttribute('onClick', 'cancel();');
?>

<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_UPLOAD_RESOURCES'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormTag(); ?>
    <div class="row">
        <div class="col-md-12">
            <div class="field-set">
                <div class="caption-wraper">
                    <label class="field_label">
                        <?php
                        $fld = $frm->getField('resource_files[]');
                        echo $fld->getCaption();
                        ?>
                        <span class="spn_must_field">*</span>
                    </label>
                </div>
                <div class="field-wraper">
                    <div class="field_cover">
                        <?php echo $fld->getHtml(); ?>
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
                        <?php echo $frm->getFieldHtml('btn_submit'); ?>
                        <?php echo $cancelBtn->getHtml(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
    <?php echo $frm->getExternalJS(); ?>
</div>