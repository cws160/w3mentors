<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'EmailFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 7;
$frm->setFormTagAttribute('autocomplete', 'off');
$frm->setFormTagAttribute('onsubmit', 'setupEmail(this); return(false);');
$currentEmail = $frm->getField('user_email');
$newEmail = $frm->getField('new_email');
$currentPassword = $frm->getField('current_password');
$submitBtn = $frm->getField('btn_submit');
$submitBtn->setFieldTagAttribute('form', $frm->getFormTagAttribute('id'));
?>
<div class="content-panel__head">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_CHANGE_PASSWORD_OR_EMAIL'); ?></h5>
        </div>
        <div></div>
    </div>
</div>
<div class="content-panel__body">
    <div class="form">
        <div class="form__body p-0">
            <nav class="tabs tabs--line ps-4 pe-4">
                <ul>
                    <li><a href="javascript:void(0);" onclick="changePasswordForm();"> <?php echo Label::getLabel('LBL_Password'); ?></a></li>
                    <li class="is-active"><a href="javascript:void(0);"><?php echo Label::getLabel('LBL_Email'); ?></a></li>
                </ul>
            </nav>
            <div class="tabs-data">
                <div class="padding-6 pb-0">
                    <?php
                    echo $frm->getFormTag();
                    echo $frm->getFieldHTML('user_id');
                    ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $currentEmail->getCaption(); ?>
                                        <?php if ($currentEmail->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $currentEmail->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $newEmail->getCaption(); ?>
                                        <?php if ($newEmail->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $newEmail->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $currentPassword->getCaption(); ?>
                                        <?php if ($currentPassword->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $currentPassword->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </form>
                    <?php echo $frm->getExternalJS(); ?>
                </div>
            </div>
        </div>
        <div class="form__actions">
            <div class="d-flex">
                <?php echo $frm->getFieldHTML('btn_submit'); ?>
            </div>
        </div>
    </div>
</div>