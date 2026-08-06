<?php

/**
 * Admin Guest Controller is used for Login|Forgot password Actions
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminGuestController extends AdminController
{

    /**
     * Initialize Admin Guest
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (AdminAuth::isAdminLogged()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonSuccess([
                    'redirectUrl' => MyUtility::makeUrl('Home'),
                    'msg' => Label::getLabel('LBL_YOU_ARE_ALREADY_LOGGED_IN')
                ]);
            }
            FatApp::redirectUser(MyUtility::makeUrl('Home'));
        }
        $this->set('bodyClass', 'page--front');
    }

    /**
     * Login Form
     */
    public function loginForm()
    {
        $frm = $this->getLoginForm();
        $frm->setValidatorJsObjectName('loginValidator');
        $frm->setFormTagAttribute('onsubmit', 'login(this, loginValidator); return(false);');
        $frm->setFormTagAttribute('id', 'adminLoginForm');
        $frm->setFormTagAttribute('class', 'form');
        $frm->setRequiredStarPosition('none');
        $frm->setRequiredStarWith('none');
        $frm->setJsErrorDisplay(FORM::FORM_ERROR_TYPE_AFTER_FIELD);
        $vwfld = $frm->getField('username');
        $vwfld->addFieldTagAttribute('title', 'Username');
        $vwfld->addFieldTagAttribute('autocomplete', 'off');
        $vwfld->setRequiredStarWith('none');
        $vwfld = $frm->getField('password');
        $vwfld->addFieldTagAttribute('title', 'Password');
        $vwfld->addFieldTagAttribute('autocomplete', 'off');
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Login Action
     */
    public function login()
    {
        $username = FatApp::getPostedData('username');
        $password = FatApp::getPostedData('password');
        $auth = new AdminAuth();
        if (!$auth->login($username, $password, MyUtility::getUserIp())) {
            FatUtility::dieJsonError($auth->getError());
        }
        if (FatApp::getPostedData('rememberme', FatUtility::VAR_INT, 0) == 1) {
            AdminAuth::setAuthToken(AdminAuth::getLoggedAdminId());
        }
        $redirectUrl = MyUtility::makeUrl('Home');
        FatUtility::dieJsonSuccess([
            'redirectUrl' => $redirectUrl,
            'msg' => Label::getLabel('LBL_LOGIN_SUCCESSFUL')
        ]);
    }

    /**
     * Forgot Password Form
     */
    public function forgotPasswordForm()
    {
        $forgotFrm = $this->getForgotForm();
        $forgotFrm->setFormTagAttribute('id', 'frmForgot');
        $forgotFrm->setFormTagAttribute('class', 'form');
        $forgotFrm->setRequiredStarPosition('none');
        $forgotFrm->setValidatorJsObjectName('forgotValidator');
        $forgotFrm->setFormTagAttribute('onsubmit', 'forgotPassword(this, forgotValidator); return false;');
        $emailFld = $forgotFrm->getField('admin_email');
        $emailFld->addFieldTagAttribute('title', 'Email Address');
        $emailFld->addFieldTagAttribute('autocomplete', 'off');
        $emailFld->setRequiredStarWith('none');
        $this->set('frmForgot', $forgotFrm);
        $this->_template->render();
    }

    /**
     * Forgot Password
     */
    public function forgotPassword()
    {
        $frm = $this->getForgotForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $captchValue = FatApp::getPostedData('g-recaptcha-response');
        if (FatApp::getConfig('CONF_RECAPTCHA_SITEKEY') && !CommonHelper::verifyCaptcha($captchValue)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INCORRECT_SECURITY_CODE'));
        }
        $auth = new AdminAuth();
        if (!$admin = $auth->checkAdminEmail($post['admin_email'])) {
            FatUtility::dieJsonError($auth->getError());
        }
        if ($auth->checkAdminPwdResetRequest($admin['admin_id'])) {
            FatUtility::dieJsonError($auth->getError());
        }
        $token = UserAuth::encryptPassword(FatUtility::getRandomString(20));
        $data = ['admin_id' => $admin['admin_id'], 'token' => $token];
        $reset_url = MyUtility::makeFullUrl('adminGuest', 'resetPassword', [$admin['admin_id'], $token]);
        $auth->deleteOldPasswordResetRequest();
        if (!$auth->addPasswordResetRequest($data)) {
            FatUtility::dieJsonError($auth->getError());
        }
        $vars = [
            '{reset_url}' => $reset_url, '{user_full_name}' => trim($admin['admin_name']),
            '{site_domain}' => MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONTEND),
        ];
        $mail = new FatMailer($this->siteLangId, 'admin_forgot_password');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$admin['admin_email']])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_Unable_to_send_email'));
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_PASSWORD_RESET_INSTRUCTIONS_SENT_TO_YOUR_EMAIL'));
    }

    /**
     * Reset Password
     * 
     * @param int $adminId
     * @param string $token
     */
    public function resetPassword($adminId = 0, $token = '')
    {
        $adminId = FatUtility::int($adminId);
        if ($adminId < 1 || strlen(trim($token)) < 20) {
            FatUtility::dieJsonError(Label::getLabel('MSG_LINK_IS_INVALID_OR_EXPIRED'));
        }
        $auth = new AdminAuth();
        if (!$auth->checkResetLink($adminId, trim($token))) {
            FatUtility::dieJsonError($auth->getError());
        }
        $frm = $this->getResetPasswordForm($adminId, trim($token));
        $frm->setFormTagAttribute('id', 'frmResetPassword');
        $frm->setFormTagAttribute('class', 'form');
        $frm->setRequiredStarPosition('none');
        $frm->setValidatorJsObjectName('resetValidator');
        $frm->setFormTagAttribute("action", '');
        $frm->setFormTagAttribute('onsubmit', 'reset_password(this, resetValidator); return false;');
        $btn_fld = $frm->getField('btn_reset');
        $btn_fld->addFieldTagAttribute('id', 'btn_reset');
        $fld_np = $frm->getField('new_pwd');
        $fld_np->addFieldTagAttribute('title', 'New Password');
        $fld_np->addFieldTagAttribute('placeholder', ' Enter New Password');
        $fld_np->addFieldTagAttribute('autocomplete', 'off');
        $fld_np->addFieldTagAttribute('id', 'new_pwd');
        $fld_np->requirements()->setLength(4, 20);
        $fld_np->setRequiredStarWith('none');
        $fld_ncp = $frm->getField('confirm_pwd');
        $fld_ncp->addFieldTagAttribute('title', Label::getLabel('MSG_Confirm_Password'));
        $fld_ncp->addFieldTagAttribute('placeholder', Label::getLabel('MSG_Enter_Confirm_Password'));
        $fld_ncp->addFieldTagAttribute('autocomplete', 'off');
        $fld_ncp->addFieldTagAttribute('id', 'confirm_pwd');
        $fld_ncp->setRequiredStarWith('none');
        $this->set('frmResetPassword', $frm);
        $this->_template->render();
    }

    /**
     * setup reset password
     *
     * @return void
     */
    public function setupResetPassword()
    {
        $newPwd = FatApp::getPostedData('new_pwd');
        $adminId = FatApp::getPostedData('apr_id', FatUtility::VAR_INT);
        $token = FatApp::getPostedData('token', FatUtility::VAR_STRING);
        if ($adminId < 1 || strlen(trim($token)) < 20) {
            FatUtility::dieJsonError(Label::getLabel('MSG_REQUEST_IS_INVALID_OR_EXPIRED'));
        }
        $frm = $this->getResetPasswordForm($adminId, $token);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (!$post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $auth = new AdminAuth();
        if (!$auth->checkResetLink($adminId, trim($token))) {
            FatUtility::dieJsonError($auth->getError());
        }
        $admin_row = $auth->getAdminById($adminId);
        $pwd = UserAuth::encryptPassword($newPwd);
        if ($admin_row['admin_id'] != $adminId || !$auth->changeAdminPwd($adminId, $pwd)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
        }
        $vars = [
            '{user_full_name}' => trim($admin_row['admin_name']),
            '{login_link}' => MyUtility::makeFullUrl('adminGuest', 'loginForm', [])
        ];
        $mail = new FatMailer($this->siteLangId, 'user_admin_password_changed_successfully');
        $mail->setVariables($vars);
        $mail->sendMail([$admin_row['admin_email']]);
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY'));
    }

    /**
     * Get Login Form
     * 
     * @return \Form
     */
    private function getLoginForm()
    {
        $userName = '';
        $pass = '';
        if (MyUtility::isDemoUrl()) {
            $userName = 'welcome';
            $pass = 'welcome';
        }
        $frm = new Form('frmLogin');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox('', 'username', $userName)->requirements()->setRequired();
        $frm->addPasswordField('', 'password', $pass)->requirements()->setRequired();
        $frm->addCheckBox('', 'rememberme', 1);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Sign_In'));
        return $frm;
    }

    /**
     * Get Forgot Password Form
     * 
     * @return \Form
     */
    private function getForgotForm()
    {
        $frm = new Form('adminFrmForgot');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addEmailField(Label::getLabel('LBL_Email_Address'), 'admin_email', '', ['placeholder' => Label::getLabel('LBL_Enter_Your_Email_Address')])->requirements()->setRequired();
        if (FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') != '') {
            $frm->addHtml('', 'security_code', '<div class="g-recaptcha" data-sitekey="' . FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') . '"></div>');
        }
        $frm->addSubmitButton('', 'btn_forgot', Label::getLabel('LBL_Send_Reset_Password_Email'));
        return $frm;
    }

    /**
     * Get Reset Password Form
     * 
     * @param int $aId
     * @param string $token
     * @return \Form
     */
    private function getResetPasswordForm(int $aId, string $token): Form
    {
        $frm = new Form('frmResetPassword');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addPasswordField(Label::getLabel('LBL_NEW_PASSWORD'), 'new_pwd');
        $fld->requirements()->setRequired();
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $fld_cp = $frm->addPasswordField(Label::getLabel('LBL_CONFIRM_PASSWORD'), 'confirm_pwd');
        $fld_cp->requirements()->setRequired();
        $fld_cp->requirements()->setCompareWith('new_pwd', 'eq', '');
        $frm->addHiddenField('', 'apr_id', $aId, ['id' => 'apr_id']);
        $frm->addHiddenField('', 'token', $token, ['id' => 'token']);
        $frm->addSubmitButton('', 'btn_reset', Label::getLabel('LBL_Reset_Pasword'));
        return $frm;
    }
}
