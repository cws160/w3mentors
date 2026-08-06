<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupChangePassword(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Admin_User_Change_Password'); ?> <?php echo !empty($adminProfile['admin_name']) ? "( $adminProfile[admin_name] )" : '' ?></h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>