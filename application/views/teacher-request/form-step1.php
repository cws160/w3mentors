<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupStep1(this); return(false);');
$usrFirstName = $frm->getField('tereq_first_name');
$usrLastName = $frm->getField('tereq_last_name');
$usrGender = $frm->getField('tereq_gender');
$usrPhoneCode = $frm->getField('tereq_phone_code');
$usrPhoneCode->setFieldTagAttribute('id', 'tereq_phone_code');
$usrPhone = $frm->getField('tereq_phone_number');
$usrPhone->setFieldTagAttribute('id', 'tereq_phone_number');
$usrPhotoId = $frm->getField('user_photo_id');
if (MyUtility::getLayoutDirection() == 'rtl') {
    $usrPhone->setFieldTagAttribute('style', 'direction: ltr;text-align:right;');
}
?>
<?php $this->includeTemplate('teacher-request/_partial/leftPanel.php', ['step' => 1]); ?>
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
                <div class="info__content">
                    <h5><?php echo Label::getLabel('LBL_Personal_Information'); ?></h5>
                    <p><?php echo Label::getLabel('LBL_tutor_reg_description') ?></p>
                </div>
            </div>
            <div class="block-content__body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo $usrFirstName->getCaption(); ?>
                                    <?php if ($usrFirstName->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $usrFirstName->getHTML(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo $usrLastName->getCaption(); ?>
                                    <?php if ($usrLastName->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $usrLastName->getHTML(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo $usrGender->getCaption(); ?>
                                    <?php if ($usrGender->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <!-- <div class="row g-3"> -->
                                        <?php echo $usrGender->getHtml(); ?>
                                    <!-- </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo $usrPhoneCode->getCaption(); ?>
                                    <?php if ($usrPhoneCode->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $usrPhoneCode->getHTML(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo $usrPhone->getCaption(); ?>
                                    <?php if ($usrPhone->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $usrPhone->getHTML(); ?>
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
                                    <?php echo $usrPhotoId->getCaption(); ?>
                                    <?php if ($usrPhotoId->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                                    <?php if (!empty($photoId)) { ?> &nbsp; &nbsp; <a class="color-secondary" href="<?php echo MyUtility::makeUrl('Image', 'download', [Afile::TYPE_TEACHER_APPROVAL_PROOF, $photoId['file_record_id']]) . '?t=' . time(); ?>"><?php echo Label::getLabel('LBL_DOWNLOAD'); ?></a><?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $usrPhotoId->getHtml(); ?>
                                    <small>(<?php
                                            $exts = implode(", ", Afile::getAllowedExts(Afile::TYPE_TEACHER_APPROVAL_PROOF));
                                            $fileSize = MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_TEACHER_APPROVAL_PROOF)) . ' ' . Label::getLabel('LBL_MB');
                                            echo str_replace(['{size}', '{ext}'], [$fileSize, $exts], Label::getLabel('LBL_FILE_MAX_SIZE_{size}_AND_ALLOWED_EXT_{ext}'));
                                            ?>)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="block-content__foot">
                <div class="form__actions">
                    <input type="submit" name="save" value="<?php echo Label::getLabel('LBL_SAVE'); ?>" />
                    <input type="button" name="next" onclick="setupStep1(document.frmFormStep1, true)" value="<?php echo Label::getLabel('LBL_NEXT'); ?>" />
                </div>
            </div>
        </div>
        <?php echo $frm->getFieldHtml('resubmit'); ?>
        </form>
        <?php echo $frm->getExternalJs(); ?>
    </div>
</div>
<script>
    var statusActive = '<?php echo Label::getLabel('LBL_Active'); ?>';
    var statusInActive = '<?php echo Label::getLabel('LBL_In-active'); ?>';
</script>