<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frmResetPassword->setFormTagAttribute('class', 'form form-login');
?>
<div id="particles-js"></div>
<div class="login-page login-1">
    <div class="container">
        <div class="login-block">
            <div class="card">
                <div class="card-head d-block">
                    <figure class="logo"><?php echo MyUtility::getLogo(); ?></figure>
                    <h3><?php echo Label::getLabel('LBL_Reset_Password'); ?> </h3>
                    <p><?php echo Label::getLabel('LBL_PLEASE_ENTER_THE_NEW_PASSWORD') ?></p>
                </div>
                <div class="card-body">
                    <?php echo $frmResetPassword->getFormTag(); ?>
                    <div class="form-group">
                        <?php echo $frmResetPassword->getFieldHTML('new_pwd'); ?>
                    </div>
                    <div class="form-group">
                        <div class="captcha-wrap">
                            <?php echo $frmResetPassword->getFieldHTML('confirm_pwd'); ?>
                            <?php echo $frmResetPassword->getFieldHTML('apr_id'); ?>
                            <?php echo $frmResetPassword->getFieldHTML('token'); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo $frmResetPassword->getFieldHTML('btn_reset'); ?>
                    </div>
                    <?php echo $frmResetPassword->getExternalJS(); ?>
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