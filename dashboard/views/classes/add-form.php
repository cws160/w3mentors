<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'groupClassesFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupClass(this, false); return(false);');
$titleFld = $frm->getField('grpcls_title');
$descFld = $frm->getField('grpcls_description');
$totalSeatFld = $frm->getField('grpcls_total_seats');
$tlangFld = $frm->getField('grpcls_tlang_id');
$entryFeeFld = $frm->getField('grpcls_entry_fee');
$starttimeFld = $frm->getField('grpcls_start_datetime');
$durationFld = $frm->getField('grpcls_duration');
$slugFld = $frm->getField('grpcls_slug');
$slugFld->setFieldTagAttribute('onchange', 'formatSlug(this);');
$fld = $frm->getField('grpcls_id');
$fld->setFieldTagAttribute('id', 'grpcls_id');
$bannerFld = $frm->getField('grpcls_banner');
$nextButton = $frm->getField('btn_next');
$nextButton->addFieldTagAttribute('onClick', 'setupClass(this.form, true); return(false);');
if ($isClassBooked) {
    $frm->getField('grpcls_start_datetime')->addFieldTagAttribute('readonly', 'readonly');
    $frm->getField('grpcls_duration')->addFieldTagAttribute('readonly', 'readonly');
    $frm->getField('grpcls_tlang_id')->addFieldTagAttribute('readonly', 'readonly');
    $frm->getField('grpcls_entry_fee')->addFieldTagAttribute('readonly', 'readonly');
}
$bannerInfo = Label::getLabel('LBL_MAX_SIZE:_{size},_EXTENSION:_{ext}_&_DIMENSIONS_{dimensions}');
$bannerExt = implode(", ", Afile::getAllowedExts(Afile::TYPE_GROUP_CLASS_BANNER));
$bannerSize = MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_GROUP_CLASS_BANNER)) . ' MB';
$bannerDimensions = implode('x', (new Afile(Afile::TYPE_GROUP_CLASS_BANNER))->getImageSizes('LARGE'));
$autoTranslateFld = $frm->getField('update_langs_data') ?? null;
$offlineFld = $frm->getField('grpcls_offline');
if ($isOfflineEnabled == AppConstant::YES) {
    $addressFld = $frm->getField('grpcls_address_id');
    $offlineFld->setFieldTagAttribute('onchange', 'showAddresses(this.value);');
}
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_SETUP_GROUP_CLASS'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="form-edit-head">
        <nav class="tabs tabs--line border-bottom-0">
            <ul>
                <li class="is-active">
                    <a href="javascript:void(0)"><?php echo Label::getLabel('LBL_GENERAL'); ?></a>
                </li>
                <?php foreach ($languages as $langId => $language) { ?>
                    <li>
                        <a href="javascript:void(0)" class="lang-li <?php echo ($classId < 1) ? 'selection-disabled' : '' ?>" data-id="<?php echo $language['language_id']; ?>" <?php if ($classId > 0) { ?> onclick="langForm(<?php echo $classId ?>, <?php echo $langId; ?>);" <?php } ?>>
                            <?php echo $language['language_name']; ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </nav>
    </div>
    <div class="form-edit-body">
        <?php echo $frm->getFormTag(); ?>
        <?php echo $frm->getFieldHTML('grpcls_id'); ?>
        <div class="row">
            <div class="col-md-8">
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
            <div class="col-md-4">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $tlangFld->getCaption(); ?>
                            <?php if ($tlangFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $tlangFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $slugFld->getCaption(); ?>
                            <?php if ($slugFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $slugFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $bannerFld->getCaption(); ?>
                            <?php if ($bannerFld->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                            <?php if (!empty($banner)) { ?><a href="<?php echo MyUtility::makeUrl('Image', 'download', [Afile::TYPE_GROUP_CLASS_BANNER, $classId], CONF_WEBROOT_FRONT_URL) . '?t=' . time(); ?>" class="color-primary"><?php echo Label::getLabel('LBL_DOWNLOAD'); ?></a><?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $bannerFld->getHtml(); ?>
                            <small class="m-0"><?php echo str_replace(['{size}', '{ext}', '{dimensions}'], [$bannerSize, $bannerExt, $bannerDimensions], $bannerInfo); ?></small>
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
        <?php if ($isOfflineEnabled == AppConstant::YES) { ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $offlineFld->getCaption(); ?>
                                <?php if ($offlineFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $offlineFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $addressFld->getCaption(); ?>
                                <?php if ($addressFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $addressFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else {
            echo $offlineFld->getHtml();
        } ?>
        <div class="row">
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $entryFeeFld->getCaption(); ?>
                            <?php if ($entryFeeFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $entryFeeFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $totalSeatFld->getCaption(); ?>
                            <?php if ($totalSeatFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $totalSeatFld->getHtml(); ?>
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
                            <?php echo $starttimeFld->getCaption(); ?>
                            <?php if ($starttimeFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $starttimeFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $durationFld->getCaption(); ?>
                            <?php if ($durationFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $durationFld->getHtml(); ?>
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
                            <?php if ($autoTranslateFld) { ?>
                                <?php echo $autoTranslateFld->getHtml(); ?>
                            <?php } ?>
                            <?php echo $nextButton->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJS(); ?>
    </div>
</div>