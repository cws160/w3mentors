<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$frm->setFormTagAttribute('onsubmit', 'langSetup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$langFld = $frm->getField('catelang_lang_id');
$langFld->addFieldTagAttribute('class', 'hide');
$langFld->setWrapperAttribute('class', 'hide');
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
            <?php foreach ($languages as $id => $langName) { ?>
                <li><a class="<?php echo ($langFld->value == $id) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $id; ?>" onclick="langForm(<?php echo $categoryId ?>, <?php echo $id; ?>);"><?php echo $langName; ?></a></li>
            <?php } ?>
            <li class="mediaTab"><a onclick="mediaForm(<?php echo $categoryId ?>);" href="javascript:void(0);"><?php echo Label::getLabel('LBL_MEDIA'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>