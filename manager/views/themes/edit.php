<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');

$primaryFld = $frm->getField('theme_primary_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$primaryFld = $frm->getField('theme_primary_inverse_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$primaryFld = $frm->getField('theme_secondary_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$primaryFld = $frm->getField('theme_secondary_inverse_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$primaryFld = $frm->getField('theme_footer_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$primaryFld = $frm->getField('theme_footer_inverse_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$primaryFld = $frm->getField('theme_gradient_primary_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$primaryFld = $frm->getField('theme_gradient_secondary_color');
$primaryFld->addFieldTagAttribute('class', 'jscolor');

$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>


<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Theme_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>