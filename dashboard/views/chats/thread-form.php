<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('onSubmit', 'setupThreadForm(this); return false;');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;

$sizeLabel = Label::getLabel('LBL_FILE_SIZE_SHOULD_BE_LESS_THAN_{FILE-SIZE}_MB');
$sizeLabel = str_replace('{file-size}',  MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_MESSAGE_ATTACHMENT)), $sizeLabel);
$formatsLabel = Label::getLabel('LBL_SUPPORTED_FILE_FORMATS_ARE_{file-formats}');
$formatsLabel = str_replace('{file-formats}', implode(', ', Afile::getAllowedExts(Afile::TYPE_MESSAGE_ATTACHMENT)), $formatsLabel);

$fld = $frm->getField('upload');
$fld->htmlAfterField = "<small>" . $sizeLabel . ' & ' . $formatsLabel . '</small>';
$fld = $frm->getField('btn_submit');
$fld->setWrapperAttribute('class', 'form-buttons-group');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_START_CONVERSATION'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>