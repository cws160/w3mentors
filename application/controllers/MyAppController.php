<?php

/**
 * MyApp Controller is a Base Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class MyAppController extends FatController
{

    protected $siteUser;
    protected $siteUserId;
    protected $siteUserType;
    protected $siteLangId;
    protected $siteLanguage;
    protected $siteCurrId;
    protected $siteCurrency;
    protected $siteTimezone;
    protected $cookieConsent;
    protected $activePlan = false;
    protected $siteLanguages;

    /**
     * Initialize Application
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);

        $this->checkMaintenance();
        $this->setLoggedUser();
        $this->setSiteLanguage();
        $this->setSiteCurrency();
        $this->setSiteTimezone();
        $this->setCookieConsent();
        $this->checkAffiliateModule();
        $this->checkUserActiveStatus();
        $this->checkUserPasswordUpdate();

        $this->siteLanguages = $this->getSiteLanguages();
        $this->sets([
            'siteUserId' => $this->siteUserId,
            'actionName' => $this->_actionName,
            'controllerName' => str_replace('Controller', '', $this->_controllerName),
            'siteLanguage' => $this->siteLanguage,
            'siteLanguages' => $this->siteLanguages,
            'siteUser' => $this->siteUser,
            'siteUserType' => $this->siteUserType,
            'siteLangId' => $this->siteLangId,
            'siteCurrId' => $this->siteCurrId,
            'siteCurrency' => $this->siteCurrency,
            'siteTimezone' => $this->siteTimezone,
            'cookieConsent' => $this->cookieConsent,
            'messageData' => Message::getData(),
            'activePlan' => $this->activePlan,
        ]);
        if (!API_CALL) {
            $this->sets([
                'siteLanguages' => $this->siteLanguages,
                'siteCurrencies' => $this->getSiteCurrencies(),
                'teachLangs' => TeachLanguage::getTeachLanguages($this->siteLangId)
            ]);
        }
        if (!FatUtility::isAjaxCall()) {
            if (!API_CALL) {
                $themeNiche = Theme::getAttributesById(FatApp::getConfig('CONF_ACTIVE_THEME'), 'theme_niche');
                $this->sets([
                    'canonicalUrl' => SeoUrl::getCanonicalUrl(),
                    'headerNav' => Navigation::getHeaderNav(),
                    'footerOneNav' => Navigation::footerOneNav(),
                    'footerTwoNav' => Navigation::footerTwoNav(),
                    'socialPlatforms' => SocialPlatform::getAll(),
                    'activeTheme' => $themeNiche,
                ]);
            }
            if (!API_CALL || (API_CALL && in_array($this->_controllerName, ['PaymentController', 'LessonsController', 'ClassesController']))) {
                $this->set('jsVariables', MyUtility::getCommonLabels($this->siteLanguages));
                $css = ['css/common-' . $this->siteLanguage['language_direction'] . '.css'];
                $controllerName = str_replace('Controller', '', $this->_controllerName);
                $viewType = 'frontend';
                if (CONF_APPLICATION_PATH == CONF_INSTALLATION_PATH . 'dashboard/') {
                    $viewType = 'dashboard';
                    if (strtolower($controllerName) == 'tutorials' || strtolower($controllerName) == 'coursepreview') {
                        $viewType = 'course-personal';
                    }
                    if (strtolower($controllerName) == 'userquiz' || strtolower($controllerName) == 'quizreview') {
                        $viewType = 'quiz';
                    }
                    $css[] = 'css/' . $viewType . '-' . $this->siteLanguage['language_direction'] . '.css';
                    if (empty($this->siteUserId)) {
                        if ($action != 'logout') {
                            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONT_URL));
                        }
                    }
                }
                if ($viewType == 'frontend') {
                    $css[] = 'css/themes/' . $themeNiche . '/frontend-' . $this->siteLanguage['language_direction'] . '.css';
                }
                $this->_template->addCss($css);
            }
        }
    }

    /**
     * Check System Maintenance Mode
     * 
     * @return boolean
     */
    private function checkMaintenance()
    {
        if (FatApp::getConfig("CONF_MAINTENANCE") == AppConstant::NO) {
            return true;
        }
        if (
            ($this->_controllerName == "ImageController") ||
            ($this->_controllerName == "MaintenanceController") ||
            ($this->_controllerName == 'CookieConsentController' &&
                in_array($this->_actionName, ['form', 'acceptAll', 'setup', 'setSiteLanguage']))
        ) {
            return true;
        }
        UserAuth::logout();
        AppToken::clearToken();
        if (FatUtility::isAjaxCall()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_MAINTENANCE_MODE_TEXT'));
        }
        FatApp::redirectUser(MyUtility::makeUrl('maintenance', '', [], CONF_WEBROOT_FRONT_URL));
    }

    /**
     * Set Site Logged User
     * 
     * @return bool
     */
    protected function setLoggedUser()
    {
        UserAuth::isUserLogged();
        UserAuth::setReferal();
        $this->siteUserType = User::LEARNER;
        $userId = API_CALL ? AppToken::getUserId() : UserAuth::getLoggedUserId();
        $this->siteUser = User::getDetail($userId);
        if (empty($this->siteUser)) {
            UserAuth::logout();
            AppToken::clearToken();
            $this->siteUser = [];
            $this->siteUserId = 0;
            return true;
        }
        $this->siteUserId = FatUtility::int($this->siteUser['user_id']);
        if ($this->siteUser['user_is_teacher'] == AppConstant::YES) {
            $this->siteUserType = User::TEACHER;
            $this->siteUser['profile_progress'] = User::getProfileProgress($this->siteUserId);
        } elseif ($this->siteUser['user_is_affiliate'] == AppConstant::YES) {
            $this->siteUserType = User::AFFILIATE;
        }
        if (!empty(MyUtility::getUserType())) {
            $this->siteUserType = MyUtility::getUserType();
        }
        MyUtility::setUserType($this->siteUserType);
        if (empty($this->siteUser['user_email']) && !in_array(
            $this->_actionName,
            ['configureEmail', 'updateEmail', 'verifyEmail', 'logout', 'show', 'setSiteLanguage']
        )) {
            if (API_CALL || FatUtility::isAjaxCall()) {
                MyUtility::dieJsonError(Label::getLabel('MSG_PLEASE_CONFIGURE_YOUR_EMAIL'));
            }
            Message::addErrorMessage(Label::getLabel('MSG_PLEASE_CONFIGURE_YOUR_EMAIL'));
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'configureEmail', [], CONF_WEBROOT_FRONT_URL));
        }
        $user = new User($this->siteUserId);
        $user->setFldValue('user_lastseen', date('Y-m-d H:i:s'));
        $user->save();
        return true;
    }

    /**
     * Set Site Language
     * 
     * @return bool
     */
    private function setSiteLanguage()
    {
        MyUtility::setSystemLanguage();
        if (defined('CONF_SITE_LANGUAGE')) {
            $this->siteLangId = CONF_SITE_LANGUAGE;
            $this->siteLanguage = Language::getData($this->siteLangId);
            MyUtility::setSiteLanguage($this->siteLanguage, true);
            return true;
        }
        $langId = $_COOKIE['CONF_SITE_LANGUAGE'] ?? 0;
        if (API_CALL) {
            $langId = $_SERVER['HTTP_CONF_SITE_LANGUAGE'] ?? 0;
        }
        $langId = FatUtility::int($langId);
        $langData = Language::getData($langId);
        if (!empty($langData)) {
            $this->siteLangId = $langId;
            $this->siteLanguage = $langData;
            MyUtility::setSiteLanguage($this->siteLanguage);
            return true;
        }
        $langId = User::getAttributesById($this->siteUserId, 'user_lang_id');
        if (!empty($langId)) {
            $this->siteLangId = FatUtility::int($langId);
            $this->siteLanguage = Language::getData($this->siteLangId);
            MyUtility::setSiteLanguage($this->siteLanguage);
            return true;
        }
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langCode = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
            $langId = array_search($langCode, Language::getCodes());
            if (!empty($langId)) {
                $this->siteLangId = FatUtility::int($langId);
                $this->siteLanguage = Language::getData($this->siteLangId);
                MyUtility::setSiteLanguage($this->siteLanguage);
                return true;
            }
        }
        $this->siteLangId = FatApp::getConfig('CONF_SITE_LANGUAGE');
        $this->siteLanguage = Language::getData($this->siteLangId);
        if (empty($this->siteLanguage)) {
            MyUtility::setCookie('CONF_SITE_LANGUAGE', '', -1);
            return true;
        }
        MyUtility::setSiteLanguage($this->siteLanguage);
    }

    /**
     * Set Site Currency
     */
    private function setSiteCurrency()
    {
        MyUtility::setSystemCurrency();
        $currencyId = $this->siteUser['user_currency_id'] ?? 0;
        if (empty($currencyId)) {
            $currencyId = $_COOKIE['CONF_SITE_CURRENCY'] ?? 0;
            if (API_CALL) {
                $currencyId = $_SERVER['HTTP_CONF_SITE_CURRENCY'] ?? 0;
            }
        }
        $currencyId = FatUtility::int($currencyId);
        $currencyData = Currency::getData($currencyId, $this->siteLangId);
        if (!empty($currencyData)) {
            $this->siteCurrId = FatUtility::int($currencyId);
            $this->siteCurrency = $currencyData;
            MyUtility::setSiteCurrency($this->siteCurrency);
            return true;
        }
        $this->siteCurrId = FatUtility::int(FatApp::getConfig('CONF_SITE_CURRENCY'));
        $this->siteCurrency = Currency::getData($this->siteCurrId, $this->siteLangId);
        if (empty($this->siteCurrency)) {
            MyUtility::setCookie('CONF_SITE_CURRENCY', '', -1);
            return true;
        }
        MyUtility::setSiteCurrency($this->siteCurrency);
    }

    /**
     * Set Site Timezone
     * 
     * @return boolean
     */
    private function setSiteTimezone()
    {
        MyUtility::setSystemTimezone();
        $timezone = $_COOKIE['CONF_SITE_TIMEZONE'] ?? '';
        if (API_CALL) {
            $timezone = $_SERVER['HTTP_CONF_SITE_TIMEZONE'] ?? '';
        }
        if (!empty($timezone)) {
            if (!MyUtility::isValidTimezone($timezone)) {
                /* some browsers return old timezone(eg. Asia/Calcutta) causing fatal errors. Set admin timezone for such cases. */
                $this->siteTimezone = Admin::getAttributesById(1, 'admin_timezone');
                MyUtility::setSiteTimezone($this->siteTimezone, true);
                return true;
            }
            $this->siteTimezone = $timezone;
            MyUtility::setSiteTimezone($timezone);
            return true;
        }
        if (!empty($this->siteUser['user_timezone'])) {
            $this->siteTimezone = $this->siteUser['user_timezone'];
            MyUtility::setSiteTimezone($this->siteTimezone);
            return true;
        }
        $this->siteTimezone = MyUtility::getSystemTimezone();
    }

    /**
     * Set Cookie Consent
     * 
     * @return boolean
     */
    private function setCookieConsent()
    {
        if (!empty($_COOKIE['CONF_SITE_CONSENTS'])) {
            $this->cookieConsent = json_decode($_COOKIE['CONF_SITE_CONSENTS'], true);
            MyUtility::setCookieConsents($this->cookieConsent);
            return true;
        }
        if (!empty($this->siteUserId)) {
            $cookieConsent = CookieConsent::getSettings($this->siteUserId);
            if (!empty($cookieConsent)) {
                $this->cookieConsent = json_decode($cookieConsent, true);
                MyUtility::setCookieConsents($this->cookieConsent);
                return true;
            }
        }
        return true;
    }

    /**
     * Get Site Languages
     * 
     * @return array
     */
    protected function getSiteLanguages(): array
    {
        $srch = new SearchBase(Language::DB_TBL);
        $srch->addMultipleFields(['language_id', 'language_code', 'lower(language_code) as lower_language_code', 'language_direction', 'language_name']);
        $srch->addCondition('language_active', '=', AppConstant::YES);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (!API_CALL) {
            return FatApp::getDb()->fetchAll($srch->getResultSet(), 'language_id');
        }
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Site Currencies
     * 
     * @return array
     */
    protected function getSiteCurrencies(): array
    {
        $srch = new SearchBase(Currency::DB_TBL, 'currency');
        $srch->joinTable(Currency::DB_TBL_LANG, 'LEFT JOIN', 'curlang.currencylang_currency_id = '
            . 'currency.currency_id AND curlang.currencylang_lang_id = ' . $this->siteLangId, 'curlang');
        $srch->addCondition('currency.currency_active', '=', AppConstant::YES);
        $srch->addMultipleFields(['currency_id', 'currency_code', 'currency_name']);
        $srch->addOrder('currency_order');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * App Settings
     */
    public function settings(int $type)
    {
        if (!API_CALL) {
            FatUtility::exitWithErrorCode(404);
        }
        MyUtility::dieJsonSuccess([
            'app_info' => MyUtility::getApps($type),
            'msg' => Label::getLabel('API_MY_APP_SETTINGS'),
            'siteLangId' => $this->siteLangId,
            'siteLanguage' => $this->siteLanguage,
            'siteCurrId' => $this->siteCurrId,
            'siteCurrency' => $this->siteCurrency,
            'siteTimezone' => $this->siteTimezone,
            'siteLanguages' => $this->getSiteLanguages(),
            'siteCurrencies' => $this->getSiteCurrencies(),
            'orderType' => [
                'lesson' => Order::TYPE_LESSON,
                'subscriptions' => Order::TYPE_SUBSCR,
                'groupClass' => Order::TYPE_GCLASS,
                'package' => Order::TYPE_PACKGE,
                'course' => Order::TYPE_COURSE,
                'wallet' => Order::TYPE_WALLET,
                'giftCard' => Order::TYPE_GFTCRD,
            ],
            'orderTypes' => [
                ['id' => Order::TYPE_LESSON, 'name' => Order::getTypeArr(Order::TYPE_LESSON)],
                ['id' => Order::TYPE_SUBSCR, 'name' => Order::getTypeArr(Order::TYPE_SUBSCR)],
                ['id' => Order::TYPE_GCLASS, 'name' => Order::getTypeArr(Order::TYPE_GCLASS)],
                ['id' => Order::TYPE_PACKGE, 'name' => Order::getTypeArr(Order::TYPE_PACKGE)],
                ['id' => Order::TYPE_COURSE, 'name' => Order::getTypeArr(Order::TYPE_COURSE)],
                ['id' => Order::TYPE_WALLET, 'name' => Order::getTypeArr(Order::TYPE_WALLET)],
                ['id' => Order::TYPE_GFTCRD, 'name' => Order::getTypeArr(Order::TYPE_GFTCRD)],
            ],
            'lesson' => AppConstant::LESSON,
            'gclass' => AppConstant::GCLASS,
            'lessonType' => [
                'freeTrail' => Lesson::TYPE_FTRAIL,
                'regular' => Lesson::TYPE_REGULAR,
                'subscription' => Lesson::TYPE_SUBCRIP
            ],
            'classType' => [
                'regular' => GroupClass::TYPE_REGULAR,
                'package' => GroupClass::TYPE_PACKAGE
            ],
            'lessonStatus' => [
                'unscheduled' => Lesson::UNSCHEDULED,
                'scheduled' => Lesson::SCHEDULED,
                'completed' => Lesson::COMPLETED,
                'cancelled' => Lesson::CANCELLED
            ],
            'disabled_modules' => [
                'group_classes' => FatApp::getConfig('CONF_GROUP_CLASSES_DISABLED', FatUtility::VAR_INT, 0)
            ],
            'labels' => MyUtility::makeFullUrl('cache', strtolower($this->siteLanguage['language_code']) . '.json'),
            'systemCurrencyCode' => MyUtility::getSystemCurrency()['currency_code'],
        ]);
    }

    /**
     * Catch All undefined actions
     * 
     * @param string $action
     */
    public function fatActionCatchAll(string $action)
    {
        $this->_template->render(false, false, 'error-pages/404.php');
    }

    /**
     * Check System Affiliate Mode
     * 
     * @return boolean
     */
    private function checkAffiliateModule()
    {
        if (User::isAffiliateEnabled()) {
            return true;
        }
        if ($this->siteUserType == User::AFFILIATE) {
            UserAuth::logout();
            AppToken::clearToken();
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('MSG_AFFILIATE_MODULE_IS_DISABLED_BY_ADMIN'));
            }
            Message::addErrorMessage(Label::getLabel('MSG_AFFILIATE_MODULE_IS_DISABLED_BY_ADMIN'));
            FatApp::redirectUser(MyUtility::makeUrl('home', '', [], CONF_WEBROOT_FRONT_URL));
        }
    }

    /**
     * Set Cookie Consent
     * 
     * @return boolean
     */
    protected function setUserSubscription()
    {
        if (!SubscriptionPlan::isEnabled()) {
            return $this->activePlan;
        }
        if (!empty($this->siteUserId)) {
            $this->activePlan = OrderSubscriptionPlan::getActivePlan($this->siteUserId);
        }
        return true;
    }

    protected function checkUserPasswordUpdate()
    {
        if (!UserAuth::getAdminLoggedIn() && !empty($this->siteUser) && !empty($this->siteUser['user_password_updated'])) {
            UserAuth::logout();
            AppToken::clearToken();
            $this->siteUser = [];
            $this->siteUserId = 0;
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('MSG_LOGGED_OUT_AS_PASSWORD_UPDATED_BY_ADMIN'));
            }
            Message::addErrorMessage(Label::getLabel('MSG_LOGGED_OUT_AS_PASSWORD_UPDATED_BY_ADMIN'));
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONT_URL));
        }
        return true;
    }

    protected function checkUserActiveStatus()
    {
        if (!empty($this->siteUser) && empty($this->siteUser['user_active'])) {
            UserAuth::logout();
            AppToken::clearToken();
            $this->siteUser = [];
            $this->siteUserId = 0;
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('MSG_LOGGED_OUT_AS_ACCOUNT_NO_LONGER_ACTIVE'));
            }
            Message::addErrorMessage(Label::getLabel('MSG_LOGGED_OUT_AS_ACCOUNT_NO_LONGER_ACTIVE'));
            FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONT_URL));
        }
        return true;
    }
}
