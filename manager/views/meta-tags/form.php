<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupMetaTag(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
if ($metaType == MetaTag::META_GROUP_OTHER) {
    $slugFld = $frm->getField('meta_slug');
    $slugFld->setFieldTagAttribute('id', 'meta_slug');
    $slugFld->setFieldTagAttribute('onkeyup', "getSlugUrl(this,this.value);");
    $url = MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONTEND);
    if (!empty($slugFld->value)) {
        $url = MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONTEND) . (ltrim($slugFld->value));
    }
    $slugFld->htmlAfterField = "<small>" . $url . "</small>";
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
            <li><a class="active" href="javascript:void(0)" onclick="editMetaTagForm(<?php echo "$metaId,'$metaType',$recordId" ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($metaId == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php if ($metaId > 0) { ?> onclick="editMetaTagLangForm(<?php echo "$metaId,$langId,'$metaType'" ?>);" <?php } ?>><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>