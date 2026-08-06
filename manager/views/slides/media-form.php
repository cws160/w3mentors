<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$form->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$form->setFormTagAttribute('onsubmit', 'setupMedia(this); return false;');
$form->developerTags['colClassPrefix'] = 'col-md-';
$form->developerTags['fld_default_col'] = 12;
$langIdFld = $form->getField('lang_id');
$langIdFld->addFieldTagAttribute('class', 'language-js');
$langIdFld->addFieldTagAttribute('onChange', 'slideMediaForm(' . $slideId . ', this.value);');
$extensionLabel = Label::getLabel('LBL_ALLOWED_FILE_EXTS_{ext}', $langIdFld->value);
$dimensionsLabel = Label::getLabel('LBL_PREFERRED_DIMENSIONS_{dimensions}', $langIdFld->value);
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_SLIDE_IMAGE_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="slideForm(<?php echo $slideId; ?>);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php
            $inactive = ($slideId == 0) ? 'is-inactive' : '';
            foreach ($languages as $langKey => $langName) {
            ?>
                <li class=" lang-li-js <?php echo $inactive; ?>">
                    <a class="<?php echo ($langKey == $langId) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $langKey; ?>" <?php if ($slideId > 0) { ?> onclick="slideMediaForm(<?php echo $slideId; ?>, <?php echo $langKey ?>);" <?php } ?>>
                        <?php echo $langName; ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $form->getFormTag(); ?>
    <div class="row">
        <?php
        foreach ($displayTypes as $type => $display) {
            $field = $form->getField('slide_image_' . $type);
        ?>
            <div class="col-md-6">
                <div class="form-group">
                    <h6><?php $field->getCaption(); ?></h6>
                    <span class="form-text text-muted">
                        <strong>
                            <?php echo str_replace('{device}', Label::getLabel("LBL_" . $display, $langIdFld->value), Label::getLabel("LBL_{device}_IMAGE", $langIdFld->value)); ?>
                        </strong> 
                        <p><?php echo str_replace('{dimensions}', $dimensions[$type], $dimensionsLabel) ?>. <?php echo str_replace('{ext}', $imageExts, $extensionLabel) ?></p>
                    </span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="dropzone mt-3 dropzoneContainerJs">
                        <div class="dropzone-uploaded dropzoneUploadedJs">
                            <img id="<?php echo  $field->getName(); ?>" src="<?php echo  MyUtility::makeUrl('image', 'show', [$type, $slideId, Afile::SIZE_LARGE, $langId]) . '?' . time(); ?>">
                            <div class="dropzone-uploaded-action">
                                <ul class="actions">
                                    <li>
                                        <a id="post_image" href="javascript:void(0)" class="homepageSlide-Js" data-fld="<?php echo  $field->getName(); ?>" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT'); ?>">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-actions.svg#edit">
                                                </use>
                                            </svg>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                    $field->setFieldTagAttribute('class', 'hide slideImages');
                    echo $field->getHtml();
                    ?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="separator  my-3">

                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <?php echo $form->getFieldHTML('btn_submit'); ?>
        </div>
    </div>

    <?php echo $form->getFieldHTML('slide_id'); ?>
    <?php echo $form->getFieldHTML('lang_id'); ?>
    </form>
    <?php echo $form->getExternalJS(); ?>
</div>
</div>