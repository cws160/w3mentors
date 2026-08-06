<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('name', 'signinFrmPopUp');
$frm->setFormTagAttribute('id', 'signinFrmPopUp');
$frm->developerTags['colClassPrefix'] = 'col-sm-';
$frm->developerTags['fld_default_col'] = 12;
$fld = $frm->getField('remember_me');
$fld->setWrapperAttribute('class', 'set-remember');
$fldPassword = $frm->getField('password');
$fldPassword->changeCaption('');
$fldPassword->captionWrapper = [
    Label::getLabel('LBL_Password'),
    '<a onClick="toggleLoginPassword(this)" href="javascript:void(0)" class="link" data-show-caption="' .
        Label::getLabel('LBL_Show_Password') . '" data-hide-caption="' . Label::getLabel('LBL_Hide_Password') . '">' . Label::getLabel('LBL_Show_Password') . '</a>'
];
$frm->setFormTagAttribute('onsubmit', 'signinSetup(this); return(false);');
$fld = $frm->getField('btn_submit');
$fld->setFieldTagAttribute('class', 'btn--block');
?>
<div class="site-form-wrapper">
    <div class="site-form">
        <div class="site-form__header text-center mb-4">
            <h4><?php echo Label::getLabel('LBL_LOGIN'); ?></h4>
            <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
        </div>
        <div class="site-form__body">
            <?php $this->includeTemplate('guest-user/_partial/learner-social-media-signup.php'); ?>
            <?php echo $frm->getFormHtml(); ?>
        </div>
        <div class="site-form__foot">
            <div class="text-center">
                <p><?php echo Label::getLabel('LBL_DO_NOT_HAVE_AN_ACCOUNT?'); ?> <a href="javascript:void(0);" onClick="signupForm()" class="link"><?php echo Label::getLabel('LBL_REGISTER'); ?></a></p>
                <p><a href="<?php echo MyUtility::makeUrl('GuestUser', 'forgotPassword'); ?>" class="link"><?php echo Label::getLabel('LBL_Forgot_Password?'); ?></a></p>
            </div>
        </div>
    </div>
</div>