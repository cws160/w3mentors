    <?php
    defined('SYSTEM_INIT') or die('Invalid Usage.');
    $frm->setFormTagAttribute('id', 'experienceFrm');
    $frm->setFormTagAttribute('class', 'form');
    $frm->setFormTagAttribute('onsubmit', 'setupTeacherQualification(this); return(false);');
    $expType = $frm->getField('uqualification_experience_type');
    $institute = $frm->getField('uqualification_institute_name');
    $address = $frm->getField('uqualification_institute_address');
    $description = $frm->getField('uqualification_description');
    $startYear = $frm->getField('uqualification_start_year');
    $endYear = $frm->getField('uqualification_end_year');
    $title = $frm->getField('uqualification_title');
    $certificate = $frm->getField('certificate');
    $exts = implode(", ", Afile::getAllowedExts(Afile::TYPE_USER_QUALIFICATION_FILE));
    $fileSize = Afile::getAllowedUploadSize(Afile::TYPE_USER_QUALIFICATION_FILE);
    $fileSize = MyUtility::convertBitesToMb($fileSize) . ' ' . Label::getLabel('LBL_MB');
    $label = str_replace(['{size}', '{ext}'], [$fileSize, $exts], Label::getLabel('LBL_CERTIFICATE_MAX_SIZE_{size}_AND_ALLOWED_EXT_{ext}'));
    $certificate->htmlAfterField = "<small>" . $label . "</small>";
    $resetBtn = $frm->getField('btn_reset');
    $submitBtn = $frm->getField('btn_submit');
    ?>
    <div class="modal-header">
        <h5><?php echo Label::getLabel('LBL_SETUP_RESUME'); ?></h5>
        <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
    </div>
    <div class="modal-body">
        <?php echo $frm->getFormTag(); ?>
        <?php echo $frm->getFieldHtml('uqualification_id'); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $expType->getCaption(); ?>
                            <?php if ($expType->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $expType->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $title->getCaption(); ?>
                            <?php if ($title->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $title->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $institute->getCaption(); ?>
                            <?php if ($institute->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $institute->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $address->getCaption(); ?>
                            <?php if ($address->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $address->getHtml(); ?>
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
                            <?php echo $description->getCaption(); ?>
                            <?php if ($description->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $description->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $startYear->getCaption(); ?>
                            <?php if ($startYear->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $startYear->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $endYear->getCaption(); ?>
                            <?php if ($endYear->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $endYear->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $certificate->getCaption(); ?>
                            <?php if ($certificate->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $certificate->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row text-right">
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