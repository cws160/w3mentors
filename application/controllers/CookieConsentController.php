<?php

/**
 * Cookie Consent Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class CookieConsentController extends MyAppController
{

    /**
     * Initialize Cookie Consent
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Accept All Cookies
     */
    public function acceptAll()
    {
        if ($this->siteUserId > 0) {
            $CookieConsent = new CookieConsent($this->siteUserId);
            $CookieConsent->updateSetting([], false);
        }
        MyUtility::setCookieConsents(CookieConsent::getDefaultConsent());
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_COOKIE_SETTINGS_UPDATE_SUCCESSFULLY'));
    }

    /**
     * Cookies Form
     */
    public function form()
    {
        $form = $this->getForm();
        if ($this->siteUserId > 0) {
            $cookieSetting = CookieConsent::getSettings($this->siteUserId);
            $form->fill(json_decode($cookieSetting, true));
        }
        $this->set('form', $form);
        $this->_template->render(false, false);
    }

    /**
     * Setup Cookies
     */
    public function setup()
    {
        $form = $this->getForm();
        $data = $form->getFormDataFromArray(FatApp::getPostedData());
        if ($data == false) {
            MyUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $data['necessary'] = 1;
        unset($data['btn_submit']);
        if ($this->siteUserId > 0) {
            $CookieConsent = new CookieConsent($this->siteUserId);
            $CookieConsent->updateSetting($data, false);
        }
        MyUtility::setCookieConsents($data, true);
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_COOKIE_SETTINGS_UPDATE_SUCCESSFULLY'));
    }

    /**
     * Get Cookies Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $form = new Form('cookieForm');
        $form = CommonHelper::setFormProperties($form);
        $checkboxValue = AppConstant::YES;
        $fld = $form->addCheckBox(Label::getLabel('LBL_NECESSARY'), CookieConsent::NECESSARY, $checkboxValue, [], true, 1);
        $fld->requirements()->setRange(1, 1);
        $form->addCheckBox(Label::getLabel('LBL_PREFERENCES'), CookieConsent::PREFERENCES, $checkboxValue, [], true, 0);
        $form->addCheckBox(Label::getLabel('LBL_STATISTICS'), CookieConsent::STATISTICS, $checkboxValue, [], true, 0);
        $form->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $form;
    }

    /**
     * Set Site Language
     */
    public function setSiteLanguage()
    {
        $url = urldecode(FatApp::getPostedData('url', FatUtility::VAR_STRING, ''));
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        MyUtility::setCookie('CONF_SITE_LANGUAGE', $langId);
        $uriPath = array_values(array_filter(explode("/", $url)));
        $urlLangId = array_search(strtolower($uriPath[0] ?? ''), Language::getCodes());
        if ($urlLangId !== false) {
            array_shift($uriPath);
        }
        $langCode = '';
        if (CONF_LANGCODE_URL && CONF_DEFAULT_LANG != $langId) {
            $langCode = Language::getCodes($langId);
        }
        $url = implode("/", $uriPath);
        $originalUrl = SeoUrl::getOriginalUrl($url);
        if (!empty($originalUrl['seourl_original'])) {
            $url = $originalUrl['seourl_original'];
            $customUrl = SeoUrl::getCustomUrl($langId, $url);
            if (!empty($customUrl)) {
                $url = $customUrl['seourl_custom'];
            }
        }
        if (CONF_LANGCODE_URL && CONF_DEFAULT_LANG != $langId && !empty($langCode)) {
            $url = $langCode . '/' . $url;
        }
        FatCache::clearAll();
        $uriPath = explode("/", $url);
        $params = array_slice($uriPath, 2);
        $controller = $uriPath[0] ?? '';
        $action = $uriPath[1] ?? '';
        User::setLanguage($this->siteUserId, $langId);
        $url = MyUtility::makeFullUrl($controller, $action, $params);
        $response = ['url' => $url, 'msg' => ''];
        if (API_CALL) {
            $code = strtolower(Language::getAttributesById($langId, 'language_code'));
            $response['labels'] = MyUtility::makeFullUrl('cache', $code . '.json');
        }
        FatUtility::dieJsonSuccess($response);
    }

    /**
     * Set Site Currency
     * 
     * @param int $currencyId
     */
    public function setSiteCurrency($currencyId)
    {
        User::setCurrency($this->siteUserId, $currencyId);
        MyUtility::setCookie('CONF_SITE_CURRENCY', $currencyId);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_CURRENCY_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Set Site Timezone
     * 
     * @param string $timezone
     */
    public function setSiteTimezone($timezone)
    {
        User::setTimezone($this->siteUserId, $timezone);
        MyUtility::setCookie('CONF_SITE_TIMEZONE', $timezone);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_TIMEZONE_UPDATED_SUCCESSFULLY'));
    }

}
