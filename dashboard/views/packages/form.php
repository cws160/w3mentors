<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setup(this,true); return(false);');
$fld = $frm->getField('grpcls_id');
$fld->setFieldTagAttribute('id', 'grpcls_id');
$titleFld = $frm->getField('grpcls_title');
$slugFld = $frm->getField('grpcls_slug');
$slugFld->setFieldTagAttribute('onchange', 'formatSlug(this);');
$descFld = $frm->getField('grpcls_description');
$descFld->setFieldTagAttribute('style', 'height:70px;');
$tlangFld = $frm->getField('grpcls_tlang_id');
$totalSeatFld = $frm->getField('grpcls_total_seats');
$entryFeeFld = $frm->getField('grpcls_entry_fee');
$slotFld = $frm->getField('grpcls_duration');
$classTitleFld = $frm->getField('title[]');
$classStattimeFld = $frm->getField('starttime[]');
$submitBtn = $frm->getField('submit');
$languages = array_column($siteLanguages, 'language_name', 'language_id');
$bannerFld = $frm->getField('grpcls_banner');
$bannerInfo = Label::getLabel('LBL_MAX_SIZE:_{size},_EXTENSION:_{ext}_&_DIMENSIONS_{dimensions}');
$bannerExt = implode(", ", Afile::getAllowedExts(Afile::TYPE_GROUP_CLASS_BANNER));
$bannerSize = MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_GROUP_CLASS_BANNER)) . ' MB';
$bannerDimensions = implode('x', (new Afile(Afile::TYPE_GROUP_CLASS_BANNER))->getImageSizes('LARGE'));
$counter = 1;
$offlineFld = $frm->getField('grpcls_offline');
if ($isofflineEnabled) {
    $addressFld = $frm->getField('grpcls_address_id');
    $offlineFld->setFieldTagAttribute('onchange', 'showAddresses(this.value);');
}
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_SETUP_CLASS_PACKAGE'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="form-edit-head">
        <div class="tabs tabs--line border-bottom-0">
            <ul>
                <li class="is-active"><a href="javascript:void(0);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
                <?php foreach ($languages as $langId => $language) { ?>
                    <li><a href="javascript:void(0)" class="lang-li <?php echo ($packageId < 1) ? 'selection-disabled' : '' ?>" data-id="<?php echo $langId; ?>" <?php if ($packageId > 0) { ?> onclick="langForm(<?php echo $packageId ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $language; ?></a></li>
                <?php } ?>
            </ul>
        </div>
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
                            <?php if (!empty($banner)) { ?><a href="<?php echo MyUtility::makeUrl('Image', 'download', [Afile::TYPE_GROUP_CLASS_BANNER, $packageId], CONF_WEBROOT_FRONT_URL) . '?t=' . time(); ?>" class="color-primary"><?php echo Label::getLabel('LBL_DOWNLOAD'); ?></a><?php } ?>
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
        <div class="row">
            <div class="col-md-4">
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
            <div class="col-md-4">
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
            <div class="col-md-4">
                <div class="field-set">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <?php echo $slotFld->getCaption(); ?>
                            <?php if ($slotFld->requirement->isRequired()) { ?>
                                <span class="spn_must_field">*</span>
                            <?php } ?>
                        </label>
                    </div>
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $slotFld->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($isofflineEnabled) { ?>
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
        <?php if ($packageId == 0) { ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $classTitleFld->getCaption() . '-' . $counter; ?>
                                <?php if ($classTitleFld->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $classTitleFld->getHtml(); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $classStattimeFld->getCaption(); ?>
                                <?php if ($classStattimeFld->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $classStattimeFld->getHtml(); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="more-container-js"></div>
            <div class="row">
                <div class="col-md-10"></div>
                <div class="col-md-2">
                    <div class="caption-wraper">
                        <label class="field_label">
                            <a href="javascript:addClassRow()" class="color-secondary"> +<?php echo Label::getLabel('LBL_ADD_MORE'); ?></a>
                        </label>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <?php foreach ($classes as $class) { ?>
                <div class="row">
                    <div class="col-md-8">
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
                    <div class="col-md-4">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $classStattimeFld->getCaption(); ?>
                                    <?php if ($classStattimeFld->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <input type="text" class="datetime" readonly="readonly" data-field-caption="<?php echo $classStattimeFld->getCaption(); ?>" data-fatreq="{&quot;required&quot;:true}" name="starttime[<?php echo $class['grpcls_id']; ?>]" value="<?php echo $class['grpcls_start_datetime']; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php $counter++; ?>
            <?php } ?>
        <?php } ?>
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
<script>
    $(document).ready(function() {
        counter = <?php echo $counter; ?>;
        bindDatetimePicker(".datetime");
    });
</script>