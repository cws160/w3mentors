<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$id = $frm->getField('cate_id');
$fldParent = $frm->getField('cate_parent');
$fldParent->setFieldTagAttribute('onchange', 'updateFeatured(this.value);');
if ($frm->getField('cate_type')->value == Category::TYPE_COURSE) {
    $fldFeatured = $frm->getField('cate_featured');
    $fldFeatured->setWrapperAttribute('class', 'fldFeaturedJs');
}
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
            <li><a class="active" href="javascript:void(0);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
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
            <li class="mediaTab <?php echo $inactive; ?>"><a <?php echo ($categoryId > 0) ? 'onclick="mediaForm(' . $categoryId . ');"' : ''; ?> href="javascript:void(0);"><?php echo Label::getLabel('LBL_MEDIA'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>