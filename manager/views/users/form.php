<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frmUser->getField('user_username')->setFieldTagAttribute('disabled', 'disabled');
$frmUser->getField('user_email')->setFieldTagAttribute('disabled', 'disabled');
$frmUser->developerTags['colClassPrefix'] = 'col-md-';
$frmUser->developerTags['fld_default_col'] = 12;
$frmUser->setFormTagAttribute('class', 'form form_horizontal');
$frmUser->setFormTagAttribute('onsubmit', 'setupUser(this); return(false);');
$countryFld = $frmUser->getField('user_country_id');
$countryFld->setFieldTagAttribute('id', 'user_country_id');
$frmUser->getField('user_phone_number')->addFieldTagAttribute('id', 'user_phone');
$frmUser->getField('user_phone_code')->addFieldTagAttribute('id', 'user_phone_code');
if (MyUtility::getLayoutDirection() == 'rtl') {
    $phoneField = $frmUser->getField('user_phone_number');
    $phoneField->addFieldTagAttribute('style', 'direction: ltr;text-align:right;');
}
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_USER_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $frmUser->getFormHtml(); ?>
</div>