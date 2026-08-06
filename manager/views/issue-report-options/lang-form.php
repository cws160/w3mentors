<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'langSetup(this); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;
$langFld = $langFrm->getField('tissueoptlang_lang_id');
$langFld->addFieldTagAttribute('class', 'hide');
$langFld->setWrapperAttribute('class', 'hide');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_ISSUE_REPORT_OPTIONS_SETUP'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="form(<?php echo $optId; ?>);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php foreach ($languages as $langId => $langName) { ?>
                <li><a class="<?php echo ($lang_id == $langId) ? 'active' : '' ?>" data-id="<?php echo $langId; ?>" href="javascript:void(0);" onclick="langForm(<?php echo $optId ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="card-body">
    <?php echo $langFrm->getFormHtml(); ?>
</div>