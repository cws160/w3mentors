<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupAdminUser(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$userNameFld = $frm->getField('admin_username');
$userNameFld->addFieldTagAttribute('id', 'admin_username');
if ($admin_id > 0) {
    $userNameFld->addFieldTagAttribute('disabled', 'disabled');
}
$emailFld = $frm->getField('admin_email');
$emailFld->addFieldTagAttribute('id', 'admin_email');
if ($admin_id > 0) {
    $emailFld->addFieldTagAttribute('disabled', 'disabled');
}
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Admin_User_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>

<script>
    $("[name='admin_timezone']").select2({
        dropdownParent: $(".contentBodyJs")
    });
</script>