<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langIds = array_column($siteLanguages, 'language_direction', 'language_id');
$formClass = (($langIds[$tabLangId] ?? '') == 'rtl') ? 'form form--rtl' : 'form form--ltr';
$frm->setFormTagAttribute('class', $formClass);
$frm->setFormTagAttribute('onsubmit', 'langSetup(this, true); return(false);');
$fld = $frm->getField('gclang_grpcls_id');
$titleFld = $frm->getField('grpcls_title');
$descFld = $frm->getField('grpcls_description');
$descFld->setFieldTagAttribute('style', 'height:100px;');
$tlangFld = $frm->getField('grpcls_tlang_id');
$classTitleFld = $frm->getField('title[]');
$submitBtn = $frm->getField('btn_submit');
$languages = array_column($siteLanguages, 'language_name', 'language_id');
$lastlangId = array_key_last($languages);
$counter = 1;
if ($lastlangId == $tabLangId) {
    $submitBtn->value = Label::getLabel('LBL_SAVE', $tabLangId);
}
$autoTranslateFld = $frm->getField('update_langs_data') ?? null;
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_SETUP_CLASS_PACKAGE'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="form-edit-head">
        <div class="tabs tabs--line border-bottom-0">
            <ul class="lang-list">
                <li><a href="javascript:void(0);" onclick="form('<?php echo $packageId ?>');"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
                <?php foreach ($languages as $langId => $language) { ?>
                    <li class="<?php echo ($langId == $tabLangId) ? 'is-active' : '' ?>"><a href="javascript:void(0)" <?php if ($packageId > 0) { ?> onclick="langForm(<?php echo $packageId ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $language; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="form-edit-body">
        <?php echo $frm->getFormTag(); ?>
        <?php echo $frm->getFieldHTML('gclang_grpcls_id'); ?>
        <?php echo $frm->getFieldHTML('gclang_lang_id'); ?>
        <div class="row">
            <div class="col-md-12">
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
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $descFld->getCaption(); ?>
                            <?php if ($descFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $descFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php foreach ($classes as $class) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $classTitleFld->getCaption() . '-' . $counter; ?>
                                <?php if ($classTitleFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <input type="text" data-field-caption="<?php echo $classTitleFld->getCaption() . '-' . $counter; ?>" data-fatreq="{&quot;required&quot;:true,&quot;lengthrange&quot;:[10,100]}" name="title[<?php echo $class['grpcls_id']; ?>]" value="<?php echo $class['grpcls_title']; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php $counter++; ?>
        <?php } ?>


        <?php if ($autoTranslateFld) { ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="field-set">
                        <div class="field-wraper">
                            <div class="field_cover">
                                <label class="switch-group d-flex align-items-center">
                                    <span class="switch switch--small">
                                        <input class="switch__label" type="<?php echo $autoTranslateFld->fldType; ?>" name="<?php echo $autoTranslateFld->getName(); ?>" value="<?php echo $autoTranslateFld->value; ?>" <?php echo ($autoTranslateFld->checked) ? 'checked' : ''; ?>>
                                        <i class="switch__handle bg-green"></i>
                                    </span>
                                    <span class="switch-group__label free-trial-status-js ms-3"><?php echo $autoTranslateFld->getCaption(); ?></span>

                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>


        <div class="row">
            <div class="col-12">
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
</div>