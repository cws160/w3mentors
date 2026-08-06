<?php
defined('SYSTEM_INIT') or exit('Invalid Usage.');
$preferencesFrm->setFormTagAttribute('id', 'teacherPreferencesFrm');
$preferencesFrm->setFormTagAttribute('class', 'form');
$preferencesFrm->setFormTagAttribute('onsubmit', 'setupTeacherPreferences(this, false); return(false);');
$preferencesFrm->developerTags['colClassPrefix'] = 'col-md-';
$preferencesFrm->developerTags['fld_default_col'] = 12;
$nextBtn = $preferencesFrm->getField('btn_next');
$nextBtn->addFieldTagAttribute('onclick', 'setupTeacherPreferences(this.form, true); return(false);');
$getAllfields = $preferencesFrm->getAllFields();
?>
<div class="content-panel__head border-bottom mb-3">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_MANAGE_SKILLS'); ?></h5>
        </div>
    </div>
</div>
<div class="content-panel__body">
    <?php echo $preferencesFrm->getFormTag(); ?>
    <div class="form__body">
        <?php
        foreach ($getAllfields as $key => $field) {
            if (in_array($field->getName(), ['submit', 'btn_next', 'btn_back'])) {
                continue;
            }
            $field->developerTags['cbHtmlBeforeCheckbox'] = '<span class="checkbox">';
            $field->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i></span>';
        ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label"> <?php echo $field->getCaption(); ?></label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $field->getHTML(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
        ?>
    </div>
    <div class="form__actions">
        <div class="d-flex align-items-center gap-1">
            <?php echo $preferencesFrm->getFieldHTML('submit'); ?>
            <?php echo $preferencesFrm->getFieldHTML('btn_next'); ?>
        </div>
    </div>
    </form>
</div>
<?php echo $preferencesFrm->getExternalJS(); ?>