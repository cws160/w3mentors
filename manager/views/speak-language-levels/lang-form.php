<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'langSetup(this); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;
$langFld = $langFrm->getField('slanglvllang_lang_id');
$langFld->addFieldTagAttribute('class', 'hide');
$langFld->setWrapperAttribute('class', 'hide');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_SPOKEN_LANGUAGE_LEVEL_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li><a href="javascript:void(0);" onclick="form(<?php echo $sLangLevelId; ?>);"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php foreach ($languages as $langId => $langName) { ?>
                <li><a class="<?php echo ($lang_id == $langId) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $langId; ?>" onclick="langForm(<?php echo $sLangLevelId ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $langFrm->getFormHtml(); ?>
</div>