<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frmForgot->setFormTagAttribute('class', 'form form-login');
?>
<script src='https://www.google.com/recaptcha/api.js'></script>
<div id="particles-js"></div>
<div class="login-page login-1">
    <div class="container">
        <div class="login-block">
            <div class="card">
                <div class="card-head d-block">
                    <figure class="logo"><?php echo MyUtility::getLogo(); ?></figure>
                    <h3><?php echo Label::getLabel('LBL_Forgot_Your_Password?'); ?> </h3>
                    <p><?php echo Label::getLabel('LBL_Enter_The_E-mail_Address_Associated_With_Your_Account'); ?></p>
                </div>
                <div class="card-body">
                    <?php echo $frmForgot->getFormTag(); ?>
                    <div class="form-group">
                        <?php echo $frmForgot->getFieldHTML('admin_email'); ?>
                    </div>
                    <?php if (FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') != '') { ?>
                        <div class="form-group">
                            <div class="captcha-wrap"><?php echo $frmForgot->getFieldHTML('security_code'); ?></div>
                        </div>
                    <?php } ?>

                    <div class="form-group">
                        <?php echo $frmForgot->getFieldHTML('btn_forgot'); ?>
                    </div>
                    <?php echo $frmForgot->getExternalJS(); ?>
                    </form>
                </div>
                <div class="card-foot">
                    <ul class="other-links">
                        <li>
                            <a href="<?php echo MyUtility::makeUrl('adminGuest', 'loginForm'); ?>" class="link"><?php echo Label::getLabel('LBL_Back_to_Login'); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>