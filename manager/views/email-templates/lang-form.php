<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'setupEtplLang(this); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;

$fld = $langFrm->getField('btn_submit');
$fld->setWrapperAttribute('class', 'flex-btns');

$fld = $langFrm->getField('btn_preview');

if ($etplCode == 'emails_header_footer_layout') {
    $fld->setFieldTagAttribute('style', 'display:none;');
} else {
    $fld->setFieldTagAttribute('onclick', 'setupAndPreview();');
}

$fld = $langFrm->getField('etpl_lang_id');
$fld->setFieldTagAttribute('onchange', 'langForm("' . $etplCode . '",this.value)');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_Email_Template_Setup'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $langFrm->getFormHtml(); ?>
    <a style="display:none;" id="previewTpl" target="_blank" href="<?php echo MyUtility::makeUrl('EmailTemplates', 'preview', [$etplCode, $langId]); ?>"></a>
</div>