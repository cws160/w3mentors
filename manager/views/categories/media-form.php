<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupMedia(this); return(false);');
$categoryImageFld = $frm->getField('category_image');
$categoryImageFld->setFieldTagAttribute('class', 'hide');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_CATEGORY_SETUP'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="categoryForm(<?php echo $categoryId ?>);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php
            $inactive = ($categoryId == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
                ?>
                <li class=" lang-li-js <?php echo $inactive; ?>">
                    <a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php if ($categoryId > 0) { ?> onclick="langForm(<?php echo $categoryId; ?>, <?php echo $langId; ?>);" <?php } ?>>
                        <?php echo $langName; ?>
                    </a>
                </li>
            <?php } ?>
            <li class="mediaTab"><a class="active" onclick="mediaForm(<?php echo $categoryId ?>);" href="javascript:void(0);"><?php echo Label::getLabel('LBL_MEDIA'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormTag(); ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <h6><?php $categoryImageFld->getCaption(); ?></h6>
                <span class="form-text text-muted">
                    <strong><?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER") ?> : </strong> <?php echo sprintf(Label::getLabel('LBL_Dimensions_%s'), '100*100') ?></span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <div class="dropzone mt-3 dropzoneContainerJs">
                    <div class="dropzone-uploaded dropzoneUploadedJs">
                        <?php if (!empty($categoryImage)) { ?>
                            <img src="<?php echo MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_CATEGORY_IMAGE, $categoryId, Afile::SIZE_LARGE]) . '?' . time(); ?>">
                        <?php } else { ?>
                            <img src="<?php echo MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_CATEGORY_IMAGE, 0, Afile::SIZE_LARGE]) . '?' . time() . '" title="" alt=""' ?>">
                        <?php } ?>
                        <div class="dropzone-uploaded-action">
                            <ul class="actions">
                                <li>
                                    <a id="post_image" href="javascript:void(0)" class="categoryFile-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $categoryImageFld->getFieldTagAttribute('data-file_type') ?>" data-frm="<?php echo $categoryImageFld->getFieldTagAttribute('data-frm') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT'); ?>">
                                        <svg class="svg" width="18" height="18">
                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-actions.svg#edit">
                                        </use>
                                        </svg>
                                    </a>
                                </li>
                                <?php if (!empty($categoryImage)) { ?>
                                    <li>
                                        <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_CATEGORY_IMAGE ?>', '<?php echo $categoryId ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE'); ?>">
                                            <svg class="svg" width="18" height="18">
                                            <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-actions.svg#delete">
                                            </use>
                                            </svg>
                                        </a>
                                    </li>
                                <?php } ?>
                                <?php echo $frm->getFieldHtml('category_id'); ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <div class="separator my-3">

                </div>
            </div>
        </div>
    </div>
</form>
</div>