<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'setupLangMetaTag(this,"' . $metaType . '"); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;
$otherMetatagsFld = $langFrm->getField('meta_other_meta_tags');
$otherMetatagsFld->htmlAfterField = '<small>' . htmlentities(stripslashes(Label::getLabel('LBL_OTHER_META_TAG_EXAMPLE', $langId))) . '</small>';
$fld1 = $langFrm->getField('open_graph_image');
if (!in_array($metaType, [MetaTag::META_GROUP_GRP_CLASS, MetaTag::META_GROUP_TEACHER, MetaTag::META_GROUP_COURSE])) {
    $fld1->addFieldTagAttribute('class', 'btn btn--primary btn--sm');
    $htmlAfterField = '<div style="margin-top:15px;" class="preferredDimensions-js">' . sprintf(Label::getLabel('LBL_Preferred_Dimensions_%s', $langId), '1200 x 627') . '</div>';
    $htmlAfterField .= '<div id="image-listing"></div>';
    $fld1->htmlAfterField = $htmlAfterField;
} else {
    $langFrm->removeField($fld1);
}
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Meta_Tag_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="editMetaTagForm('<?php echo $metaId; ?>','<?php echo $metaType; ?>','<?php echo $recordId; ?>');"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            if ($metaId > 0) {
                foreach ($languages as $lang_Id => $langName) {
            ?>
                    <li><a class="lang-form-js <?php echo ($lang_Id == $langId) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $lang_Id; ?>" onclick="editMetaTagLangForm('<?php echo $metaId ?>','<?php echo $lang_Id; ?>','<?php echo $metaType; ?>');"><?php echo $langName; ?></a></li>
            <?php
                }
            }
            ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $langFrm->getFormHtml(); ?>
</div>