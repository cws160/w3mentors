<?php

/**
 * User Controller is used for Users handling
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UsersController extends AdminBaseController
{

    /**
     * Initialize Users
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewUsers();
    }

    /**
     * Render User Search Form
     */
    public function index()
    {
        $frm = $this->getUserSearchForm();
        $frm->fill(FatApp::getQueryStringData());
        $this->set('form', $frm);
        $this->_template->addJs(['js/jquery.form.js']);
        $this->_template->render();
    }

    /**
     * View User Detail
     * 
     * @param int $userId
     */
    public function view($userId = 0)
    {
        $userId = FatUtility::int($userId);
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->joinTable(User::DB_TBL_LANG, 'LEFT JOIN', 'user.user_id = userlang.userlang_user_id and userlang.userlang_lang_id = ' . $this->siteLangId, 'userlang');
        $srch->joinTable(Country::DB_TBL, 'LEFT JOIN', 'user.user_country_id = country.country_id', 'country');
        $srch->joinTable(Country::DB_TBL_LANG, 'LEFT JOIN', 'country.country_id = countrylang_country_id and countrylang_lang_id = ' . $this->siteLangId, 'countrylang');
        $srch->addMultipleFields([
            'CONCAT(user.user_first_name, " ", user.user_last_name) AS user_full_name',
            'user.user_created',
            'user.user_email',
            'user.user_username',
            'user.user_timezone',
            'uset.user_phone_code',
            'uset.user_phone_number',
            'IFNULL(countrylang.country_name, country.country_code) as country_name',
            'IFNULL(userlang.user_biography, "") as user_biography'
        ]);
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->addCondition('user.user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        $data = current($this->fetchAndFormat($rows));
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data['user_phone_code'] = FatUtility::int($data['user_phone_code']);
        $data['user_phone_code'] = Country::getAttributesById($data['user_phone_code'], 'country_dial_code');
        $this->set('data', $data);
        $this->_template->render(false, false);
    }

    /**
     * Render User Form
     * 
     * @param int $userId
     */
    public function form($userId = 0)
    {
        $this->objPrivilege->canEditUsers();
        $userId = FatUtility::int($userId);
        $data = User::getDetail($userId);
        $isTeacher = FatUtility::int($data['user_is_teacher']);
        $frmUser = $this->getForm($isTeacher);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frmUser->fill($data);
        $this->set('frmUser', $frmUser);
        $this->_template->render(false, false);
    }

    /**
     * Setup User
     */
    public function setup()
    {
        $this->objPrivilege->canEditUsers();
        $post = FatApp::getPostedData();
        $userId = FatUtility::int($post['user_id']);
        $data = User::getDetail($userId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $isTeacher = FatUtility::int($data['user_is_teacher']);
        $frm = $this->getForm($isTeacher);
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!CommonHelper::sanitizeInput([$post['user_first_name'], $post['user_last_name']])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SCRIPT_TAG_NOT_ALLOWED_IN_FIELDS') . ' ' . implode(', ', CommonHelper::getCaptionsForFields($frm, ['user_first_name', 'user_last_name'])));
        }
        unset($post['user_id']);
        unset($post['user_email']);
        unset($post['user_username']);
        $db = FatApp::getDb();
        $db->startTransaction();
        $user = new User($userId);
        $user->assignValues($post);
        if (!$user->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($user->getError());
        }
        if (!$user->setSettings($post)) {
            $db->rollbackTransaction();
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_USER_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Search & List Users
     */
    public function search()
    {
        $frmSearch = $this->getUserSearchForm();
        if (!$post = $frmSearch->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieWithError(current($frmSearch->getValidationErrors()));
        }
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $page = $post['page'];
        $srch = new SearchBased(User::DB_TBL, 'user');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->addOrder('user.user_active', 'DESC');
        $srch->addOrder('user.user_id', 'DESC');
        $keyword = trim(FatApp::getPostedData('keyword', FatUtility::VAR_STRING, ''));
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('mysql_func_CONCAT(user_first_name," ", user_last_name)', 'like', '%' . $keyword . '%', 'AND', true);
            $cnd->attachCondition('user.user_username', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('user.user_email', 'like', '%' . $keyword . '%');
        }
        if ($post['user_id'] > 0) {
            $srch->addCondition('user.user_id', '=', FatUtility::int($post['user_id']));
        }
        if ($post['user_active'] != '') {
            $srch->addCondition('user.user_active', '=', $post['user_active']);
        }
        if ($post['user_verified'] != '') {
            if ($post['user_verified'] == AppConstant::YES) {
                $srch->addDirectCondition('user.user_verified IS NOT NULL');
            } elseif ($post['user_verified'] == AppConstant::NO) {
                $srch->addDirectCondition('user.user_verified IS NULL');
            }
        }
        if ($post['user_featured'] != '') {
            $srch->addCondition('user.user_featured', '=', $post['user_featured']);
        }
        $type = FatApp::getPostedData('type', FatUtility::VAR_STRING, 0);
        switch ($type) {
            case User::TEACHER:
                $srch->addCondition('user.user_is_teacher', '=', AppConstant::YES);
                break;
            case User::LEARNER:
                $srch->addCondition('user.user_is_affiliate', '=', AppConstant::NO);
                break;
            case User::AFFILIATE:
                $srch->addCondition('user.user_is_affiliate', '=', AppConstant::YES);
                break;
        }

        $user_regdate_from = FatApp::getPostedData('user_regdate_from', FatUtility::VAR_DATE, '');
        if (!empty($user_regdate_from)) {
            $user_regdate_from = MyDate::formatToSystemTimezone($user_regdate_from);
            $srch->addCondition("user.user_created", '>=', $user_regdate_from, 'AND', true);
        }
        $user_regdate_to = FatApp::getPostedData('user_regdate_to', FatUtility::VAR_DATE, '');
        if (!empty($user_regdate_to)) {
            $user_regdate_to = MyDate::formatToSystemTimezone($user_regdate_to . " 23:59:59");
            $srch->addCondition('user.user_created', '<=', $user_regdate_to, 'AND', true);
        }
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $srch->addMultipleFields([
            'user.user_id',
            'user_email',
            'user_registered_as',
            'user_verified',
            'user_active',
            'user_created',
            'user_is_teacher',
            'user_phone_code',
            'user_phone_number',
            'CONCAT(user_first_name, " ", user_last_name) AS user_full_name',
            'user_featured',
            'user_is_affiliate'
        ]);
        $srch->addOrder('user.user_active', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        $arrListing = $this->fetchAndFormat($rows);
        $ccodeIds = array_column($arrListing, 'user_phone_code');
        $this->sets([
            'arrListing' => $arrListing,
            'page' => $page,
            'postedData' => $post,
            'pageSize' => $pagesize,
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'ccode' => Country::getDialCodes($ccodeIds),
            'canEdit' => $this->objPrivilege->canEditUsers(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * User Login
     * 
     * @param int $userId
     */
    public function login($userId)
    {
        $this->objPrivilege->canEditUsers();
        $data = User::getDetail($userId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data['user_email'] = FatUtility::convertToType($data['user_email'], FatUtility::VAR_STRING);
        $userAuth = new UserAuth();
        UserAuth::logout();
        TeacherRequest::closeSession();
        UserAuth::setAdminLoggedIn(true);
        if (!$userAuth->login($data['user_email'], $data['user_password'], MyUtility::getUserIp(), false)) {
            FatUtility::dieJsonError($userAuth->getError());
        }
        FatUtility::dieJsonSuccess(['redirectUrl' => MyUtility::makeUrl('Account', '', [], CONF_WEBROOT_DASHBOARD)]);
    }

    /**
     * User Transaction
     * 
     * @param int $userId
     */
    public function transaction($userId = 0)
    {
        $data = User::getDetail($userId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $userId = FatUtility::int($userId);
        $postData = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $srch = new SearchBase(Transaction::DB_TBL, 'utxn');
        $srch->addCondition('utxn.usrtxn_user_id', '=', $userId);
        $srch->addOrder('usrtxn_id', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $postData['userId'] = $userId;
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($arrListing as $key => $row) {
            $row['usrtxn_datetime'] = MyDate::formatDate($row['usrtxn_datetime']);
            $arrListing[$key] = $row;
        }
        $this->sets([
            'arrListing' => $arrListing,
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'page' => $page,
            'pageSize' => $pagesize,
            'userId' => $userId,
            'postedData' => $postData,
            'canEdit' => $this->objPrivilege->canEditUsers(true)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Transaction Form
     * 
     * @param type $userId
     */
    public function transactionForm($userId = 0)
    {
        $this->objPrivilege->canEditUsers();
        $userId = FatUtility::int($userId);
        $data = User::getDetail($userId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getTransactionForm();
        $frm->fill(['user_id' => $userId]);
        $this->sets(['frm' => $frm, 'userId' => $userId]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Transaction
     */
    public function setupTransaction()
    {
        $this->objPrivilege->canEditUsers();
        $frm = $this->getTransactionForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $userId = FatUtility::int($post['user_id']);
        $data = User::getDetail($userId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($post['type'] == Transaction::CREDIT_TYPE) {
            $txn = new Transaction($userId, Transaction::TYPE_SUPPORT_CREDIT);
            if (!$txn->credit($post['amount'], $post['description'])) {
                FatUtility::dieJsonError($txn->getError());
            }
            $msg = Label::getLabel('LBL_ACCOUNT_CREDITED_SUCCESSFULLY');
            $notifi = new Notification($userId, Notification::TYPE_WALLET_CREDIT);
        } elseif ($post['type'] == Transaction::DEBIT_TYPE) {
            $txn = new Transaction($userId, Transaction::TYPE_SUPPORT_DEBIT);
            if (!$txn->debit($post['amount'], $post['description'])) {
                FatUtility::dieJsonError($txn->getError());
            }
            $msg = Label::getLabel('LBL_ACCOUNT_DEBITED_SUCCESSFULLY');
            $notifi = new Notification($userId, Notification::TYPE_WALLET_DEBIT);
        }
        $notifiVar = [
            '{amount}' => MyUtility::formatMoney($post['amount']),
            '{reason}' => strip_tags($post['description'])
        ];
        $notifi->sendNotification($notifiVar);
        $txn->sendEmail();
        FatUtility::dieJsonSuccess(['userId' => $userId, 'msg' => $msg]);
    }

    /**
     * Render Change Password Form
     */
    public function changePasswordForm()
    {
        $this->objPrivilege->canEditUsers();
        $userId = FatApp::getPostedData('userId', FatUtility::VAR_INT, 0);
        $data = User::getDetail($userId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->set('frm', $this->getChangePasswordForm($userId));
        $this->_template->render(false, false);
    }

    /**
     * Update Password
     */
    public function updatePassword()
    {
        $this->objPrivilege->canEditUsers();
        $userId = FatApp::getPostedData('userId', FatUtility::VAR_INT, 0);
        $data = User::getDetail($userId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $pwdFrm = $this->getChangePasswordForm($userId);
        if (!$post = $pwdFrm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($pwdFrm->getValidationErrors()));
        }
        $user = new User($userId);
        $user->setFldValue('user_password', UserAuth::encryptPassword($post['new_password']));
        $user->setFldValue('user_password_updated', AppConstant::YES);
        if (!$user->save()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_PASSWORD_COULD_NOT_BE_SET'));
        }
        $notifi = new Notification($userId, Notification::TYPE_CHANGE_PASSWORD);
        $notifi->sendNotification();
        $vars = [
            '{user_full_name}' => $data['user_first_name'] . ' ' . $data['user_last_name'],
            '{user_email}' => $data['user_email'],
            '{user_password}' => $post['new_password'],
            '{login_link}' => MyUtility::makeFullUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND)
        ];
        $mail = new FatMailer($this->siteLangId, 'user_password_changed_successfully');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$data['user_email']])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PASSWORD_UPDATED_BUT_MAIL_NOT_SENT'));
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_PASSWORD_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Auto Complete JSON
     */
    public function autoCompleteJson()
    {
        $keyword = trim(FatApp::getPostedData('keyword', FatUtility::VAR_STRING, ''));
        $isTeacher = FatApp::getPostedData('user_is_teacher', FatUtility::VAR_STRING, '');
        $isAffiliate = FatApp::getPostedData('user_is_affiliate', FatUtility::VAR_STRING, '');
        if (empty($keyword)) {
            FatUtility::dieJsonSuccess(['data' => []]);
        }
        $srch = new SearchBase(User::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->addMultipleFields(['user_id', 'user_email', 'user_username', 'CONCAT(user_first_name," ", user_last_name) as full_name']);
        if (!empty($keyword)) {
            $cond = $srch->addCondition('user_username', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('user_email', 'LIKE', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('mysql_func_CONCAT(user_first_name," ", user_last_name)', 'LIKE', '%' . $keyword . '%', 'OR', true);
        }
        if ($isTeacher == AppConstant::YES) {
            $srch->addCondition('user_is_teacher', '=', AppConstant::YES);
        }
        if ($isAffiliate == AppConstant::YES) {
            $srch->addCondition('user_is_affiliate', '=', AppConstant::YES);
        }
        $srch->addOrder('full_name', 'ASC');
        $srch->setPageSize(20);
        $users = FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
        FatUtility::dieJsonSuccess(['data' => $users]);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditUsers();
        $userId = FatApp::getPostedData('userId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (empty(User::getAttributesById($userId, 'user_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $user = new User($userId);
        if (!$user->updateStatus($status)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($user->getError());
        }
        $record = new RewardPoint($userId);
        if (!$record->registerRewards()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($record->getError());
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    /**
     *  Addresses
     */
    public function addresses($userId = 0)
    {
        $this->objPrivilege->canViewUsers();
        $userId = FatUtility::int($userId);
        if (empty(User::getAttributesById($userId, 'user_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $addresses =  (new UserAddresses($userId))->getAll($this->siteLangId, [], true);
        $this->set('addresses', $addresses);
        $this->_template->render(false, false);
    }


    /**
     * Get User Form
     * 
     * @param int $userId
     * @return Form
     */
    private function getForm(int $isTeacher = 0): Form
    {
        $frm = new Form('frmUser', ['id' => 'frmUser']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'user_id', 0);
        $frm->addTextBox(Label::getLabel('LBL_USERNAME'), 'user_username', '');
        $fld = $frm->addRequiredField(Label::getLabel('LBL_FIRST_NAME'), 'user_first_name');
        $frm->addTextBox(Label::getLabel('LBL_LAST_NAME'), 'user_last_name');
        $countries = Country::getAll($this->siteLangId);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_PHONE_CODE'), 'user_phone_code', array_column($countries, 'phone_code', 'country_id'), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $fld = $frm->addTextBox(Label::getLabel('LBL_PHONE'), 'user_phone_number');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PHONE_NO_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PHONE_NO_VALIDATION_MSG'));
        $fld = $frm->addSelectBox(Label::getLabel('LBL_COUNTRY'), 'user_country_id', array_column($countries, 'country_name', 'country_id'), FatApp::getConfig('CONF_COUNTRY'), [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        if ($isTeacher) {
            $fld = $frm->addSelectBox(Label::getLabel('LBL_FEATURED'), 'user_featured', [1 => 'Yes', 0 => 'No'], '', [], Label::getLabel('LBL_SELECT'));
            $fld->requirements()->setRequired(true);
        }
        $frm->addTextBox(Label::getLabel('LBL_EMAIL'), 'user_email', '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Change Password Form
     * 
     * @param int $userId
     * @return Form
     */
    private function getChangePasswordForm(int $userId): Form
    {
        $frm = new Form('changePwdFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'userId', $userId);
        $fld = $frm->addPasswordField(Label::getLabel('LBL_NEW_PASSWORD'), 'new_password', '', ['id' => 'new_password']);
        $fld->requirements()->setRequired();
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $conNewPwd = $frm->addPasswordField(Label::getLabel('LBL_CONFIRM_NEW_PASSWORD'), 'conf_new_password', '', ['id' => 'conf_new_password']);
        $conNewPwdReq = $conNewPwd->requirements();
        $conNewPwdReq->setRequired();
        $conNewPwdReq->setCompareWith('new_password', 'eq', Label::getLabel('LBL_NEW_PASSWORD'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'), ['id' => 'btn_submit']);
        return $frm;
    }

    /**
     * Get Transaction Form
     * 
     * @return Form
     */
    private function getTransactionForm(): Form
    {
        $frm = new Form('frmUserTransaction');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'user_id');
        $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'type', Transaction::getCreditDebitTypes(), '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired(true);
        $fld = $frm->addFloatField(Label::getLabel('LBL_USERTXN_AMOUNT'), 'amount');
        $fld->requirements()->setRange(1, 9999999999);
        $frm->addTextArea(Label::getLabel('LBL_DESCRIPTION'), 'description')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get User Search Form
     * 
     * @return Form
     */
    private function getUserSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_NAME_OR_EMAIL'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_USER_TYPE'), 'type', User::getUserTypes(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_EMAIL_VERIFIED'), 'user_verified', AppConstant::getYesNoArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_FEATURED'), 'user_featured', AppConstant::getYesNoArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'user_active', AppConstant::getActiveArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_REG_DATE_FROM'), 'user_regdate_from', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_REG_DATE_TO'), 'user_regdate_to', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'user_id', '');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }


    private function fetchAndFormat($rows)
    {
        foreach ($rows as $key => $row) {
            $row['user_created'] = MyDate::formatDate($row['user_created']);
            $rows[$key] = $row;
        }
        return $rows;
    }
}
