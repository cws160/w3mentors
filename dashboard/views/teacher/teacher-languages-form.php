<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'teacherPreferencesFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupTeacherLanguages(this, false); return(false);');
$teachLangField = $frm->getField('teach_lang_id');
$teachLangFieldValue = $teachLangField->value;
$nextBtn = $frm->getField('next_btn');
$nextBtn->addFieldTagAttribute("onClick", "setupTeacherLanguages(this.form, true); return(false);");
$saveBtn = $frm->getField('submit');
?>
<div class="content-panel__head">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_MANAGE_LANGUAGES'); ?></h5>
        </div>
        <div></div>
    </div>
</div>
<div class="content-panel__body">
    <div class="form">
        <?php echo $frm->getFormTag(); ?>
        <div class="form__body">
            <div class="colum-layout">
                <div class="colum-layout__cell">
                    <div class="colum-layout__head">
                        <span class="bold-600"><?php echo $teachLangField->getCaption(); ?></span>
                        <?php if ($teachLangField->requirement->isRequired()) { ?>
                            <span class="spn_must_field">*</span>
                        <?php } ?>
                    </div>
                    <div class="colum-layout__body">
                        <div class="colum-layout__scroll scrollbar">
                            <div class="multilevel-dropdown p-4 accordionJs">
                                <?php $this->includeTemplate('teacher/languages.php', ['languages' => $teachLangField->options, 'values' => $teachLangField->value]) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="colum-layout__cell">
                    <div class="colum-layout__head">
                        <span class="bold-600"><?php echo Label::getLabel('LBL_LANGUAGE_I_SPEAK'); ?></span>
                        <span class="spn_must_field">*</span>
                    </div>
                    <div class="colum-layout__body">
                        <div class="colum-layout__scroll scrollbar">
                            <?php
                            if ($profRequired) {
                                foreach ($speakLangs as $key => $value) {
                                    $speakLangField = $frm->getField('uslang_slang_id[' . $key . ']');
                                    $proficiencyField = $frm->getField('uslang_proficiency[' . $key . ']');
                                    $proficiencyField->addFieldTagAttribute('onchange', 'changeProficiency(this,' . $key . ');');
                                    $proficiencyField->addFieldTagAttribute('data-lang-id', $key);
                                    $isLangSpeak = $speakLangField->checked;
                            ?>
                                    <div class="selection selection--select slanguage-<?php echo $key; ?> <?php echo ($isLangSpeak) ? 'is-selected' : ''; ?>">
                                        <label class="selection__trigger ">
                                            <input type="checkbox" value="<?php echo $key; ?>" class="slanguage-checkbox-js slanguage-checkbox-<?php echo $key; ?>" onchange="changeSpeakLang(this, <?php echo $key; ?>);" name="<?php echo $speakLangField->getName(); ?>" <?php echo ($isLangSpeak) ? 'checked' : ''; ?>>
                                            <span class="selection__trigger-action">
                                                <span class="selection__trigger-label">
                                                    <?php echo $value; ?>
                                                    <?php if (array_key_exists($proficiencyField->value, $profArr)) { ?>
                                                        <span class="badge color-secondary badge-js  badge--round badge--small m-0">
                                                            <?php echo $profArr[$proficiencyField->value]; ?>
                                                        </span>
                                                    <?php } ?>
                                                </span>
                                                <span class="selection__trigger-icon"></span>
                                            </span>
                                        </label>
                                        <div class="selection__target">
                                            <?php echo $proficiencyField->getHTML(); ?>
                                        </div>
                                    </div>
                                <?php }
                            } else {
                                foreach ($speakLangs as $key => $value) {
                                    $speakLangField = $frm->getField('uslang_slang_id[' . $key . ']');
                                    $isLangSpeak = $speakLangField->checked;
                                ?>
                                    <div class="selection">
                                        <label class="selection__trigger">
                                            <input name="<?php echo $speakLangField->getName(); ?>" value="<?php echo $key; ?>" <?php echo $isLangSpeak ? 'checked' : ''; ?> class="selection__trigger-input" type="checkbox">
                                            <span class="selection__trigger-action">
                                                <span class="selection__trigger-label"><?php echo $value; ?></span>
                                                <span class="selection__trigger-icon"></span>
                                            </span>
                                        </label>
                                    </div>
                            <?php }
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form__actions">
            <div class="d-flex align-items-center gap-1">
                <?php
                echo $saveBtn->getHTML();
                echo $nextBtn->getHTML();
                ?>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJS(); ?>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('.is-dropdown').css({
            'display': 'none'
        });
        $('input[type=checkbox]:checked').each(function() {
            $(this).parents('.is-dropdown').siblings('.accordion-header').addClass('is-active');
            $(this).parents('.is-dropdown').slideDown();
        });
        $('.accordion-header').click(function() {
            $(this).toggleClass('is-active');
            $(this).next('.is-dropdown').slideToggle();
        });
    });
</script>