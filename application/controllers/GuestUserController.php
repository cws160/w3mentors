<?php

use Google\Service\Oauth2;

/**
 * Guest User Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GuestUserController extends MyAppController
{

    /**
     * Initialize Guest User
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $actions = ['verifyEmail', 'configureEmail', 'updateEmail'];
        if (!in_array($action, $actions) && $this->siteUserId > 0) {
            if (API_CALL || FatUtility::isAjaxCall()) {
                MyUtility::dieJsonError(Label::getLabel('LBL_USER_ALREADY_LOGGED_IN'));
            }
            FatApp::redirectUser(MyUtility::makeUrl('Account', '', [], CONF_WEBROOT_DASHBOARD));
        }
    }

    /**
     * Render Login|Signin Form
     * 
     * @return type
     */
    public function loginForm()
    {
        $this->set('frm', UserAuth::getSigninForm());
        $_SESSION[AppConstant::SEARCH_SESSION] = FatApp::getPostedData();
        if (FatApp::getPostedData('isPopUp', FatUtility::VAR_INT, 0)) {
            $this->_template->render(false, false, 'guest-user/login-form-popup.php');
            return;
        }
        $this->_template->render();
    }

    /**
     * Login|Signin Setup
     */
    public function signinSetup()
    {
        $frm = UserAuth::getSigninForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $auth = new UserAuth();
        if (!$auth->login($post['username'], $post['password'], MyUtility::getUserIp())) {
            if ($auth->verified == AppConstant::NO) {
                MyUtility::dieUnverified($auth->getError());
            } else {
                MyUtility::dieJsonError($auth->getError());
            }
        }
        if (API_CALL) {
            $user = User::getDetail(UserAuth::getLoggedUserId());
            User::setDevice(FatUtility::int($user['user_id']));
            $user['user_photo'] = User::getPhoto($user['user_id']);
            MyUtility::dieJsonSuccess([
                'msg' => Label::getLabel("MSG_LOGIN_SUCCESSFULL"),
                'token' => $auth->getToken(), 'user' => $user
            ]);
        }
        if (FatUtility::int($post['remember_me']) == AppConstant::YES) {
            UserAuth::setAuthTokenUser(UserAuth::getLoggedUserId());
        }
        MyUtility::dieJsonSuccess(Label::getLabel("MSG_LOGIN_SUCCESSFULL"));
    }

    /**
     * Register|Signup Form
     */
    public function registerForm()
    {
        $termPageId = FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0);
        $policyPageId = FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_INT, 0);
        $privacyPolicyLink = $termsConditionsLink = '';
        if ($policyPageId > 0) {
            $privacyPolicyLink = MyUtility::makeUrl('Cms', 'view', [$policyPageId]);
        }
        if ($termPageId > 0) {
            $termsConditionsLink = MyUtility::makeUrl('Cms', 'view', [$termPageId]);
        }
        $this->sets([
            'frm' => $this->getSignupForm(),
            'privacyPolicyLink' => $privacyPolicyLink,
            'termsConditionsLink' => $termsConditionsLink,
            'siteKey' => FatApp::getConfig('CONF_RECAPTCHA_SITEKEY'),
            'secretKey' => FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY'),
        ]);
        $this->_template->render(true, true, 'guest-user/registration-form.php');
    }

    /**
     * Render Register|Signup Form
     */
    public function signupForm()
    {
        $termPageId = FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0);
        $policyPageId = FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_INT, 0);
        $privacyPolicyLink = $termsConditionsLink = '';
        if ($policyPageId > 0) {
            $privacyPolicyLink = MyUtility::makeUrl('Cms', 'view', [$policyPageId]);
        }
        if ($termPageId > 0) {
            $termsConditionsLink = MyUtility::makeUrl('Cms', 'view', [$termPageId]);
        }
        $this->sets([
            'frm' => $this->getSignupForm(),
            'privacyPolicyLink' => $privacyPolicyLink,
            'termsConditionsLink' => $termsConditionsLink,
            'siteKey' => FatApp::getConfig('CONF_RECAPTCHA_SITEKEY'),
            'secretKey' => FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY'),
        ]);
        $this->set('frm', $this->getSignupForm());
        $this->_template->render(false, false);
    }

    /**
     * Register|Signup Setup
     */
    public function signupSetup()
    {
        $frm = $this->getSignupForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!CommonHelper::sanitizeInput([$post['user_first_name'], $post['user_last_name']])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SCRIPT_TAG_NOT_ALLOWED_IN_FIELDS') . ' ' . implode(', ', CommonHelper::getCaptionsForFields($frm, ['user_first_name', 'user_last_name'])));
        }
        $post['user_email'] = trim($post['user_email']);
        if (!MyUtility::validatePassword($post['user_password'])) {
            MyUtility::dieJsonError(Label::getLabel('MSG_PASSWORD_MUST_BE_EIGHT_ALPHANUMERIC'));
        }
        if (
            FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') != '' &&
            FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '') != ''
        ) {
            $recaptcha = FatApp::getPostedData('g-recaptcha-response', FatUtility::VAR_STRING, '');
            if (!CommonHelper::verifyCaptcha($recaptcha)) {
                FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_CAPTCHA'));
            }
        }
        $auth = new UserAuth();
        if (!$auth->signup($post)) {
            MyUtility::dieJsonError($auth->getError());
        }
        $autoLogin = false;
        $user = User::getByEmail($post['user_email']);
        if (API_CALL) {
            User::setDevice(FatUtility::int($user['user_id']));
        }
        $response = $auth->sendSignupEmails($user);
        if (!empty($response)) {
            MyUtility::dieJsonSuccess([
                'msg' => $response['msg'],
                'redirectUrl' => $response['url'],
                'autoLogin' => $autoLogin
            ]);
        }
        $redirectUrl = MyUtility::makeFullUrl('Home');
        $msg = Label::getLabel('LBL_REGISTERATION_SUCCESSFULL,_YOU_CAN_LOGIN!');
        if (
            FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION') == AppConstant::NO &&
            FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION') == AppConstant::NO &&
            FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION') == AppConstant::YES
        ) {
            $auth = new UserAuth();
            if (!$auth->login($post['user_email'], $post['user_password'], MyUtility::getUserIp())) {
                MyUtility::dieJsonError($auth->getError());
            }
            $redirectUrl = CommonHelper::getRefererPageUrl();
            $autoLogin = true;
            $msg = Label::getLabel('LBL_REGISTERATION_SUCCESSFULL,_PLEASE_WAIT!..');
        }
        $response = ['msg' => $msg, 'redirectUrl' => $redirectUrl, 'autoLogin' => $autoLogin];
        if (API_CALL && $autoLogin) {
            $response['token'] = $auth->getToken();
            $response['user'] = User::getDetail(UserAuth::getLoggedUserId());
            $response['user']['user_photo'] = User::getPhoto(UserAuth::getLoggedUserId());
        }
        MyUtility::dieJsonSuccess($response);
    }

    /**
     * Verify User Email Id
     * 
     * @param string $code
     */
    public function verifyEmail(string $code)
    {
        $verification = new Verification();
        if (!$verification->verify($code)) {
            Message::addErrorMessage($verification->getError());
            FatUtility::exitWithErrorCode(404);
        }
        $verification->removeExpiredToken();
        Message::addMessage(Label::getLabel("MSG_EMAIL_VERIFIED_SUCCESFULLY"));
        FatApp::redirectUser(MyUtility::makeUrl('Home'));
    }

    /**
     * Render Forgot Password Form
     */
    public function forgotPassword()
    {
        $this->sets([
            'siteKey' => FatApp::getConfig('CONF_RECAPTCHA_SITEKEY'),
            'secretKey' => FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY'),
            'frm' => $this->getForgotPasswordForm()
        ]);
        $this->_template->render();
    }

    /**
     * Setup Forgot Password Request
     */
    public function forgotPasswordSetup()
    {
        $frm = $this->getForgotPasswordForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $userAuth = new UserAuth();
        $captcha = FatApp::getPostedData('g-recaptcha-response', FatUtility::VAR_STRING, '');
        if (!$userAuth->setupResetPasswordRequest($post['user_email'], $captcha)) {
            MyUtility::dieJsonError($userAuth->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel("MSG_SENT_RESET_PASSWORD_INSTRUCTIONS_ON_YOUR_EMAIL"));
    }

    /**
     * Render Reset Password Form
     * 
     * @param int $userId
     * @param string $token
     */
    public function resetPassword($userId, $token)
    {
        $userId = FatUtility::int($userId);
        $userAuth = new UserAuth();
        if (!$userAuth->validateResetPasswordLink($userId, $token)) {
            Message::addErrorMessage($userAuth->getError());
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
        }
        $this->set('frm', $this->getResetPasswordForm($userId, $token));
        $this->_template->render();
    }

    /**
     * Setup Reset Password
     */
    public function resetPasswordSetup()
    {
        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);
        $token = FatApp::getPostedData('token', FatUtility::VAR_STRING, '');
        $frm = $this->getResetPasswordForm($userId, $token);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!MyUtility::validatePassword($post['new_password'])) {
            MyUtility::dieJsonError(Label::getLabel('MSG_PASSWORD_MUST_BE_EIGHT_ALPHANUMERIC'));
        }
        $userAuth = new UserAuth();
        if (!$userAuth->validateResetPasswordLink($userId, $token)) {
            MyUtility::dieJsonError($userAuth->getError());
        }
        if (!$userAuth->setupResetPassword($userId, $post['new_password'])) {
            MyUtility::dieJsonError($userAuth->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY'));
    }

    /**
     * Resend Signup Verify Email
     * 
     * @param string $email
     */
    public function resendSignupVerifyEmail(string $email)
    {
        $user = User::getByEmail($email);
        if (empty($user)) {
            MyUtility::dieJsonError(Label::getLabel('ERR_INVALID_REQUEST'));
        }
        $auth = new UserAuth();
        if (!$auth->sendVerifyEmail($user)) {
            MyUtility::dieJsonError($auth->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_VERIFICATION_EMAIL_HAS_BEEN_SENT'));
    }

    /**
     * Get Signup Form
     * 
     * @return Form
     */
    private function getSignupForm(): Form
    {
        $frm = new Form('signupFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'user_id', 0, ['id' => 'user_id']);
        $frm->addRequiredField(Label::getLabel('LBL_FIRST_NAME'), 'user_first_name');
        $frm->addTextBox(Label::getLabel('LBL_LAST_NAME'), 'user_last_name');
        $fld = $frm->addEmailField(Label::getLabel('LBL_EMAIL_ID'), 'user_email', '', ['autocomplete="off"']);
        $fld->setUnique('tbl_users', 'user_email', 'user_id', 'user_id', 'user_id');
        $fld = $frm->addPasswordField(Label::getLabel('LBL_PASSWORD'), 'user_password');
        $fld->requirements()->setRequired();
        $fld->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_POSITION_NONE);
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $fld = $frm->addCheckBox(Label::getLabel('LBL_I_ACCEPT_TO_THE'), 'agree', AppConstant::NO);
        $fld->requirements()->setRequired();
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('MSG_TERMS_AND_CONDITION_ARE_MANDATORY'));
        $recaptchaKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
        if (!empty($recaptchaKey)) {
            $frm->addHtml('', 'htmlNote', '<div class="g-recaptcha" data-sitekey="' . FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') . '"></div>');
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Register'));
        return $frm;
    }

    /**
     * Get Forgot Password Form
     * 
     * @return Form
     */
    private function getForgotPasswordForm(): Form
    {
        $frm = new Form('forgotPasswordFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addEmailField(Label::getLabel('LBL_EMAIL_ID'), 'user_email')->requirements()->setRequired();
        $frm->addHtml('', 'htmlNote', '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('BTN_SUBMIT'));
        return $frm;
    }

    /**
     * Get Reset Password Form
     * 
     * @param int $userId
     * @param string $token
     * @return Form
     */
    private function getResetPasswordForm($userId, $token): Form
    {
        $frm = new Form('frmResetPwd');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addPasswordField(Label::getLabel('LBL_NEW_PASSWORD'), 'new_password');
        $fld->requirements()->setRequired();
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $fld_cp = $frm->addPasswordField(Label::getLabel('LBL_CONFIRM_NEW_PASSWORD'), 'confirm_password');
        $fld_cp->requirements()->setRequired();
        $fld_cp->requirements()->setCompareWith('new_password', 'eq', Label::getLabel('LBL_NEW_PASSWORD'));
        $frm->addHiddenField('', 'user_id', $userId, ['id' => 'user_id']);
        $frm->addHiddenField('', 'token', $token, ['id' => 'token']);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_RESET_PASSWORD'));
        return $frm;
    }

    /**
     * Google(Social) Login
     */
    public function googleLogin()
    {
        try {
            if (!empty($error)) {
                if (API_CALL) {
                    MyUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
                }
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
            }
            $code = $_GET['code'] ?? null;
            $google = new Google();
            if (!$client = $google->getClient()) {
                if (API_CALL) {
                    MyUtility::dieJsonError($google->getError());
                }
                Message::addErrorMessage($google->getError());
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
            }
            $client->setApplicationName(FatApp::getConfig('CONF_WEBSITE_NAME_' . MyUtility::getSiteLangId()));
            $client->setScopes([Oauth2::USERINFO_EMAIL, Oauth2::USERINFO_PROFILE]);
            $client->setRedirectUri(MyUtility::generateFullUrl('GuestUser', 'googleLogin'));
            if (!empty($code)) {
                if (API_CALL) {
                    $accessToken = $code;
                } else {
                    $accessToken = $client->fetchAccessTokenWithAuthCode($code);
                    if (array_key_exists('error', $accessToken)) {
                        if (API_CALL) {
                            MyUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
                        }
                        Message::addErrorMessage(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
                        FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                    }
                }
                $client->setAccessToken($accessToken);
                $oauth2 = new Oauth2($client);
                $userInfo = $oauth2->userinfo->get();
                if (empty($userInfo['id'])) {
                    if (API_CALL) {
                        MyUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
                    }
                    Message::addErrorMessage(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
                    FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                }
                $auth = new UserAuth();
                if (!$auth->updateGoogleLogin($userInfo['id'], $userInfo['email'], $userInfo['name'] ?? 'Test')) {
                    if (API_CALL) {
                        MyUtility::dieJsonError($auth->getError());
                    }
                    Message::addErrorMessage($auth->getError());
                    FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                }
                $user = User::getByEmail($userInfo['email']);
                if (!$auth->login($user['user_email'], $user['user_password'], MyUtility::getUserIp(), false)) {
                    if (API_CALL) {
                        MyUtility::dieJsonError($auth->getError());
                    }
                    Message::addErrorMessage(Label::getLabel($auth->getError()));
                    FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                }
                if (API_CALL) {
                    User::setDevice(FatUtility::int($user['user_id']));
                    $user['user_photo'] = User::getPhoto($user['user_id']);
                    $user['user_is_verified'] = is_null($user['user_verified']) ? AppConstant::NO : AppConstant::YES;
                    MyUtility::dieJsonSuccess([
                        'user' => $user, 'token' => $auth->getToken(),
                        'msg' => Label::getLabel("LBL_LOG_IN_SUCCESSFULL")
                    ]);
                }
                Message::addMessage(Label::getLabel("LBL_LOG_IN_SUCCESSFULL"));
                FatApp::redirectUser(CommonHelper::getRefererPageUrl());
            }
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
            }
            FatApp::redirectUser($client->createAuthUrl());
        } catch (\Throwable $th) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_GOOGLE_LOGIN_IS_NOT_AVAILABLE'));
            }
            Message::addErrorMessage(Label::getLabel('LBL_GOOGLE_LOGIN_IS_NOT_AVAILABLE'));
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
        }
    }

    /**
     * Facebook(Social) Login
     */
    public function facebookLogin()
    {
        try {
            $code = $_GET['code'] ?? null;
            $fb = new Facebook\Facebook([
                'app_id' => FatApp::getConfig('CONF_FACEBOOK_APP_ID', FatUtility::VAR_STRING, ''),
                'app_secret' => FatApp::getConfig('CONF_FACEBOOK_APP_SECRET', FatUtility::VAR_STRING, ''),
                'default_graph_version' => 'v2.10'
            ]);
            $helper = $fb->getRedirectLoginHelper();
            if (!empty($code)) {
                $accessToken = $code;
                if (!API_CALL) {
                    $accessToken = $helper->getAccessToken();
                }
                if (empty($accessToken)) {
                    if (API_CALL) {
                        MyUtility::dieJsonError($helper->getError());
                    }
                    Message::addErrorMessage($helper->getError());
                    FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'signinForm'));
                }
                $fb->setDefaultAccessToken($accessToken);
                $accessToken = $fb->getDefaultAccessToken($accessToken);
                $profileRequest = $fb->get('/me?fields=id,name,email,first_name,last_name', $accessToken);
                $userInfo = $profileRequest->getDecodedBody();
                $auth = new UserAuth();
                if (!$user = $auth->facebookLogin($userInfo['id'], $userInfo['name'], $userInfo['email'] ?? null)) {
                    if (API_CALL) {
                        MyUtility::dieJsonError($auth->getError());
                    }
                    Message::addErrorMessage($auth->getError());
                    FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                }
                $redirectUrl = CommonHelper::getRefererPageUrl();
                $successLabel = 'LBL_LOG_IN_SUCCESSFULL';
                if (empty($user['user_email'])) {
                    if (!$auth->setUserSession($user['user_email'], MyUtility::getUserIp(), $user)) {
                        if (API_CALL) {
                            MyUtility::dieJsonError($auth->getError());
                        }
                        Message::addErrorMessage(Label::getLabel($auth->getError()));
                        FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                    }
                    if (API_CALL) {
                        MyUtility::dieJsonError([
                            'user' => $user, 'token' => $auth->getToken(),
                            'msg' => Label::getLabel("MSG_PLEASE_CONFIGURE_YOUR_EMAIL")
                        ]);
                    }
                    $redirectUrl = MyUtility::makeUrl('GuestUser', 'configureEmail');
                    $successLabel = 'LBL_LOG_IN_SUCCESSFULL_PLEASE_UPDATE_YOUR_EMAIL';
                } elseif (!$auth->login($user['user_email'], $user['user_password'], MyUtility::getUserIp(), false)) {
                    if (API_CALL) {
                        MyUtility::dieJsonError($auth->getError());
                    }
                    Message::addErrorMessage(Label::getLabel($auth->getError()));
                    FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                }
                if (API_CALL) {
                    User::setDevice(FatUtility::int($user['user_id']));
                    $userDetail = User::getDetail($user['user_id']);
                    $userDetail['user_is_verified'] = is_null($user['user_verified']) ? AppConstant::NO : AppConstant::YES;
                    $userDetail['user_photo'] = User::getPhoto($user['user_id']);
                    MyUtility::dieJsonSuccess([
                        'user' => $userDetail,
                        'token' => $auth->getToken(),
                        'msg' => Label::getLabel($successLabel)
                    ]);
                }
                Message::addMessage(Label::getLabel($successLabel));
                FatApp::redirectUser($redirectUrl);
            }
            $redirectUrl = MyUtility::generateFullUrl('GuestUser', 'facebookLogin');
            $protocol = FatUtility::int(FatApp::getConfig('CONF_USE_SSL')) ? 'https://' : 'http://';
            $redirectUrl = $protocol . $_SERVER['SERVER_NAME'] . urldecode($redirectUrl);
            FatApp::redirectUser($helper->getLoginUrl($redirectUrl, ['email', 'public_profile']));
        } catch (\Throwable $th) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_FACEBOOK_LOGIN_IS_NOT_AVAILABLE'));
            }
            Message::addErrorMessage(Label::getLabel('LBL_FACEBOOK_LOGIN_IS_NOT_AVAILABLE'));
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
        }
    }

    /**
     * Apple Login
     */
    public function appleLogin()
    {
        $post = FatApp::getPostedData();
        if (isset($post['code'])) {
            if (isset($post['error'])) {
                $message = Label::getLabel('MSG_AUTHORIZATION_SERVER_RETURNED_AN_ERROR: ');
                $message .= htmlspecialchars($post['error']);
                if (API_CALL) {
                    MyUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
            }
            if (empty($post['id_token'])) {
                if (API_CALL) {
                    MyUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
                }
                Message::addErrorMessage(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
            }
            $claims = explode('.', $post['id_token'])[1];
            $claims = json_decode(base64_decode($claims), true);
            $appleUserInfo = isset($post['user']) ? json_decode($post['user'], true) : false;
            $appleId = isset($claims['sub']) ? $claims['sub'] : '';
            if (!$appleUserInfo) {
                $email = isset($claims['email']) ? $claims['email'] : null;
            } else {
                $email = $appleUserInfo['email'];
            }
            if (!empty($email)) {
                $exp = explode("@", $email);
                $username = preg_replace('/[^A-Za-z\- ]/', '', isset($exp[0]) ? $exp[0] : '');
            } else {
                $appleIdArr = explode(".", $appleId);
                $username = 'apl' . $appleIdArr[0];
                $email = null;
            }
            $auth = new UserAuth();
            if (!$user = $auth->updateAppleLogin($appleId, $username, $email)) {
                if (API_CALL) {
                    MyUtility::dieJsonError($auth->getError());
                }
                Message::addErrorMessage($auth->getError());
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
            }
            if (empty($user['user_email'])) {
                if (!$auth->setUserSession($user['user_email'], MyUtility::getUserIp(), $user)) {
                    if (API_CALL) {
                        MyUtility::dieJsonError($auth->getError());
                    }
                    Message::addErrorMessage(Label::getLabel($auth->getError()));
                    FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
                }
                if (API_CALL) {
                    MyUtility::dieJsonError([
                        'user' => $user, 'token' => $auth->getToken(),
                        'msg' => Label::getLabel("MSG_PLEASE_CONFIGURE_YOUR_EMAIL")
                    ]);
                }
                Message::addErrorMessage(Label::getLabel('LBL_LOG_IN_SUCCESSFULL_PLEASE_UPDATE_YOUR_EMAIL'));
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'configureEmail'));
            } elseif (!$auth->login($user['user_email'], $user['user_password'], MyUtility::getUserIp(), false)) {
                if (API_CALL) {
                    MyUtility::dieJsonError($auth->getError());
                }
                Message::addErrorMessage(Label::getLabel($auth->getError()));
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
            }
            if (API_CALL) {
                User::setDevice(FatUtility::int($user['user_id']));
                $userDetail = User::getDetail($user['user_id']);
                $userDetail['user_photo'] = User::getPhoto($user['user_id']);
                $userDetail['user_is_verified'] = is_null($user['user_verified']) ? AppConstant::NO : AppConstant::YES;
                MyUtility::dieJsonSuccess([
                    'user' => $userDetail, 'token' => $auth->getToken(),
                    'msg' => Label::getLabel("LBL_LOG_IN_SUCCESSFULL")
                ]);
            }
            Message::addMessage(Label::getLabel("LBL_LOG_IN_SUCCESSFULL"));
            FatApp::redirectUser(CommonHelper::getRefererPageUrl());
        }
        if (API_CALL) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER'));
        }
        FatApp::redirectUser($this->getRequestUri());
    }

    public function socialLogin($socialType)
    {
        switch ($socialType) {
            case 'facebook':
                if (empty($_GET['code'])) {
                    MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                }
                $this->facebookLogin();
                break;
            case 'google':
                if (empty($_GET['code'])) {
                    MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                }
                $this->googleLogin();
                break;
            case 'apple':
                if (empty($_POST['code'])) {
                    MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                }
                $this->appleLogin();
                break;
            default:
                MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                break;
        }
    }

    /**
     * Render Configure Email Form
     */
    public function configureEmail()
    {
        if (empty($this->siteUserId)) {
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
        }
        if (!empty($this->siteUser['user_email'])) {
            FatApp::redirectUser(MyUtility::makeUrl('Account', '', [], CONF_WEBROOT_DASHBOARD));
        }
        $this->set('frm', $this->getConfigureEmailForm());
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render();
    }

    /**
     * Update Email Address
     */
    public function updateEmail()
    {
        $emailFrm = $this->getConfigureEmailForm();
        if (!$post = $emailFrm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($emailFrm->getValidationErrors()));
        }
        $fields = ['user_id', 'user_email', 'user_verified', 'user_lang_id', 'user_first_name', 'user_last_name'];
        $user = User::getAttributesById($this->siteUserId, $fields);
        if (!empty($user['user_email'])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $token = $this->siteUserId . '_' . FatUtility::getRandomString(15);
        $verification = new Verification($this->siteUserId);
        if (!$verification->removeToken($this->siteUserId)) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError($verification->getError());
        }
        if (!$verification->addToken($token, $this->siteUserId, $post['new_email'], Verification::TYPE_EMAIL_CHANGE)) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError($verification->getError());
        }
        $user['user_email'] = $post['new_email'];
        if (!$this->sendEmailChangeVerificationLink($token, $user)) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError(Label::getLabel('MSG_UNABLE_TO_PROCESS_YOUR_REQUSET'));
        }
        $db->commitTransaction();
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_PLEASE_VERIFY_YOUR_EMAIL'));
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_UPDATE_EMAIL_REQUEST_SENT_PLEASE_VERIFY_YOUR_NEW_EMAIL'));
    }

    /**
     * Resend Verification Link
     * 
     * @param string $email
     */
    public function resendVerificationLink(string $email)
    {
        $user = User::getByEmail($email);
        if (empty($user)) {
            MyUtility::dieJsonError(Label::getLabel('MSG_ERROR_INVALID_REQUEST'));
        }
        if (!empty($user['user_verified'])) {
            FatUtility::dieWithError(Label::getLabel("MSG_ALREADY_VERIFIED_PLEASE_LOGIN."));
        }
        $userAuth = new UserAuth();
        if (!$userAuth->sendVerifyEmail($user)) {
            FatUtility::dieWithError($userAuth->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_VERIFICATION_EMAIL_SENT_AGAIN'));
    }

    /**
     * Get Configure Email Form
     * 
     * @return Form
     */
    private function getConfigureEmailForm(): Form
    {
        $frm = new Form('changeEmailFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'user_id', $this->siteUserId);
        $newEmail = $frm->addEmailField(Label::getLabel('LBL_NEW_EMAIL'), 'new_email');
        $newEmail->setUnique('tbl_users', 'user_email', 'user_id', 'user_id', 'user_id');
        $newEmail->requirements()->setRequired();
        $conNewEmail = $frm->addEmailField(Label::getLabel('LBL_CONFIRM_NEW_EMAIL'), 'conf_new_email');
        $conNewEmailReq = $conNewEmail->requirements();
        $conNewEmailReq->setRequired();
        $conNewEmailReq->setCompareWith('new_email', 'eq');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Get Email Verification Form
     * 
     * @param string $token
     * @param array $data
     * @return boolean
     */
    private function sendEmailChangeVerificationLink(string $token, array $data): bool
    {
        $vars = [
            '{user_first_name}' => $data['user_first_name'],
            '{user_last_name}' => $data['user_last_name'],
            '{user_full_name}' => $data['user_first_name'] . ' ' . $data['user_last_name'],
            '{verification_url}' => MyUtility::makeFullUrl('GuestUser', 'verifyEmail', [$token], CONF_WEBROOT_FRONT_URL),
        ];
        $mail = new FatMailer($this->siteLangId, 'user_email_change_verification');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$data['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Apple Login Request URI
     */
    private function getRequestUri()
    {
        $client_id = FatApp::getConfig('CONF_APPLE_CLIENT_ID', FatUtility::VAR_STRING, '');
        if (empty($client_id)) {
            Message::addErrorMessage(Label::getLabel('LBL_APPLE_LOGIN_IS_NOT_AVAILABLE', $this->siteLangId));
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm'));
        }
        $redirectUri = MyUtility::generateFullUrl('GuestUser', 'appleLogin');
        $_SESSION['appleSignIn']['state'] = bin2hex(random_bytes(5));
        return 'https://appleid.apple.com/auth/authorize?' . http_build_query([
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'client_id' => $client_id,
            'redirect_uri' => $redirectUri,
            'state' => $_SESSION['appleSignIn']['state'],
            'scope' => 'name email',
        ]);
    }

    /**
     * Register Affiliate Form
     */
    public function affiliateSignupForm()
    {
        if (!User::isAffiliateEnabled()) {
            Message::addErrorMessage(Label::getLabel('LBL_AFFILIATE_MODULE_IS_NOT_ENABLED'));
            FatApp::redirectUser(MyUtility::makeUrl('Home'));
        }
        $contentBlocks = ExtraPage::getPageBlocks(ExtraPage::TYPE_AFFLIATE_REGISTRATION, $this->siteLangId);
        $termPageId = FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0);
        $policyPageId = FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_INT, 0);
        $privacyPolicyLink = $termsConditionsLink = '';
        if ($policyPageId > 0) {
            $privacyPolicyLink = MyUtility::makeUrl('Cms', 'view', [$policyPageId]);
        }
        if ($termPageId > 0) {
            $termsConditionsLink = MyUtility::makeUrl('Cms', 'view', [$termPageId]);
        }
        $this->sets([
            'frm' => $this->getSignupForm(),
            'siteLangId' => $this->siteLangId,
            'privacyPolicyLink' => $privacyPolicyLink,
            'termsConditionsLink' => $termsConditionsLink,
            'contentBlocks' => $contentBlocks,
            'siteKey' => FatApp::getConfig('CONF_RECAPTCHA_SITEKEY'),
            'secretKey' => FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY'),
        ]);
        $this->_template->render();
    }

    /**
     * Register|Signup Form
     */

    public function affiliateSignupSetup()
    {
        if (!User::isAffiliateEnabled()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_AFFILIATE_MODULE_IS_NOT_ENABLED'));
        }
        $frm = $this->getSignupForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (
            FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') != '' &&
            FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '') != ''
        ) {
            $recaptcha = FatApp::getPostedData('g-recaptcha-response', FatUtility::VAR_STRING, '');
            if (!CommonHelper::verifyCaptcha($recaptcha)) {
                FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_CAPTCHA'));
            }
        }
        if (!CommonHelper::sanitizeInput([$post['user_first_name'], $post['user_last_name']])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SCRIPT_TAG_NOT_ALLOWED_IN_FIELDS') . ' ' . implode(', ', CommonHelper::getCaptionsForFields($frm, ['user_first_name', 'user_last_name'])));
        }
        $post['user_email'] = trim($post['user_email']);
        if (!MyUtility::validatePassword($post['user_password'])) {
            MyUtility::dieJsonError(Label::getLabel('MSG_PASSWORD_MUST_BE_EIGHT_ALPHANUMERIC'));
        }
        $auth = new UserAuth();
        if (!$auth->affiliateSignup($post)) {
            MyUtility::dieJsonError($auth->getError());
        }
        $autoLogin = false;
        $user = User::getByEmail($post['user_email']);
        $response = $auth->sendSignupEmails($user);
        if (!empty($response)) {
            MyUtility::dieJsonSuccess([
                'msg' => $response['msg'],
                'redirectUrl' => $response['url'],
                'autoLogin' => $autoLogin
            ]);
        }
        $redirectUrl = MyUtility::makeFullUrl('Home');
        $msg = Label::getLabel('LBL_REGISTERATION_SUCCESSFULL,_YOU_CAN_LOGIN!');
        if (
            FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION') == AppConstant::NO &&
            FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION') == AppConstant::NO &&
            FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION') == AppConstant::YES
        ) {
            $auth = new UserAuth();
            if (!$auth->login($post['user_email'], $post['user_password'], MyUtility::getUserIp())) {
                MyUtility::dieJsonError($auth->getError());
            }
            $redirectUrl = MyUtility::makeFullUrl('Account', '', [], CONF_WEBROOT_DASHBOARD);
            $autoLogin = true;
            $msg = Label::getLabel('LBL_REGISTERATION_SUCCESSFULL,_PLEASE_WAIT!..');
        }
        $response = ['msg' => $msg, 'redirectUrl' => $redirectUrl, 'autoLogin' => $autoLogin];
        MyUtility::dieJsonSuccess($response);
    }
}
