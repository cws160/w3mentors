<?php

/**
 * Packages Controller is used for handling Packages
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PackagesController extends DashboardController
{

    public function __construct($action)
    {
        parent::__construct($action);
        if (!GroupClass::isEnabled()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_GROUP_CLASS_MODULE_NOT_AVAILABLE'));
            }
            FatUtility::exitWithErrorCode(404);
        }
    }

    public function index()
    {

        $frm = PackageSearch::getSearchForm();
        $frm->fill(FatApp::getQueryStringData());
        $this->set('frm', $frm);
        if (API_CALL) {
            $this->_template->render(false, false);
        } else {
            $this->_template->addJs(['js/jquery.datetimepicker.js', 'js/moment.min.js', 'js/translate.fill.js']);
            $this->_template->render();
        }
    }

    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = PackageSearch::getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new PackageSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        if ($this->siteUserType == User::LEARNER) {
            $srch->addFld('orders.order_addedon AS order_addedon');
        }
        $srch->addSearchListingFields();
        if ($this->siteUserType == User::LEARNER || $this->siteUserType == User::SUPPORT) {
            $srch->addFld('order_addedon');
            $srch->addOrder('ordpkg.ordpkg_order_id', 'DESC');
        } else {
            $srch->addOrder('grpcls_id', 'DESC');
        }
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $rows = $srch->fetchAndFormat();
        if (API_CALL) {
            $classCounts = GroupClassSearch::getSubClassesCounts(array_column($rows, 'grpcls_id'));
            $this->set('classCounts', $classCounts);
        }
        $this->sets([
            'post' => $post,
            'packages' => $rows,
            'recordCount' => $srch->recordCount(),
        ]);
        $this->_template->render(false, false);
    }

    public function view($packageId = 0)
    {
        if (!API_CALL || empty($packageId)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langId = $this->siteLangId;
        $userId = $this->siteUserId;
        $userType = $this->siteUserType;
        $srch = new PackageSearch($langId, $userId, $userType);
        $srch->applySearchConditions(['ordpkg_id' => $packageId]);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $rows = $srch->fetchAndFormat();
        if (empty($rows)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $package = current($rows);
        $srch = new ClassSearch($langId, $userId, $userType);
        $srch->applySearchConditions([
            'order_id' => $package['ordpkg_order_id'],
            'package_id' => $package['ordpkg_package_id']
        ]);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        $this->sets([
            'package' => $package,
            'subclasses' => $srch->fetchAndFormat(),
        ]);
        $this->_template->render(false, false);
    }

    public function form()
    {
        $packageId = FatApp::getPostedData('packageId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm();
        if ($packageId > 0) {
            $groupClass = new GroupClass($packageId, $this->siteUserId, $this->siteUserType);
            if (!$record = $groupClass->getPackageToSave()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $file = new Afile(Afile::TYPE_GROUP_CLASS_BANNER);
            $this->set('banner', $file->getFile($packageId));
            $frm->fill($record);
        }
        $classes = PackageSearch::getClasses($packageId);
        $timeFormat = MyDate::getFormatTime();
        foreach ($classes as $key => $class) {
            $class['grpcls_end_datetime'] = MyDate::formatDate(MyDate::formatToSystemTimezone($class['grpcls_end_datetime']), 'Y-m-d ' . $timeFormat);
            $class['grpcls_start_datetime'] = MyDate::formatDate(MyDate::formatToSystemTimezone($class['grpcls_start_datetime']), 'Y-m-d ' . $timeFormat);
            $classes[$key] = $class;
        }
        $this->set('frm', $frm);
        $this->set('packageId', $packageId);
        $this->set('isofflineEnabled', User::offlineSessionsEnabled($this->siteUserId));
        $this->set('classes', $classes);
        $this->_template->render(false, false);
    }

    public function setup()
    {
        $form = $this->getForm(true);
        $post = FatApp::getPostedData() + $_FILES;
        $post['grpcls_slug'] = MyUtility::createSlug($post['grpcls_slug'] ?? '');
        if (!$post = $form->getFormDataFromArray($post, ['grpcls_address_id', 'grpcls_offline'])) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $packageId = FatUtility::int($post['grpcls_id']);
        if ($packageId > 0) {
            $groupClass = new GroupClass($packageId, $this->siteUserId, $this->siteUserType);
            if (!$record = $groupClass->getPackageToSave()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
        }
        $isOfflineEnabled = User::offlineSessionsEnabled($this->siteUserId);
        if ($post['grpcls_offline'] == AppConstant::YES && $isOfflineEnabled == AppConstant::NO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_OFFLINE_CLASSES_MODULE_HAS_BEEN_DISABLED'));
        }
        if ($isOfflineEnabled == AppConstant::NO) {
            $post['grpcls_address_id'] = $post['grpcls_offline'] = 0;
        }
        if ($post['grpcls_address_id'] > 0) {
            $userAddress = new UserAddresses($this->siteUserId, $post['grpcls_address_id']);
            if (!$userAddress->getAddressById($this->siteLangId)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ADDRESS_NOT_FOUND'));
            }
        }
        $datesArr = [];
        foreach($post['starttime'] as $startTime) {
            $datesArr[] = date('Y-m-d H:i:s', strtotime($startTime));
        }
        $mindate = MyDate::formatToSystemTimezone(min($datesArr));
        if (time() > strtotime($mindate)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_START_TIME_MUST_WE_GRATHER_CURRENT_TIME'));
        }
        $maxdate = MyDate::formatToSystemTimezone(max($datesArr));
        $maxdate = strtotime($maxdate . " +" . $post['grpcls_duration'] . ' minutes');
        $package = array_merge($post, [
            'grpcls_parent' => 0,
            'grpcls_end_datetime' => date("Y-m-d H:i:s", $maxdate),
            'grpcls_start_datetime' => $mindate,
            'grpcls_duration' => $post['grpcls_duration'],
            'grpcls_type' => GroupClass::TYPE_PACKAGE,
            'grpcls_status' => GroupClass::SCHEDULED,
            'grpcls_teacher_id' => $this->siteUserId,
        ]);
        /* Insert|Updated Package Data */
        $db = FatApp::getDb();
        $db->startTransaction();
        $record = new GroupClass($packageId, $this->siteUserId, User::TEACHER);
        $record->assignValues($package);
        if (!$record->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($record->getError());
        }
        $packageNewId = $record->getMainTableRecordId();
        if (!empty($post['grpcls_banner']['name'])) {
            $file = new Afile(Afile::TYPE_GROUP_CLASS_BANNER);
            if (!$file->saveFile($post['grpcls_banner'], $packageNewId, true)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($file->getError());
            }
        }
        if (!$record->addMetaTags($packageId, $package)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($record->getError());
        }
        /* Delete Package Classes Data  */
        $stmt = ['smt' => 'grpcls_parent = ?', 'vals' => [$packageNewId]];
        if (!$db->deleteRecords(GroupClass::DB_TBL, $stmt)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($db->getError());
        }
        /* Insert|Update Package Classes Data  */
        $classes = [];
        $classCount = count($post['title']);
        $avail = new Availability($this->siteUserId);
        foreach ($post['title'] as $key => $title) {
            $slug = $post['grpcls_slug'] . '-' . $key;
            $starttime = $post['starttime'][$key];
            $starttime = MyDate::formatToSystemTimezone($post['starttime'][$key]);
            $endtime = date("Y-m-d H:i:s", strtotime($starttime . ' +' . $post['grpcls_duration'] . ' minutes'));
            if (!$avail->isUserAvailable($starttime, $endtime, $key)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($avail->getError());
            }
            $class = [
                'grpcls_title' => $title,
                'grpcls_slug' => $slug,
                'grpcls_parent' => $packageNewId,
                'grpcls_end_datetime' => $endtime,
                'grpcls_start_datetime' => $starttime,
                'grpcls_teacher_id' => $this->siteUserId,
                'grpcls_status' => GroupClass::SCHEDULED,
                'grpcls_type' => GroupClass::TYPE_REGULAR,
                'grpcls_tlang_id' => $post['grpcls_tlang_id'],
                'grpcls_duration' => $post['grpcls_duration'],
                'grpcls_total_seats' => $post['grpcls_total_seats'],
                'grpcls_entry_fee' => $post['grpcls_entry_fee'] / $classCount,
                'grpcls_added_on' => date('Y-m-d H:i:s'),
                'grpcls_offline' => $post['grpcls_offline'],
                'grpcls_address_id' => $post['grpcls_address_id'],
            ];
            if ($packageId > 0) {
                $class['grpcls_id'] = $key;
            }
            $record = new TableRecord(GroupClass::DB_TBL);
            $record->assignValues($class);
            if (!$record->addNew([], $class)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($record->getError());
            }
            if (0 >= $packageId) {
                $class['grpcls_id'] = $record->getId();
            }
            if (0 == $packageId) {
                $thread = new Thread(0);
                if (!$thread->setupGroup($this->siteUserId, $record->getId())) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError($thread->getError());
                }
            }
            $classes[] = $class;
            Meeting::checkLicense($class['grpcls_start_datetime'], $class['grpcls_end_datetime']);
        }
        $db->commitTransaction();
        $languages = Language::getCodes();
        $newTabLangId = 0;
        foreach ($languages as $id => $code) {
            if (empty(GroupClass::getAttrByLangId($id, $packageNewId))) {
                $newTabLangId = $id;
                break;
            }
        }
        $this->addGoogleEvents($classes, $packageId);
        FatUtility::dieJsonSuccess([
            'packageId' => $packageNewId,
            'langId' => $newTabLangId,
            'msg' => Label::getLabel('LBL_PACKAGE_SETUP_SUCCESSFULLY')
        ]);
    }

    public function langForm()
    {
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        $packageId = FatApp::getPostedData('packageId', FatUtility::VAR_INT, 0);
        if (empty($langId) || empty($packageId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $groupClassObj = new GroupClass($packageId, $this->siteUserId, $this->siteUserType);
        if (!$groupClass = $groupClassObj->getPackageToSave($langId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($packageId, $langId);
        if ($langData = GroupClass::getAttrByLangId($langId, $packageId)) {
            $langFrm->fill($langData);
        } else {
            $groupClass['gclang_lang_id'] = $langId;
            $groupClass['gclang_grpcls_id'] = $packageId;
            $langFrm->fill($groupClass);
        }
        $this->set('frm', $langFrm);
        $this->set('tabLangId', $langId);
        $this->set('packageId', $packageId);
        $this->set('classes', PackageSearch::getClasses($packageId, $langId));
        $this->set('languages', Language::getAllNames(false));
        $this->_template->render(false, false);
    }

    public function setupLang()
    {
        $post = FatApp::getPostedData();
        $packageId = FatUtility::int($post['gclang_grpcls_id']);
        $langId = FatUtility::int($post['gclang_lang_id']);
        $form = $this->getLangForm($packageId, $langId);
        if (!$post = $form->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        /* Insert|Update Package Language Data */
        $db = FatApp::getDb();
        $db->startTransaction();
        $record = new GroupClass($packageId, $this->siteUserId, $this->siteUserType);
        if (!$record->saveLangData($post)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($record->getError());
        }

        $translator = new Translator($this->siteLangId);
        if (!$translator->validateAndTranslate(GroupClass::DB_TBL_LANG, $packageId, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }

        /* Insert|Update Package Classes Language Data */
        foreach ($post['title'] as $key => $title) {
            $data = [
                'gclang_lang_id' => $post['gclang_lang_id'],
                'grpcls_title' => $title,
                'gclang_grpcls_id' => $key
            ];
            $record = new TableRecord(GroupClass::DB_TBL_LANG);
            $record->assignValues($data);
            if (!$record->addNew([], $data)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($record->getError());
            }
            $data['update_langs_data'] = $post['update_langs_data'] ?? 0;
            if (!$translator->validateAndTranslate(GroupClass::DB_TBL_LANG, $key, $data)) {
                FatUtility::dieJsonError($translator->getError());
            }
        }
        $db->commitTransaction();
        $languages = Language::getCodes();
        $newTabLangId = 0;
        foreach ($languages as $id => $code) {
            if (empty(GroupClass::getAttrByLangId($id, $packageId))) {
                $newTabLangId = $id;
                break;
            }
        }
        FatUtility::dieJsonSuccess([
            'packageId' => $packageId,
            'langId' => $newTabLangId,
            'msg' => Label::getLabel('LBL_PACKAGE_SETUP_SUCCESSFULLY')
        ]);
    }

    public function cancelSetup()
    {
        $packageId = FatApp::getPostedData('packageId', FatUtility::VAR_INT, 0);
        if ($this->siteUserType == User::LEARNER) {
            $class = new OrderPackage(0, $this->siteUserId, $this->siteUserType);
            $record = $class->cancelPackage($packageId, $this->siteLangId);
        } else {
            $class = new GroupClass($packageId, $this->siteUserId, $this->siteUserType);
            $record = $class->cancelPackage();
        }
        if (!$record) {
            FatUtility::dieJsonError($class->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel("LBL_PACKAGE_CANCELLED_SUCCESSFULLY!"));
    }

    private function getForm(bool $setUnique = false)
    {
        $userTeachLangs = (new UserTeachLanguage($this->siteUserId))->getUserTechLandIds();
        $userTeachLangs = !empty($userTeachLangs) ? $userTeachLangs : [0];
        $teachLangData = TeachLanguage::getNames($this->siteLangId, $userTeachLangs);
        $form = new Form('frmPackage');
        $form = CommonHelper::setFormProperties($form);
        $form->addHiddenField('', 'grpcls_id')->requirements()->setIntPositive();
        $fld = $form->addRequiredField(Label::getLabel('LBL_TITLE'), 'grpcls_title');
        $fld->requirements()->setLength(10, 100);
        $fld = $form->addTextBox(Label::getLabel('LBL_SLUG'), 'grpcls_slug');
        if ($setUnique) {
            $fld->setUnique(GroupClass::DB_TBL, 'grpcls_slug', 'grpcls_id', 'grpcls_id', 'grpcls_id');
        }
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setLength(10, 100);
        $form->addFileUpload(Label::getLabel('LBL_CLASS_BANNER'), 'grpcls_banner');
        $fld = $form->addTextArea(Label::getLabel('LBL_DESCRIPTION'), 'grpcls_description');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setLength(10, 1000);
        $fld = $form->addSelectBox(Label::getLabel('LBL_LANGUAGE'), 'grpcls_tlang_id', $teachLangData, '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $fld = $form->addIntegerField(Label::getLabel('LBL_MAX_LEARNERS'), 'grpcls_total_seats');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setRange(1, FatApp::getConfig('CONF_GROUP_CLASS_MAX_LEARNERS'));
        $currencyCode = MyUtility::getSystemCurrency()['currency_code'];
        $fld = $form->addFloatField(str_replace('{currency}', $currencyCode, Label::getLabel('LBL_ENTRY_FEE_[{currency}]')), 'grpcls_entry_fee');
        $fld->requirements()->setPositive(true);
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setRange(1, 9999999999);
        if (User::offlineSessionsEnabled($this->siteUserId)) {
            $fld = $form->addSelectBox(Label::getLabel('LBL_SERVICE_TYPE'), 'grpcls_offline', AppConstant::getServiceType(), '', [], '');
            $fld->requirements()->setRequired();
            $usrAddress = new UserAddresses($this->siteUserId);
            $addresses = $usrAddress->getoptions($this->siteLangId);
            $addressField = $form->addSelectBox(Label::getLabel('LBL_ADDRESSES'), 'grpcls_address_id', $addresses, '', [], Label::getLabel('LBL_SELECT'));
            $addressFieldOptional = new FormFieldRequirement($addressField->getName(), $addressField->getCaption());
            $addressFieldOptional->setRequired(false);
            $addressFieldRequire = new FormFieldRequirement($addressField->getName(), $addressField->getCaption());
            $addressFieldRequire->setRequired(true);
            $fld->requirements()->addOnChangerequirementUpdate(AppConstant::NO, 'eq', $addressField->getName(), $addressFieldOptional);
            $fld->requirements()->addOnChangerequirementUpdate(AppConstant::YES, 'eq', $addressField->getName(), $addressFieldRequire);
        } else {
            $form->addHiddenField('', 'grpcls_offline', AppConstant::NO);
        }
        $fld = $form->addSelectBox(Label::getLabel('LBL_EACH_CLASS_(MINUTES)'), 'grpcls_duration', AppConstant::fromatClassSlots(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $form->addRequiredField(Label::getLabel('LBL_CLASS_TITLE'), 'title[]')->requirements()->setLength(10, 100);
        $starttime = $form->addRequiredField(Label::getLabel('LBL_START_TIME'), 'starttime[]', '', ['class' => 'datetime', 'autocomplete' => 'off', 'readonly' => 'readonly']);
        $form->addSubmitButton('', 'submit', Label::getLabel('LBL_SAVE_&_NEXT'));
        return $form;
    }

    private function getLangForm($packageId, $langId)
    {
        $frm = new Form('frmPackageLang');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'gclang_grpcls_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setIntPositive(true);
        $fld = $frm->addHiddenField('', 'gclang_lang_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setIntPositive(true);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_TITLE', $langId), 'grpcls_title');
        $fld->requirements()->setLength(10, 100);
        $fld = $frm->addTextArea(Label::getLabel('LBL_DESCRIPTION', $langId), 'grpcls_description');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setLength(10, 1000);
        $frm->addRequiredField(Label::getLabel('LBL_CLASS_TITLE', $langId), 'title[]')->requirements()->setLength(10, 100);
        Translator::addTranslatorActions($frm, $langId, $packageId, GroupClass::DB_TBL_LANG, $this->siteLangId);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_&_NEXT', $langId));
        return $frm;
    }

    private function addGoogleEvents(array $classes, int $packageId)
    {
        $googleEvents = [];
        if ($packageId > 0) {
            $classIds = array_column($classes, 'grpcls_id');
            $googleEvents = $this->getEvents($classIds);
        }
        $token = (new UserSetting($this->siteUserId))->getGoogleToken();
        foreach ($classes as $key => $class) {
            $googleCalendar = new GoogleCalendarEvent($this->siteUserId, $class['grpcls_id'], AppConstant::GCLASS);
            if (!empty($googleEvents[$class['grpcls_id']])) {
                $googleCalendar->deletEvent($token, $googleEvents[$class['grpcls_id']]['gocaev_event_id']);
            }
            $class['google_token'] = $token;
            $class['grpcls_description'] = '';
            $googleCalendar->addClassEvent($class, User::TEACHER);
        }
    }

    private function getEvents(array $recordIds)
    {
        if (empty($recordIds)) {
            return [];
        }
        $srch = new SearchBase(GoogleCalendarEvent::DB_TBL, 'gocaev');
        $srch->addMultipleFields(['gocaev.*']);
        $srch->addCondition('gocaev.gocaev_record_id', 'IN', $recordIds);
        $srch->addCondition('gocaev.gocaev_record_type', '=', AppConstant::GCLASS);
        $srch->addCondition('gocaev.gocaev_user_id', '=', $this->siteUserId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'gocaev_record_id');
    }
}
