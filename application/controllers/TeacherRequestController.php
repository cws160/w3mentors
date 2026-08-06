<?php

/**
 * Teacher Request Controller
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeacherRequestController extends MyAppController
{

    private $userId;
    private $requestCount;

    /**
     * Initialize Teacher Request
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType == USER::AFFILIATE) {
            if (FatUtility::isAjaxCall()) {
                MyUtility::dieJsonError(Label::getLabel("LBL_YOU_ARE_ALREADY_LOGIN_AS_AFFILIATE"));
            }
            Message::addErrorMessage(Label::getLabel("LBL_YOU_ARE_ALREADY_LOGIN_AS_AFFILIATE"));
            FatApp::redirectUser(MyUtility::makeUrl('Home', '', [], CONF_WEBROOT_FRONTEND));
        }
        MyUtility::setUserType(User::LEARNER);
        $this->userId = 0;
        if ($this->siteUserId > 0) {
            $this->userId = $this->siteUserId;
        } elseif (TeacherRequest::getSession('user_id')) {
            $this->userId = TeacherRequest::getSession('user_id');
        }
        $this->requestCount = 0;
    }

    /**
     * Render Apply to Teach Form
     */
    public function index()
    {
        if (TeacherRequest::getSession('user_id') > 0) {
            FatApp::redirectUser(MyUtility::makeUrl('TeacherRequest', 'form', [], CONF_WEBROOT_FRONTEND));
        }
        $contentBlocks = ExtraPage::getPageBlocks(ExtraPage::TYPE_APPLY_TO_TEACH, $this->siteLangId);
        $this->set('faqs', $this->getApplyToTeachFaqs());
        $this->set('applyTeachFrm', $this->getApplyTeachFrm($this->siteLangId));
        $this->set('contentBlocks', $contentBlocks);
        $this->set('siteKey', FatApp::getConfig('CONF_RECAPTCHA_SITEKEY'));
        $this->set('secretKey', FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY'));
        $this->_template->render();
    }

    public function form()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            TeacherRequest::closeSession();
            FatApp::redirectUser(MyUtility::makeUrl('TeacherRequest', '', [], CONF_WEBROOT_FRONTEND));
        }
        $this->requestCount = TeacherRequest::getRequestCount($userId);
        if ($this->requestCount == FatApp::getConfig('CONF_MAX_TEACHER_REQUEST_ATTEMPT')) {
            $this->set('step', 5);
        } else {
            $this->set('step', TeacherRequest::getRequestByUserId($userId)['tereq_step'] ?? 1);
        }
        $this->set('exculdeMainHeaderDiv', false);
        $this->_template->addJs('js/jquery.form.js');
        $this->_template->addJs('js/cropper.js');
        $this->_template->render(true, false);
    }

    private function attemptReachedCheck()
    {
        $this->requestCount = TeacherRequest::getRequestCount($this->userId);
        if ($this->requestCount < FatApp::getConfig('CONF_MAX_TEACHER_REQUEST_ATTEMPT')) {
            return true;
        }
        FatUtility::dieJsonError(Label::getLabel('LBL_YOU_HAVE_REACH_MAX_ATTEMPTS_TO_SUBMIT_REQUEST'));
    }

    /**
     * Render Form Step1
     */
    public function formStep1()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->attemptReachedCheck();
        $frm = $this->getFormStep1($this->siteLangId);
        $request = TeacherRequest::getRequestByUserId($userId);
        if (empty($request)) {
            $user = User::getDetail($this->userId);
            $request = [
                'tereq_first_name' => $user['user_first_name'],
                'tereq_last_name' => $user['user_last_name'],
                'tereq_gender' => $user['user_gender'],
                'tereq_phone_code' => $user['user_phone_code'],
                'tereq_phone_number' => $user['user_phone_number'],
                'tereq_user_id' => $user['user_id']
            ];
        }
        if (!empty($request)) {
            $frm->fill($request);
        }
        if(isset($request['tereq_status']) && $request['tereq_status']  == TeacherRequest::STATUS_APPROVED) {
            Message::addMessage(Label::getLabel("MSG_APPLICATION_ALREADY_APPROVED"));
            FatApp::redirectUser(MyUtility::makeUrl('TeacherRequest', 'form'));
        }
        $file = new Afile(Afile::TYPE_TEACHER_APPROVAL_PROOF);
        $this->sets([
            'frm' => $frm,
            'request' => $request,
            'user' => User::getAttributesById($userId),
            'photoId' => $file->getFile($this->userId),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Form Step2
     */
    public function formStep2()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->attemptReachedCheck();
        $request = TeacherRequest::getRequestByUserId($userId, TeacherRequest::STATUS_PENDING);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getFormStep2($this->siteLangId);
        $frm->fill($request);
        if ($this->requestCount > 0 && $request['tereq_step'] == 2 && (empty($request['tereq_video_link']) || empty($request['tereq_biography']))) {
            $cancelledReq = TeacherRequest::getRequestByUserId($userId, TeacherRequest::STATUS_CANCELLED);
            unset($cancelledReq['tereq_id']);
            $frm->fill($cancelledReq);
        }
        $imageType = Afile::TYPE_TEACHER_APPROVAL_IMAGE;
        $file = new Afile(Afile::TYPE_TEACHER_APPROVAL_IMAGE);
        if ($image = $file->getFile($this->userId)) {
            $imageType = Afile::TYPE_TEACHER_APPROVAL_IMAGE;
        } else {
            $file = new Afile(Afile::TYPE_USER_PROFILE_IMAGE);
            if ($image = $file->getFile($this->userId)) {
                $imageType = Afile::TYPE_USER_PROFILE_IMAGE;
            }
        }
        $this->sets([
            'imageType' => $imageType,
            'frm' => $frm,
            'userId' => $userId,
            'request' => $request,
            'imageExt' => Afile::getAllowedExts(Afile::TYPE_TEACHER_APPROVAL_IMAGE),
            'fileSize' => Afile::getAllowedUploadSize(Afile::TYPE_TEACHER_APPROVAL_IMAGE),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Form Step3
     */
    public function formStep3()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->attemptReachedCheck();
        $request = TeacherRequest::getRequestByUserId($userId);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($this->requestCount > 0 && $request['tereq_step'] == 3) {
            $lastRequest = TeacherRequest::getRequestByUserId($userId, TeacherRequest::STATUS_CANCELLED);
            if (!empty($lastRequest)) {
                $request['tereq_teach_langs'] = $lastRequest['tereq_teach_langs'];
                $request['tereq_speak_langs'] = $lastRequest['tereq_speak_langs'];
                $request['tereq_slang_proficiency'] = $lastRequest['tereq_slang_proficiency'];
            }
        }
        $request['tereq_teach_langs'] = empty($request['tereq_teach_langs']) ? '[]' : $request['tereq_teach_langs'];
        $request['tereq_speak_langs'] = empty($request['tereq_speak_langs']) ? '[]' : $request['tereq_speak_langs'];
        $request['tereq_slang_proficiency'] = empty($request['tereq_slang_proficiency']) ? '[]' : $request['tereq_slang_proficiency'];
        $request['tereq_teach_langs'] = json_decode($request['tereq_teach_langs'], true);
        $request['tereq_speak_langs'] = json_decode($request['tereq_speak_langs'], true);
        $request['tereq_slang_proficiency'] = json_decode($request['tereq_slang_proficiency'], true);
        $spokenLangs = SpeakLanguage::getAllLangs($this->siteLangId, true);
        $profArr = SpeakLanguageLevel::getAllLangLevels($this->siteLangId, true);
        $frm = $this->getFormStep3($this->siteLangId, $spokenLangs);
        $frm->fill($request);
        $this->set('frm', $frm);
        $this->set('request', $request);
        $this->set('spokenLangs', $spokenLangs);
        $this->set('profRequired', count($profArr) > 0 ? true : false);
        $this->set('user', User::getAttributesById($userId));
        $this->_template->render(false, false);
    }

    /**
     * Render Form Step4
     */
    public function formStep4()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->attemptReachedCheck();
        $request = TeacherRequest::getRequestByUserId($userId, TeacherRequest::STATUS_PENDING);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getFormStep4();
        $frm->fill($request);
        $this->set('frm', $frm);
        $this->set('request', $request);
        $this->set('user', User::getAttributesById($userId));
        $this->_template->render(false, false);
    }

    /**
     * Render Form Step5
     */
    public function formStep5()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $request = TeacherRequest::getRequestByUserId($userId);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->set('request', $request);
        $this->set('user', User::getAttributesById($userId));
        $this->set('requestCount', TeacherRequest::getRequestCount($userId));
        $this->set('allowedCount', FatApp::getConfig('CONF_MAX_TEACHER_REQUEST_ATTEMPT'));
        $this->_template->render(false, false);
    }

    /**
     * Setup Step1 Form
     */
    public function setupStep1()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $resubmit = FatApp::getPostedData('resubmit', FatUtility::VAR_INT, 0);
        $frm = $this->getFormStep1($this->siteLangId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!CommonHelper::sanitizeInput([$post['tereq_first_name'], $post['tereq_last_name']])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SCRIPT_TAG_NOT_ALLOWED_IN_FIELDS') . ' ' . implode(', ', CommonHelper::getCaptionsForFields($frm, ['tereq_first_name', 'tereq_last_name'])));
        }
        if (!empty($_FILES['user_photo_id']['name'])) {
            $file = new Afile(Afile::TYPE_TEACHER_APPROVAL_PROOF);
            if (!$file->saveFile($_FILES['user_photo_id'], $userId, true)) {
                FatUtility::dieJsonError($file->getError());
            }
        }
        $data = [
            'tereq_step' => 2,
            'tereq_user_id' => $userId,
            'tereq_language_id' => $this->siteLangId,
            'tereq_reference' => $userId . '-' . time(),
            'tereq_date' => date('Y-m-d H:i:s'),
            'tereq_first_name' => $post['tereq_first_name'],
            'tereq_last_name' => $post['tereq_last_name'],
            'tereq_gender' => $post['tereq_gender'],
            'tereq_phone_code' => $post['tereq_phone_code'],
            'tereq_phone_number' => $post['tereq_phone_number'],
        ];
        $request = TeacherRequest::getRequestByUserId($userId, TeacherRequest::STATUS_PENDING);
        if (!empty($request)) {
            $data['tereq_id'] = $request['tereq_id'];
        }
        $record = new TableRecord(TeacherRequest::DB_TBL);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN'));
        }
        FatUtility::dieJsonSuccess(['step' => 2, 'msg' => Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY')]);
    }

    /**
     * Setup Profile Image
     */
    public function setupProfileImage()
    {
        if ($this->userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (empty($_FILES['user_profile_image'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $request = TeacherRequest::getRequestByUserId($this->userId, TeacherRequest::STATUS_PENDING);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->attemptReachedCheck();
        if (!is_uploaded_file($_FILES['user_profile_image']['tmp_name'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PLEASE_SELECT_A_FILE'));
        }
        $file = new Afile(Afile::TYPE_TEACHER_APPROVAL_IMAGE);
        if (!$file->saveFile($_FILES['user_profile_image'], $this->userId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        $file = MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_TEACHER_APPROVAL_IMAGE, $this->userId, Afile::SIZE_LARGE]) . '?' . time();
        FatUtility::dieJsonSuccess(['msg' => Label::getLabel('MSG_File_uploaded_successfully'), 'file' => $file]);
    }

    /**
     * Setup Step2 Form
     */
    public function setupStep2()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getFormStep2($this->siteLangId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $request = TeacherRequest::getRequestByUserId($userId, TeacherRequest::STATUS_PENDING);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $userImage = (new Afile(Afile::TYPE_TEACHER_APPROVAL_IMAGE))->getFile($this->userId);
        if (empty($userImage)) {
            $userImage = (new Afile(Afile::TYPE_USER_PROFILE_IMAGE))->getFile($this->userId);
            if (empty($userImage)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_PROFILE_PICTURE_REQUIRED'));
            }
        }
        $record = new TableRecord(TeacherRequest::DB_TBL);
        $data = [
            'tereq_step' => 3,
            'tereq_id' => $request['tereq_id'],
            'tereq_video_link' => $post['tereq_video_link'],
            'tereq_biography' => $post['tereq_biography'],
        ];
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN'));
        }
        FatUtility::dieJsonSuccess(['step' => 3, 'msg' => Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY')]);
    }

    /**
     * Setup Step3 Form
     */
    public function setupStep3()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $spokenLangs = SpeakLanguage::getAllLangs($this->siteLangId, true);
        $profArr = SpeakLanguageLevel::getAllLangLevels($this->siteLangId, true);
        $frm = $this->getFormStep3($this->siteLangId, $spokenLangs);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['tereq_teach_langs'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $request = TeacherRequest::getRequestByUserId($userId, TeacherRequest::STATUS_PENDING);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $teachLangs = json_encode(array_filter(FatUtility::int($post['tereq_teach_langs'])));
        $speakLangs = [];
        if (!isset($post['tereq_speak_langs'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SPEAK_LANGUAGE_IS_REQUIRED'));
        }
        $speakLangArr = array_filter(FatUtility::int(array_values($post['tereq_speak_langs'])));
        if (empty($speakLangArr)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SPEAK_LANGUAGE_IS_REQUIRED'));
        }
        foreach ($speakLangArr as $key => $value) {
            array_push($speakLangs, $value);
        }
        $speakLangsProf = [];
        if(count($profArr) > 0) {
            $speakLangsProfArr = array_filter(FatUtility::int(array_values($post['tereq_slang_proficiency'])));
            foreach ($speakLangsProfArr as $key => $value) {
                array_push($speakLangsProf, $value);
            }
            if (empty($speakLangs) || empty($speakLangsProf)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_SPEAK_LANGUAGE_AND_PROFICIENCY_REQUIRED'));
            }
        }
        $record = new TableRecord(TeacherRequest::DB_TBL);
        $data = [
            'tereq_step' => 4,
            'tereq_id' => $request['tereq_id'],
            'tereq_teach_langs' => $teachLangs,
            'tereq_speak_langs' => json_encode($speakLangs),
            'tereq_slang_proficiency' => json_encode($speakLangsProf),
        ];
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN'));
        }
        FatUtility::dieJsonSuccess(['step' => 4, 'msg' => Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY')]);
    }

    /**
     * Setup Step4 Form
     */
    public function setupStep4()
    {
        $userId = FatUtility::int($this->userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getFormStep4();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $request = TeacherRequest::getRequestByUserId($userId);
        if (empty($request)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $qualification = new UserQualification(0, $this->userId);
        $rows = $qualification->getUQualification(false, true);
        if (empty($rows)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_TEACHER_QUALIFICATION_REQUIRED'));
        }
        $record = new TableRecord(TeacherRequest::DB_TBL);
        $record->assignValues(['tereq_step' => 5, 'tereq_terms' => $post['tereq_terms']]);
        if (!$record->update(['smt' => 'tereq_id = ?', 'vals' => [$request['tereq_id']]])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN'));
        }
        $mail = new FatMailer(FatApp::getConfig("CONF_DEFAULT_LANG"), 'teacher_request_received');
        $request['tereq_phone_code'] = Country::getAttributesById($request['tereq_phone_code'], 'country_dial_code');
        $requestDate = MyDate::formatDate($request['tereq_date'], 'Y-m-d H:i:s', MyUtility::getSuperAdminTimeZone());
        $requestDate = MyDate::showDate($requestDate, true) . ' (' . (MyUtility::getSuperAdminTimeZone() ?? MyUtility::getSiteTimezone()) . ')';
        $vars = [
            '{refnum}' => $request['tereq_reference'],
            '{name}' => $request['tereq_first_name'] . ' ' . $request['tereq_last_name'],
            '{phone}' => $request['tereq_phone_code'] . ' ' . $request['tereq_phone_number'],
            '{request_date}' => $requestDate
        ];
        $mail->setVariables($vars);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);
        FatUtility::dieJsonSuccess(['step' => 5, 'msg' => Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY')]);
    }

    /**
     * Get Step1 Form
     *
     * @return Form
     */
    private function getFormStep1(): Form
    {
        $frm = new Form('frmFormStep1', ['id' => 'frmFormStep1']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addRequiredField(Label::getLabel('LBL_First_Name'), 'tereq_first_name')->requirements()->setRequired();
        $frm->addTextBox(Label::getLabel('LBL_Last_Name'), 'tereq_last_name');
        $frm->addSelectBox(Label::getLabel('LBL_Gender'), 'tereq_gender', User::getGenderTypes(), '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired();
        $countries = Country::getAll($this->siteLangId);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_PHONE_CODE'), 'tereq_phone_code', array_column($countries, 'phone_code', 'country_id'), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $fld = $frm->addTextBox(Label::getLabel('LBL_PHONE_NUMBER'), 'tereq_phone_number');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PHONE_NO_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PHONE_NO_VALIDATION_MSG'));
        $frm->addHiddenField('', 'resubmit', 0);
        $frm->addFileUpload(Label::getLabel('LBL_Photo_Id'), 'user_photo_id');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Step2 Form
     *
     * @return Form
     */
    private function getFormStep2(): Form
    {
        $frm = new Form('frmFormStep2', ['id' => 'frmFormStep2']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addFileUpload(Label::getLabel('LBL_Profile_Picture'), 'user_profile_image', ['onchange' => 'popupImage(this)', 'accept' => 'image/*']);
        $frm->addTextArea(Label::getLabel('LBL_Biography'), 'tereq_biography')->requirements()->setLength(1, 2000);
        $fld = $frm->addTextBox(Label::getLabel('LBL_Introduction_video'), 'tereq_video_link');
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::INTRODUCTION_VIDEO_LINK_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('MSG_Please_Enter_Valid_Video_Link'));
        $frm->addHiddenField('', 'update_profile_img', Label::getLabel('LBL_Update_Profile_Picture'), ['id' => 'update_profile_img']);
        $frm->addHiddenField('', 'rotate_left', Label::getLabel('LBL_Rotate_Left'), ['id' => 'rotate_left']);
        $frm->addHiddenField('', 'rotate_right', Label::getLabel('LBL_Rotate_Right'), ['id' => 'rotate_right']);
        $frm->addHiddenField('', 'img_data', '', ['id' => 'img_data']);
        $frm->addHiddenField('', 'action', 'avatar', ['id' => 'avatar-action']);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Step3 Form
     *
     * @param int $langId
     * @param type $spokenLangs
     * @return Form
     */
    private function getFormStep3($langId, $spokenLangs): Form
    {
        $frm = new Form('frmFormStep3', ['id' => 'frmFormStep3']);
        $frm = CommonHelper::setFormProperties($frm);
        $profArr = SpeakLanguageLevel::getAllLangLevels($langId, true);
        $teachLanguages = TeachLanguage::getTeachLangsRecursively($langId);
        $fld = $frm->addCheckBoxes(Label::getLabel('LBL_LANGUAGE_TO_TEACH'), 'tereq_teach_langs', $teachLanguages);
        $count = 0;
        array_walk_recursive($teachLanguages, function ($data, $key) use (&$count) {
            $count += ($key == 'tlang_subcategories' && $data == 0) ? 1 : 0;
        });
        $fld->requirements()->setSelectionRange(1, $count);
        $fld->requirements()->setRequired();
        $fld->requirements()->setCustomErrorMessage(str_replace(['{from}', '{to}', '{caption}'], [1, $count, Label::getLabel('LBL_LANGUAGE_TO_TEACH')], Label::getLabel('LBL_Please_select_{from}_to_{to}_options_for_{caption}')));
        $langArr = $spokenLangs ?: SpeakLanguage::getAllLangs($langId, true);
        $proficiencyLabel = stripslashes(Label::getLabel("LBL_I_DO_NOT_SPEAK_THIS_LANGUAGE"));
        foreach ($langArr as $key => $lang) {
            $speekLangField = $frm->addCheckBox(Label::getLabel('LBL_LANGUAGE_I_SPEAK'), 'tereq_speak_langs[' . $key . ']', $key, ['class' => 'uslang_slang_id'], false, '0');
            if(count($profArr) > 0) {
                $proficiencyField = $frm->addSelectBox(Label::getLabel('LBL_LANGUAGE_PROFICIENCY'), 'tereq_slang_proficiency[' . $key . ']', $profArr, '', ['class' => 'uslang_proficiency select__dropdown'], $proficiencyLabel);
                $proficiencyField->requirements()->setRequired();
                $speekLangField->requirements()->addOnChangerequirementUpdate(0, 'gt', $proficiencyField->getName(), $proficiencyField->requirements());
                $proficiencyField->requirements()->setRequired(false);
                $speekLangField->requirements()->addOnChangerequirementUpdate(0, 'le', $proficiencyField->getName(), $proficiencyField->requirements());
                $speekLangField->requirements()->setRequired();
                $proficiencyField->requirements()->addOnChangerequirementUpdate(0, 'gt', $proficiencyField->getName(), $speekLangField->requirements());
                $speekLangField->requirements()->setRequired(false);
                $proficiencyField->requirements()->addOnChangerequirementUpdate(0, 'le', $proficiencyField->getName(), $speekLangField->requirements());
            }
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Step4 Form
     *
     * @return Form
     */
    private function getFormStep4(): Form
    {
        $frm = new Form('frmFormStep4', ['id' => 'frmFormStep4']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addCheckBox(Label::getLabel('LBL_ACCEPT_TEACHER_APPROVAL_TERMS_&_CONDITION'), 'tereq_terms', 1, [], false, 0)->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Search Teacher Qualification
     */
    public function searchTeacherQualification()
    {
        $qualification = new UserQualification(0, $this->userId);
        $this->set("rows", $qualification->getUQualification(false, true));
        $this->set("userId", $this->userId);
        $this->_template->render(false, false);
    }

    /**
     * Render Teacher Qualification Form
     */
    public function teacherQualificationForm()
    {
        $qualificationId = FatApp::getPostedData('uqualification_id', FatUtility::VAR_INT, 0);
        $frm = UserQualification::getForm();
        if ($qualificationId > 0) {
            $qualification = new UserQualification($qualificationId, $this->userId);
            if (!$row = $qualification->getQualiForUpdate()) {
                FatUtility::dieJsonError($qualification->getError());
            }
            $frm->fill($row);
            $field = $frm->getField('certificate');
            $field->requirements()->setRequired(false);
        }
        $this->set('frm', $frm);
        $this->set('qualificationId', $qualificationId);
        $this->_template->render(false, false);
    }

    /**
     * Setup Teacher Qualification
     */
    public function setupTeacherQualification()
    {
        $frm = UserQualification::getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $post['uqualification_user_id'] = $this->userId;
        $qualification = new UserQualification($post['uqualification_id']);
        $db = FatApp::getDb();
        $db->startTransaction();
        $qualification->assignValues($post);
        if (!$qualification->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($qualification->getError());
        }
        if (!empty($_FILES['certificate']['name'])) {
            $uqualification_id = $qualification->getMainTableRecordId();
            $file = new Afile(Afile::TYPE_USER_QUALIFICATION_FILE);
            if (!$file->saveFile($_FILES['certificate'], $uqualification_id, true)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($file->getError());
            }
        }
        if (!$db->commitTransaction()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_SOMETHING_WENT_WRONG'));
        } else {
            FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
        }
    }

    /**
     * Delete Teacher Qualification
     */
    public function deleteTeacherQualification()
    {
        $qualificationId = FatApp::getPostedData('uqualification_id', FatUtility::VAR_INT, 0);
        if ($qualificationId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $qualification = new UserQualification($qualificationId, $this->userId);
        if (!$row = $qualification->getQualiForUpdate()) {
            FatUtility::dieJsonError($qualification->getError());
        }
        $userQualification = new UserQualification($qualificationId);
        if (true !== $userQualification->deleteRecord()) {
            FatUtility::dieJsonError($userQualification->getError());
        }
        $file = new Afile(Afile::TYPE_USER_QUALIFICATION_FILE);
        $file->removeFile($qualificationId, true);
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_QUALIFICATION_REMOVED_SUCCESSFULY'));
    }

    /**
     * Logout Guest User
     */
    public function logoutGuestUser()
    {
        TeacherRequest::closeSession();
        FatApp::redirectUser(MyUtility::makeUrl());
    }

    /**
     * Get Apply Teach Form
     *
     * @return Form
     */
    private function getApplyTeachFrm(): Form
    {
        $frm = new Form('frmApplyTeachFrm');
        $frm->addHiddenField('', 'user_id', 0);
        $fld = $frm->addEmailField(Label::getLabel('LBL_Email_ID'), 'user_email', '', ['autocomplete' => 'off']);
        $fld->setUnique('tbl_users', 'user_email', 'user_id', 'user_id', 'user_id');
        $fld = $frm->addPasswordField(Label::getLabel('LBL_Password'), 'user_password');
        $fld->requirements()->setRequired();
        $fld->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_POSITION_NONE);
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $frm->addHiddenField('', 'user_dashboard', User::TEACHER);
        $frm->addHiddenField('', 'agree', 1)->requirements()->setRequired();
        $recaptchaKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
        if (!empty($recaptchaKey)) {
            $frm->addHtml('', 'htmlNote', '<div class="g-recaptcha" data-sitekey="' . FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') . '"></div>');
        }
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_REGISTER_WITH_EMAIL'));
        return $frm;
    }

    private function getApplyToTeachFaqs()
    {
        $srch = Faq::getSearchObject($this->siteLangId, false);
        $srch->addMultipleFields(['IFNULL(faq_title, faq_identifier) as faq_title', 'faq_id', 'faq_category', 'faq_active', 'faq_description']);
        $srch->addCondition('faq_category', '=', Faq::CATEGORY_APPLY_TO_TEACH);
        $srch->joinTable(FaqCategory::DB_TBL, 'INNER JOIN', 'faqcat_id = faq_category');
        $srch->addCondition('faqcat_active', '=', AppConstant::YES);
        $srch->addCondition('faq_active', '=', AppConstant::ACTIVE);
        $srch->addOrder('faq_id', 'desc');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        return $records;
    }

    /**
     * Teacher Setup
     */
    public function teacherSetup()
    {
        $frm = $this->getTeacherSignupForm();
        $post = FatApp::getPostedData();
        $post['user_email'] = trim($post['user_email']);
        if (!isset($post['user_first_name'])) {
            $post['user_first_name'] = strstr($post['user_email'], '@', true);
        }
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!MyUtility::validatePassword($post['user_password'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC'));
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
        $db = FatApp::getDb();
        $db->startTransaction();
        $userData = array_merge($post, [
            'user_dashboard' => User::TEACHER,
            'user_registered_as' => User::TEACHER,
            'user_lang_id' => MyUtility::getSiteLangId(),
            'user_currency_id' => MyUtility::getSiteCurrId(),
            'user_timezone' => MyUtility::getSiteTimezone(),
        ]);

        $refUserId = User::getReferrerId(UserAuth::getReferal());

        if ($refUserId > 0) {
            $isAffiliate = User::isAffilate($refUserId);
            if (($isAffiliate && FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE')) ||
                (!$isAffiliate &&  FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))
            ) {
                $userData['user_referred_by'] = $refUserId;
            }
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
            FatUtility::dieJsonError(Label::getLabel("MSG_USER_COULD_NOT_BE_SET"));
        }

        $userId = $user->getMainTableRecordId();
        $user->setupMeetUser();

        if (!$user->setSettings($userData)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Label::getLabel("MSG_USER_COULD_NOT_BE_SET"));
        }
        if (!$user->setPassword($post['user_password'])) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Label::getLabel("MSG_USER_COULD_NOT_BE_SET"));
        }
        if ($refUserId > 0) {
            if (!$isAffiliate) {
                $record = new RewardPoint($userId);
                if (!$record->registerRewards()) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError(Label::getLabel("MSG_REWARDS_COULD_NOT_BE_SET"));
                }
            } else {
                $record = new User($userId);
                if (!$record->settleAffiliateSignupCommission()) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError(Label::getLabel('MSG_AFFILIATE_COMMISSION_COULD_NOT_BE_SET'));
                }
            }
        }
        if (!$db->commitTransaction()) {
            FatUtility::dieJsonError(Label::getLabel("MSG_USER_COULD_NOT_BE_SET"));
        }

        UserAuth::resetReferal();

        $userData['user_id'] = $user->getMainTableRecordId();
        $auth = new UserAuth();
        $res = $auth->sendSignupEmails($userData);
        if (
            FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION') == AppConstant::NO &&
            FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION') == AppConstant::NO &&
            FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION') == AppConstant::YES
        ) {
            if (!$auth->login($userData['user_email'], $userData['user_password'], MyUtility::getUserIp())) {
                FatUtility::dieJsonError($auth->getError());
            }
        } else {
            TeacherRequest::startSession($userData);
        }

        FatUtility::dieJsonSuccess([
            'msg' => $res['msg'] ?? Label::getLabel('LBL_REGISTERATION_SUCCESSFULL'),
            'redirectUrl' => MyUtility::makeUrl('TeacherRequest', 'form')
        ]);
    }

    /**
     * Get Teacher Signup Form
     *
     * @return Form
     */
    private function getTeacherSignupForm(): Form
    {
        $frm = new Form('signupForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addRequiredField(Label::getLabel('LBL_FIRST_NAME'), 'user_first_name');
        $frm->addTextBox(Label::getLabel('LBL_LAST_NAME'), 'user_last_name');
        $fld = $frm->addEmailField(Label::getLabel('LBL_EMAIL_ID'), 'user_email', '', ['autocomplete="off"']);
        $fld->setUnique('tbl_users', 'user_email', 'user_id', 'user_id', 'user_id');
        $fld = $frm->addPasswordField(Label::getLabel('LBL_PASSWORD'), 'user_password');
        $fld->requirements()->setRequired();
        $fld->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_POSITION_NONE);
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('MSG_Please_Enter_8_Digit_AlphaNumeric_Password'));
        $termsConditionLabel = Label::getLabel('LBL_I_accept_to_the');
        $fld = $frm->addCheckBox($termsConditionLabel, 'agree', 1);
        $fld->requirements()->setRequired();
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('MSG_Terms_and_Condition_and_Privacy_Policy_are_mandatory.'));
        $frm->addHiddenField('', 'user_dashboard', User::LEARNER);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Register'));
        return $frm;
    }
}
