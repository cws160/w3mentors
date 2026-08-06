<?php

/**
 * Configurations Controller is used for Configurations handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ConfigurationsController extends AdminBaseController
{
    /* these variables must be only those which will store array type data and will saved as serialized array [ */

    private $serializeArrayValues = [];

    /* ] */

    /**
     * Initialize Configurations
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->set("includeEditor", true);
        $this->objPrivilege->canViewGeneralSettings();
    }

    public function index()
    {
        $activeTab = FatApp::getQueryStringData('tab', FatUtility::VAR_INT, Configurations::FORM_GENERAL_SETTINGS);
        $tabs = Configurations::getTabs();
        $activeTab = (!empty($tabs[$activeTab])) ? $activeTab : Configurations::FORM_GENERAL_SETTINGS;
        $this->sets(['activeTab' => $activeTab, 'tabs' => $tabs]);
        $this->_template->render();
    }

    /**
     * Render Configuration Form
     * 
     * @param type $frmType
     */
    public function form($frmType)
    {
        $frmType = FatUtility::int($frmType);
        if (in_array($frmType, Configurations::getLangTypeForms())) {
            $this->set('languages', Language::getAllNames());
        }
        $record = Configurations::getConfigurations();
        if (($frmType == Configurations::FORM_DASHBOARD_LESSONS || $frmType == Configurations::FORM_DASHBOARD_CLASSES)) {
            $record['CONF_PAID_LESSON_DURATION'] = explode(',', $record['CONF_PAID_LESSON_DURATION']);
            $record['CONF_GROUP_CLASS_DURATION'] = explode(',', $record['CONF_GROUP_CLASS_DURATION']);
        }
        if ($frmType == Configurations::FORM_PWA_SETTINGS) {
            if (!empty($record['CONF_PWA_SETTINGS'])) {
                $record = [
                    'pwa_settings' => json_decode($record['CONF_PWA_SETTINGS'], true),
                    'CONF_ENABLE_PWA' => $record['CONF_ENABLE_PWA']
                ];
            }
            $this->sets(['iconData' => (new Afile(Afile::TYPE_PWA_APP_ICON))->getFile()]);
        }
        if ($frmType == Configurations::FORM_REFERRAL_SETTINGS) {
            $record = [
                'CONF_ENABLE_REFERRAL_REWARDS' => $record['CONF_ENABLE_REFERRAL_REWARDS'],
                'CONF_REWARD_POINT_MULTIPLIER' => $record['CONF_REWARD_POINT_MULTIPLIER'],
                'CONF_REWARD_POINT_MINIMUM_USE' => $record['CONF_REWARD_POINT_MINIMUM_USE'],
                'CONF_REFERRER_REGISTER_REWARDS' => $record['CONF_REFERRER_REGISTER_REWARDS'],
                'CONF_REFERENT_REGISTER_REWARDS' => $record['CONF_REFERENT_REGISTER_REWARDS'],
                'CONF_REFERRER_PURCHASE_REWARDS' => $record['CONF_REFERRER_PURCHASE_REWARDS'],
                'CONF_REFERENT_PURCHASE_REWARDS' => $record['CONF_REFERENT_PURCHASE_REWARDS'],
            ];
        }
        $frm = $this->getForm($frmType);
        $frm->fill($record);
        if ($frmType == Configurations::FORM_THIRD_PARTY_APIS) {
            $google = new Google();
            $this->sets([
                'accessToken' => $google->getGoogleAuthToken(),
                'isGoogleAuthSet' => ($google->getClient() !== false)
            ]);
        }
        $disableFormType = [Configurations::FORM_THIRD_PARTY_APIS, Configurations::FORM_SEO_AND_GOOGLE_TAGS, Configurations::FORM_DASHBOARD_COURSES];
        if (in_array($frmType, $disableFormType) && MyUtility::isDemoUrl()) {
            MyUtility::maskAndDisableFormFields($frm, ['CONF_SITE_TRACKER_CODE', 'CONF_COURSE_CANCEL_DURATION', 'CONF_COURSE_DEFAULT_CANCELLATION_STATUS']);
        }
        $this->sets([
            'frm' => $frm,
            'canEdit' => $this->objPrivilege->canEditGeneralSettings(true),
            'frmType' => $frmType,
            'lang_id' => 0,
            'formLayout' => '',
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Configuration Lang Form
     * 
     * @param int $frmType
     * @param int $langId
     * @param int $tabId
     */
    public function langForm($frmType, $langId, $tabId = null)
    {

        $frmType = FatUtility::int($frmType);
        $langId = FatUtility::int($langId);
        $frm = $this->getLangForm($frmType, $langId);
        if (in_array($frmType, Configurations::getLangTypeForms())) {
            $this->set('languages', Language::getAllNames());
        }
        if ($frmType == Configurations::FORM_MEDIA_AND_LOGOS) {
            $getFiles = $this->getFiles($langId);
            $this->set('mediaData', $getFiles);
        }
        $record = Configurations::getConfigurations();
        $frm->fill($record);
        if ($tabId) {
            $this->set('tabId', $tabId);
        }
        $this->set('frm', $frm);
        $this->set('lang_id', $langId);
        $this->set('frmType', $frmType);
        $this->set('languages', Language::getAllNames());
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('canEdit', $this->objPrivilege->canEditGeneralSettings(true));
        $this->_template->render(false, false, 'configurations/form.php');
    }

    /**
     * Get Media Files
     * 
     * @param int $langId
     * @return array
     */
    private function getFiles(int $langId): array
    {
        $searchBase = new SearchBase(Afile::DB_TBL);
        $searchBase->doNotCalculateRecords();
        $searchBase->addMultipleFields(['file_type', 'file_lang_id', 'file_id']);
        $searchBase->addCondition('file_type', 'IN', $this->getConfMediaType());
        $searchBase->addCondition('file_lang_id', '=', $langId);
        $searchBase->addCondition('file_path', '!=', '');
        return FatApp::getDb()->fetchAll($searchBase->getResultSet(), 'file_type');
    }

    /**
     * Setup Configuration
     */
    public function setup()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $post = FatApp::getPostedData();
        $frmType = FatApp::getPostedData('form_type', FatUtility::VAR_INT, 0);
        if (1 > $frmType) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm($frmType);
        $post = $frm->getFormDataFromArray($post, ['CONF_COUNTRY', 'CONF_MUX_RESOLUTION']);
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (array_key_exists('CONF_COUNTRY', $post)) {
            $country = Country::getAttributesById($post['CONF_COUNTRY'], ['country_active']);
            if ($country['country_active'] == AppConstant::NO) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COUNTRY_IS_INACTIVE', $this->siteLangId));
            }
        }
        $disableFormType = [Configurations::FORM_THIRD_PARTY_APIS, Configurations::FORM_SEO_AND_GOOGLE_TAGS];
        if (MyUtility::isDemoUrl() && in_array($frmType, $disableFormType)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_CHANGE_SETTINGS'));
        }
        if ($frmType == Configurations::FORM_DASHBOARD_COURSES) {
            if (MyUtility::isDemoUrl()) {
                unset($post['CONF_VIDEO_CIPHER_API_KEY']);
                unset($post['CONF_VIDEO_CIPHER_FOLDER_ID']);
                unset($post['CONF_MUX_ACCESS_TOKEN_ID']);
                unset($post['CONF_MUX_SECRET_KEY']);
                unset($post['CONF_MUX_ENCODING_TIER']);
                unset($post['CONF_MUX_RESOLUTION']);
            }
            if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_MUX && !in_array($post['CONF_MUX_RESOLUTION'], Mux::getResolutionsArr($post['CONF_MUX_ENCODING_TIER']))) {
                FatUtility::dieJsonError(Label::getLabel('LBL_RESOLUTION_NOT_ALLOWED_WITH_SELECTED_ENCODING_TYPE', $this->siteLangId));
            }
        }
        if ($frmType == Configurations::FORM_SEO_AND_GOOGLE_TAGS && MyUtility::isDemoUrl()) {
            unset($post['CONF_SITE_TRACKER_CODE']);
        }
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($post['form_type']);
        unset($post['btn_submit']);
        foreach ($this->serializeArrayValues as $val) {
            if (array_key_exists($val, $post)) {
                if (is_array($post[$val])) {
                    $post[$val] = serialize($post[$val]);
                }
            } else {
                if (isset($post[$val])) {
                    $post[$val] = 0;
                }
            }
        }

        $record = new Configurations();
        if ($frmType == Configurations::FORM_PWA_SETTINGS) {
            if (!empty($_FILES['icon']['name'])) {
                $file = new Afile(Afile::TYPE_PWA_APP_ICON);
                if (!$file->saveFile($_FILES['icon'], 0, true)) {
                    FatUtility::dieJsonError($file->getError());
                }
            }
            $post = ['CONF_PWA_SETTINGS' => json_encode($post['pwa_settings']), 'CONF_ENABLE_PWA' => $post['CONF_ENABLE_PWA']];
        }
        if ($frmType == Configurations::FORM_COMMON_SETTINGS) {
            if ($post['CONF_EMAIL_VERIFICATION_REGISTRATION'] || $post['CONF_ADMIN_APPROVAL_REGISTRATION']) {
                $post['CONF_AUTO_LOGIN_REGISTRATION'] = 0;
            }
            if ($post['CONF_ENABLE_SUBSCRIPTION_PLAN']) {
                $post['CONF_MANAGE_PRICES'] = AppConstant::MANAGE_PRICE_ADMIN;
            }

            if (FatApp::getConfig('CONF_ENABLE_SUBSCRIPTION_PLAN')) {
                unset($post['CONF_ENABLE_SUBSCRIPTION_PLAN']);
                unset($post['CONF_MANAGE_PRICES']);
            }
        }
        if ($frmType == Configurations::FORM_REFERRAL_SETTINGS) {
            $post = [
                'CONF_ENABLE_REFERRAL_REWARDS' => $post['CONF_ENABLE_REFERRAL_REWARDS'],
                'CONF_REWARD_POINT_MULTIPLIER' => $post['CONF_REWARD_POINT_MULTIPLIER'],
                'CONF_REWARD_POINT_MINIMUM_USE' => $post['CONF_REWARD_POINT_MINIMUM_USE'],
                'CONF_REFERRER_REGISTER_REWARDS' => $post['CONF_REFERRER_REGISTER_REWARDS'],
                'CONF_REFERENT_REGISTER_REWARDS' => $post['CONF_REFERENT_REGISTER_REWARDS'],
                'CONF_REFERRER_PURCHASE_REWARDS' => $post['CONF_REFERRER_PURCHASE_REWARDS'],
                'CONF_REFERENT_PURCHASE_REWARDS' => $post['CONF_REFERENT_PURCHASE_REWARDS'],
            ];
        }
        $msg = '';
        if (
            isset($post["CONF_SEND_SMTP_EMAIL"]) &&
            $post["CONF_SEND_EMAIL"] && $post["CONF_SEND_SMTP_EMAIL"] &&
            (
                ($post["CONF_SEND_SMTP_EMAIL"] != FatApp::getConfig("CONF_SEND_SMTP_EMAIL")) ||
                ($post["CONF_SMTP_HOST"] != FatApp::getConfig("CONF_SMTP_HOST")) ||
                ($post["CONF_SMTP_PORT"] != FatApp::getConfig("CONF_SMTP_PORT")) ||
                ($post["CONF_SMTP_USERNAME"] != FatApp::getConfig("CONF_SMTP_USERNAME")) ||
                ($post["CONF_SMTP_SECURE"] != FatApp::getConfig("CONF_SMTP_SECURE")) ||
                ($post["CONF_SMTP_PASSWORD"] != FatApp::getConfig("CONF_SMTP_PASSWORD"))
            )
        ) {
            $mail = new FatMailer($this->siteLangId, 'test_email');
            $saved = $mail->sendSmtpTestEmail($post);
            if ($saved) {
                if (!$record->update($post)) {
                    FatUtility::dieJsonError($record->getError());
                }
                FatUtility::dieJsonSuccess(Label::getLabel('LBL_WE_HAVE_SENT_A_TEST_EMAIL_TO_ADMINISTRATOR_ACCOUNT_' . FatApp::getConfig("CONF_SITE_OWNER_EMAIL")));
            } else {
                FatUtility::dieJsonError(Label::getLabel("LBL_SMTP_settings_provided_is_invalid_or_unable_to_send_email_so_we_have_not_saved_SMTP_settings"));
            }
        }
        if (isset($post['CONF_USE_SSL']) && $post['CONF_USE_SSL'] == 1) {
            if (!$this->is_ssl_enabled()) {
                if ($post['CONF_USE_SSL'] != FatApp::getConfig('CONF_USE_SSL')) {
                    FatUtility::dieJsonError(Label::getLabel('MSG_SSL_NOT_INSTALLED_FOR_WEBSITE_Try_to_Save_data_without_Enabling_ssl'));
                }
                unset($post['CONF_USE_SSL']);
            }
        }

        $unselectedSlot = [];
        if (array_key_exists('CONF_PAID_LESSON_DURATION', $post)) {
            $bookingSlots = FatApp::getConfig('CONF_PAID_LESSON_DURATION');
            $bookingSlots = explode(',', $bookingSlots);
            $unselectedSlot = array_diff($bookingSlots, $post['CONF_PAID_LESSON_DURATION']);
            if (!empty($unselectedSlot)) {
                $slotInUse = UserSetting::findInSlots($unselectedSlot);
                if ($slotInUse) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_SLOTS_REMOVED_ARE_ALREADY_IN_USE'));
                }
            }
            $post['CONF_PAID_LESSON_DURATION'] = implode(',', $post['CONF_PAID_LESSON_DURATION']);
        }
        if (array_key_exists('CONF_GROUP_CLASS_DURATION', $post)) {
            $post['CONF_GROUP_CLASS_DURATION'] = implode(',', $post['CONF_GROUP_CLASS_DURATION']);
        }
        if (array_key_exists('CONF_DEFAULT_RADIUS_FOR_SEARCH', $post)) {
            if ($post['CONF_DEFAULT_RADIUS_FOR_SEARCH'] < 1) {
                FatUtility::dieJsonError(Label::getLabel('LBL_RADIUS_SHOULD_BE_GREATER_THAN_ZERO'));
            }
        }
        if (($frmType == Configurations::FORM_GENERAL_SETTINGS)) {
            $post['FRONTEND_TIME_FORMAT_PHP'] = 'H:i:s';
            $post['FRONTEND_TIME_FORMAT_JS'] = 'HH:mm:ss';
            if ($post['FRONTEND_TIME_FORMAT'] == MyDate::FORMAT_12_HR) {
                $post['FRONTEND_TIME_FORMAT_PHP'] = 'h:i:s A';
                $post['FRONTEND_TIME_FORMAT_JS'] = 'hh:mm:ss A';
            }
        }
        if ($frmType == Configurations::FORM_THIRD_PARTY_APIS) {
            if ($post['CONF_GOOGLE_CLIENT_JSON'] != FatApp::getConfig('CONF_PAID_LESSON_DURATION', FatUtility::VAR_STRING, '')) {
                $post['GOOGLE_AUTH_TOKEN'] = '';
            }
        }
        if (isset($post['CONF_ENABLE_SUBSCRIPTION_PLAN']) && $post['CONF_ENABLE_SUBSCRIPTION_PLAN'] == AppConstant::ACTIVE) {
            /* add new menu */
            $navLinkObj = new NavigationLinks();
            $navLinkObj->assignValues([
                'nlink_nav_id' => 2,
                'nlink_identifier' => 'Subscription Plans',
                'nlink_target' => '_self',
                'nlink_type' => NavigationLinks::NAVLINK_TYPE_EXTERNAL_PAGE,
                'nlink_url' => '{domain}subscription-plans',
                'nlink_order' => 6
            ]);
            $navLinkObj->save();
        }
        if (!$record->update($post)) {
            FatUtility::dieJsonError($record->getError());
        }
        if (array_key_exists('CONF_SITE_CURRENCY', $post)) {
            $siteCurrency = Currency::getData($post['CONF_SITE_CURRENCY'], $this->siteLangId);
            MyUtility::setSiteCurrency($siteCurrency, true);
        }
        $data = [
            'msg' => $msg ?: Label::getLabel('MSG_Setup_Successful'),
            'frmType' => $frmType,
            'langId' => 0
        ];
        Fatutility::dieJsonSuccess($data);
    }

    /**
     * Is SSL Enabled
     * 
     * @return boolean
     */
    public function is_ssl_enabled()
    {
        // url connection
        $url = "https://" . $_SERVER["HTTP_HOST"];
        // Initiate connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); // set browser/user agent
        // Set cURL and other options
        curl_setopt($ch, CURLOPT_URL, $url); // set url
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // allow https verification if true
        curl_setopt($ch, CURLOPT_NOBODY, true);
        // grab URL and pass it to the browser
        $res = curl_exec($ch);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Setup Language
     */
    public function setupLang()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $post = FatApp::getPostedData();
        $frmType = FatUtility::int($post['form_type']);
        $langId = FatUtility::int($post['lang_id']);
        if (1 > $frmType || 1 > $langId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($frmType, $langId);
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($post['form_type']);
        unset($post['lang_id']);
        unset($post['btn_submit']);
        $config = new Configurations();
        if (!$config->update($post)) {
            FatUtility::dieJsonError($config->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(Configurations::DB_TBL, $frmType, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('MSG_Setup_Successful'),
            'frmType' => $frmType,
            'langId' => $langId
        ]);
    }

    /**
     * Upload Media
     */
    public function uploadMedia()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED'));
        }
        $fileType = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $allowedFileTypeArr = $this->getConfMediaType();
        if (MyUtility::isDemoUrl() && $fileType == Afile::TYPE_FRONT_LOGO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_CHANGE_LOGO_ON_DEMO'));
        }
        if (!in_array($fileType, $allowedFileTypeArr)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile($fileType, $lang_id);
        if (!$file->saveFile($_FILES['file'], 0, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        $data = [
            'file' => $_FILES['file']['name'],
            'frmType' => Configurations::FORM_GENERAL_SETTINGS,
            'msg' => $_FILES['file']['name'] . ' ' . Label::getLabel('MSG_UPLOADED_SUCCESSFULLY')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Google Authorize
     */
    public function googleAuthorize()
    {
        $code = FatApp::getQueryStringData('code', FatUtility::VAR_STRING, NULL);
        $error = FatApp::getQueryStringData('error', FatUtility::VAR_STRING, NULL);
        if (!empty($error)) {
            FatApp::redirectUser(MyUtility::makeUrl('Configurations') . '?tab=' . Configurations::FORM_THIRD_PARTY_APIS);
        }
        $googleAnalytics = new GoogleCalendar();
        $authorize = $googleAnalytics->authorize($code, true);
        if (!$authorize) {
            $msg = $googleAnalytics->getError();
            $redirectUrl = $googleAnalytics->getRedirectUrl();
            if (empty($code)) {
                FatUtility::dieJsonError(['msg' => $msg, 'redirectUrl' => $redirectUrl]);
            }
            Message::addErrorMessage($msg);
            FatApp::redirectUser(MyUtility::makeUrl('Configurations'));
        }
        if (empty($code)) {
            FatUtility::dieJsonSuccess(['redirectUrl' => $googleAnalytics->getRedirectUrl()]);
        }
        Message::addMessage(Label::getLabel('LBL_GOOGLE_AUTHORIZATION_SUCCESSFUL'));
        FatApp::redirectUser(MyUtility::makeUrl('Configurations'));
    }

    /**
     * Remove Media
     */
    public function removeMedia()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (MyUtility::isDemoUrl() && $type == Afile::TYPE_FRONT_LOGO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_CHANGE_LOGO_ON_DEMO'));
        }
        $file = new Afile($type, $langId);
        if (!$file->removeFile(0, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_Deleted_Successfully'));
    }

    /**
     * Get Configuration Form
     * 
     * @param int $type
     * @param array $arrValues
     * @return Form
     */
    private function getForm(int $type, array $arrValues = []): Form
    {
        $frm = new Form('frmConfiguration');
        $frm = CommonHelper::setFormProperties($frm);
        switch ($type) {
            case Configurations::FORM_GENERAL_SETTINGS:
                $cmsPages = ContentPage::getPagesForSelectBox($this->siteLangId);
                $fld = $frm->addEmailField(Label::getLabel('LBL_Site_Owner_Email'), 'CONF_SITE_OWNER_EMAIL');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Site_Owner_Email") . '</small>';
                $fld = $frm->addTextBox(Label::getLabel('LBL_Telephone_Number'), 'CONF_SITE_PHONE');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Telephone_Number") . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Site_Language'), 'CONF_DEFAULT_LANG', Language::getAllNames(), false, [], '');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Site_Language") . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Site_Currency'), 'CONF_SITE_CURRENCY', Currency::getCurrencyNameWithCode($this->siteLangId), false, [], '');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Site_Currency") . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Site_Country'), 'CONF_COUNTRY', Country::getNames($this->siteLangId), '', [], '');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Site_Country") . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Site_TIME_FORMAT'), 'FRONTEND_TIME_FORMAT', MyDate::getSysTimeFormatOpt(), '', [], '');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_System_Time_Format") . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Privacy_Policy'), 'CONF_PRIVACY_POLICY_PAGE', $cmsPages, '', [], '');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Privacy_Policy") . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Terms_&_Conditions'), 'CONF_TERMS_AND_CONDITIONS_PAGE', $cmsPages, '', [], '');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Terms_&_Conditions") . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Cookies_Policies'), 'CONF_COOKIES_BUTTON_LINK', $cmsPages, '', [], '');
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Cookies_Policies") . '</small>';
                $fld = $frm->addCheckBox(Label::getLabel('LBL_COOKIES_POLICIES_NOTICE'), 'CONF_ENABLE_COOKIES', 1, [], false, 0);
                $fld->htmlAfterField = "<small>" . Label::getLabel("LBL_cookies_policies_section_will_be_shown_on_frontend") . "</small>";
                break;
            case Configurations::FORM_SEO_AND_GOOGLE_TAGS:
                $frm->addHtml('', 'SiteTracking', '<h3>' . Label::getLabel("LBL_SITE_TRACKING_SCRIPTS") . '</h3>');
                $fld = $frm->addCheckBox(Label::getLabel('LBL_ENABLE_LANGUAGE_CODE_TO_SITE_URLS'), 'CONF_LANGCODE_URL', 1, [], false, 0);
                $fld->htmlAfterField = '<small>' . Label::getLabel("LBL_LANGUAGE_CODE_TO_SITE_URLS_EXAMPLES") . '</small>';
                $fld2 = $frm->addTextarea(Label::getLabel('LBL_Site_Tracker_Code'), 'CONF_SITE_TRACKER_CODE');
                $fld2->htmlAfterField = '<small>' . Label::getLabel("LBL_This_is_the_site_tracker_script,_used_to_track_and_analyze_data_about_how_people_are_getting_to_your_website._e.g.,_Google_Analytics.") . ' http://www.google.com/analytics/</small>';
                $frm->addHtml('', 'Analytics', '<h3>' . Label::getLabel("LBL_Google_Tag_Manager") . '</h3>');
                $fld = $frm->addTextarea(Label::getLabel("LBL_Head_Script"), 'CONF_GOOGLE_TAG_MANAGER_HEAD_SCRIPT');
                $fld = $frm->addTextarea(Label::getLabel("LBL_Body_Script"), 'CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT');
                break;
            case Configurations::FORM_COMMON_SETTINGS:
                $frm->addHtml('', 'Admin', '<h3>' . Label::getLabel('LBL_MISCELLANEOUS_SETTINGS') . '</h3>');
                $fld3 = $frm->addIntegerField(Label::getLabel("LBL_Default_Items_Per_Page"), "CONF_ADMIN_PAGESIZE");
                $fld3->requirements()->setRange(1, 500);
                $fld3->htmlAfterField = "<small>" . Label::getLabel("LBL_Set_number_of_records_shown_per_page_(Users,_orders,_etc)") . "</small>";
                $fld = $frm->addTextBox(Label::getLabel("LBL_MINIMUM_GIFTCARD_AMOUNT"), "MINIMUM_GIFT_CARD_AMOUNT");
                $fld->requirements()->setRequired();
                $fld->requirements()->setIntPositive();
                $fld->requirements()->setRange(1, 9999999999);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_MINIMUM_GIFTCARD_AMOUNT") . "</small>";
                $fld = $frm->addTextBox(Label::getLabel("LBL_CANCEL_PENDING_ORDERS_AFTER_[IN_MINUTES]"), "CONF_CANCEL_ORDER_DURATION");
                $fld->requirements()->setRequired();
                $fld->requirements()->setIntPositive();
                $fld->requirements()->setRange(1, 60);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_CANCEL_ORDER_DURATION") . "</small>";
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_MANAGE_LANGUAGE_PRICES"), 'CONF_MANAGE_PRICES', AppConstant::managePrices(), AppConstant::MANAGE_PRICE_TEACHER, ['class' => 'list-inline']);
                if (FatApp::getConfig('CONF_ENABLE_SUBSCRIPTION_PLAN')) {
                    $fld->setFieldTagAttribute('disabled', 'disabled');
                }
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_MANAGE_LANGUAGE_PRICES") . "</small>";
                $fld = $frm->addCheckBox(Label::getLabel("LBL_ENABLE_USER_NOTES"), 'CONF_ENABLE_FLASHCARD', 1, [], false, 0);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ENABLE_USER_NOTES") . "</small>";
                $fld = $frm->addCheckBox(Label::getLabel("LBL_ENABLE_NEWSLETTER_SUBSCRIPTION"), 'CONF_ENABLE_NEWSLETTER_SUBSCRIPTION', 1, [], false, 0);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ENABLE_NEWSLETTER_SUBSCRIPTION") . "</small>";
                $fld = $frm->addCheckBox(Label::getLabel("LBL_ENABLE_FREE_TRIAL"), 'CONF_ENABLE_FREE_TRIAL', AppConstant::YES, [], false, AppConstant::NO);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ENABLE_FREE_TRIAL") . "</small>";
                $fld = $frm->addCheckBox(Label::getLabel("LBL_ENABLE_COURSES"), 'CONF_ENABLE_COURSES', 1, [], false, 0);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ENABLE_COURSES") . "</small>";
                if (MyUtility::isDemoUrl()) {
                    $fld->setFieldTagAttribute('disabled', 'disabled');
                    $fld->htmlAfterField = "<small>" . Label::getLabel("NOTE_SETTINGS_NOT_ALLOWED_TO_BE_MODIFIED_ON_DEMO_VERSION") . "</small>";
                }
                $fld = $frm->addCheckBox(Label::getLabel("LBL_ENABLE_SUBSCRIPTION_PLAN"), 'CONF_ENABLE_SUBSCRIPTION_PLAN', 1, [], false, 0);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ENABLE_SUBSCRIPTION_PLAN") . "</small>";
                $fld->htmlAfterField = "<small style='color:var(--bs-red)'>" . Label::getLabel("NOTE_SETTINGS_CANT_BE_REVERTED_ONCE_ENABLED") . "</small>";
                if (FatApp::getConfig('CONF_ENABLE_SUBSCRIPTION_PLAN')) {
                    $fld->setFieldTagAttribute('disabled', 'disabled');
                }
                $fld = $frm->addIntegerField(Label::getLabel("LBL_APPLY_TO_TEACH_MAX_ATTEMPT"), "CONF_MAX_TEACHER_REQUEST_ATTEMPT");
                $fld->requirements()->setRange(0, 10);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_APPLY_TO_TEACH_MAX_ATTEMPT") . "</small>";
                $frm->addHtml('', 'Account', '<h3>' . Label::getLabel("LBL_NEW_ACCOUNT_SETTINGS") . '</h3>');
                $fld5 = $frm->addCheckBox(Label::getLabel("LBL_Activate_Admin_Approval_After_Registration_(Sign_Up)"), 'CONF_ADMIN_APPROVAL_REGISTRATION', 1, [], false, 0);
                $fld5->htmlAfterField = '<small>' . Label::getLabel("LBL_On_enabling_this_feature,_admin_need_to_approve_each_learner_after_registration_(Learner_cannot_login_until_admin_approves)") . '</small>';
                $fld7 = $frm->addCheckBox(Label::getLabel("LBL_Activate_Email_Verification_After_Registration"), 'CONF_EMAIL_VERIFICATION_REGISTRATION', 1, [], false, 0);
                $fld7->htmlAfterField = "<small>" . Label::getLabel("LBL_user_need_to_verify_their_email_address_provided_during_registration") . " </small>";
                $fld9 = $frm->addCheckBox(Label::getLabel("LBL_Activate_Auto_Login_After_Registration"), 'CONF_AUTO_LOGIN_REGISTRATION', 1, [], false, 0);
                $fld9->htmlAfterField = "<small>" . Label::getLabel("LBL_On_enabling_this_feature,_users_will_be_automatically_logged-in_after_registration") . "</small>";
                $fld10 = $frm->addCheckBox(Label::getLabel("LBL_Activate_Sending_Welcome_Mail_After_Registration"), 'CONF_WELCOME_EMAIL_REGISTRATION', 1, [], false, 0);
                $fld10->htmlAfterField = "<small>" . Label::getLabel("LBL_On_enabling_this_feature,_users_will_receive_a_welcome_mail_after_registration.") . "</small>";
                $frm->addHtml('', 'report_issue', '<h3>' . Label::getLabel('LBL_REPORT/ESCALATE_ISSUE_TIME') . '</h3>');
                $fld = $frm->addTextBox(Label::getLabel("CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION"), "CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION");
                $fld->requirements()->setIntPositive();
                $fld->requirements()->setRange(0, 168);
                $fld->htmlAfterField = "<small>" . Label::getLabel("htmlAfterField_REPORT_ISSUE_HOURS_AFTER_COMPLETION_TEXT") . "</small>";
                $fld = $frm->addTextBox(Label::getLabel("CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION"), "CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION");
                $fld->requirements()->setIntPositive();
                $fld->requirements()->setRange(0, 168);
                $fld->htmlAfterField = "<br><small>" . Label::getLabel("htmlAfterField_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION_TEXT") . "</small>";
                $frm->addHtml('', 'Wallet', '<h3>' . Label::getLabel("LBL_WALLET") . '</h3>');
                $fld = $frm->addIntegerField(Label::getLabel("LBL_MINIMUM_RECHARGE_AMOUNT") . ' [' . $this->siteCurrency['currency_code'] . ']', 'MINIMUM_WALLET_RECHARGE_AMOUNT', '');
                $fld->htmlAfterField = "<small> " . Label::getLabel("LBL_MINIMUM_AMOUNT_REQUIRED_TO_RECHARGE_WALLET") . "</small>";
                $fld->requirements()->setRange(1, 9999999999);
                $frm->addHtml('', 'Withdrawal', '<h3>' . Label::getLabel("LBL_Withdrawal") . '</h3>');
                $fld = $frm->addIntegerField(Label::getLabel("LBL_Minimum_Withdrawal_Amount") . ' [' . $this->siteCurrency['currency_code'] . ']', 'CONF_MIN_WITHDRAW_LIMIT', '');
                $fld->htmlAfterField = "<small> " . Label::getLabel("LBL_This_is_the_minimum_withdrawable_amount.") . "</small>";
                $fld->requirements()->setRange(1, 9999999999);
                $fld = $frm->addIntegerField(Label::getLabel("LBL_Minimum_Interval_[Days]"), 'CONF_MIN_INTERVAL_WITHDRAW_REQUESTS', '');
                $fld->htmlAfterField = "<small>" . Label::getLabel("LBL_This_is_the_minimum_interval_in_days_between_two_withdrawal_requests.") . "</small>";
                $fld->requirements()->setRange(0, 999999);
                $reviewStatus = [RatingReview::STATUS_PENDING => Label::getLabel('STATUS_PENDING'), RatingReview::STATUS_APPROVED => Label::getLabel('STATUS_APPROVED')];
                $frm->addHtml('', 'reviews', '<h3>' . Label::getLabel("LBL_REVIEWS") . '</h3>');
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_ALLOW_REVIEWS"), 'CONF_ALLOW_REVIEWS', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ALLOW_REVIEWS") . "</small>";
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_DEFAULT_REVIEW_STATUS"), 'CONF_DEFAULT_REVIEW_STATUS', $reviewStatus, '', ['class' => 'list-inline']);
                $fld->htmlAfterField = "<small>" . Label::getLabel("LBL_SET_THE_DEFAULT_REVIEW_ORDER_STATUS_WHEN_A_NEW_REVIEW_IS_PLACED") . "</small>";
                $frm->addHtml('', 'notification', '<h3>' . Label::getLabel("LBL_Notifications") . '</h3>');
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_Enable_Unread_Messages_Notifications"), 'CONF_ENABLE_UNREAD_MSG_NOTIFICATION', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld->htmlAfterField = "<small>" . Label::getLabel("LBL_Enable_Email_Notifications_For_Unread_Messages.") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_Unread_Messages_Notify_Duration[mins]"), 'CONF_UNREAD_MSG_NOTIFICATION_DURATION', '');
                $fld->htmlAfterField = "<small>" . Label::getLabel("LBL_This_Is_The_Messages_Unread_Duration_After_Which_Users_Will_Get_Notification._Recommended_Duration:_10_Mins") . "</small>";
                $fld->requirements()->setRange(1, 99999);
                $fld = $frm->addIntegerField(Label::getLabel("LBL_DELETE_ATTACHMENT_DURATION[MINS]"), 'CONF_DELETE_ATTACHMENT_ALLOWED_DURATION', '');
                $fld->requirements()->setRange(0, 99999);
                $fld->htmlAfterField = "<small>" . Label::getLabel("LBL_THIS_IS_THE_DURATION_UNTIL_THE_USERS_ARE_ALLOWED_TO_DELETE_SENT_ATTACHMENTS_IN_MESSAGES") . "</small>";
                break;
            case Configurations::FORM_EMAIL_AND_SMTPS:
                $frm->addHtml('', 'email_and_smtps', '<h3>' . Label::getLabel("LBL_EMAIL_AND_SMTPS") . '</h3>');
                $fld = $frm->addEmailField(Label::getLabel("LBL_From_Email"), 'CONF_FROM_EMAIL');
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_From_Email") . "</small>";
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_Send_Email"), 'CONF_SEND_EMAIL', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld->htmlAfterField = '<small><a href="javascript:void(0)" id="testMail-js">' . Label::getLabel("LBL_Click_Here_to_test_email") . '</a>. ' . Label::getLabel("LBL_This_will_send_Test_Email_to_Site_Owner_Email") . ' - ' . FatApp::getConfig("CONF_SITE_OWNER_EMAIL") . '</small>';
                $fld = $frm->addEmailField(Label::getLabel("LBL_Contact_Email"), 'CONF_CONTACT_EMAIL');
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_Contact_Email") . "</small>";
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_Send_SMTP_Email"), 'CONF_SEND_SMTP_EMAIL', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld = $frm->addTextBox(Label::getLabel("LBL_SMTP_Host"), 'CONF_SMTP_HOST');
                $fld = $frm->addTextBox(Label::getLabel("LBL_SMTP_Port"), 'CONF_SMTP_PORT');
                $fld = $frm->addTextBox(Label::getLabel("LBL_SMTP_Username"), 'CONF_SMTP_USERNAME');
                $fld = $fld = $frm->addPasswordField(Label::getLabel("LBL_SMTP_Password"), 'CONF_SMTP_PASSWORD');
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_SMTP_Secure"), 'CONF_SMTP_SECURE', AppConstant::getSmtpSecureArr(), '', ['class' => 'list-inline']);
                break;
            case Configurations::FORM_THIRD_PARTY_APIS:
                $frm->addHtml('', 'Newsletter', '<h3>' . Label::getLabel("LBL_FACEBOOK_LOGIN") . '</h3>');
                $fld = $frm->addTextBox(Label::getLabel("LBL_Facebook_APP_ID"), 'CONF_FACEBOOK_APP_ID');
                $fld = $frm->addTextBox(Label::getLabel("LBL_Facebook_App_Secret"), 'CONF_FACEBOOK_APP_SECRET');
                $frm->addHtml('', 'Newsletter', '<h3>' . Label::getLabel("LBL_APPLE_LOGIN") . '</h3>');
                $fld = $frm->addTextBox(Label::getLabel("LBL_APPLE_CLIENT_ID"), 'CONF_APPLE_CLIENT_ID');
                $frm->addHtml('', 'Newsletter', '<h3>' . Label::getLabel("LBL_NEWSLETTER_SUBSCRIPTION") . '</h3>');
                $fld = $frm->addTextBox(Label::getLabel("LBL_Mailchimp_Key"), 'CONF_MAILCHIMP_KEY');
                $fld = $frm->addTextBox(Label::getLabel("LBL_Mailchimp_List_ID"), 'CONF_MAILCHIMP_LIST_ID');
                $fld = $frm->addTextBox(Label::getLabel("LBL_MAILCHIMP_SERVER_PREFIX"), 'CONF_MAILCHIMP_SERVER_PREFIX');
                $frm->addHtml('', '', '<h3>' . Label::getLabel("LBL_MICROSOFT_TEXT_TRANSLATOR") . '</h3>');
                $fld = $frm->addTextBox(Label::getLabel("LBL_SUBSCRIPTION_KEY"), 'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_KEY');
                $frm->addTextBox(Label::getLabel("LBL_SUBSCRIPTION_REGION"), 'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_REGION');
                $frm->addHtml('', 'Analytics', '<h3>' . Label::getLabel("LBL_Google_Analytics") . '</h3>');
                $frm->addTextBox(Label::getLabel("LBL_GOOGLE_ANALYTICS_PROPERTY_ID"), 'CONF_ANALYTICS_TABLE_ID');
                $frm->addTextarea(Label::getLabel('LBL_GOOGLE_ANALYTICS_CLIENT_JSON'), 'CONF_GOOGLE_ANALYTICS_CLIENT_JSON');
                $frm->addHtml('', 'Analytics', '<h3>' . Label::getLabel("LBL_Google_Recaptcha") . '</h3>');
                $fld = $frm->addTextBox(Label::getLabel("LBL_Site_Key"), 'CONF_RECAPTCHA_SITEKEY');
                $fld = $frm->addTextBox(Label::getLabel("LBL_Secret_Key"), 'CONF_RECAPTCHA_SECRETKEY');
                $frm->addHtml('', '', '<h3>' . Label::getLabel("LBL_Google_Client_Json") . '</h3>');
                $fld2 = $frm->addTextarea(Label::getLabel('LBL_Google_Client_Json'), 'CONF_GOOGLE_CLIENT_JSON');
                $frm->addHtml('', '', '<h3>' . Label::getLabel("LBL_Google_Api_Key") . '</h3>');
                $fld2 = $frm->addTextBox(Label::getLabel('LBL_Google_Api_Key'), 'CONF_GOOGLE_API_KEY');
                $frm->addHtml('', '', '<h3>' . Label::getLabel("LBL_FIREBASE_CONFIGURATION") . '</h3>');
                $fld = $frm->addTextarea(Label::getLabel('LBL_SERVICE_ACCOUNT_JSON_FOR_FIREBASE'), 'CONF_SERVICE_ACCOUNT_FIREBASE_JSON');
                $frm->addHtml('', '', '<h3>' . Label::getLabel("LBL_SHARETHIS_PLUGIN") . '</h3>');
                $fld = $frm->addTextBox(Label::getLabel('LBL_SHARE_THIS_PLUGIN_PROPERTY_ID'), 'CONF_SHARE_THIS_PROPERTY_ID');
               
                break;
            case Configurations::FORM_MAINTAINANCE_AND_SSL:
                $frm->addHtml('', 'maintainance_and_ss', '<h3>' . Label::getLabel("LBL_MAINTAINANCE_AND_SSL") . '</h3>');
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_ENABLE_SSL"), 'CONF_USE_SSL', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld->htmlAfterField = '<small>' . Label::getLabel("LBL_NOTE:_To_use_SSL,_check_with_your_host") . '</small>';
                if (!MyUtility::isDemoUrl()) {
                    $fld = $frm->addRadioButtons(Label::getLabel("LBL_Maintenance_Mode"), 'CONF_MAINTENANCE', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                    $fld->htmlAfterField = '<small>' . Label::getLabel("LBL_Enable_Maintenance_Mode_Text") . '</small>';
                }
                break;
            case Configurations::FORM_REMEMBER_ME_SECURITY:
                $frm->addHtml('', 'remember_me_security', '<h3>' . Label::getLabel("LBL_REMEMBER_ME_SECURITY_SETTINGS") . '</h3>');
                $fld = $frm->addIntegerField(Label::getLabel("LBL_REMEMBER_ME_DAYS_FOR_ADMIN"), 'CONF_ADMIN_REMEMBER_ME_DAYS');
                $fld->htmlAfterField = "<small> " . Label::getLabel("HAF_REMEMBER_ME_DAYS_FOR_ADMIN") . "</small>";
                $fld->requirements()->setRange(1, 365);
                $fld = $frm->addSelectBox(Label::getLabel("LBL_REMEMBER_ME_SECURITY_FOR_ADMIN"), 'CONF_ADMIN_REMEMBER_ME_IP_ENABLE', Configurations::getSecuritySettings(), '', [], '');
                $fld->requirements()->setRequired();
                $fld->htmlAfterField = "<small> " . Label::getLabel("HAF_REMEMBER_ME_SECURITY_FOR_ADMIN") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_REMEMBER_ME_DAYS_FOR_USER"), 'CONF_USER_REMEMBER_ME_DAYS');
                $fld->htmlAfterField = "<small> " . Label::getLabel("HAF_REMEMBER_ME_DAYS_FOR_USER") . "</small>";
                $fld->requirements()->setRange(1, 365);
                $fld = $frm->addSelectBox(Label::getLabel("LBL_REMEMBER_ME_SECURITY_FOR_USER"), 'CONF_USER_REMEMBER_ME_IP_ENABLE', Configurations::getSecuritySettings(), '', [], '');
                $fld->requirements()->setRequired();
                $fld->htmlAfterField = "<small> " . Label::getLabel("HAF_REMEMBER_ME_SECURITY_FOR_USER") . "</small>";
                break;
            case Configurations::FORM_DISCUSSION_FORUM:
                $frm->addHtml('', 'forum', '<h3>' . Label::getLabel("LBL_DISCUSSION_FORUM") . '</h3>');
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_ENABLE_EMAIL_NOTIFICATIONS"), 'CONF_FORUM_SEND_EMAILS', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_ENABLE_FORUM_EMAIL_NOTIFICATIONS") . '.</small>';
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_ENABLE_SYSTEM_NOTIFICATIONS"), 'FORUM_SEND_SYSTEM_NOTIFICATIONS', AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_ENABLE_FORUM_SYSTEM_NOTIFICATIONS") . '.</small>';
                break;
            case Configurations::FORM_DASHBOARD_LESSONS:
                $frm->addHtml('', 'dashboard_lessons', '<h3>' . Label::getLabel('LBL_DASHBOARD_LESSONS') . '</h3>');
                $bookingSlots = AppConstant::getBookingSlots();
                $fld = $frm->addCheckBoxes(Label::getLabel("LBL_ALLOWED_LESSON_SLOTS"), "CONF_PAID_LESSON_DURATION", $bookingSlots, [], ['class' => 'list-inline']);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ALLOWED_LESSON_SLOTS") . "</small>";
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_ALLOWED_TRAIL_LESSON_SLOT"), "CONF_TRIAL_LESSON_DURATION", $bookingSlots, '', ['class' => 'list-inline']);
                $fld->requirements()->setRequired();
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ALLOWED_TRIAL_LESSON_SLOTS") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_LESSON_CANCEL_DURATION"), "CONF_LESSON_CANCEL_DURATION");
                $fld->requirements()->setRange(0, 50);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_LESSON_CANCEL_DURATION") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_LESSON_RESCHEDULE_DURATION"), "CONF_LESSON_RESCHEDULE_DURATION");
                $fld->requirements()->setRange(0, 50);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_LESSON_RESCHEDULE_DURATION") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_LESSON_REFUND_DURATION"), "CONF_LESSON_REFUND_DURATION");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_LESSON_REFUND_DURATION") . "</small>";
                $fld = $frm->addFloatField(Label::getLabel("LBL_REFUND_BEFORE_REFUND_DURATION"), "CONF_LESSON_REFUND_PERCENTAGE_BEFORE_DURATION");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_REFUND_BEFORE_REFUND_DURATION") . "</small>";
                $fld = $frm->addFloatField(Label::getLabel("LBL_REFUND_AFTER_REFUND_DURATION"), "CONF_LESSON_REFUND_PERCENTAGE_AFTER_DURATION");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_REFUND_AFTER_REFUND_DURATION") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_END_LESSON_DURATION"), "CONF_ALLOW_TEACHER_END_LESSON");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_END_LESSON_DURATION") . "</small>";
                $fld = $frm->addFloatField(Label::getLabel("LBL_UNSCHEDULE_LESSON_CANCEL_REFUND"), "CONF_UNSCHEDULE_LESSON_REFUND_PERCENTAGE");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_UNSCHEDULE_LESSON_CANCEL_REFUND") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_AUTO_COMPLETE_LESSON_AFTER_X_HOURS"), "CONF_AUTOCOMPLETE_LESSON_SESSION");
                $fld->requirements()->setRange(0, 10);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_AUTO_COMPLETE_LESSON_AFTER_X_HOURS") . "</small>";
                break;
            case Configurations::FORM_DASHBOARD_CLASSES:
                if (!GroupClass::isEnabled()) {
                    FatUtility::dieWithError(Label::getLabel('LBL_CLASS_MODULE_NOT_AVAILABLE'));
                }
                $frm->addHtml('', 'dashboard_classes', '<h3>' . Label::getLabel('LBL_DASHBOARD_CLASSES') . '</h3>');
                $slots = AppConstant::getGroupClassSlots();
                $fld = $frm->addCheckBoxes(Label::getLabel("LBL_ALLOWED_CLASS_SLOTS"), "CONF_GROUP_CLASS_DURATION", $slots, [], ['class' => 'list-inline']);
                $fld->requirements()->setSelectionRange(1, count($slots));
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_ALLOWED_CLASS_SLOTS") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_CLASS_CANCEL_DURATION"), "CONF_CLASS_CANCEL_DURATION");
                $fld->requirements()->setRange(0, 50);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_CLASS_CANCEL_DURATION") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_CLASS_REFUND_DURATION"), "CONF_CLASS_REFUND_DURATION");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_CLASS_REFUND_DURATION") . "</small>";
                $fld = $frm->addFloatField(Label::getLabel("LBL_CLASS_REFUND_BEFORE_REFUND_DURATION"), "CONF_CLASS_REFUND_PERCENTAGE_BEFORE_DURATION");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_CLASS_REFUND_BEFORE_REFUND_DURATION") . "</small>";
                $fld = $frm->addFloatField(Label::getLabel("LBL_CLASS_REFUND_AFTER_REFUND_DURATION"), "CONF_CLASS_REFUND_PERCENTAGE_AFTER_DURATION");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_CLASS_REFUND_AFTER_REFUND_DURATION") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_END_CLASS_DURATION"), "CONF_ALLOW_TEACHER_END_CLASS");
                $fld->requirements()->setRange(0, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_END_CLASS_DURATION") . "</small>";
                $fld = $frm->addTextBox(Label::getLabel("LBL_Class_Book_Before"), "CONF_CLASS_BOOKING_GAP");
                $fld->requirements()->setIntPositive();
                $fld->requirements()->setRange(0, 1000);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_CLASS_BOOK_BEFORE") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_Class_Max_learners"), "CONF_GROUP_CLASS_MAX_LEARNERS");
                $fld->requirements()->setRange(1, 99999);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_CLASS_MAX_LEARNERS") . "</small>";
                $fld = $frm->addIntegerField(Label::getLabel("LBL_AUTO_COMPLETE_CLASSES_AFTER_X_HOURS"), "CONF_AUTOCOMPLETE_CLASSES_SESSION");
                $fld->requirements()->setRange(0, 10);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_AUTO_COMPLETE_CLASSES_AFTER_X_HOURS") . "</small>";
                break;
            case Configurations::FORM_DASHBOARD_COURSES:
                if (!Course::isEnabled()) {
                    FatUtility::dieWithError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
                }
                $frm->addHtml('', 'Course', '<h3>' . Label::getLabel("LBL_DASHBOARD_COURSE") . '</h3>');
                $fld3 = $frm->addIntegerField(Label::getLabel("LBL_COURSE_CANCELLATION_DURATION(DAYS)"), "CONF_COURSE_CANCEL_DURATION");
                $fld3->requirements()->setRange(0, 100);
                $fld3->htmlAfterField = "<br><small>" . Label::getLabel("htmlAfterField_COURSE_CANCELLATION_DURATION_TEXT") . ".</small>";
                $reviewStatus = [
                    Course::REFUND_PENDING => Label::getLabel('STATUS_PENDING'),
                    Course::REFUND_APPROVED => Label::getLabel('STATUS_APPROVED')
                ];
                $fld = $frm->addRadioButtons(Label::getLabel("LBL_COURSE_DEFAULT_CANCELLATION_STATUS"), 'CONF_COURSE_DEFAULT_CANCELLATION_STATUS', $reviewStatus, '', ['class' => 'list-inline']);
                $fld->htmlAfterField = "<small>" . Label::getLabel("LBL_SET_THE_DEFAULT_STATUS_WHEN_A_COURSE_CANCELLATION_REQUEST_IS_PLACED") . "</small>";

                $fld3 = $frm->addIntegerField(Label::getLabel("LBL_COURSE_EDIT_DURATION(DAYS)"), "CONF_COURSE_EDIT_DURATION");
                $fld3->requirements()->setRange(1, 100);
                $fld3->htmlAfterField = "<br><small>" . Label::getLabel("htmlAfterField_COURSE_EDIT_DURATION_TEXT") . "</small>";
                $fld3 = $frm->addRadioButtons(Label::getLabel("LBL_SEND_COURSE_EDIT_EMAILS"), "CONF_SEND_COURSE_EDIT_EMAILS", AppConstant::getYesNoArr(), '', ['class' => 'list-inline']);
                $fld3->htmlAfterField = "<br><small>" . Label::getLabel("htmlAfterField_COURSE_EDIT_EMAILS_SETTING_TEXT") . "</small>";

                $videoTool = FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL');
                if ($videoTool == VideoStreamer::TYPE_VIDEO_CIPHER) {
                    $frm->addHtml('', '', '<h3>' . Label::getLabel("LBL_VIDEO_CIPHER") . '</h3>');
                    $fld2 = $frm->addTextBox(Label::getLabel('LBL_VIDEO_CIPHER_API_KEY'), 'CONF_VIDEO_CIPHER_API_KEY');
                    $fld2->htmlAfterField = '<small>' . Label::getLabel("LBL_VIDEO_CIPHER_API_KEY_MESSAGE") . '</small>';
                    $fld2 = $frm->addTextBox(Label::getLabel('LBL_VIDEO_CIPHER_FOLDER_ID'), 'CONF_VIDEO_CIPHER_FOLDER_ID');
                    $fld2->htmlAfterField = '<small>' . Label::getLabel("LBL_VIDEO_CIPHER_FOLDER_ID_MESSAGE") . '</small>';
                } elseif ($videoTool == VideoStreamer::TYPE_MUX) {
                    $encodingArr = Mux::getEncodingArr();
                    $resolutionArr = Mux::getResolutionsArr(FatApp::getConfig('CONF_MUX_ENCODING_TIER', FatUtility::VAR_STRING, 'baseline'));
                    $frm->addHtml('', '', '<h3>' . Label::getLabel("LBL_MUX_VIDEOS") . '</h3>');
                    $fld2 = $frm->addTextBox(Label::getLabel('LBL_MUX_ACCESS_TOKEN_ID'), 'CONF_MUX_ACCESS_TOKEN_ID');
                    $fld2->htmlAfterField = '<small>' . Label::getLabel("LBL_ACCESS_TOKEN_ID_TO_AUTHENTICATE") . '</small>';
                    $fld2 = $frm->addTextBox(Label::getLabel('LBL_MUX_SECRET_KEY'), 'CONF_MUX_SECRET_KEY');
                    $fld2->htmlAfterField = '<small>' . Label::getLabel("LBL_SECRET_KEY_TO_AUTHENTICATE") . '</small>';
                    $fld2 = $frm->addSelectBox(Label::getLabel("LBL_ENCODING_TIER"), 'CONF_MUX_ENCODING_TIER', $encodingArr, '', [], '');
                    $fld2->htmlAfterField = '<small>' . Label::getLabel("LBL_ENCODING_TIER_INFORMS_THE_COST,_QUALITY,_AND_AVAILABLE_PLATFORM_FEATURES_FOR_THE_ASSET") . '</small>';
                    $fld2 = $frm->addSelectBox(Label::getLabel("LBL_HIGHEST_VIDEO_RESOLUTION"), 'CONF_MUX_RESOLUTION', $resolutionArr, '', [], '');
                    $fld2->htmlAfterField = '<small>' . Label::getLabel("LBL_HIGHEST_VIDEO_RESOLUTION_CAN_BE_UPLOADED") . '</small>';
                    $fld3 = $frm->addTextBox(Label::getLabel('LBL_MUX_WEBHOOK_SECRET'), 'CONF_MUX_WEBHOOK_SECRET_KEY');
                    $fld3->htmlAfterField = '<small>' . Label::getLabel("LBL_WEBHOOK_SECRET") . '</small>';
                }
                break;
            case Configurations::FORM_PWA_SETTINGS:
                $frm->addHtml('', 'pwasettings', '<h3>' . Label::getLabel('LBL_PWA_SETTINGS') . '</h3>');
                $fld = $frm->addCheckBox(Label::getLabel('LBL_Enable_PWA'), 'CONF_ENABLE_PWA', 1, [], false, 0);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_Enable_PWA') . '</small>';
                $fld = $frm->addRequiredField(Label::getLabel('LBL_App_Name'), 'pwa_settings[name]');
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_App_Name') . '</small>';
                $fld->requirements()->setLength(1, 50);
                $fld = $frm->addRequiredField(Label::getLabel('LBL_App_Short_Name'), 'pwa_settings[short_name]');
                $fld->requirements()->setLength(1, 50);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_App_Short_Name') . '</small>';
                $fld = $frm->addTextBox(Label::getLabel('LBL_PWA_Description'), 'pwa_settings[description]');
                $fld->requirements()->setLength(1, 200);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_PWA_Description') . '</small>';
                $fld = $frm->addFileUpload(Label::getLabel('LBL_App_Icon'), 'icon', ['accept' => 'image/png']);
                $label = Label::getLabel('LBL_PREFERRED_DIMENSIONS_{DIMENSIONS}');
                $dimensions = implode('x', (new Afile(Afile::TYPE_PWA_APP_ICON))->getImageSizes('LARGE'));
                $label = str_replace('{dimensions}', $dimensions, $label);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_PWA_App_Icon') . ' ' . $label . '</small>';
                $fld->attachField($frm->addHTML('', 'icon_img', ''));
                $fld = $frm->addRequiredField(Label::getLabel('LBL_Background_Color'), 'pwa_settings[background_color]');
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_PWA_Background_color') . '</small>';
                $fld = $frm->addRequiredField(Label::getLabel('LBL_Theme_Color'), 'pwa_settings[theme_color]');
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_PWA_Theme_Color') . '</small>';
                $fld = $frm->addRequiredField(Label::getLabel('LBL_Start_Page'), 'pwa_settings[start_url]');
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_PWA_Start_Page') . '</small>';
                $orientation = ['portrait' => Label::getLabel('LBL_PORTRAIT'), 'landscape' => Label::getLabel('LBL_LANDSCAPE')];
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Orientation'), 'pwa_settings[orientation]', $orientation, '', [], '');
                $fld->requirements()->setRequired();
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_PWA_orientation') . '</small>';
                $fld = $frm->addSelectBox(Label::getLabel('LBL_Display'), 'pwa_settings[display]', static::getPwaDisplaySize(), '', [], '');
                $fld->requirements()->setRequired();
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_PWA_Display') . '</small>';
                break;
            case Configurations::FORM_REFERRAL_SETTINGS:
                $frm->addHtml('', 'Admin', '<h3>' . Label::getLabel('LBL_REFERRAL_SETTINGS') . '</h3>');
                $fld = $frm->addCheckBox(Label::getLabel('CONF_ENABLE_REFERRAL_REWARDS'), 'CONF_ENABLE_REFERRAL_REWARDS', 1, [], false, 0);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_ENABLE/DISABLE_REFERRAL_MODULE') . '</small>';
                $fld = $frm->addIntegerField(Label::getLabel("CONF_REWARD_POINT_MULTIPLIER"), 'CONF_REWARD_POINT_MULTIPLIER');
                $fld->requirements()->setRange(1, 1000);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_Rate_Of_Converstion_1_Currency_Unit_=_X_No_Of_Reward_Points') . '</small>';
                $fld = $frm->addIntegerField(Label::getLabel("CONF_REWARD_POINT_MINIMUM_USE"), 'CONF_REWARD_POINT_MINIMUM_USE');
                $fld->requirements()->setRange(1, 10000);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_MINIMUM_REWARD_POINT_USE_LIMIT') . '</small>';
                $frm->addHtml('', 'Admin', '<h3>' . Label::getLabel('LBL_REWARD_POINTS_ON_REGISTRATION:') . '</h3>');
                $fld = $frm->addIntegerField(Label::getLabel("CONF_REFERRER_REGISTER_REWARDS"), 'CONF_REFERRER_REGISTER_REWARDS');
                $fld->requirements()->setRange(0, 1000);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_REWARDS_TO_REFERRER_ON_REFEREE_SIGNUP') . '</small>';
                $fld = $frm->addIntegerField(Label::getLabel("CONF_REFEREE_REGISTER_REWARDS"), 'CONF_REFERENT_REGISTER_REWARDS');
                $fld->requirements()->setRange(0, 1000);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_REWARDS_TO_REFERREE_ON_REFERAL_SIGNUP') . '</small>';
                $frm->addHtml('', 'Admin', '<h3>' . Label::getLabel('LBL_REWARD_POINTS_ON_FIRST_PURCHASE:') . '</h3>');
                $fld = $frm->addIntegerField(Label::getLabel("CONF_REFERRER_PURCHASE_REWARDS"), 'CONF_REFERRER_PURCHASE_REWARDS');
                $fld->requirements()->setRange(0, 1000);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_REWARDS_TO_REFERRER_ON_REFEREE_FIRST_PURCHASE') . '</small>';
                $fld = $frm->addIntegerField(Label::getLabel("CONF_REFEREE_PURCHASE_REWARDS"), 'CONF_REFERENT_PURCHASE_REWARDS');
                $fld->requirements()->setRange(0, 1000);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_REWARDS_TO_REFERREE_ON_FIRST_PURCHASE') . '</small>';
                break;
            case Configurations::FORM_OFFLINE_SESSIONS_SETTINGS:
                $frm->addHtml('', 'offline_lesson', '<h3>' . Label::getLabel("LBL_OFFLINE_SESSIONS_SETTINGS") . '</h3>');
                $fld5 = $frm->addCheckBox(Label::getLabel("LBL_ENABLE_OFFLINE_SESSIONS"), 'CONF_ENABLE_OFFLINE_SESSIONS', 1, [], false, 0);
                $fld = $frm->addIntegerField(Label::getLabel("LBL_DEFAULT_RADIUS_FOR_SEARCH(MILES)"), "CONF_DEFAULT_RADIUS_FOR_SEARCH");
                $fld->requirements()->setRange(1, 100);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_DEFAULT_RADIUS_SEARCH_INFO") . "</small>";

                $fld = $frm->addIntegerField(Label::getLabel("LBL_END_SESSION_DURATION(HOURS)"), "CONF_TEACHER_END_SESSION_DURATION");
                $fld->requirements()->setRange(1, 20);
                $fld->htmlAfterField = "<small>" . Label::getLabel("HAF_TEACHER_SESSION_END_DURATION_INFO") . "</small>";
                break;
            case Configurations::FORM_AFFILIATE_SETTINGS:
                $frm->addHtml('', 'Admin', '<h3>' . Label::getLabel('LBL_AFFILIATE_SETTINGS') . '</h3>');
                $fld = $frm->addCheckBox(Label::getLabel('CONF_ENABLE_AFFILIATE_MODULE'), 'CONF_ENABLE_AFFILIATE_MODULE', 1, [], false, 0);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_ENABLE/DISABLE_AFFILIATE_MODULE') . '</small>';
                $fld = $frm->addIntegerField(Label::getLabel("CONF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION"), 'CONF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION');
                $fld->requirements()->setRange(0, 9999999999);
                $fld->htmlAfterField = '<small>' . Label::getLabel('HAF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION') . '</small>';
                break;
        }
        $frm->addHiddenField('', 'form_type', $type);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel("LBL_Save_Changes"));
        return $frm;
    }

    /**
     * Get Lang Form
     * 
     * @param int $type
     * @param int $langId
     * @return Form
     */
    private function getLangForm(int $type, int $langId): Form
    {
        $frm = new Form('frmConfiguration');
        $frm = CommonHelper::setFormProperties($frm);
        switch ($type) {
            case Configurations::FORM_GENERAL_SETTINGS:
                $fld = $frm->addTextBox(Label::getLabel("LBL_Site_Name", $langId), 'CONF_WEBSITE_NAME_' . $langId);
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Site_Name", $langId) . '</small>';
                $fld = $frm->addTextBox(Label::getLabel("LBL_EMAIL_FROM_NAME", $langId), 'CONF_FROM_NAME_' . $langId);
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_EMAIL_FROM_NAME", $langId) . '</small>';
                $fld = $frm->addTextarea(Label::getLabel("LBL_ADDRESS", $langId), 'CONF_ADDRESS_' . $langId);
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_ADDRESS", $langId) . '</small>';
                $fld->requirements()->setLength(20, 250);
                $fld = $frm->addTextarea(Label::getLabel('LBL_Cookies_Policies_Text', $langId), 'CONF_COOKIES_TEXT_' . $langId);
                $fld->htmlAfterField = '<small>' . Label::getLabel("HAF_Cookies_Policies_Text", $langId) . '</small>';
                $fld->requirements()->setLength(50, 500);
                break;
            case Configurations::FORM_MEDIA_AND_LOGOS:
                $frm->addButton(Label::getLabel("LBL_WEBSITE_LOGO", $langId), 'front_logo', 'Upload file', ['class' => 'logoFiles-Js', 'id' => 'front_logo', 'data-file_type' => Afile::TYPE_FRONT_LOGO]);
                $frm->addButton(Label::getLabel("LBL_WEBSITE_FAVICON", $langId), 'favicon', 'Upload file', ['class' => 'logoFiles-Js', 'id' => 'favicon', 'data-file_type' => Afile::TYPE_FAVICON]);
                $frm->addButton(Label::getLabel('LBL_HOME_BANNER_BACKGROUND', $langId), 'home_banner_bg', 'Upload file', ['class' => 'logoFiles-Js', 'id' => 'home_banner_bg', 'data-file_type' => Afile::TYPE_HOME_BANNER_BACKGROUND]);
                $frm->addButton(Label::getLabel('LBL_LESSON_BANNER', $langId), 'lesson_img', 'Upload file', ['class' => 'logoFiles-Js', 'id' => 'lesson_img', 'data-file_type' => Afile::TYPE_LESSON_PAGE_IMAGE]);
                $frm->addButton(Label::getLabel('LBL_APPLY_TO_TEACH_BANNER', $langId), 'apply_to_teach_banner', 'Upload file', ['class' => 'logoFiles-Js', 'id' => 'apply_to_teach_banner', 'data-file_type' => Afile::TYPE_APPLY_TO_TEACH_BANNER]);
                if (Course::isEnabled()) {
                    $frm->addButton(Label::getLabel('LBL_CERTIFICATE_LOGO', $langId), 'certificate_logo', 'Upload file', ['class' => 'logoFiles-Js', 'id' => 'certificate_logo', 'data-file_type' => Afile::TYPE_CERTIFICATE_LOGO]);
                }
                $frm->addButton(Label::getLabel('LBL_AFFILIATE_REGISTRATION_PAGE_BANNER', $langId), 'affiliate_register_img', 'Upload file', ['class' => 'logoFiles-Js', 'id' => 'lesson_img', 'data-file_type' => Afile::TYPE_AFFILIATE_REGISTRATION_BANNER]);

                break;
            case Configurations::FORM_MAINTAINANCE_AND_SSL:
                $fld = $frm->addHtmlEditor(Label::getLabel('LBL_Maintenance_Text', $langId), 'CONF_MAINTENANCE_TEXT_' . $langId);
                $fld->requirements()->setRequired(true);
                break;
        }
        $frm->addHiddenField('', 'lang_id', $langId);
        $frm->addHiddenField('', 'form_type', $type);
        Translator::addTranslatorActions($frm, $langId, Configurations::FORM_GENERAL_SETTINGS, Configurations::DB_TBL);
        return $frm;
    }

    /**
     * Test Email
     */
    public function testEmail()
    {
        if (!defined('ALLOW_EMAILS') || ALLOW_EMAILS != true) {
            FatUtility::dieJsonError(Label::getLabel('LBL_ALLOW_EMAILS_IS_FALSE_IN_COMMON_CONF'));
        }
        if (FatApp::getConfig('CONF_SEND_EMAIL', FatUtility::VAR_INT, 0) == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SELECT_SEND_EMAILS_YES_TO_TEST_EMAIL'));
        }
        try {
            $mail = new FatMailer($this->siteLangId, 'test_email');
            if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
                FatUtility::dieJsonError($mail->getError());
            }
            FatUtility::dieJsonSuccess("Mail sent to - " . FatApp::getConfig('CONF_SITE_OWNER_EMAIL'));
        } catch (Exception $e) {
            FatUtility::dieJsonError($e->getMessage());
        }
    }

    /**
     * Check if course module can be disabled
     *
     * @return json
     */
    public function checkCourses()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $config = new Configurations();
        $stats = $config->getCoursesStats();
        $this->set('stats', $stats);
        $this->_template->render(false, false);
    }

    /**
     * Subsription Plan Alert
     *
     * @return json
     */
    public function checkSubscriptionPlan()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $config = new Configurations();
        $this->_template->render(false, false);
    }

    /**
     * Render contact technical team form
     *
     */
    public function contactTeam()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $frm = $this->getContactForm();
        $this->set('frm', $frm);
        $this->set('formLayout', '');
        $this->_template->render(false, false);
    }

    /**
     * Send contact request for courses removal
     *
     * @return json
     */
    public function setupContactRequest()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $post = FatApp::getPostedData();
        $frm = $this->getContactForm();
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $admin = Admin::getAttributesById($this->siteAdminId, ['admin_name', 'admin_email']);
        $data = $admin + $post;
        $mail = new FatMailer($this->siteLangId, '');
        if (!$mail->sendCourseRemovalMail($data)) {
            FatUtility::dieJsonError($mail->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REQUEST_SUBMITTED_SUCCESSFULLY'));
    }

    /**
     * Contact Form
     *
     */
    private function getContactForm()
    {
        $frm = new Form('frmContactform');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextArea(Label::getLabel('LBL_MESSAGE'), 'message')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit');
        return $frm;
    }

    /**
     * Conf Media Types
     * 
     * @return array
     */
    private function getConfMediaType(): array
    {
        return [
            Afile::TYPE_FRONT_LOGO,
            Afile::TYPE_FAVICON,
            Afile::TYPE_HOME_BANNER_BACKGROUND,
            Afile::TYPE_BLOG_PAGE_IMAGE,
            Afile::TYPE_LESSON_PAGE_IMAGE,
            Afile::TYPE_APPLY_TO_TEACH_BANNER,
            Afile::TYPE_CERTIFICATE_LOGO,
            Afile::TYPE_AFFILIATE_REGISTRATION_BANNER
        ];
    }

    /**
     * Get Screen Sizes
     * 
     * @return array
     */
    private static function getPwaDisplaySize(): array
    {
        return [
            'fullscreen' => Label::getLabel('LBL_FULL_SCREEN'),
            'standalone' => Label::getLabel('LBL_STANDALONE'),
            'minimal-ui' => Label::getLabel('LBL_MINIMAL_UI'),
            'browser' => Label::getLabel('LBL_BROWSER')
        ];
    }

    /**
     * Check if course module can be disabled
     *
     * @return json
     */
    public function checkAffiliates()
    {
        $config = new Configurations();
        $stats = $config->getAffiliateStats();
        $this->set('stats', $stats);
        $this->_template->render(false, false);
    }

    /**
     * Get MUX resolutions list
     *
     * @return json
     */
    public function getResolutions()
    {
        $encoding = FatApp::getPostedData('encoding', FatUtility::VAR_STRING, 'baseline');
        $resolutions = Mux::getResolutionsArr($encoding);
        MyUtility::dieJsonSuccess(['resolutions' => json_encode($resolutions)]);
    }
}
