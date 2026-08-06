<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'lessonPlanFrm');
$frm->setFormTagAttribute('enctype', 'multipart/form-data');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$titleFld = $frm->getField('plan_title');
$levelFld = $frm->getField('plan_level');
$detailFld = $frm->getField('plan_detail');
$fileFld = $frm->getField('plan_file[]');
$filesHtml = '';
$fileDisplay = $frm->getField('plan_file_display');
if (!empty($files)) {
    $filesHtml = '<div class="field-set filelink">';
    foreach ($files as $file) {
        $filesHtml .= '<span class="tag"><span><a target="_blank" href=' . MyUtility::makeFullUrl('Plans', 'download', [$planId, $planId, -1, $file['file_id']]) . '>' .
            ucwords($file['file_name']) . '&nbsp;</a></span><a href="javascript:void(0);" onclick="removeFile(this,' . $file['file_id'] . ', ' . $planId . ')">x</a></span>&nbsp;';
    }
    $filesHtml .= "</div>";
}
$fileDisplay->value = $filesHtml;
$exts = implode(", ", Afile::getAllowedExts(Afile::TYPE_LESSON_PLAN_FILE));
$fileSize = Afile::getAllowedUploadSize(Afile::TYPE_LESSON_PLAN_FILE);
$fileSize = MyUtility::convertBitesToMb($fileSize) . ' ' . Label::getLabel('LBL_MB');
$fld = $frm->getField('plan_file[]');
$fld->htmlAfterField = "<small>" . str_replace(['{size}', '{ext}'], [$fileSize, $exts], Label::getLabel('LBL_FILE_MAX_SIZE_{size}_AND_ALLOWED_EXT_{ext}')) . "</small>";

$submitBtn = $frm->getField('btn_submit');
$cancelBtn = $frm->getField('btn_cancel');
$cancelBtn->addFieldTagAttribute('onClick', 'cancel();');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_SETUP_LESSON_PLAN'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormTag(); ?>
    <?php echo $frm->getFieldHTML('plan_id'); ?>
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
                        <?php echo $levelFld->getCaption(); ?>
                        <?php if ($levelFld->requirement->isRequired()) { ?>
                            <span class="spn_must_field">*</span>
                        <?php } ?>
                    </label>
                </div>
                <div class="field-wraper">
                    <div class="field_cover">
                        <?php echo $levelFld->getHtml(); ?>
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
        <div class="col-md-12">
            <div class="field-set">
                <div class="caption-wraper">
                    <label class="field_label">
                        <?php echo $fileFld->getCaption(); ?>
                        <?php if ($fileFld->requirement->isRequired()) { ?>
                            <span class="spn_must_field">*</span>
                        <?php } ?>
                    </label>
                </div>
                <div class="field-wraper">
                    <div class="field_cover">
                        <?php echo $fileFld->getHtml(); ?>
                        <?php echo $fileDisplay->getHtml(); ?>
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
                        <?php echo $cancelBtn->getHtml(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
    <?php echo $frm->getExternalJS(); ?>
</div>