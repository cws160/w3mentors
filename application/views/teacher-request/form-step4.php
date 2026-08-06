<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupStep4(this, true); return(false);');
?>
<?php $this->includeTemplate('teacher-request/_partial/leftPanel.php', ['step' => 4]); ?>
<div class="page-block__right">
    <div class="page-block__head">
        <div class="head__title">
            <h4><?php echo Label::getLabel('LBL_Tutor_registration'); ?></h4>
        </div>
    </div>
    <div class="page-block__body">
        <?php echo $frm->getFormTag() ?>
        <div class="block-content">
            <div class="block-content__head">
                <div>
                    <div class="info__content">
                        <h5><?php echo Label::getLabel('LBL_Resume_Section_Title'); ?></h5>
                        <p><?php echo Label::getLabel('LBL_Resume_section_desc'); ?></p>
                    </div>
                </div>
                <div>
                    <div class="info__action">
                        <a href="javascript:void(0);" onclick="teacherQualificationForm(0);" class="btn btn--bordered btn--small color-secondary qualification-add-js d-none"><?php echo Label::getLabel('LBL_ADD_RESUME'); ?></a>
                    </div>
                </div>
            </div>
            <div class="block-content__body">
                <div id="qualification-container"></div>
                <div class="accept--field mt-5">
                    <label class="checkbox">
                        <input type="checkbox" data-field-caption="<?php echo Label::getLabel('LBL_TERMS_&_CONDITIONS'); ?>" name="tereq_terms" data-fatreq='{"required":true}' value="1" <?php echo $frm->getField('tereq_terms')->checked ? 'checked' : ''; ?> />
                        <i class="input-helper"></i>
                        <?php echo Label::getLabel('LBL_ACCEPT_TUTOR_APPROVAL'); ?>
                        <a class="link" target="_blank" href="<?php echo MyUtility::makeUrl('cms', 'view', [FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE')]) ?>"><?php echo Label::getLabel('LBL_TERMS_&_CONDITIONS'); ?></a>
                    </label>
                </div>
            </div>
            <div class="block-content__foot">
                <div class="form__actions">
                    <input type="submit" name="save" value="<?php echo Label::getLabel('LBL_SUBMIT'); ?>">
                </div>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJs(); ?>
    </div>
</div>
<script>
    $(document).ready(function() {
        searchTeacherQualification();
    });
</script>