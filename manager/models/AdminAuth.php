<?php

/**
 * Admin Class is used to handle Admin Authentication
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminAuth extends FatModel
{

    const SESSION_ELEMENT = 'W3MENTORS_ADMIN';
    const REMEMBER_ELEMENT = 'W3MENTORS_REMEMBER_ME';

    public static $_instance;

    /**
     * Initialize AdminAuth
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check Admin Logged
     * 
     * @return bool
     */
    public static function isAdminLogged(): bool
    {
        if (($_SESSION[static::SESSION_ELEMENT]['admin_id'] ?? 0) > 0) {
            return true;
        }
        return static::doCookieAdminLogin();
    }

    /**
     * Do Cookie Admin Login
     * 
     * @return bool
     */
    private static function doCookieAdminLogin(): bool
    {
        $token = $_COOKIE[static::REMEMBER_ELEMENT] ?? '';
        if (empty($token)) {
            return false;
        }
        $authRow = static::checkLoginTokenInDB($token);
        if (empty($authRow)) {
            static::clearLoggedAdminLoginCookie();
            return false;
        }
        if (!static::loginById($authRow['admauth_admin_id'])) {
            static::clearLoggedAdminLoginCookie();
            return false;
        }
        return true;
    }

    /**
     * Login By Id
     * 
     * @param int $adminId
     * @return bool
     */
    private static function loginById(int $adminId): bool
    {
        if ($row = Admin::getAttributesById($adminId, ['admin_id', 'admin_name'])) {
            $row['admin_ip'] = MyUtility::getUserIp();
            $adminAuth = new self();
            $adminAuth->setAdminSession($row);
            return true;
        }
        return false;
    }

    /**
     * Login 
     * 
     * @param string $username
     * @param string $password
     * @param string $ip
     * @return bool
     */
    public function login(string $username, string $password, string $ip): bool
    {
        $userAuth = new UserAuth();
        if ($userAuth->isBruteForcing($username, $ip)) {
            $this->error = Label::getLabel('ERR_YOUR_ATTEMPT_LIMIT_EXCEEDED');
            return false;
        }
        $password = UserAuth::encryptPassword($password);
        $srch = new SearchBase(Admin::DB_TBL);
        $srch->addCondition('admin_username', '=', $username);
        $srch->addCondition('admin_password', '=', $password);
        if (!$row = FatApp::getDb()->fetch($srch->getResultSet())) {
            $userAuth->logFailedLoginAttempt($username, $ip);
            $this->error = Label::getLabel('MSG_INVALID_USERNAME_OR_PASSWORD');
            return false;
        }
        if (strtolower($row['admin_username']) != strtolower($username) || $row['admin_password'] != $password) {
            $userAuth->logFailedLoginAttempt($username, $ip);
            $this->error = Label::getLabel('MSG_INVALID_USERNAME_OR_PASSWORD');
            return false;
        }
        if ($row['admin_active'] !== AppConstant::ACTIVE) {
            $userAuth->logFailedLoginAttempt($username, $ip);
            $this->error = Label::getLabel('MSG_YOUR_ACCOUNT_IS_INACTIVE');
            return false;
        }
        $stmt = ['smt' => 'attempt_username = ? and attempt_ip = ?', 'vals' => [$username, $ip]];
        if (!FatApp::getDb()->deleteRecords('tbl_failed_login_attempts', $stmt)) {
            return false;
        }
        $row['admin_ip'] = $ip;
        $this->setAdminSession($row);
        $user = new Admin($row['admin_id']);
        $user->setFldValue('admin_password_update', 0);
        $user->save();
        MyUtility::setAdminTimezone($row['admin_timezone'], true);
        return true;
    }

    /**
     * Set Admin Session
     * 
     * @param array $row
     * @return void
     */
    public function setAdminSession(array $row): void
    {
        $_SESSION[static::SESSION_ELEMENT] = [
            'admin_id' => $row['admin_id'],
            'admin_name' => $row['admin_name'],
            'admin_ip' => $row['admin_ip']
        ];
    }
    
    /**
     * Get Logged Admin Id
     * 
     * @return int
     */
    public static function getLoggedAdminId()
    {
        return $_SESSION[static::SESSION_ELEMENT]['admin_id'] ?? 0;
    }

    /**
     * Check Admin Email
     * 
     * @param string $email
     * @return boolean
     */
    public function checkAdminEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error = Label::getLabel('MSG_Invalid_email_address!');
            return false;
        }
        $db = FatApp::getDb();
        $srch = new SearchBase('tbl_admin');
        $srch->addCondition('admin_email', '=', $email);
        $srch->addMultipleFields(['admin_id', 'admin_name', 'admin_email']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (!$row = $db->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('MSG_Invalid_email_address!');
            return false;
        }
        if ($row['admin_email'] !== $email) {
            $this->error = Label::getLabel('MSG_Invalid_email_address!');
            return false;
        }
        return $row;
    }

    /**
     * Check Admin Password Reset Request
     * 
     * @param int $admin_id
     * @return bool
     */
    public function checkAdminPwdResetRequest(int $admin_id): bool
    {
        $db = FatApp::getDb();
        $srch = new SearchBase('tbl_admin_password_reset_requests');
        $srch->addCondition('aprr_admin_id', '=', $admin_id);
        $srch->addCondition('aprr_expiry', '>', date('Y-m-d H:i:s'));
        $srch->addFld('aprr_admin_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (!$row = $db->fetch($srch->getResultSet())) {
            return false;
        }
        $this->error = Label::getLabel('MSG_Your_request_to_reset_password_has_already_been_placed_within_last_24_hours._Please_check_your_emails_or_retry_after_24_hours_of_your_previous_request');
        return true;
    }

    /**
     * Delete Old Password Reset Requests
     * 
     * @return boolean
     */
    public function deleteOldPasswordResetRequest(): bool
    {
        $db = FatApp::getDb();
        if (!$db->deleteRecords('tbl_admin_password_reset_requests', ['smt' => 'aprr_expiry < ?', 'vals' => [date('Y-m-d H:i:s')]])) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Add Password Reset Request
     * 
     * @param array $data
     * @return bool
     */
    public function addPasswordResetRequest(array $data = []): bool
    {
        if (!isset($data['admin_id']) || $data['admin_id'] < 1 || strlen($data['token']) < 20) {
            return false;
        }
        $db = FatApp::getDb();
        if ($db->insertFromArray('tbl_admin_password_reset_requests', [
            'aprr_admin_id' => intval($data['admin_id']),
            'aprr_token' => $data['token'],
            'aprr_expiry' => date('Y-m-d H:i:s', strtotime("+1 DAY"))
        ])) {
            $db->deleteRecords('tbl_admin_auth_token', [
                'smt' => 'admauth_admin_id = ?',
                'vals' => [$data['admin_id']]
            ]);
            return true;
        }
        return false;
    }

    /**
     * Check Reset Link
     * 
     * @param int $aId
     * @param string $token
     * @return bool
     */
    public function checkResetLink(int $aId, string $token): bool
    {
        if ($aId < 1 || empty($token)) {
            $this->error = Label::getLabel('MSG_LINK_IS_INVALID_OR_EXPIRED!');
            return false;
        }
        $srch = new SearchBase('tbl_admin_password_reset_requests');
        $srch->addCondition('aprr_admin_id', '=', $aId);
        $srch->addCondition('aprr_token', '=', $token);
        $srch->addCondition('aprr_expiry', '>', date('Y-m-d H:i:s'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (!$row = FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('MSG_Link_is_invalid_or_expired!');
            return false;
        }
        if ($row['aprr_admin_id'] == $aId && $row['aprr_token'] === $token) {
            return true;
        }
        $this->error = Label::getLabel('MSG_Link_is_invalid_or_expired!');
        return false;
    }

    /**
     * Get Admin By Id
     * 
     * @param int $aId
     * @return boolean
     */
    public function getAdminById(int $aId)
    {
        $srch = new SearchBase(Admin::DB_TBL);
        $srch->addCondition('admin_id', '=', $aId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (!$row = FatApp::getDb()->fetch($srch->getResultSet())) {
            return false;
        }
        return $row;
    }

    /**
     * Change Admin Password
     * 
     * @param int $aId
     * @param string $pwd
     * @return bool
     */
    public function changeAdminPwd(int $aId, string $pwd): bool
    {
        if ($aId < 1) {
            $this->error = Label::getLabel('MSG_Invalid_Request');
            return false;
        }
        $db = FatApp::getDb();
        $data = ['admin_password' => $pwd];
        if ($db->updateFromArray('tbl_admin', $data, ['smt' => 'admin_id=?', 'vals' => [$aId]])) {
            $db->deleteRecords('tbl_admin_password_reset_requests', ['smt' => 'aprr_admin_id=?', 'vals' => [$aId]]);
            return true;
        }
        return false;
    }

    /**
     * Save Remember Login Token
     * 
     * @param array $values
     * @return bool
     */
    public function saveRememberLoginToken(array $values): bool
    {
        $db = FatApp::getDb();
        if ($db->insertFromArray('tbl_admin_auth_token', $values)) {
            return true;
        }
        $this->error = $db->getError();
        return false;
    }

    /**
     * Set Auth Token
     * 
     * @param int $adminId
     * @return bool
     */
    public static function setAuthToken(int $adminId): bool
    {

        $token = substr(md5(microtime()), 0, 32);
        $rememberMeDays = FatApp::getConfig('CONF_ADMIN_REMEMBER_ME_DAYS', FatUtility::VAR_INT);
        $values = [
            'admauth_admin_id' => $adminId,
            'admauth_token' => $token,
            'admauth_expiry' => date('Y-m-d H:i:s', strtotime('+' . $rememberMeDays . ' day')),
            'admauth_browser' => MyUtility::getUserAgent(),
            'admauth_last_access' => date('Y-m-d H:i:s'),
            'admauth_last_ip' => MyUtility::getUserIp()
        ];
        $auth = new AdminAuth();
        if ($auth->saveRememberLoginToken($values)) {
            MyUtility::setCookie(AdminAuth::REMEMBER_ELEMENT, $token, $rememberMeDays * 86400, CONF_WEBROOT_BACKEND);
            return true;
        }
        return false;
    }

    /**
     * Check Login Token In DB
     * 
     * @param string $token
     * @return type
     */
    public static function checkLoginTokenInDB(string $token)
    {
        $srch = new SearchBase('tbl_admin_auth_token');
        $srch->addCondition('admauth_token', '=', $token);
        $srch->addCondition('admauth_browser', '=', MyUtility::getUserAgent());
        if (FatApp::getConfig('CONF_ADMIN_REMEMBER_ME_IP_ENABLE', FatUtility::VAR_INT)) {
            $srch->addCondition('admauth_last_ip', '=', MyUtility::getUserIp());
        }
        $srch->addCondition('admauth_expiry', '>=', date('Y-m-d H:i:s'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    /**
     * Clear Logged Admin Login Cookie
     * 
     * @return bool
     */
    public static function clearLoggedAdminLoginCookie(): bool
    {
        if (!isset($_COOKIE[static::REMEMBER_ELEMENT])) {
            return false;
        }
        $db = FatApp::getDb();
        if (strlen($_COOKIE[static::REMEMBER_ELEMENT])) {
            $db->deleteRecords('tbl_admin_auth_token', ['smt' => 'admauth_token = ?', 'vals' => [$_COOKIE[static::REMEMBER_ELEMENT]]]);
        }
        MyUtility::setCookie(static::REMEMBER_ELEMENT, '', time() - 3600, CONF_WEBROOT_BACKEND);
        return true;
    }
}
