<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<section class="section bg-gradiant">
    <div class="container container--fixed">
        <div class="site-form-wrapper">
            <div class="site-form">
                <div class="site-form__header text-center mb-5">
                    <h4><?php echo Label::getLabel('LBL_RESET_PASSWORD?'); ?></h4>
                    <p><?php echo Label::getLabel('LBL_CHANGE_OR_RESET_YOUR_PASSWORD.'); ?></p>
                </div>
                <div class="site-form__body">
                    <?php
                    $frm->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_POSITION_AFTER);
                    $frm->setFormTagAttribute('class', 'form');
                    $frm->setValidatorJsObjectName('resetValObj');
                    $frm->developerTags['colClassPrefix'] = 'col-md-';
                    $frm->developerTags['fld_default_col'] = 12;
                    $frm->setFormTagAttribute('action', '');
                    $frm->setFormTagAttribute('onSubmit', 'resetpwd(this, resetValObj); return(false);');
                    $fldPassword = $frm->getField('new_password');
                    $fldPassword->captionWrapper = ['', '<a onClick="toggleResetPassword(this)" href="javascript:void(0)" class="link show-hide-btn" data-show-caption="' . Label::getLabel('LBL_SHOW_PASSWORD') . '" data-hide-caption="' . Label::getLabel('LBL_HIDE_PASSWORD') . '">' . Label::getLabel('LBL_SHOW_PASSWORD') . '</a>'];
                    echo $frm->getFormHtml();
                    echo $frm->getExternalJs();
                    ?>
                </div>
                <div class="site-form__foot text-center pt-2">
                    <p><a href="<?php echo MyUtility::makeUrl('GuestUser', 'loginForm'); ?>" class="link"><?php echo Label::getLabel('LBL_BACK_TO_LOGIN'); ?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>