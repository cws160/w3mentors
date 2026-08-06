<?php

/**
 * Account Controller is used for User Account handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AccountController extends DashboardController
{

    /**
     * Initialize AccountController
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->_template->addJs([
            'js/translate.fill.js',
        ]);
    }

    public function index()
    {
        switch ($this->siteUserType) {
            case User::AFFILIATE:
                $url = MyUtility::makeFullUrl('Affiliate', '', [], CONF_WEBROOT_DASHBOARD);
                break;
            case User::TEACHER:
                $url = MyUtility::makeFullUrl('Teacher', '', [], CONF_WEBROOT_DASHBOARD);
                break;
            default:
                $url = MyUtility::makeFullUrl('Learner', '', [], CONF_WEBROOT_DASHBOARD);
                break;
        }
        FatApp::redirectUser($url);
    }

    /**
     * Render Change Password Page
     */
    public function changePassword()
    {
        $this->_template->render();
    }

    /**
     * Render Change Password Form
     */
    public function changePasswordForm()
    {
        $this->set('frm', $this->getChangePasswordForm());
        $this->_template->render(false, false);
    }

    /**
     * Render Delete Account Page
     */
    public function deleteAccount()
    {
        $this->_template->render(false, false);
    }

    /**
     * Render Change Email Form
     */
    public function changeEmailForm()
    {
        $this->set('frm', $this->getChangeEmailForm());
        $this->_template->render(false, false);
    }

    /**
     * Setup Password
     */
    public function setupPassword()
    {
        $pwdFrm = $this->getChangePasswordForm();
        if (!$post = $pwdFrm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($pwdFrm->getValidationErrors()));
        }
        if ($post['new_password'] != $post['conf_new_password']) {
            MyUtility::dieJsonError(Label::getLabel('MSG_NEW_PASSWORD_CONFIRM_PASSWORD_DOES_NOT_MATCH'));
        }
        if (!MyUtility::validatePassword($post['new_password'])) {
            MyUtility::dieJsonError(Label::getLabel('MSG_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC'));
        }
        $userPassword = User::getAttributesById($this->siteUserId, 'user_password');
        if ($userPassword != UserAuth::encryptPassword($post['current_password'])) {
            MyUtility::dieJsonError(Label::getLabel('MSG_YOUR_CURRENT_PASSWORD_MIS_MATCHED'));
        }
        $user = new User($this->siteUserId);
        $user->setFldValue('user_password', UserAuth::encryptPassword($post['new_password']));
        if (!$user->save()) {
            MyUtility::dieJsonError(Label::getLabel('MSG_PASSWORD_COULD_NOT_BE_SET'));
        }
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY'));
    }

    /**
     * Setup Email
     */
    public function setupEmail()
    {
        $emailFrm = $this->getChangeEmailForm();
        if (!$post = $emailFrm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($emailFrm->getValidationErrors()));
        }
        $post['new_email'] = trim($post['new_email']);
        $userRow = User::getAttributesById($this->siteUserId, ['user_first_name', 'user_last_name', 'user_password']);
        $userData = [
            'user_email' => $post['new_email'],
            'user_first_name' => $userRow['user_first_name'],
            'user_last_name' => $userRow['user_last_name']
        ];
        if ($userRow['user_password'] != UserAuth::encryptPassword($post['current_password'])) {
            MyUtility::dieJsonError(Label::getLabel('MSG_YOUR_CURRENT_PASSWORD_MIS_MATCHED'));
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
        if (!$this->sendEmailChangeVerificationLink($token, $userData)) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError(Label::getLabel('MSG_UNABLE_TO_PROCESS_YOUR_REQUSET'));
        }
        $db->commitTransaction();
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_PLEASE_VERIFY_YOUR_EMAIL'));
    }

    /**
     * Remove Profile Image
     */
    public function removeProfileImage()
    {
        $file = new Afile(Afile::TYPE_USER_PROFILE_IMAGE);
        if (!$file->removeFile($this->siteUserId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        $file = new Afile(Afile::TYPE_OPENGRAPH_IMAGE);
        $file->removeFile($this->siteUserId);
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_PROFILE_IMAGE_REMOVED!'));
    }

    /**
     * Render User Profile Info Page
     */
    public function profileInfo()
    {
        $file = new Afile(Afile::TYPE_USER_PROFILE_IMAGE);
        $this->set('userImage', $file->getFile($this->siteUserId));
        $this->set('payoutMethods', PaymentMethod::getPayouts());
        if (!API_CALL) {
            $this->_template->addJs([
                'js/jquery.form.js',
                'js/cropper.js',
                'js/jquery-confirm.min.js'
            ]);
        }
        $this->_template->render();
    }

    /**
     * Render Profile Info Form
     */
    public function profileInfoForm()
    {
        $userRow = User::getDetail($this->siteUserId);
        $isTeacher = ($this->siteUserType == User::TEACHER);
        $profileFrm = $this->getProfileInfoForm($isTeacher);
        $profileFrm->fill($userRow);
        $googleCalendar = new GoogleCalendar($this->siteUserId);
        $accessToken = $googleCalendar->getUserToken($userRow['user_google_token'] ?? '');
        $isGoogleAuthSet = ($googleCalendar->getClient() !== false);
        $this->sets([
            'userRow' => $userRow,
            'profileFrm' => $profileFrm,
            'accessToken' => $accessToken,
            'isGoogleAuthSet' => $isGoogleAuthSet,
            'languages' => Language::getAllNames(false),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render User Language Form
     * 
     * @param int $langId
     */
    public function userLangForm(int $langId = 0)
    {
        if ($langId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $form = $this->getUserLangForm($langId);
        $langData = (new User($this->siteUserId))->getBio($langId);
        $langData['userlang_lang_id'] = $langData['userlang_lang_id'] ?? $langId;
        $form->fill($langData);
        $this->sets([
            'form' => $form, 'langId' => $langId,
            'languages' => Language::getAllNames(false),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup User Profile Language Data
     */
    public function setUpProfileLangInfo()
    {
        $post = FatApp::getPostedData();
        $langId = FatUtility::int($post['userlang_lang_id'] ?? 0);
        $frm = $this->getUserLangForm($langId);
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $data = [
            'userlang_user_id' => $this->siteUserId,
            'userlang_lang_id' => $post['userlang_lang_id'],
            'user_biography' => $post['user_biography']
        ];
        $record = new TableRecord(User::DB_TBL_LANG);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            FatUtility::dieJsonError($record->getError());
        }

        $translator = new Translator($this->siteLangId);
        if (!$translator->validateAndTranslate(User::DB_TBL_LANG, $this->siteUserId, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }

        $teacherMeta = MetaTag::getMetaTag(MetaTag::META_GROUP_TEACHER, $this->siteUser['user_username']);
        $metaId = $teacherMeta['meta_id'] ?? 0;
        if ($metaId > 0) {
            $metaTag = new MetaTag($metaId);
            if (!$metaTag->updateTeacherDes($post['userlang_lang_id'], $post['user_biography'])) {
                FatUtility::dieJsonError($metaTag->getError());
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * Setup Profile Image
     */
    public function setupProfileImage()
    {
        if (empty($_FILES['user_profile_image']['tmp_name'])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_IMAGE_IS_REQURIDE'));
        }
        $file = new Afile(Afile::TYPE_USER_PROFILE_IMAGE);
        if (!$file->saveFile($_FILES['user_profile_image'], $this->siteUserId, true)) {
            MyUtility::dieJsonError($file->getError());
        }
        if ($this->siteUserType == User::TEACHER) {
            $filedata = $file->getFile($this->siteUserId);
            $record = new TableRecord(Afile::DB_TBL);
            $record->assignValues([
                'file_type' => Afile::TYPE_OPENGRAPH_IMAGE,
                'file_record_id' => $this->siteUserId,
                'file_name' => $filedata['file_name'],
                'file_path' => $filedata['file_path'],
                'file_added' => date('Y-m-d H:i:s')
            ]);
            if (!$record->addNew()) {
                $this->error = $record->getError();
                return false;
            }
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_PHOTO_SAVED_SUCCESSFULLY'));
    }

    /**
     * Setup Video Link
     */
    public function setupVideoLink()
    {
        if ($this->siteUserType == User::TEACHER) {
            $frm = $this->getProfileImageForm();
            if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
                FatUtility::dieJsonError(current($frm->getValidationErrors()));
            }
            $userSettings = new UserSetting($this->siteUserId);
            if (!$userSettings->saveData(['user_video_link' => $post['user_video_link']])) {
                FatUtility::dieJsonError($userSettings->getError());
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_PHOTO_SAVED_SUCCESSFULLY'));
    }

    /**
     * Setup Profile Info
     */
    public function setupProfileInfo()
    {
        $post = FatApp::getPostedData();
        $isTeacher = ($this->siteUserType == User::TEACHER);
        if ($isTeacher) {
            $post['user_username'] = CommonHelper::seoUrl($post['user_username'] ?? '');
            $post['user_username'] = MyUtility::createSlug($post['user_username']);
        }
        $frm = $this->getProfileInfoForm($isTeacher, true);
        if (!$post = $frm->getFormDataFromArray($post, ['user_country_id', 'user_phone_code'])) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!CommonHelper::sanitizeInput([$post['user_first_name'], $post['user_last_name']])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SCRIPT_TAG_NOT_ALLOWED_IN_FIELDS') . ' ' . implode(', ', CommonHelper::getCaptionsForFields($frm, ['user_first_name', 'user_last_name'])));
        }
        $country = Country::getAttributesById($post['user_country_id'], ['country_active']);
        if ($country['country_active'] == AppConstant::NO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COUNTRY_IS_INACTIVE', $this->siteLangId));
        }
        if ($post['user_phone_code'] != $post['user_country_id']) {
            $country = Country::getAttributesById($post['user_phone_code'], ['country_active']);
            if ($country['country_active'] == AppConstant::NO) {
                FatUtility::dieJsonError(Label::getLabel('LBL_PHONE_CODE_COUNTRY_IS_INACTIVE', $this->siteLangId));
            }
        }
        if (isset($post['user_offline_sessions']) && $post['user_offline_sessions'] == AppConstant::YES) {
            $address = (new UserAddresses($this->siteUserId))->validateDefault();
            if ($address < 1) {
                MyUtility::dieJsonError(Label::getLabel('LBL_TO_ENABLE_OFFLINE_SESSIONS,_PLEASE_ADD_ADDRESSES_FIRST'));
            }
        }

        $db = FatApp::getDb();
        $db->startTransaction();
        unset($post['user_id']);
        $user = new User($this->siteUserId);
        $user->assignValues($post);
        if (!$user->save()) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError($user->getError());
        }
        if (
            $this->siteUser['user_is_teacher'] == AppConstant::YES &&
            $this->siteUser['user_timezone'] != $post['user_timezone']
        ) {
            $availability = new Availability($this->siteUserId);
            if (!$availability->removeAvailability()) {
                $db->rollbackTransaction();
                MyUtility::dieJsonError($availability->getError());
            }
        }
        $userSetting = new UserSetting($this->siteUserId);
        if (!$userSetting->saveData($post)) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError($userSetting->getError());
        }
        $updateKeyWords = ($isTeacher && !$this->siteUser['profile_progress']['generalProfile'] &&
            $this->siteUser['profile_progress']['priceCount']);
        if ($isTeacher && !$user->updateTeacherMeta($post, $updateKeyWords)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($user->getError());
        }
        $db->commitTransaction();
        if (!empty($post['user_timezone'])) {
            MyUtility::setSiteTimezone($post['user_timezone'], true);
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_PROFILE_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Render Delete Account Form
     */
    public function deleteAccountForm()
    {
        $reqData = GdprRequest::getRequestFromUserId($this->siteUserId);
        if (!empty($reqData)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_REQUEST_IS_ALREADY_PLACED_TO_DELETE_ACCOUNT'));
        }
        $this->set('frm', $this->getDeleteAccountForm($this->siteLangId));
        $this->_template->render(false, false);
    }

    /**
     * Setup GDPR Delete Account
     */
    public function setupGdprDeleteAcc()
    {
        $reqData = GdprRequest::getRequestFromUserId($this->siteUserId);
        if (!empty($reqData)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_REQUEST_IS_ALREADY_PLACED_TO_DELETE_ACCOUNT'));
        }
        $frm = $this->getDeleteAccountForm($this->siteLangId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $data = [
            'gdpreq_user_id' => $this->siteUserId,
            'gdpreq_reason' => $post['gdpreq_reason'],
            'gdpreq_added_on' => date('Y-m-d H:i:s'),
            'gdpreq_updated_on' => date('Y-m-d H:i:s'),
            'gdpreq_type' => GdprRequest::TRUNCATE_DATA,
            'gdpreq_status' => GdprRequest::STATUS_PENDING,
        ];
        $gdprRequest = new GdprRequest();
        $gdprRequest->assignValues($data);
        if (!$gdprRequest->save()) {
            MyUtility::dieJsonError($gdprRequest->getError());
        }
        $gdprRequest->sendGdprRequestMailToAdmin($this->siteUserId, $post);
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_REQUEST_PLACED_SUCCESSFULLY'));
    }

    /**
     * Get Delete Account Form
     * 
     * @param int $langId
     * @return Form
     */
    private function getDeleteAccountForm(int $langId): Form
    {
        $frm = new Form('gdprRequestForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextArea(Label::getLabel('LBL_REASON_FOR_ERASURE'), 'gdpreq_reason')->requirements()->setRequired(true);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEND'), ['class' => 'btn btn--primary block-on-mobile']);
        return $frm;
    }

    /**
     * Get User Language Form
     * 
     * @return Form
     */
    private function getUserLangForm(int $langId): Form
    {
        $language = Language::getAllNames();
        $frm = new Form('frmUserLang');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addSelectBox('', 'userlang_lang_id', $language, '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBL_BIOGRAPHY', $langId), 'user_biography');
        $fld->requirements()->setLength(1, 2000);
        $fld->requirements()->setRequired();
        Translator::addTranslatorActions($frm, $langId, $this->siteUserId, User::DB_TBL_LANG, $this->siteLangId);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE', $langId));
        $frm->addButton('', 'btn_next', Label::getLabel('LBL_NEXT', $langId));
        return $frm;
    }

    /**
     * Get Profile Info Form
     * 
     * @param bool $isTeacher
     * @param bool $setUnique
     * @return Form
     */
    private function getProfileInfoForm(bool $isTeacher = false, bool $setUnique = false): Form
    {
        $frm = new Form('frmProfileInfo');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'user_id', 'user_id');
        if ($isTeacher) {
            $fldUname = $frm->addTextBox(Label::getLabel('LBL_USERNAME'), 'user_username');
            $fldUname->requirements()->setLength(6, 60);
            $fldUname->requirements()->setRequired();
            if ($setUnique) {
                $fldUname->setUnique(User::DB_TBL, 'user_username', 'user_id', 'user_id', 'user_id');
            }
        }

        $fld = $frm->addRequiredField(Label::getLabel('LBL_First_Name'), 'user_first_name');
        $fld = $frm->addTextBox(Label::getLabel('LBL_Last_Name'), 'user_last_name');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_GENDER'), 'user_gender', User::getGenderTypes(), 'user_gender', [], Label::getLabel('LBL_SELECT_GENDER'));
        $fld->requirements()->setRequired();
        $countries = Country::getAll($this->siteLangId);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_COUNTRY'), 'user_country_id', array_column($countries, 'country_name', 'country_id'), FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 0), [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_PHONE_CODE'), 'user_phone_code', array_column($countries, 'phone_code', 'country_id'), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $fld = $frm->addTextBox(Label::getLabel('LBL_PHONE'), 'user_phone_number');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PHONE_NO_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PHONE_NO_VALIDATION_MSG'));

        $fld = $frm->addSelectBox(Label::getLabel('LBL_TIMEZONE'), 'user_timezone', MyDate::timeZoneListing(), CONF_SERVER_TIMEZONE, [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        if ($isTeacher) {
            $bookingOptionArr = [0 => Label::getLabel('LBL_IMMEDIATE'), 12 => Label::getLabel('LBL_12_HOURS'), 24 => Label::getLabel('LBL_24_HOURS')];
            $fld = $frm->addSelectBox(Label::getLabel('LBL_BOOKING_BEFORE'), 'user_book_before', $bookingOptionArr, 'user_book_before', [], Label::getLabel('LBL_SELECT'));
            $fld->requirements()->setRequired(true);
            if (FatApp::getConfig('CONF_ENABLE_FREE_TRIAL', FatUtility::VAR_INT, 0) == AppConstant::YES) {
                $frm->addCheckBox(Label::getLabel('LBL_ENABLE_TRIAL_LESSON'), 'user_trial_enabled', AppConstant::YES, [], true, AppConstant::NO);
            }
        }
        if ($this->siteUserType == User::TEACHER && (User::offlineSessionsEnabled())) {
            $fld = $frm->addCheckBox(Label::getLabel('LBL_OFFLINE_SESSIONS'), 'user_offline_sessions', AppConstant::YES, [], true, AppConstant::NO);
        }
        $fld = $frm->addSelectBox(Label::getLabel('LBL_NOTIFICATION_LANGUAGE'), 'user_lang_id', Language::getAllNames(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        $frm->addButton('', 'btn_next', Label::getLabel('LBL_NEXT'));
        return $frm;
    }

    /**
     * Render Profile Image Form
     */
    public function profileImageForm()
    {
        $userSettings = UserSetting::getSettings($this->siteUserId);
        $profileImgFrm = $this->getProfileImageForm();
        $profileImgFrm->fill(['user_video_link' => $userSettings['user_video_link'] ?? '']);
        $file = new Afile(Afile::TYPE_USER_PROFILE_IMAGE);
        $userImage = $file->getFile($this->siteUserId);
        $this->sets([
            'userImage' => $userImage,
            'form' => $profileImgFrm,
            'imageExt' => Afile::getAllowedExts(Afile::TYPE_USER_PROFILE_IMAGE),
            'fileSize' => Afile::getAllowedUploadSize(Afile::TYPE_USER_PROFILE_IMAGE),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Profile Image Form
     * 
     * @return Form
     */
    private function getProfileImageForm(): Form
    {
        $frm = new Form('frmProfile', ['id' => 'frmProfile']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addFileUpload(Label::getLabel('LBL_PROFILE_PICTURE'), 'user_profile_image');
        if ($this->siteUserType == User::TEACHER) {
            $vidoLinkfield = $frm->addTextBox(Label::getLabel('LBL_INTRODUCTION_VIDEO_LINK'), 'user_video_link', '');
            $vidoLinkfield->requirements()->setRegularExpressionToValidate(AppConstant::INTRODUCTION_VIDEO_LINK_REGEX);
            $vidoLinkfield->requirements()->setCustomErrorMessage(Label::getLabel('MSG_PLEASE_ENTER_VALID_VIDEO_LINK'));
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        $frm->addButton('', 'btn_next', Label::getLabel('LBL_NEXT'));
        return $frm;
    }

    /**
     * Get Change Password Form
     * 
     * @return Form
     */
    private function getChangePasswordForm(): Form
    {
        $frm = new Form('changePwdFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $curPwd = $frm->addPasswordField(Label::getLabel('LBL_CURRENT_PASSWORD'), 'current_password');
        $curPwd->requirements()->setRequired();
        $newPwd = $frm->addPasswordField(Label::getLabel('LBL_NEW_PASSWORD'), 'new_password');
        $newPwd->requirements()->setRequired();
        $newPwd->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $newPwd->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $conNewPwd = $frm->addPasswordField(Label::getLabel('LBL_CONFIRM_NEW_PASSWORD'), 'conf_new_password');
        $conNewPwdReq = $conNewPwd->requirements();
        $conNewPwdReq->setRequired();
        $conNewPwdReq->setCompareWith('new_password', 'eq');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        $frm->addSubmitButton('', 'btn_next', Label::getLabel('LBL_NEXT'));
        return $frm;
    }

    /**
     * Send Email Change Verification Link
     * 
     * @param string $token
     * @param array $data
     * @return bool
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
     * Google Calendar Authorize
     */
    public function googleCalendarAuthorize()
    {
        $code = $_GET['code'] ?? null;
        if (API_CALL) {
            $code = FatApp::getPostedData('code', FatUtility::VAR_STRING, '');
        }
        $error = $_GET['error'] ?? null;
        if (!empty($error)) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED'));
            }
            FatApp::redirectUser(MyUtility::makeUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD));
        }
        $googleCalendar = new GoogleCalendar($this->siteUserId);
        $authorize = $googleCalendar->authorize($code);
        $redirectUrl = $googleCalendar->getRedirectUrl();
        $msg = $googleCalendar->getError();

        if (!$authorize) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_AUTHORIZATION_FAILED'));
            }
            if (empty($code)) {
                FatUtility::dieJsonError(['msg' => $msg, 'redirectUrl' => $redirectUrl]);
            }
            Message::addErrorMessage($msg);
            FatApp::redirectUser($redirectUrl);
        }
        if (empty($code)) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_AUTHENTICATION_CODE_MISSING'));
            }
            FatUtility::dieJsonSuccess(['redirectUrl' => $googleCalendar->getRedirectUrl()]);
        }
        if (API_CALL) {
            MyUtility::dieJsonSuccess(Label::getLabel('LBL_GOOGLE_CALENDAR_SYNC_SCCESSFULY'));
        }
        Message::addMessage(Label::getLabel('LBL_GOOGLE_CALENDAR_SYNC_SCCESSFULY'));
        FatApp::redirectUser($redirectUrl);
    }

    /**
     * Get Change Email Form
     * 
     * @param bool $passwordField
     * @return Form
     */
    protected function getChangeEmailForm($passwordField = true): Form
    {
        $frm = new Form('changeEmailFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $userEmail = User::getAttributesById($this->siteUserId, 'user_email');
        $frm->addHiddenField('', 'user_id', $this->siteUserId);
        $curEmail = $frm->addEmailField(Label::getLabel('LBL_CURRENT_EMAIL'), 'user_email', $userEmail);
        $curEmail->requirements()->setRequired();
        $curEmail->addFieldTagAttribute('readonly', 'true');
        $newEmail = $frm->addEmailField(Label::getLabel('LBL_NEW_EMAIL'), 'new_email');
        $newEmail->setUnique(User::DB_TBL, 'user_email', 'user_id', 'user_id', 'user_id');
        $newEmail->requirements()->setRequired();
        if ($passwordField) {
            $curPwd = $frm->addPasswordField(Label::getLabel('LBL_CURRENT_PASSWORD'), 'current_password');
            $curPwd->requirements()->setRequired();
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Get PayPal Email Address Form
     * 
     * @return Form
     */
    private function getPaypalEmailAddressForm(): Form
    {
        $frm = new Form('frmBankInfo');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addEmailField(Label::getLabel('M_Paypal_Email_Address'), 'ub_paypal_email_address');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Get Bank Info Form
     * 
     * @return Form
     */
    private function getBankInfoForm(): Form
    {
        $frm = new Form('frmBankInfo');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addRequiredField(Label::getLabel('M_Bank_Name'), 'ub_bank_name', '');
        $frm->addRequiredField(Label::getLabel('M_Beneficiary/Account_Holder_Name'), 'ub_account_holder_name', '');
        $frm->addRequiredField(Label::getLabel('M_Bank_Account_Number'), 'ub_account_number', '');
        $frm->addRequiredField(Label::getLabel('M_IFSC_Code/Swift_Code'), 'ub_ifsc_swift_code', '');
        $frm->addTextArea(Label::getLabel('M_Bank_Address'), 'ub_bank_address', '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Render PayPal Email Address Form
     */
    public function paypalEmailAddressForm()
    {
        $payoutMethods = PaymentMethod::getPayouts();
        if (empty($payoutMethods[PaypalPayout::KEY])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PAYMENT_METHOD_NOT_ACTIVE_YET'));
        }
        $frm = $this->getPaypalEmailAddressForm();
        $userObj = new User($this->siteUserId);
        $data = $userObj->getUserPaypalInfo();
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->set('payoutMethods', $payoutMethods);
        $this->_template->render(false, false, 'account/paypal-email-address-form.php');
    }

    /**
     * Setup PayPal Info
     */
    public function setupPaypalInfo()
    {
        $frm = $this->getPaypalEmailAddressForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $userObj = new User($this->siteUserId);
        if (!$userObj->updatePaypalInfo($post)) {
            MyUtility::dieJsonError($userObj->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_PAYPAL_DETAIL_SAVED_SUCCESSFULY'));
    }

    public function getPayoutForm()
    {
        $payout = FatApp::getPostedData('payout', FatUtility::VAR_STRING, '');
        $payoutMethods = PaymentMethod::getPayouts();
        if ((count($payoutMethods) < 1) || ($payout == 'bankPayout' && !isset($payoutMethods[BankPayout::KEY])) || ($payout == 'paypalPayout' && !isset($payoutMethods[PaypalPayout::KEY]))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PAYMENT_METHOD_NOT_ACTIVE_YET'));
        }

        if (!empty($payout)) {
            if ($payout == 'bankPayout') {
                $this->bankInfoForm();
                return;
            }
            $this->paypalEmailAddressForm();
            return;
        }
        if (isset($payoutMethods[BankPayout::KEY])) {
            $this->bankInfoForm();
            return;
        }
        $this->paypalEmailAddressForm();
        return;
    }

    /**
     * Render Bank Info Form
     */
    public function bankInfoForm()
    {
        $payoutMethods = PaymentMethod::getPayouts();

        if (empty($payoutMethods[BankPayout::KEY])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PAYMENT_METHOD_NOT_ACTIVE_YET'));
        }
        $frm = $this->getBankInfoForm();
        $userObj = new User($this->siteUserId);
        $data = $userObj->getUserBankInfo();
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->set('payoutMethods', $payoutMethods);
        $this->_template->render(false, false, 'account/bank-info-form.php');
    }

    /**
     * Setup Bank Info
     */
    public function setUpBankInfo()
    {
        $frm = $this->getBankInfoForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $userObj = new User($this->siteUserId);
        if (!$userObj->updateBankInfo($post)) {
            MyUtility::dieJsonError($userObj->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('MSG_BANK_DETAIL_SAVED_SUCCESSFULY'));
    }

    /**
     * User Logout
     */
    public function logout()
    {
        if (API_CALL) {
            AppToken::clearToken();
            MyUtility::dieJsonSuccess(Label::getLabel('MSG_USER_LOGOUT_SUCCESSFULLY'));
        }
        UserAuth::logout();
        TeacherRequest::closeSession();
        FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONT_URL));
    }

    /**
     * User Logout
     */
    public function addressForm()
    {
        $addressId = FatApp::getPostedData('address_id');
        $frm = $this->getAddressesForm();
        $address = new UserAddresses($this->siteUserId);
        $data = $address->getAll($this->siteLangId, [1]);
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Get Profile Info Form
     * @return Form
     */
    private function getAddressesForm(): Form
    {
        $data = State::getNames($this->siteLangId, $this->siteUser['user_country_id']);
        if (empty($data)) {
            MyUtility::dieJsonError(Label::getLabel('MSG_COMPLETE_YOUR_PROFILE_FIRST'));
        }
        $frm = new Form('frmAddressInfo');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'usradd_id', 'usradd_id');
        $frm->addHiddenField('', 'usradd_country_id', 'usradd_country_id');
        $frm->addHiddenField('', 'usradd_place_name', 'usradd_place_name');
        $frm->addHiddenField('', 'usradd_place_id', 'usradd_place_id');
        $frm->addHiddenField('', 'usradd_country_id', 'usradd_country_id');
        $frm->addRequiredField(Label::getLabel('LBL_Addresses'), 'usradd_address');
        $frm->addRequiredField(Label::getLabel('LBL_Phone'), 'usradd_phone');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_State'), 'usradd_state_id', $data, 0, [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $frm->addRequiredField(Label::getLabel('LBL_City'), 'usradd_city');
        $frm->addRequiredField(Label::getLabel('LBL_Zipcode'), 'usradd_zipcode');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Type'), 'usradd_type', MyDate::timeZoneListing(), CONF_SERVER_TIMEZONE, [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $frm->addRequiredField(Label::getLabel('LBL_Latitude'), 'usradd_latitude');
        $frm->addRequiredField(Label::getLabel('LBL_Longitude'), 'usradd_longitude');
        $frm->addCheckBox(Label::getLabel('LBL_Default'), 'usradd_default', AppConstant::YES, [], true, AppConstant::NO);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        $frm->addButton('', 'btn_next', Label::getLabel('LBL_NEXT'));
        return $frm;
    }
}
