<?php

/**
 * Teacher Controller is used for handling Teachers
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeacherController extends DashboardController
{

    /**
     * Initialize Teacher
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        MyUtility::setUserType(User::TEACHER);
        parent::__construct($action);
    }

    /**
     * Teacher Search Form
     */
    public function index()
    {
        $statistics = new Statistics($this->siteUserId);
        $earningData = $statistics->getEarning(MyDate::TYPE_ALL, Transaction::TYPE_TEACHER_PAYMENT);
        $sessionStats = $statistics->getSessionStats();
        $courseStats = (new OrderCourse(0, $this->siteUserId, $this->siteUserType, $this->siteLangId))->getCourseStats();
        $this->sets([
            'earnings' => $earningData['earning'] ?? 0,
            'schLessonCount' => $sessionStats['lessStats']['schLessonCount'],
            'schClassCount' => $sessionStats['classStats']['schClassCount'],
            'viewProfile' => $this->siteUser['profileProgress']['isProfileCompleted'] ?? 0,
            'durationType' => MyDate::getDurationTypesArr(),
            'userTimezone' => $this->siteTimezone,
            'walletBalance' => User::getWalletBalance($this->siteUserId),
            'courseCount' => $courseStats['totalCourses']
        ]);
		$this->zoomVerificationCheck();
        $this->_template->addJs([
            'js/moment.min.js', 'js/fullcalendar-luxon.min.js',
            'js/fateventcalendar.js', 'js/app.timer.js',
            'js/fullcalendar.min.js', 'js/fullcalendar-luxon-global.min.js',
        ]);
        $this->_template->render();
    }

    /**
     * Render Tech Lang Price Form
     */
    public function techLangPriceForm()
    {
        $userLangs = UserTeachLanguage::getUserTeachLangs($this->siteLangId, $this->siteUserId);
        $frm = $this->getLangPriceForm($userLangs);
        $this->set('userLangs', $userLangs);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Teach Lang Price
     */
    public function setupLangPrice()
    {
        $adminManagePrice = FatApp::getConfig('CONF_MANAGE_PRICES');
        $userLangs = UserTeachLanguage::getUserTeachLangs($this->siteLangId, $this->siteUserId);
        $form = $this->getLangPriceForm($userLangs);
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        if (isset($post['utlang_price'])) {
            foreach ($post['utlang_price'] as $recordID => $price) {
                $record = new UserTeachLanguage($this->siteUserId, $recordID);
                $adminPrice = FatUtility::float($userLangs[$recordID]['utlang_price']);
                $data = ['utlang_price' => $adminManagePrice ? $adminPrice : $price];
                $record->assignValues($data);
                if (!$record->save()) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError($record->getError());
                }
            }
        }
        $userSetting = new UserSetting($this->siteUserId);
        $data = ['user_slots' => json_encode($post['slots'])];
        if (!$userSetting->saveData($data)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($userSetting->getError());
        }
        $stat = new TeacherStat($this->siteUserId);
        if (!$stat->setTeachLangPrices()) {
            FatUtility::dieJsonError($stat->getError());
        }
        $teacherMeta = MetaTag::getMetaTag(MetaTag::META_GROUP_TEACHER, $this->siteUser['user_username']);
        $metaId = $teacherMeta['meta_id'] ?? 0;
        if ($metaId > 0) {
            $metaTag = new MetaTag($metaId);
            if (!$metaTag->updateTeacherMetaKeyword($this->siteUserId)) {
                FatUtility::dieJsonError($metaTag->getError());
            }
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Render Teacher Languages Form
     */
    public function teacherLanguagesForm()
    {
        $speakLangs = SpeakLanguage::getAllLangs($this->siteLangId, true);
        $profArr = SpeakLanguageLevel::getAllLangLevels($this->siteLangId, true);
        $this->sets([
            'speakLangs' => $speakLangs,
            'profArr' => $profArr,
            'profRequired' => count($profArr) > 0 ? true : false,
            'frm' => $this->getTeacherLanguagesForm($this->siteLangId, $speakLangs)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Teacher Profile Progress
     */
    public function profileProgress()
    {
        FatUtility::dieJsonSuccess(['PrfProg' => $this->siteUser['profile_progress']]);
    }

    /**
     * Setup Teacher Languages
     */
    public function setupTeacherLanguages()
    {
        $frm = $this->getTeacherLanguagesForm($this->siteLangId, []);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['teach_lang_id'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $this->validateTeachLang($post['teach_lang_id']);
        if (!isset(($post['uslang_slang_id']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SPEAK_LANGUAGE_IS_REQUIRED'));
        }
        $speakLangs = array_filter(FatUtility::int($post['uslang_slang_id']));
        if (empty($speakLangs)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SPEAK_LANGUAGE_IS_REQUIRED'));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $error = '';
        if (!$this->deleteUserTeachLang($post['teach_lang_id'], $error)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($error);
        }
        foreach ($post['teach_lang_id'] as $tlang) {
            if (empty($tlang)) {
                continue;
            }
            $userTeachLanguage = new UserTeachLanguage($this->siteUserId);
            if (!$userTeachLanguage->saveTeachLang($tlang)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($userTeachLanguage->getError());
            }
        }
        if (!$this->deleteUserSpeakLang($speakLangs, $error)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($error);
        }
        foreach ($speakLangs as $key => $lang) {
            if (empty($lang)) {
                continue;
            }
            $insertArr = ['uslang_slang_id' => $lang, 'uslang_proficiency' => $post['uslang_proficiency'][$key] ?? 0, 'uslang_user_id' => $this->siteUserId];
            if (!$db->insertFromArray(UserSpeakLanguage::DB_TBL, $insertArr, false, [], $insertArr)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($db->getError());
            }
        }
        (new TeacherStat($this->siteUserId))->setTeachLangPrices();
        (new TeacherStat($this->siteUserId))->setSpeakLang();
        $teacherMeta = MetaTag::getMetaTag(MetaTag::META_GROUP_TEACHER, $this->siteUser['user_username']);
        $metaId = $teacherMeta['meta_id'] ?? 0;
        if ($metaId > 0) {
            $metaTag = new MetaTag($metaId);
            if (!$metaTag->updateTeacherMetaKeyword($this->siteUserId)) {
                FatUtility::dieJsonError($metaTag->getError());
            }
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Will check sub language
     * @param array $teachLangIds
     * @return bool
     */
    private function validateTeachLang($teachLangIds) : bool
    {
        if($teachLangIds){
            $srch = new SearchBase(TeachLanguage::DB_TBL);
            $srch->addFld('COUNT(tlang_id) as rcount');
            $srch->addCondition('tlang_id', 'IN', $teachLangIds);
            $srch->addCondition('tlang_subcategories', '=', 0);
            $srch->addCondition('tlang_active', '=', AppConstant::ACTIVE);
            $srch->doNotCalculateRecords();
            $rowCount = FatApp::getDb()->fetch($srch->getResultSet())['rcount'] ?? 0;
            if(count($teachLangIds) != $rowCount){
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
        }
        return true;
    }

    /**
     * Render Teacher Qualification Form
     * 
     * @param int $qualifId
     */
    public function teacherQualificationForm($qualifId = 0)
    {
        $qualifId = FatUtility::int($qualifId);
        $frm = UserQualification::getForm();
        if ($qualifId > 0) {
            $uQuali = new UserQualification($qualifId, $this->siteUserId);
            if (!$row = $uQuali->getQualiForUpdate()) {
                FatUtility::dieJsonError($uQuali->getError());
            }
            $frm->fill($row);
        }
        $this->set('frm', $frm);
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
        $id = FatApp::getPostedData('uqualification_id', FatUtility::VAR_INT, 0);
        $qualification = new UserQualification($id, $this->siteUserId);
        if ($id > 0 && !$qualification->getQualiForUpdate()) {
            FatUtility::dieJsonError($qualification->getError());
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $post['uqualification_active'] = AppConstant::YES;
        $post['uqualification_user_id'] = $this->siteUserId;
        $qualification->assignValues($post);
        if (!$qualification->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($qualification->getError());
        }
        if (!empty($_FILES['certificate']['name'])) {
            $file = new Afile(Afile::TYPE_USER_QUALIFICATION_FILE);
            if (!$file->saveFile($_FILES['certificate'], $qualification->getMainTableRecordId(), true)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($file->getError());
            }
        }
        (new TeacherStat($this->siteUserId))->setQualification();
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_QUALIFICATION_SETUP_SUCCESSFUL'));
    }

    /**
     * Delete Teacher Qualification
     * 
     * @param int $id
     */
    public function deleteTeacherQualification($id = 0)
    {
        $id = FatUtility::int($id);
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $qualification = new UserQualification($id, $this->siteUserId);
        if (!$qualification->getQualiForUpdate()) {
            FatUtility::dieJsonError($qualification->getError());
        }
        if (!$qualification->deleteRecord()) {
            FatUtility::dieJsonError($qualification->getError());
        }
        $file = new Afile(Afile::TYPE_USER_QUALIFICATION_FILE);
        $file->removeFile($id, true);
        (new TeacherStat($this->siteUserId))->setQualification();
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_QUALIFICATION_REMOVED_SUCCESSFULY'));
    }

    /**
     * Teacher Qualification
     */
    public function teacherQualification()
    {
        $qualification = new UserQualification(0, $this->siteUserId);
        $this->set('qualificationData', $qualification->getUQualification(false, true));
        $this->_template->render(false, false);
    }

    /**
     * RenderTeacher Preferences Form
     */
    public function teacherPreferencesForm()
    {
        $teacherPrefArr = Preference::getUserPreferences($this->siteUserId);
        $arrOptions = [];
        foreach ($teacherPrefArr as $val) {
            $arrOptions['pref_' . $val['prefer_type']][] = $val['uprefer_prefer_id'];
        }
        $frm = $this->getTeacherPreferencesForm();
        $frm->fill($arrOptions);
        $this->set('preferencesFrm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Teacher Preferences
     */
    public function setupTeacherPreferences()
    {
        $frm = $this->getTeacherPreferencesForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$db->deleteRecords(Preference::DB_TBL_USER_PREF, ['smt' => 'uprefer_user_id = ?', 'vals' => [$this->siteUserId]])) {
            FatUtility::dieJsonError($db->getError());
        }
        $preference = 0;
        foreach ($post as $val) {
            if (empty($val)) {
                continue;
            }
            foreach ($val as $innerVal) {
                if (!$db->insertFromArray(Preference::DB_TBL_USER_PREF, ['uprefer_prefer_id' => $innerVal, 'uprefer_user_id' => $this->siteUserId])) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError($db->getError());
                }
                $preference = 1;
            }
        }
        $db->commitTransaction();
        (new TeacherStat($this->siteUserId))->setPreference($preference);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_PREFERENCES_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Render Availability Page
     */
    public function availability()
    {
        $this->_template->addJs([
            'js/moment.min.js',
            'js/fullcalendar-luxon.min.js',
            'js/fullcalendar.min.js',
            'js/fullcalendar-luxon-global.min.js',
            'js/fateventcalendar.js'
        ]);
        $this->_template->render();
    }

    /**
     * Render General Availability page
     */
    public function generalAvailability()
    {
        $this->_template->render(false, false);
    }

    /**
     * Render Weekly Availability page
     */
    public function weeklyAvailability()
    {
        $this->_template->render(false, false);
    }

    /**
     * Setup General Availability
     */
    public function setupGeneralAvailability()
    {
        $post = FatApp::getPostedData();
        $availabilityData = !empty($post['data']) ? json_decode($post['data'], true) : [];
        $availability = new Availability($this->siteUserId);
        if (!$availability->setGeneral($availabilityData)) {
            FatUtility::dieJsonError($availability->getError());
        }
        $available = empty($availabilityData) ? 0 : 1;
        (new TeacherStat($this->siteUserId))->setAvailability($available);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_AVAILABILITY_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Setup Availability
     */
    public function setupAvailability()
    {
        $start = FatApp::getPostedData('start', FatUtility::VAR_STRING, '');
        $end = FatApp::getPostedData('end', FatUtility::VAR_STRING, '');
        $availability = FatApp::getPostedData('availability', FatUtility::VAR_STRING, '');
        if (empty($start) || empty($end) || empty($availability)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $start = MyDate::formatToSystemTimezone($start);
        $end = MyDate::formatToSystemTimezone($end);
        $availability = json_decode($availability, true);
        $avail = new Availability($this->siteUserId);
        if (!$avail->setAvailability($start, $end, $availability)) {
            FatUtility::dieWithError($avail->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_AVAILABILITY_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Send Message
     * 
     * @param int $userId
     */
    public function message(int $userId)
    {
        $userDetails = User::getDetail($userId);
        if (empty($userDetails) || $userId == $this->siteUserId) {
            Message::addErrorMessage(Label::getLabel('MSG_ERROR_INVALID_ACCESS'));
            CommonHelper::redirectUserReferer();
        }
        $teacherDetails = User::getDetail($this->siteUserId);
        $this->set('teacherDetails', $teacherDetails);
        $this->set('userDetails', $userDetails);
        $this->_template->render();
    }

    /**
     * Get Tech Lang Price Form
     * 
     * @param array $langs
     * @return Form
     */
    private function getLangPriceForm(array $userLangs): Form
    {
        $frm = new Form('frmLangPrice');
        $frm = CommonHelper::setFormProperties($frm);
        foreach ($userLangs as $lang) {
            $langname = $frm->addHTML($lang['tlang_name'], 'tlang_name[' . $lang['utlang_id'] . ']', $lang['tlang_name']);
            if (empty(FatApp::getConfig('CONF_MANAGE_PRICES'))) {
                $langname->htmlAfterField = '<small>(' . str_replace(['{min}', '{max}'], [MyUtility::formatSystemMoney(max($lang['tlang_min_price'], 1)), MyUtility::formatSystemMoney($lang['tlang_max_price'])], Label::getLabel('LBL_Price_Between_{min}_AND_{max}')) . ')</small>';
            }
            $fld = $frm->addTextBox($lang['tlang_name'], 'utlang_price[' . $lang['utlang_id'] . ']', $lang['utlang_price'],
                    ['data-lang_id' => $lang['utlang_id'], 'onkeyup' => 'updatePrice(this)',
                        'placeholder' => MyUtility::formatSystemMoney('0.00')]);
            if (empty(FatApp::getConfig('CONF_MANAGE_PRICES'))) {
                $fld->requirements()->setRange(max($lang['tlang_min_price'], 1), $lang['tlang_max_price']);
                $fld->requirements()->setRequired(true);
                $fld->requirements()->setFloat();
            } else {
                $fld->addFieldTagAttribute('disabled', 'disabled');
                $fld->setFieldTagAttribute('class', 'border-0');
            }
        }
        $slotOptions = [];
        $slots = MyUtility::getActiveSlots();
        foreach ($slots as $slot) {
            $slotOptions[$slot] = str_replace('{minute}', $slot, Label::getLabel('LBL_{minute}_MINUTES'));
        }
        $selectedSlots = json_decode(User::getDetail($this->siteUserId)['user_slots'] ?? '') ?? [];
        $fld = $frm->addCheckBoxes(Label::getLabel('LBL_SLOTS'), 'slots', $slotOptions, $selectedSlots);
        $fld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        $frm->addButton('', 'nextBtn', Label::getLabel('LBL_Next'));
        $frm->addButton('', 'backBtn', Label::getLabel('LBL_Back'));
        return $frm;
    }

    /**
     * Get Teacher Languages Form
     * 
     * @param int $langId
     * @param array $spokenLangs
     * @return Form
     */
    private function getTeacherLanguagesForm(int $langId, array $spokenLangs = []): Form
    {
        $db = FatApp::getDb();
        
        /* get user teach languages */
        $userTeachLanguage = new UserTeachLanguage($this->siteUserId);
        $userToTeachLangRows = $userTeachLanguage->getUserTeachLangs($langId, $this->siteUserId);
        $userToTeachLangRows = array_column($userToTeachLangRows, 'tlang_id');
        
        /* get user speak languages */
        $userToLangSrch = new SearchBase(UserSpeakLanguage::DB_TBL);
        $userToLangSrch->addMultiplefields(['uslang_slang_id', 'uslang_proficiency']);
        $userToLangSrch->addCondition('uslang_user_id', '=', $this->siteUserId);
        $spokenLangRows = $db->fetchAllAssoc($userToLangSrch->getResultSet());
        
        /* get all teach languages with parents */
        $teachLangs = TeachLanguage::getTeachLangsRecursively($langId);

        /* get all speak languages */
        $langArr = $spokenLangs ?: SpeakLanguage::getAllLangs($langId, true);

        /* get proficiencies list */
        $profArr = SpeakLanguageLevel::getAllLangLevels($langId, true);
        
        $frm = new Form('frmTeacherLanguages');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addCheckBoxes(Label::getLabel('LBL_LANGUAGE_TO_TEACH'), 'teach_lang_id', $teachLangs, $userToTeachLangRows)->requirements()->setRequired();
        $speekLangFieldLabel = Label::getLabel('LBL_LANGUAGE_I_SPEAK');
        foreach ($langArr as $key => $lang) {
            $speekLangField = $frm->addCheckBox($speekLangFieldLabel, 'uslang_slang_id[' . $key . ']', $key, ['class' => 'uslang_slang_id'], false, 0);
            if(count($profArr) > 0) {
                $proficiencyLabel = stripslashes(Label::getLabel('LBL_I_DO_SPEAK_THIS_LANGUAGE'));
                $proficiencyFieldLabel = Label::getLabel('LBL_LANGUAGE_PROFICIENCY');
                $proficiencyField = $frm->addSelectBox($proficiencyFieldLabel, 'uslang_proficiency[' . $key . ']', $profArr, '', ['class' => 'uslang_proficiency select__dropdown'], $proficiencyLabel);
                if (array_key_exists($key, $spokenLangRows)) {
                    $proficiencyField->value = $spokenLangRows[$key];
                    $speekLangField->checked = true;
                    $speekLangField->value = $key;
                }
                $proficiencyRequired = new FormFieldRequirement($proficiencyField->getName(), $proficiencyField->getCaption());
                $proficiencyRequired->setRequired(true);
                $proficiencyOptional = new FormFieldRequirement($proficiencyField->getName(), $proficiencyField->getCaption());
                $proficiencyOptional->setRequired(false);
                $speekLangField->requirements()->addOnChangerequirementUpdate(0, 'gt', $proficiencyField->getName(), $proficiencyRequired);
                $speekLangField->requirements()->addOnChangerequirementUpdate(0, 'le', $proficiencyField->getName(), $proficiencyOptional);
            } else {
                if (array_key_exists($key, $spokenLangRows)) {
                    $speekLangField->checked = true;
                    $speekLangField->value = $key;
                }
            }
        }
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SAVE'));
        $frm->addButton('', 'next_btn', Label::getLabel('LBL_Next'));
        $frm->addButton('', 'back_btn', Label::getLabel('LBL_Back'));
        return $frm;
    }

    /**
     * Delete User Teach Lang
     * 
     * @param array $langIds
     * @param type $error
     * @return bool
     */
    private function deleteUserTeachLang(array $langIds = [], &$error = ''): bool
    {
        $teacherId = $this->siteUserId;
        $teachLangPriceQuery = 'DELETE ' . UserTeachLanguage::DB_TBL . ' FROM ' .
                UserTeachLanguage::DB_TBL . ' WHERE utlang_user_id = ' . $teacherId;
        if (!empty($langIds)) {
            $langIds = implode(',', $langIds);
            $teachLangPriceQuery .= ' and utlang_tlang_id NOT IN (' . $langIds . ')';
        }
        $db = FatApp::getDb();
        $db->query($teachLangPriceQuery);
        if ($db->getError()) {
            $error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Delete User Speak Lang
     * 
     * @param array $langIds
     * @param type $error
     * @return bool
     */
    private function deleteUserSpeakLang(array $langIds = [], &$error = ''): bool
    {
        $teacherId = $this->siteUserId;
        $query = 'DELETE  FROM ' . UserSpeakLanguage::DB_TBL . ' WHERE uslang_user_id = ' . $teacherId;
        if (!empty($langIds)) {
            $langIds = implode(',', $langIds);
            $query .= ' and uslang_slang_id NOT IN (' . $langIds . ')';
        }
        $db = FatApp::getDb();
        $db->query($query);
        if ($db->getError()) {
            $error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Teacher Preferences Form
     * 
     * @return Form
     */
    private function getTeacherPreferencesForm(): Form
    {
        $frm = new Form('teacherPreferencesFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $preferencesArr = Preference::getPreferencesArr($this->siteLangId);
        $titleArr = Preference::getPreferenceTypeArr($this->siteLangId);
        foreach ($preferencesArr as $key => $val) {
            if (empty($preferencesArr[$key])) {
                continue;
            }
            $optionsArr = array_column($preferencesArr[$key], 'prefer_title', 'prefer_id');
            if (isset($titleArr[$key])) {
                $frm->addCheckBoxes($titleArr[$key], 'pref_' . $key, $optionsArr, [], ['class' => 'list-onethird list-onethird--bg']);
            }
        }
        $frm->addButton('', 'btn_back', Label::getLabel('LBL_BACK'));
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SAVE'));
        $frm->addButton('', 'btn_next', Label::getLabel('LBL_NEXT'));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    public static function getSearchForm(): Form
    {
        $frm = new Form('frmSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'ordles_status', Lesson::SCHEDULED);
        $frm->addHiddenField('', 'ordles_lesson_starttime', date('Y-m-d H:i:s'));
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE);
        $frm->addHiddenField('', 'pageno', 1);
        $frm->addHiddenField('', 'view', AppConstant::VIEW_SHORT);
        return $frm;
    }

    /**
     * Get General Availability JSON Data
     *
     * @return void
     */
    public function generalAvblJson()
    {
        $availability = new Availability($this->siteUserId);
        FatUtility::dieJsonSuccess(['data' => $availability->getGeneral()]);
    }

    /**
     * Get General Availability JSON Data
     *
     * @return void
     */
    public function avalabilityJson()
    {
        $start = FatApp::getPostedData('start', FatUtility::VAR_STRING, '');
        $end = FatApp::getPostedData('end', FatUtility::VAR_STRING, '');
        if (empty($start) || empty($end)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $start = MyDate::formatToSystemTimezone($start);
        $end = MyDate::formatToSystemTimezone($end);
        $availability = new Availability($this->siteUserId);
        FatUtility::dieJsonSuccess(['data' => $availability->getAvailability($start, $end)]);
    }

    public function downloadQualification(int $id)
    {
        $userId = UserQualification::getAttributesById($id, 'uqualification_user_id');
        if ($userId != $this->siteUserId) {
            FatUtility::dieWithError(Label::getLabel('LBL_Access_Denied'));
        }
        $file = new Afile(Afile::TYPE_USER_QUALIFICATION_FILE, 0);
        $file->downloadByRecordId($id);
    }

}
