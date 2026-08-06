<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$form->developerTags['colClassPrefix'] = 'col-md-';
$form->developerTags['fld_default_col'] = 12;
$form->setFormTagAttribute('id', 'profileLangInfoFrm');
$form->setFormTagAttribute('class', 'form form--' . $languages[$langId]['language_direction'] ?? 'ltr');
$form->setFormTagAttribute('onsubmit', 'setUpProfileLangInfo(this, false); return(false);');
$profileInfo = $form->getField('user_biography');
$langFld = $form->getField('userlang_lang_id');
$langFld->addFieldTagAttribute('class', 'd-none');
$languagesKeys = array_keys($languages);
$lastLangId = end($languagesKeys);
$nextButton = $form->getField('btn_next');
$nextButton->addFieldTagAttribute('class', '');
$nextButton->addFieldTagAttribute('onclick', 'setUpProfileLangInfo(this.form, true); return(false);');
if ($lastLangId == $langId) {
    $nextButton->setFieldTagAttribute('onclick', 'setUpProfileLangInfo(this.form, false, true);  return(false);');
}
$autoTranslateFld = $form->getField('update_langs_data') ?? null;
?>
<div class="padding-6">
    <div class="max-width-80">
        <?php
        echo $form->getFormTag();
        echo $form->getFieldHtml('userlang_lang_id');
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label"><?php echo $profileInfo->getCaption(); ?>
                            <?php if ($profileInfo->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $profileInfo->getHTML(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="field-set">
                    <?php if ($autoTranslateFld && $siteUserType == User::TEACHER) { ?>
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




        <div class="row submit-row submit-row-lang">

            <div class="col-sm-12">
                <div class="field-set">
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php
                            echo $form->getFieldHtml('btn_submit');
                            if ($lastLangId != $langId || $siteUserType == User::TEACHER) {
                                echo $form->getFieldHtml('btn_next');
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
        <?php echo $form->getExternalJS(); ?>
    </div>
</div>