<?php

/**
 * This class is used to handle User Auth
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserAuth extends FatModel
{

    const SESSION_ELEMENT = 'APP_SESSION';
    const REFERAL_ELEMENT = 'REFERAL_SESSION';
    const ADMIN_SESSION_ELEMENT = 'ADMIN_SESSION_ELEMENT';
    const COOKIES_ELEMENT = 'APP_COOKIES';
    const DB_TBL_USER_AUTH = 'tbl_user_auth_token';
    const DB_TBL_USER_PRR = 'tbl_user_password_reset_requests';
    const DB_TBL_FAILED_ATTEMPTS = 'tbl_failed_login_attempts';

    private $token;
    public $verified = 1;

    public function __construct()
    {
        $this->token = null;
        parent::__construct();
    }

    public static function getReferal()
    {
        if (API_CALL) {
            return $_REQUEST['referral'] ?? '';
        }
        return $_SESSION[static::REFERAL_ELEMENT]['REFERAL'] ?? '';
    }

    public static function setReferal()
    {
        if (!empty($_REQUEST['referral'] ?? "")) {
            $_SESSION[static::REFERAL_ELEMENT]['REFERAL'] = $_REQUEST['referral'];
        }
    }

    public static function resetReferal()
    {
        if (!API_CALL) {
            $_SESSION[static::REFERAL_ELEMENT]['REFERAL'] = '';
        }
    }

    public static function setAdminLoggedIn($status)
    {
        $_SESSION[static::ADMIN_SESSION_ELEMENT]['LOGGED'] = $status;
    }

    public static function getAdminLoggedIn()
    {
        return $_SESSION[static::ADMIN_SESSION_ELEMENT]['LOGGED'] ?? 0;
    }

    /**
     *  User Login
     * 
     * @param string $username
     * @param string $password
     * @param string $userip
     * @return bool
     */
    public function login(string $username, string $password = null, string $userip = null, bool $enypass = true): bool
    {
        if (empty($username) || ($enypass && empty($password))) {
            $this->error = Label::getLabel('ERR_INVALID_CERDENTIALS');
            return false;
        }
        $user = User::getByEmail($username);
        if (empty($user)) {
            $this->logFailedLoginAttempt($username, $userip);
            $this->error = Label::getLabel('ERR_INVALID_CERDENTIALS');
            return false;
        }
        if ($this->isBruteForcing($username, $userip)) {
            $this->sendFailedLoginEmail($user);
            $this->error = Label::getLabel('ERR_YOUR_ATTEMPT_LIMIT_EXCEEDED');
            return false;
        }
        $password = $enypass ? static::encryptPassword($password) : $password;
        if ($user['user_password'] != $password) {
            $this->logFailedLoginAttempt($username, $userip);
            $this->error = Label::getLabel('ERR_INVALID_CERDENTIALS');
            return false;
        }
        if (empty($user['user_active'])) {
            $this->error = Label::getLabel('ERR_YOUR_ACCOUNT_IS_INACTIVE');
            return false;
        }
        if (empty($user['user_verified'])) {
            $this->verified = 0;
            if (API_CALL) {
                $this->error = Label::getLabel('ERR_YOUR_VERIFICATION_PENDING');
                return false;
            }
            $error = Label::getLabel('ERR_YOUR_VERIFICATION_PENDING_{link}');
            $link = new HtmlElement('a', [
                'href' => "javascript:void(0)",
                "onclick" => "resendVerificationLink('" . $username . "')"
            ]);
            $link->appendElement('plaintext', [], '<b>' . Label::getLabel('LBL_CLICK_HERE') . '</b>', true);
            $this->error = str_replace("{link}", $link->getHtml(), $error);
            return false;
        }
        if (API_CALL && $user['user_is_affiliate'] == AppConstant::YES) {
            $this->error = Label::getLabel('ERR_ACCESS_DENIED');
            return false;
        }
        if ($user['user_is_affiliate'] == AppConstant::YES && !User::isAffiliateEnabled()) {
            $this->error = Label::getLabel('MSG_AFFILIATE_MODULE_IS_DISABLED_BY_ADMIN');
            return false;
        }
        if (!$this->setUserSession($username, $userip, $user)) {
            $this->error = Label::getLabel('ERR_SOMETHING_WENT_WRONT_TRY_AGAIN');
            return false;
        }
        return true;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     * Brute Force Check
     * 
     * @param string $username
     * @param string $userip
     * @return bool
     */
    public function isBruteForcing(string $username, string $userip): bool
    {
        $srch = new SearchBase('tbl_failed_login_attempts');
        $cond = $srch->addCondition('attempt_ip', '=', $userip);
        $cond->attachCondition('attempt_username', '=', $username);
        $srch->addCondition('attempt_time', '>=', date('Y-m-d H:i:s', strtotime("-5 minutes")));
        $srch->addFld('COUNT(*) AS total');
        $srch->doNotCalculateRecords();
        $attempts = FatApp::getDb()->fetch($srch->getResultSet());
        return ($attempts['total'] > 3);
    }

    /**
     * Send Failed Login Email
     * 
     * @param array $user
     * @return bool
     */
    private function sendFailedLoginEmail(array $user): bool
    {
        $mail = new FatMailer($user['user_lang_id'], 'failed_login_attempt');
        $mail->setVariables(['{user_full_name}' => $user['user_first_name'] . ' ' . $user['user_last_name']]);
        if (!$mail->sendMail([$user['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Log Failed Login Attempt
     * 
     * @param string $username
     * @param string $userip
     */
    public function logFailedLoginAttempt(string $username, string $userip)
    {
        FatApp::getDb()->deleteRecords('tbl_failed_login_attempts', ['smt' => 'attempt_time < ?', 'vals' => [date('Y-m-d H:i:s', strtotime("-7 Day"))]]);
        FatApp::getDb()->insertFromArray('tbl_failed_login_attempts', ['attempt_username' => $username, 'attempt_ip' => $userip, 'attempt_time' => date('Y-m-d H:i:s')]);
    }

    /**
     * Set User Session
     * 
     * @param string $username
     * @param string $userip
     * @param array $user
     * @return bool
     */
    public function setUserSession($username, string $userip, array $user): bool
    {
        $cookieSettings = CookieConsent::getSettings($user['user_id']);
        if (empty($cookieSettings) && !empty($_COOKIE[CookieConsent::COOKIE_NAME])) {
            $cookieConsent = new CookieConsent($user['user_id']);
            $cookieConsent->updateSetting(json_decode($_COOKIE[CookieConsent::COOKIE_NAME], true));
        }
        if (!empty($cookieSettings)) {
            MyUtility::setCookieConsents(json_decode($cookieSettings, true), true);
        }
        if (!empty($user['user_timezone'])) {
            MyUtility::setSiteTimezone($user['user_timezone'], true);
        }
        $stmt = ['smt' => 'attempt_username = ? and attempt_ip = ?', 'vals' => [$username, $userip]];
        if (!FatApp::getDb()->deleteRecords('tbl_failed_login_attempts', $stmt)) {
            return false;
        }
        if (API_CALL && !$this->token = AppToken::generate($user['user_id'])) {
            $this->error = Label::getLabel('ERR_SOMETHING_WENT_WRONT_TRY_AGAIN');
            return false;
        }
        $user['user_token'] = $this->token;
        $userType = ($user['user_is_teacher'] == AppConstant::YES) ? User::TEACHER : (($user['user_is_affiliate'] == AppConstant::YES) ? User::AFFILIATE : User::LEARNER);
        MyUtility::setUserType($userType);
        $_SESSION[UserAuth::SESSION_ELEMENT] = [
            'user_ip' => $userip,
            'user_id' => $user['user_id'],
            'user_email' => $user['user_email'],
            'user_username' => $user['user_username'] ?? '',
            'user_first_name' => $user['user_first_name'],
            'user_last_name' => $user['user_last_name'],
            'user_token' => $user['user_token'] ?? '',
        ];
        if (!UserAuth::getAdminLoggedIn()) {
            $user = new User($user['user_id']);
            $user->setFldValue('user_password_updated', 0);
            $user->save();
        }
        return true;
    }

    /**
     * Is User Logged
     * 
     * @return bool
     */
    public static function isUserLogged(): bool
    {
        if (!empty($_SESSION[static::SESSION_ELEMENT] ?? '')) {
            return true;
        }
        if (empty($_COOKIE[static::COOKIES_ELEMENT] ?? '')) {
            static::clearAuthTokenUser();
            return false;
        }
        if (!$userId = static::getAuthTokenUserId()) {
            static::clearAuthTokenUser();
            return false;
        }
        $user = User::getAttributesById($userId);
        if (empty($user['user_verified'] ?? '') || !empty($user['user_deleted'])) {
            static::clearAuthTokenUser();
            return false;
        }
        $userAuth = new UserAuth();
        if (!$userAuth->setUserSession($user['user_email'], MyUtility::getUserIp(), $user)) {
            static::clearAuthTokenUser();
            return false;
        }
        return true;
    }

    /**
     * Get Logged User Id
     * 
     * @return int
     */
    public static function getLoggedUserId()
    {
        return FatUtility::int($_SESSION[static::SESSION_ELEMENT]['user_id'] ?? 0);
    }

    /**
     * Get Auth Token User
     * 
     * @return int|bool
     */
    private static function getAuthTokenUserId()
    {
        $token = $_COOKIE[static::COOKIES_ELEMENT] ?? '';
        $srch = new SearchBase(static::DB_TBL_USER_AUTH);
        $srch->addCondition('usrtok_token', '=', $token);
        $srch->addCondition('usrtok_expiry', '>=', date('Y-m-d H:i:s'));
        if (FatApp::getConfig('CONF_USER_REMEMBER_ME_IP_ENABLE', FatUtility::VAR_INT)) {
            $srch->addCondition('usrtok_last_ip', '=', MyUtility::getUserIp());
        }
        $srch->addCondition('usrtok_browser', '=', MyUtility::getUserAgent());
        $srch->addFld('usrtok_user_id as user_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetch($srch->getResultSet())['user_id'] ?? false;
    }

    /**
     * Set Auth Token User
     * 
     * @param int $userId
     * @return boolean
     */
    public static function setAuthTokenUser(int $userId)
    {
        $authToken = substr(md5(microtime()), 0, 32);
        $rememberMeDays = FatApp::getConfig('CONF_USER_REMEMBER_ME_DAYS', FatUtility::VAR_INT);
        $values = [
            'usrtok_user_id' => $userId,
            'usrtok_token' => $authToken,
            'usrtok_expiry' => date('Y-m-d H:i:s', strtotime('+' . $rememberMeDays . ' days')),
            'usrtok_last_ip' => MyUtility::getUserIp(),
            'usrtok_last_access' => date('Y-m-d H:i:s'),
            'usrtok_browser' => MyUtility::getUserAgent()
        ];
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USER_AUTH, $values)) {
            return false;
        }
        MyUtility::setCookie(static::COOKIES_ELEMENT, $authToken, $rememberMeDays * 86400, CONF_WEBROOT_FRONTEND);
        return true;
    }

    private static function clearAuthTokenUser()
    {
        FatApp::getDb()->deleteRecords(static::DB_TBL_USER_AUTH, [
            'smt' => 'usrtok_token = ?', 'vals' => [$_COOKIE[static::COOKIES_ELEMENT] ?? '']
        ]);
        MyUtility::setCookie(static::COOKIES_ELEMENT, '', time() - 3600, CONF_WEBROOT_FRONTEND);
    }

    public function signup(array $data): bool
    {
        if (
            empty($data['user_email']) || empty($data['user_password']) ||
            empty($data['user_first_name']) || !isset($data['user_last_name'])
        ) {
            $this->error = Label::getLabel('MSG_USER_COULD_NOT_BE_SET');
            return false;
        }
        $userData = [
            'user_dashboard' => User::LEARNER,
            'user_registered_as' => User::LEARNER,
            'user_email' => $data['user_email'],
            'user_first_name' => $data['user_first_name'],
            'user_last_name' => $data['user_last_name'],
            'user_currency_id' => MyUtility::getSiteCurrId(),
            'user_lang_id' => MyUtility::getSiteLangId(),
            'user_timezone' => MyUtility::getSiteTimezone(),
        ];
        $refUserId = User::getReferrerId(UserAuth::getReferal());
        if ($refUserId > 0) {
            $isAffiliate = User::isAffilate($refUserId);
            if (($isAffiliate && FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE')) ||
                (!$isAffiliate &&  FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))
            ) {
                $userData['user_referred_by'] = $refUserId;
            }
        }

        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = Label::getLabel('ERR_SOMETHING_WENT_WRONG');
            return false;
        }
        $user = new User();
        $user->assignValues($userData);
        if (FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION') == AppConstant::NO) {
            $user->setFldValue('user_verified', date('Y-m-d H:i:s'));
        }
        if (empty(FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION'))) {
            $user->setFldValue('user_active', AppConstant::YES);
        }
        if (!$user->save()) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('MSG_USER_COULD_NOT_BE_SET');
            return false;
        }
        $userId = $user->getMainTableRecordId();
        if (!$user->setSettings($userData)) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('MSG_SETTINGS_COULD_NOT_BE_SET');
            return false;
        }
        if (!$user->setPassword($data['user_password'])) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('MSG_PASSWORD_COULD_NOT_BE_SET');
            return false;
        }
        if (!$user->assignGiftCard($data['user_email'])) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('MSG_GIFTCARD_COULD_NOT_BE_SET');
            return false;
        }
        if ($refUserId > 0) {
            if (!$isAffiliate) {
                $record = new RewardPoint($userId);
                if (!$record->registerRewards()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_REWARDS_COULD_NOT_BE_SET');
                    return false;
                }
            } else {
                $record = new User($userId);
                if (!$record->settleAffiliateSignupCommission()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_AFFILIATE_COMMISSION_COULD_NOT_BE_SET');
                    return false;
                }
            }
        }
        if (!$db->commitTransaction()) {
            $this->error = Label::getLabel('ERR_SOMETHING_WENT_WRONG');
            return false;
        }
        UserAuth::resetReferal();
        return true;
    }

    /**
     * Logout
     */
    public static function logout()
    {
        UserAuth::clearAuthTokenUser();
        unset($_SESSION[UserAuth::SESSION_ELEMENT]);
        unset($_SESSION[UserAuth::COOKIES_ELEMENT]);
        unset($_SESSION[UserAuth::ADMIN_SESSION_ELEMENT]);
        unset($_SESSION['SITE_USER_TYPE']);
    }

    /**
     * Setup Reset Password Request
     * 
     * @param string $email
     * @param string $captch
     * @return bool
     */
    public function setupResetPasswordRequest(string $email, string $captch = ''): bool
    {
        if (empty($email)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        if (!CommonHelper::verifyCaptcha($captch)) {
            $this->error = Label::getLabel('MSG_INVALID_CAPTCHA');
            return false;
        }
        $user = User::getByEmail($email);
        if (empty($user)) {
            $this->error = Label::getLabel('LBL_PLEASE_ENTER_REGISTERED_EMAIL');
            return false;
        }
        if (empty($user['user_verified'])) {
            $link = '<a href="javascript:void(0)" onclick="resendSignupVerifyEmail(' . "'" .
                $user['user_email'] . "'" . ')">' . Label::getLabel('LBL_CLICK_HERE') . '</a>';
            $this->error = str_replace("{clickhere}", $link, Label::getLabel('MSG_VERIFICATION_PENDING_{clickhere}_TO_VERIFY'));
            return false;
        }
        if ($this->checkPasswordResetRequest($user['user_id']) > 0) {
            $this->error = Label::getLabel('ERR_RESET_PASSWORD_REQUEST_ALREADY_PLACED');
            return false;
        }
        $token = uniqid('Y', true);
        $db = FatApp::getDb();
        $db->startTransaction();
        $record = new TableRecord(static::DB_TBL_USER_PRR);
        $record->assignValues([
            'uprr_token' => $token, 'uprr_user_id' => $user['user_id'],
            'uprr_expiry' => date('Y-m-d H:i:s', strtotime("+1 DAY"))
        ]);
        if (!$record->addNew(['HIGH_PRIORITY'])) {
            $this->error = $record->getError();
            $db->rollbackTransaction();
            return false;
        }
        $stmt = ['smt' => 'usrtok_user_id = ?', 'vals' => [$user['user_id']]];
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL_USER_AUTH, $stmt)) {
            $this->error = FatApp::getDb()->getError();
            $db->rollbackTransaction();
            return false;
        }
        $mail = new FatMailer(MyUtility::getSiteLangId(), 'forgot_password');
        $mail->setVariables([
            '{user_full_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
            '{reset_url}' => MyUtility::makeFullUrl('GuestUser', 'resetPassword', [$user['user_id'], $token])
        ]);
        if (!$mail->sendMail([$user['user_email']])) {
            $this->error = Label::getLabel('MSG_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN');
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Check Password Reset Request
     * 
     * @param int $userId
     * @return int
     */
    private function checkPasswordResetRequest(int $userId): int
    {
        $srch = new SearchBase(static::DB_TBL_USER_PRR);
        $srch->addCondition('uprr_user_id', '=', $userId);
        $srch->addCondition('uprr_expiry', '>', date('Y-m-d H:i:s'));
        $srch->addFld('uprr_user_id');
        $srch->getResultSet();
        return $srch->recordCount();
    }

    /**
     * Validate Reset Password Link
     * 
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public function validateResetPasswordLink(int $userId, string $token): bool
    {
        if (empty($userId) || empty($token)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $srch = new SearchBase(static::DB_TBL_USER_PRR);
        $srch->addCondition('uprr_token', '=', $token);
        $srch->addCondition('uprr_user_id', '=', $userId);
        $srch->addCondition('uprr_expiry', '>', date('Y-m-d H:i:s'));
        $srch->getResultSet();
        if ($srch->recordCount() < 1) {
            $this->error = Label::getLabel('ERR_INVALID_LINK');
            return false;
        }
        return true;
    }

    /**
     * Setup Reset Password
     * 
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function setupResetPassword(int $userId, string $password): bool
    {
        if (empty($userId) || empty($password)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $userObj = new User($userId);
        $hashPassword = static::encryptPassword($password);
        $userObj->setFldValue('user_password', $hashPassword);
        if (!$userObj->save()) {
            $this->error = $userObj->getError();
            return false;
        }
        $stmt = ['smt' => 'uprr_user_id = ?', 'vals' => [$userId]];
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL_USER_PRR, $stmt)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        $user = User::getAttributesById($userId, ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id']);
        $mail = new FatMailer($user['user_lang_id'], 'password_changed_successfully');
        $mail->setVariables([
            '{user_full_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
            '{login_link}' => MyUtility::makeFullUrl('GuestUser', 'loginForm')
        ]);
        $mail->sendMail([$user['user_email']]);
        return true;
    }

    /**
     * Update Google Login
     * 
     * @param string $id
     * @param string $email
     * @param string $name
     * @return boolean
     */
    public function updateGoogleLogin(string $id, string $email, string $name)
    {
        if (empty($id) || empty($email) || empty($name)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $user = User::getByEmail($email);
        if (!empty($user)) {
            if ($user['user_active'] != AppConstant::ACTIVE) {
                $this->error = Label::getLabel('ERR_YOUR_ACCOUNT_IS_INACTIVE');
                return false;
            }
            if (!empty($user['user_deleted'])) {
                $this->error = Label::getLabel('ERR_YOUR_ACCOUNT_ID_DELETED');
                return false;
            }
            $userObj = new User($user['user_id']);
            if (empty($user['user_verified'])) {
                $userObj->setFldValue('user_verified', date('Y-m-d H:i:s'));
                if (!$userObj->save()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('ERR_ACCOUNT_VERIFICATION_IS_PENDING');
                    return false;
                }
            }
            if (!$userObj->setSettings(['user_google_id' => $id])) {
                $db->rollbackTransaction();
                $this->error = Label::getLabel('LBL_ERROR_TO_UPDATE_USER_DATA');
                return false;
            }
            $db->commitTransaction();
            return $user;
        }
        $names = explode(' ', $name);
        $userData = [
            'user_email' => $email,
            'user_username' => $id,
            'user_google_id' => $id,
            'user_first_name' => $names[0],
            'user_last_name' => $names[1] ?? '',
            'user_dashboard' => User::LEARNER,
            'user_registered_as' => User::LEARNER,
            'user_lang_id' => MyUtility::getSiteLangId(),
            'user_currency_id' => MyUtility::getSiteCurrId(),
            'user_timezone' => MyUtility::getSiteTimezone()
        ];

        $refUserId = User::getReferrerId(UserAuth::getReferal());
        if ($refUserId > 0) {
            $isAffiliate = User::isAffilate($refUserId);
            if (($isAffiliate && FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE')) ||
                (!$isAffiliate &&  FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))
            ) {
                $userData['user_referred_by'] = $refUserId;
            }
        }

        $userObj = new User();
        $userObj->assignValues($userData);
        $userObj->setFldValue('user_active', AppConstant::YES);
        $userObj->setFldValue('user_verified', date('Y-m-d H:i:s'));
        $userObj->setFldValue('user_created', date('Y-m-d H:i:s'));
        if (!$userObj->save()) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel("MSG_USER_COULD_NOT_BE_SET");
            return false;
        }
        if (!$userObj->setSettings($userData)) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel("MSG_SETTING_COULD_NOT_BE_SET");
            return false;
        }
        $userId = $userObj->getMainTableRecordId();
        if ($refUserId > 0) {
            if (!$isAffiliate) {
                $record = new RewardPoint($userId);
                if (!$record->registerRewards()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_REWARDS_COULD_NOT_BE_SET');
                    return false;
                }
            } else {
                $record = new User($userId);
                if (!$record->settleAffiliateSignupCommission()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_AFFILIATE_COMMISSION_COULD_NOT_BE_SET');
                    return false;
                }
            }
        }
        $db->commitTransaction();
        UserAuth::resetReferal();
        if (!empty(FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION'))) {
            $this->sendSignupWelcomeEmail($userData);
        }
        if (!empty(FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION'))) {
            $this->sendSignupAdminNotifion($userData);
        }
        $userData['user_password'] = NULL;
        return $userData;
    }

    /**
     * Facebook Login
     * 
     * @param string $id
     * @param string $name
     * @param string $email
     * @return bool|array
     */
    public function facebookLogin(string $id, string $name, string $email = null)
    {
        if (empty($id)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->addMultipleFields([
            'user.user_id', 'user_email', 'user_username', 'user_password', 'user_is_teacher', 'user_first_name', 'user_last_name',
            'user_dashboard', 'user_facebook_id', 'user_verified', 'user_active', 'user_deleted'
        ]);
        if (!empty($email)) {
            $srch->addCondition('user_email', '=', $email);
        } else {
            $srch->addCondition('user_facebook_id', '=', $id);
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $user = $db->fetch($srch->getResultSet());
        $db->startTransaction();
        if (!empty($user)) {
            if (!empty($user['user_deleted'])) {
                $this->error = Label::getLabel('ERR_YOUR_ACCOUNT_ID_DELETED');
                return false;
            }
            if ($user['user_active'] != AppConstant::ACTIVE) {
                $this->error = Label::getLabel('ERR_YOUR_ACCOUNT_IS_INACTIVE');
                return false;
            }
            $userObj = new User($user['user_id']);
            if (empty($user['user_verified']) && !empty($email)) {
                $userObj->setFldValue('user_verified', date('Y-m-d H:i:s'));
                if (!$userObj->save()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('ERR_ACCOUNT_VERIFICATION_IS_PENDING');
                    return false;
                }
            }
            if (!$userObj->setSettings(['user_facebook_id' => $id])) {
                $db->rollbackTransaction();
                $this->error = Label::getLabel('LBL_ERROR_TO_UPDATE_USER_DATA');
                return false;
            }
            $db->commitTransaction();
            return $user;
        }
        $names = explode(" ", $name);
        $userData = [
            'user_email' => $email,
            'user_username' => $id,
            'user_facebook_id' => $id,
            'user_first_name' => $names[0],
            'user_last_name' => $names[1] ?? '',
            'user_dashboard' => User::LEARNER,
            'user_registered_as' => User::LEARNER,
            'user_lang_id' => MyUtility::getSiteLangId(),
            'user_currency_id' => MyUtility::getSiteCurrId(),
            'user_timezone' => MyUtility::getSiteTimezone(),
        ];
        $refUserId = User::getReferrerId(UserAuth::getReferal());
        if ($refUserId > 0) {
            $isAffiliate = User::isAffilate($refUserId);
            if (($isAffiliate && FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE')) ||
                (!$isAffiliate &&  FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))
            ) {
                $userData['user_referred_by'] = $refUserId;
            }
        }
        $userObj = new User();
        $userObj->assignValues($userData);
        $userObj->setFldValue('user_active', AppConstant::YES);
        $userObj->setFldValue('user_created', date('Y-m-d H:i:s'));
        $userObj->setFldValue('user_verified', date('Y-m-d H:i:s'));
        if (!$userObj->save()) {
            $db->rollbackTransaction();
            $this->error = $userObj->getError();
            Label::getLabel("MSG_USER_COULD_NOT_BE_SET");
            return false;
        }
        if (!$userObj->setSettings($userData)) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel("MSG_USER_COULD_NOT_BE_SET");
            return false;
        }
        $userId = $userObj->getMainTableRecordId();
        if ($refUserId > 0) {
            if (!$isAffiliate) {
                $record = new RewardPoint($userId);
                if (!$record->registerRewards()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_REWARDS_COULD_NOT_BE_SET');
                    return false;
                }
            } else {
                $record = new User($userId);
                if (!$record->settleAffiliateSignupCommission()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_AFFILIATE_COMMISSION_COULD_NOT_BE_SET');
                    return false;
                }
            }
        }
        $db->commitTransaction();
        UserAuth::resetReferal();
        if (!empty(FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION'))) {
            $this->sendSignupWelcomeEmail($userData);
        }
        if (!empty(FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION'))) {
            $this->sendSignupAdminNotifion($userData);
        }
        $userData['user_id'] = $userObj->getMainTableRecordId();
        $userData['user_is_teacher'] = AppConstant::NO;
        $userData['user_verified'] = date('Y-m-d H:i:s');
        $userData['user_password'] = NULL;
        return $userData;
    }

    /**
     * Apple Login
     * 
     * @param string $id
     * @param string $name
     * @param string $email
     * @return bool|array
     */
    public function updateAppleLogin(string $id, string $name, string $email = null)
    {
        if (empty($id)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->addMultipleFields([
            'user.user_id', 'user_email', 'user_username', 'user_password', 'user_is_teacher', 'user_first_name', 'user_last_name',
            'user_dashboard', 'user_apple_id', 'user_verified', 'user_active', 'user_deleted'
        ]);
        if (!is_null($email)) {
            $srch->addCondition('user_email', '=', $email);
        } else {
            $srch->addCondition('user_apple_id', '=', $id);
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $user = $db->fetch($srch->getResultSet());
        $db->startTransaction();
        if (!empty($user)) {
            if (!empty($user['user_deleted'])) {
                $this->error = Label::getLabel('ERR_YOUR_ACCOUNT_ID_DELETED');
                return false;
            }
            if ($user['user_active'] != AppConstant::ACTIVE) {
                $this->error = Label::getLabel('ERR_YOUR_ACCOUNT_IS_INACTIVE');
                return false;
            }
            $userObj = new User($user['user_id']);
            if (empty($user['user_verified']) && !is_null($email)) {
                $userObj->setFldValue('user_verified', date('Y-m-d H:i:s'));
                if (!$userObj->save()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('ERR_ACCOUNT_VERIFICATION_IS_PENDING');
                    return false;
                }
            }
            if (!$userObj->setSettings(['user_apple_id' => $id])) {
                $db->rollbackTransaction();
                $this->error = Label::getLabel('LBL_ERROR_TO_UPDATE_USER_DATA');
                return false;
            }
            $db->commitTransaction();
            return $user;
        }
        $names = explode(" ", $name);
        $userData = [
            'user_email' => $email,
            'user_apple_id' => $id,
            'user_first_name' => $names[0],
            'user_last_name' => $names[1] ?? '',
            'user_dashboard' => User::LEARNER,
            'user_registered_as' => User::LEARNER,
            'user_lang_id' => MyUtility::getSiteLangId(),
            'user_currency_id' => MyUtility::getSiteCurrId(),
            'user_timezone' => MyUtility::getSiteTimezone(),
        ];
        $refUserId = User::getReferrerId(UserAuth::getReferal());

        if ($refUserId > 0) {
            $isAffiliate = User::isAffilate($refUserId);
            if (($isAffiliate && FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE')) ||
                (!$isAffiliate &&  FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))
            ) {
                $userData['user_referred_by'] = $refUserId;
            }
        }

        $userObj = new User();
        $userObj->assignValues($userData);
        $userObj->setFldValue('user_active', AppConstant::YES);
        $userObj->setFldValue('user_created', date('Y-m-d H:i:s'));
        $userObj->setFldValue('user_verified', date('Y-m-d H:i:s'));
        if (!$userObj->save()) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel("MSG_USER_COULD_NOT_BE_SET");
            return false;
        }
        if (!$userObj->setSettings($userData)) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel("MSG_USER_COULD_NOT_BE_SET");
            return false;
        }
        $userId = $userObj->getMainTableRecordId();
        if ($refUserId > 0) {
            if (!$isAffiliate) {
                $record = new RewardPoint($userId);
                if (!$record->registerRewards()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_REWARDS_COULD_NOT_BE_SET');
                    return false;
                }
            } else {
                $record = new User($userId);
                if (!$record->settleAffiliateSignupCommission()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_AFFILIATE_COMMISSION_COULD_NOT_BE_SET');
                    return false;
                }
            }
        }
        $db->commitTransaction();
        UserAuth::resetReferal();
        if (!empty(FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION'))) {
            $this->sendSignupWelcomeEmail($userData);
        }
        if (!empty(FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION'))) {
            $this->sendSignupAdminNotifion($userData);
        }
        $userData['user_id'] = $userObj->getMainTableRecordId();
        $userData['user_is_teacher'] = AppConstant::NO;
        $userData['user_verified'] = date('Y-m-d H:i:s');
        $userData['user_password'] = null;
        return $userData;
    }

    /**
     * Send Signup Emails
     * 
     * @param array $user
     * @return array
     */
    public function sendSignupEmails(array $user): array
    {
        $msg = [];
        $response = [];
        if (!empty(FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION'))) {
            $this->sendSignupWelcomeEmail($user);
        }
        if (!empty(FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION'))) {
            $this->sendSignupAdminNotifion($user);
        }
        if (!empty(FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION'))) {
            $this->sendVerifyEmail($user);
            $msg[] = Label::getLabel('MSG_VERIFICATION_EMAIL_SENT');
        }
        if (!empty(FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION'))) {
            $msg[] = Label::getLabel('MSG_ADMIN_WILL_APPROVE_ACCOUNT');
        }
        if (!empty($msg)) {
            $msg[] = Label::getLabel('LBL_REGISTERATION_SUCCESSFULL');
            $response = [
                'msg' => implode(", ", $msg),
                'url' => MyUtility::makeFullUrl('Home', '', [],)
            ];
        }
        return $response;
    }

    /**
     * Send Signup Welcome Email
     * 
     * @param array $user
     * @return bool
     */
    public function sendSignupWelcomeEmail(array $user): bool
    {
        $mail = new FatMailer($user['user_lang_id'], 'welcome_registration');
        $mail->setVariables([
            '{user_full_name}' => $user['user_first_name'] . ' ' . ($user['user_last_name'] ?? ''),
            '{contact_us_email}' => FatApp::getConfig('CONF_CONTACT_EMAIL'),
        ]);
        if (!empty($user['user_email']) && !$mail->sendMail([$user['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Send Signup Admin Notification
     * 
     * @param array $user
     * @return bool
     */
    public function sendSignupAdminNotifion(array $user): bool
    {
        $mail = new FatMailer(FatApp::getConfig("CONF_DEFAULT_LANG"), 'new_registration_admin');
        $mail->setVariables([
            '{user_full_name}' => $user['user_first_name'] . ' ' . ($user['user_last_name'] ?? ''),
            '{user_email}' => $user['user_email'],
        ]);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Send Verify Email
     * 
     * @param array $user
     * @return bool
     */
    public function sendVerifyEmail(array $user): bool
    {
        if (empty($user)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        if (!empty($user['user_verified'])) {
            $this->error = Label::getLabel('LBL_ACCOUNT_ALREADY_VERIFIED');
            return false;
        }
        $token = $user['user_id'] . '-' . FatUtility::getRandomString(15);
        $verification = new Verification($user['user_id']);
        if (!$verification->addToken($token, $user['user_id'], $user['user_email'], Verification::TYPE_EMAIL_VERIFICATION)) {
            $this->error = $verification->getError();
            return false;
        }
        $mail = new FatMailer($user['user_lang_id'], 'user_email_verification');
        $mail->setVariables([
            '{user_full_name}' => $user['user_first_name'] . ' ' . ($user['user_last_name'] ?? ''),
            '{verification_url}' => MyUtility::makeFullUrl('GuestUser', 'verifyEmail', ['verify' => $token])
        ]);
        if (!$mail->sendMail([$user['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Encrypt Password
     * 
     * @param string $pass
     * @return string
     */
    public static function encryptPassword(string $pass): string
    {
        return md5(PASSWORD_SALT . $pass . PASSWORD_SALT);
    }

    /**
     * Get Signin Form
     * 
     * @return Form
     */
    public static function getSigninForm(): Form
    {
        $frm = new Form('signinFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addEmailField(Label::getLabel('LBL_Email'), 'username', (MyUtility::isDemoUrl() ? 'lydia.deckow@dummyid.com' : ''), ['placeholder' => Label::getLabel('LBL_EMAIL_ADDRESS')]);
        $fld->requirements()->setRequired();
        $pwd = $frm->addPasswordField(Label::getLabel('LBL_Password'), 'password', (MyUtility::isDemoUrl() ? 'lydia@123' : ''), ['placeholder' => Label::getLabel('LBL_PASSWORD')]);
        $pwd->requirements()->setRequired();
        $frm->addCheckbox(Label::getLabel('LBL_Remember_Me'), 'remember_me', 1, [], false, 0);
        $frm->addHtml('', 'forgot', '');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_LOGIN'));
        return $frm;
    }

    public function affiliateSignup(array $data): bool
    {
        if (
            empty($data['user_email']) || empty($data['user_password']) ||
            empty($data['user_first_name']) || !isset($data['user_last_name'])
        ) {
            $this->error = Label::getLabel('MSG_USER_COULD_NOT_BE_SET');
            return false;
        }
        $userData = [
            'user_dashboard' => User::AFFILIATE,
            'user_registered_as' => User::AFFILIATE,
            'user_email' => $data['user_email'],
            'user_first_name' => $data['user_first_name'],
            'user_last_name' => $data['user_last_name'],
            'user_currency_id' => MyUtility::getSiteCurrId(),
            'user_lang_id' => MyUtility::getSiteLangId(),
            'user_timezone' => MyUtility::getSiteTimezone(),
            'user_is_affiliate' => AppConstant::YES,
            'user_referral_code' => uniqid(),
        ];
        $refUserId = User::getReferrerId(UserAuth::getReferal());
        if ($refUserId > 0) {
            $isAffiliate = User::isAffilate($refUserId);
            if (($isAffiliate && FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE')) ||
                (!$isAffiliate &&  FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))
            ) {
                $userData['user_referred_by'] = $refUserId;
            }
        }

        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = Label::getLabel('ERR_SOMETHING_WENT_WRONG');
            return false;
        }
        $user = new User();
        $user->assignValues($userData);
        if (FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION') == AppConstant::NO) {
            $user->setFldValue('user_verified', date('Y-m-d H:i:s'));
        }
        if (empty(FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION'))) {
            $user->setFldValue('user_active', AppConstant::YES);
        }
        if (!$user->save()) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('MSG_USER_COULD_NOT_BE_SET');
            return false;
        }
        $userId = $user->getMainTableRecordId();
        if (!$user->setSettings($userData)) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('MSG_SETTINGS_COULD_NOT_BE_SET');
            return false;
        }
        if (!$user->setPassword($data['user_password'])) {
            $db->rollbackTransaction();
            $this->error = Label::getLabel('MSG_PASSWORD_COULD_NOT_BE_SET');
            return false;
        }
        if ($refUserId > 0) {
            if (!$isAffiliate) {
                $record = new RewardPoint($userId);
                if (!$record->registerRewards()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_REWARDS_COULD_NOT_BE_SET');
                    return false;
                }
            } else {
                $record = new User($userId);
                if (!$record->settleAffiliateSignupCommission()) {
                    $db->rollbackTransaction();
                    $this->error = Label::getLabel('MSG_AFFILIATE_COMMISSION_COULD_NOT_BE_SET');
                    return false;
                }
            }
        }
        if (!$db->commitTransaction()) {
            $this->error = Label::getLabel('ERR_SOMETHING_WENT_WRONG');
            return false;
        }
        UserAuth::resetReferal();
        return true;
    }
}
