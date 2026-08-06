<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$mediaFrm->setFormTagAttribute('class', 'form form_horizontal');
$mediaFrm->developerTags['colClassPrefix'] = 'col-md-';
$mediaFrm->developerTags['fld_default_col'] = 12;
$imageFile = $mediaFrm->getField('tlang_image_file');
$imageFile->addFieldTagAttribute('class', 'hide tlang_image_file');
$imageFile->addFieldTagAttribute('onChange', 'uploadImage(this, ' . $tLangId . ', ' . Afile::TYPE_TEACHING_LANGUAGES . ')');
$fld1 = $mediaFrm->getField('tlang_image');
$fld1->addFieldTagAttribute('class', 'btn btn--primary btn--sm');
$extensionLabel = Label::getLabel('LBL_ALLOWED_FILE_EXTS_{extension}');
$demensionLabel = Label::getLabel('LBL_PREFERRED_DIMENSIONS_ARE_{dimensions}');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_LANGUAGE_IMAGE'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li><a href="javascript:void(0);" onclick="form(<?php echo $tLangId; ?>);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php foreach ($languages as $langId => $langName) { ?>
                <li><a href="javascript:void(0);" onclick="langForm(<?php echo $tLangId ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
            <?php } ?>
            <li><a class="active" href="javascript:void(0)" onclick="mediaForm(<?php echo $tLangId ?>);"><?php echo Label::getLabel('LBL_MEDIA'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $mediaFrm->getFormTag(); ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <h6><?php $fld1->getCaption(); ?></h6>
                <span class="form-text text-muted">
                    <strong><?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER") ?> : </strong> <?php echo str_replace('{dimensions}', $teachLangSize, $demensionLabel) ?> <?php echo str_replace('{extension}', $teachLangExt, $extensionLabel) ?></span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <div class="dropzone mt-3 dropzoneContainerJs">
                    <div class="dropzone-uploaded dropzoneUploadedJs">
                        <?php if (!empty($image)) { ?>
                            <img src="<?php echo  MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_TEACHING_LANGUAGES, $image['file_record_id'], Afile::SIZE_LARGE]) . '?' . time() . '" title="' . $image['file_name'] . '" alt="' . $image['file_name']; ?>">
                        <?php } else { ?>
                            <img src="<?php echo  MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_TEACHING_LANGUAGES, 0, Afile::SIZE_LARGE]) . '?' . time() . '" title="" alt=""' ?>">
                        <?php } ?>
                        <div class="dropzone-uploaded-action">
                            <ul class="actions">
                                <li>
                                    <a id="post_image" href="javascript:void(0)" class="tlanguageFile-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $fld1->getFieldTagAttribute('data-file_type') ?>" data-tlang_id="<?php echo $fld1->getFieldTagAttribute('data-tlang_id') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT'); ?>">
                                        <svg class="svg" width="18" height="18">
                                            <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-actions.svg#edit">
                                            </use>
                                        </svg>
                                    </a>
                                </li>
                                <?php if ($canEdit && !empty($image)) { ?>
                                    <li>
                                        <a href="javascript:void(0)" onclick="removeFile('<?php echo $tLangId ?>', '<?php echo Afile::TYPE_TEACHING_LANGUAGES ?>');" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE'); ?>">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-actions.svg#delete">
                                                </use>
                                            </svg>
                                        </a>
                                    </li>
                                <?php } ?>
                                <?php echo $imageFile->getHtml(); ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <div class="separator  my-3">

                </div>
            </div>
        </div>
    </div>
    </form>
</div>