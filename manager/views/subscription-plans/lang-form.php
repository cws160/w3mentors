<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'langSetup(this); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;
$langFld = $langFrm->getField('subplang_lang_id');
$langFld->addFieldTagAttribute('class', 'hide');
$langFld->setWrapperAttribute('class', 'hide');
$subPlanId = $langFrm->getField('subplang_subplan_id')->value;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_Subscription_Plan_Setup'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tabs-nav tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="form(<?php echo $subPlanId ?>);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php foreach ($languages as $id => $langName) { ?>
                <li><a class="<?php echo ($langFld->value == $id) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $id; ?>" onclick="langForm(<?php echo $subPlanId ?>, <?php echo $id; ?>);"><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="card-body">
    <?php echo $langFrm->getFormHtml(); ?>
</div>