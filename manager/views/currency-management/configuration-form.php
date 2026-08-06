<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$form->setFormTagAttribute('class', 'form form_horizontal');
$form->setFormTagAttribute('onsubmit', 'setupConfig(this); return(false);');
$form->developerTags['colClassPrefix'] = 'col-md-';
$form->developerTags['fld_default_col'] = 12;
$submitField = $form->getField('btn_submit');
$submitField->htmlBeforeField = "<p>" . CommonHelper::renderHtml($config['info']) . "<p><br>";
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_FIXER_CURRENCY_CONVERSION_CONFIGURATION'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $form->getFormHtml(); ?>
</div>