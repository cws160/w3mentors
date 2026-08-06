<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form-login');
$userNameFld = $frm->getField('username');
$userNameFld->addFieldTagAttribute('placeholder', Label::getlabel('LBL_Username'));
$passwordFld = $frm->getField('password');
$passwordFld->addFieldTagAttribute('placeholder', Label::getlabel('LBL_Password'));
$rememberMeFld = $frm->getField('rememberme');
$rememberMeFld->addFieldTagAttribute('class', 'switch-labels');

$getFieldHTML = $frm->getField('btn_submit');
$getFieldHTML->addFieldTagAttribute('class', 'btn btn-brand btn-lg btn-block');
?>
<div id="particles-js"></div>
<div class="login-page login-1">
    <div class="container">
        <div class="login-block">
            <div class="card">
                <div class="card-head">
                    <figure class="logo"><?php echo MyUtility::getLogo(); ?></figure>
                </div>
                <div class="card-body">
                    <?php echo $frm->getFormTag(); ?>
                    <div class="form-group">
                        <label class="label">Username</label>
                        <?php echo $frm->getFieldHTML('username'); ?>
                    </div>
                    <div class="form-group">
                        <label class="label">Password</label>
                        <?php echo $frm->getFieldHTML('password'); ?>
                    </div>
                    <div class="form-group">
                        <label class="switch switch-sm switch-icon remember-me">
                            <?php
                            $remeberfld = $frm->getFieldHTML('rememberme');
                            $remeberfld = str_replace("<label>", "", $remeberfld);
                            $remeberfld = str_replace("</label>", "", $remeberfld);
                            echo $remeberfld;
                            ?>
                            <span class="input-helper"></span>
                            <?php echo Label::getlabel('LBL_Remember_me'); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <?php echo $frm->getFieldHTML('btn_submit'); ?>
                    </div>
                    <?php echo $frm->getExternalJS(); ?>
                    </form>
                </div>
                <div class="card-foot">
                    <ul class="other-links">
                        <li>
                            <a href="<?php echo MyUtility::makeUrl('adminGuest', 'forgotPasswordForm'); ?>" class="link"><?php echo Label::getLabel('LBL_Forgot_Password?'); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>