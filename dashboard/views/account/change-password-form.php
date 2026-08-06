<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'pwdFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 7;
$frm->setFormTagAttribute('autocomplete', 'off');
$frm->setFormTagAttribute('onsubmit', 'setupPassword(this); return(false);');
$currentPassword = $frm->getField('current_password');
$newPassword = $frm->getField('new_password');
$confNewPassword = $frm->getField('conf_new_password');
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
                    <li class="is-active"><a href="javascript:void(0);" onclick="changePasswordForm();"> <?php echo Label::getLabel('LBL_Password'); ?></a></li>
                    <li><a href="javascript:void(0);" onclick="changeEmailForm()"><?php echo Label::getLabel('LBL_Email'); ?></a></li>
                </ul>
            </nav>
            <div class="tabs-data">
                <div class="padding-6 pb-0">
                    <?php echo $frm->getFormTag(); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper d-flex align-items-center">
                                    <label class="field_label">
                                        <?php echo $currentPassword->getCaption(); ?>
                                        <?php if ($currentPassword->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                        <a onClick="toggleChangePassword(this, 'current_password')" href="javascript:void(0)" class="-link-underline -float-right link-color" data-show-caption="<?php echo Label::getLabel('LBL_Show_Password'); ?>" data-hide-caption="<?php echo Label::getLabel('LBL_Hide_Password'); ?>"><?php echo Label::getLabel('LBL_Show_Password'); ?></a>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $newPassword->getCaption(); ?>
                                        <?php if ($newPassword->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                        <a onClick="toggleChangePassword(this, 'new_password')" href="javascript:void(0)" class="-link-underline -float-right link-color" data-show-caption="<?php echo Label::getLabel('LBL_Show_Password'); ?>" data-hide-caption="<?php echo Label::getLabel('LBL_Hide_Password'); ?>"><?php echo Label::getLabel('LBL_Show_Password'); ?></a>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $newPassword->getHtml(); ?>
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
                                        <?php echo $confNewPassword->getCaption(); ?>
                                        <?php if ($confNewPassword->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $confNewPassword->getHtml(); ?>
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
            <div class="d-flex align-items-center">
                <?php echo $frm->getFieldHTML('btn_submit'); ?>
            </div>
        </div>
    </div>
</div>