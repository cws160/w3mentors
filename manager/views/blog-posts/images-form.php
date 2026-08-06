<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$imagesFrm->setFormTagAttribute('class', 'form form_horizontal');
$imagesFrm->developerTags['colClassPrefix'] = 'col-md-';
$imagesFrm->developerTags['fld_default_col'] = 12;
$img_fld = $imagesFrm->getField('post_image');
$img_fld->addFieldTagAttribute('class', 'btn btn--primary btn--sm');
$langFld = $imagesFrm->getField('lang_id');
$langFld->addFieldTagAttribute('class', 'language-js');
$preferredDimensionsStr = '<small class="text--small">' . sprintf(Label::getLabel('LBL_Preferred_Dimensions_%s'), '945*710') . '</small>';
$htmlAfterField = $preferredDimensionsStr;
$img_fld->htmlAfterField = $htmlAfterField;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Blog_Post_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <?php $inactive = ($post_id == 0) ? 'is-inactive' : ''; ?>
            <li><a href="javascript:void(0);" onclick="blogPostForm(<?php echo $post_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php foreach ($languages as $langId => $langName) { ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" <?php if ($post_id > 0) { ?> onclick="langForm(<?php echo $post_id ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $langName; ?></a></li>
            <?php } ?>
            <li><a class="active" href="javascript:void(0);" onclick="postImages(<?php echo $post_id ?>);"><?php echo Label::getLabel('LBL_Post_Images'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="card-body">
    <?php echo $imagesFrm->getFormHtml(); ?>
    <div id="image-listing"></div>
</div>