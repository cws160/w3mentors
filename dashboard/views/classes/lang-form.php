<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$languageIds = array_column($languages, 'language_direction', 'language_id');
$class = ($languageIds[$langId] == 'rtl') ? 'form form--rtl' : 'form form--ltr';
$frm->setFormTagAttribute('id', 'classLangForm');
$frm->setFormTagAttribute('class', $class);
$frm->setFormTagAttribute('onsubmit', 'setupLangData(this, true); return(false);');
$titleFld = $frm->getField('grpcls_title');
$descFld = $frm->getField('grpcls_description');
$submitBtn = $frm->getField('btn_submit');
$lastlangId = array_key_last($languageIds);
if ($lastlangId == $langId) {
    $submitBtn->value = Label::getLabel('LBL_SAVE', $langId);
}
$autoTranslateFld = $frm->getField('update_langs_data') ?? null;
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_SETUP_GROUP_CLASS'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="form-edit-head">
        <nav class="tabs tabs--line border-bottom-0">
            <ul class="lang-list">
                <li class="">
                    <a href="javascript:void(0)" onclick="addForm('<?php echo $classId ?>');"><?php echo Label::getLabel('LBL_GENERAL'); ?></a>
                </li>
                <?php foreach ($languages as $language) { ?>
                    <li class="<?php echo ($language['language_id'] == $langId) ? 'is-active' : '' ?> ">
                        <a href="javascript:void(0)" class="" data-id="<?php echo $language['language_id']; ?>" onclick="langForm('<?php echo $classId ?>', '<?php echo $language['language_id']; ?>')">
                            <?php echo $language['language_name']; ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </nav>
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

        <div class="row">
            <div class="col-md-12">
                <div class="field-set">

                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php if ($autoTranslateFld) { ?>
                                <label class="switch-group d-flex align-items-center">
                                    <span class="switch switch--small">
                                        <input class="switch__label" type="<?php echo $autoTranslateFld->fldType; ?>" name="<?php echo $autoTranslateFld->getName(); ?>" value="<?php echo $autoTranslateFld->value; ?>" <?php echo ($autoTranslateFld->checked) ? 'checked' : ''; ?>>
                                        <i class="switch__handle bg-green"></i>
                                    </span>
                                    <span class="switch-group__label free-trial-status-js ms-3"><?php echo $autoTranslateFld->getCaption(); ?></span>

                                </label>
                            <?php } ?>
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
</div>