<?php

/**
 * A Common MyUtility
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class MyUtility extends FatUtility
{
    private static $userIp;
    private static $siteLangId = 1;
    private static $siteCurrId = 1;
    private static $siteLanguage;
    private static $siteCurrency;
    private static $siteTimezone;
    private static $systemTimezone;
    private static $systemLanguage;
    private static $systemCurrency;
    private static $cookieConsents;

    /**
     * Get User Agent
     *
     * @return string
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get User Type
     *
     * @return int
     */
    public static function getUserType(): int
    {
        return (API_CALL) ? User::LEARNER : $_SESSION['SITE_USER_TYPE'] ?? 0;
    }

    /**
     * Set User Type
     *
     * @param int $userType
     */
    public static function setUserType(int $userType)
    {
        $_SESSION['SITE_USER_TYPE'] = $userType;
    }

    /**
     * Set Site Language
     *
     * @param array $arr
     * @param bool $forcefully
     * @return void
     */
    public static function setSiteLanguage(array $arr, bool $forcefully = false): void
    {
        self::$siteLanguage = $arr;
        self::$siteLangId = static::int($arr['language_id']);
        if (empty($_COOKIE['CONF_SITE_LANGUAGE']) || $forcefully) {
            static::setCookie('CONF_SITE_LANGUAGE', self::$siteLangId);
        }
        header('CONF_SITE_LANGUAGE:' . self::$siteLangId);
    }

    /**
     * Get Site Lang Id
     *
     * @return int
     */
    public static function getSiteLangId(): int
    {
        return self::$siteLangId ?? 1;
    }

    /**
     * Get Site Language
     *
     * @return array
     */
    public static function getSiteLanguage(): array
    {
        return self::$siteLanguage;
    }

    /**
     * Set Site Currency
     *
     * @param array $arr
     * @return void
     */
    public static function setSiteCurrency(array $arr, bool $forcefully = false): void
    {
        self::$siteCurrency = $arr;
        self::$siteCurrId = static::int($arr['currency_id']);
        if (empty($_COOKIE['CONF_SITE_CURRENCY']) || $forcefully) {
            static::setCookie('CONF_SITE_CURRENCY', self::$siteCurrId);
        }
        header('CONF_SITE_CURRENCY:' . self::$siteCurrId);
    }

    /**
     * Get Site Currency Id
     *
     * @return int
     */
    public static function getSiteCurrId(): int
    {
        return self::$siteCurrId;
    }

    /**
     * Get Site Currency
     *
     * @return array
     */
    public static function getSiteCurrency(): array
    {
        return self::$siteCurrency;
    }

    /**
     * Set Admin Timezone
     *
     * @param string $timezone
     * @param bool $forcefully
     */
    public static function setAdminTimezone(string $timezone, bool $forcefully = false)
    {
        self::$siteTimezone = $timezone;
        if (empty($_COOKIE['CONF_ADMIN_TIMEZONE']) || $forcefully) {
            static::setCookie('CONF_ADMIN_TIMEZONE', self::$siteTimezone, 604800, CONF_WEBROOT_BACKEND, false);
        }
    }

    /**
     * Set Site Timezone
     *
     * @param string $timezone
     * @param bool $forcefully
     */
    public static function setSiteTimezone(string $timezone, bool $forcefully = false)
    {
        self::$siteTimezone = $timezone;
        if (empty($_COOKIE['CONF_SITE_TIMEZONE']) || $forcefully) {
            static::setCookie('CONF_SITE_TIMEZONE', self::$siteTimezone, 604800, CONF_WEBROOT_FRONT_URL, false);
        }
        header('CONF_SITE_TIMEZONE: ' . self::$siteTimezone);
    }

    /**
     * Get Site Timezone
     *
     * @return string
     */
    public static function getSiteTimezone(): string
    {
        return empty(self::$siteTimezone) ? CONF_SERVER_TIMEZONE : self::$siteTimezone;
    }

    /**
     * Set Cookie Consents
     *
     * @param array $arr
     */
    public static function setCookieConsents(array $arr, bool $forcefully = false)
    {
        self::$cookieConsents = json_encode($arr);
        if (empty($_COOKIE['CONF_SITE_CONSENTS']) || $forcefully) {
            static::setCookie('CONF_SITE_CONSENTS', self::$cookieConsents);
        }
    }

    /**
     * Get Cookie Consents
     *
     * @return array
     */
    public static function getCookieConsents(): array
    {
        return json_decode(self::$cookieConsents, true);
    }

    /**
     * Set System Timezone
     */
    public static function setSystemTimezone()
    {
        self::$systemTimezone = CONF_SERVER_TIMEZONE;
    }

    /**
     * Get System Timezone
     *
     * @return string
     */
    public static function getSystemTimezone(): string
    {
        return self::$systemTimezone;
    }

    /**
     * Set System Language
     */
    public static function setSystemLanguage()
    {
        self::$systemLanguage = Language::getData(CONF_DEFAULT_LANG);
    }

    /**
     * @return array
     */
    public static function getSystemLanguage()
    {
        return self::$systemLanguage;
    }

    /**
     * Set System Currency
     */
    public static function setSystemCurrency()
    {
        self::$systemCurrency = Currency::getSystemCurrency(self::$siteLangId);
    }

    /**
     * Get System Currency
     *
     * @return type
     */
    public static function getSystemCurrency()
    {
        return self::$systemCurrency;
    }

    /**
     * Get System System Currency Symbol
     *
     * @return string
     */
    public static function getSystemCurrencySymbol(): string
    {
        return trim(self::$systemCurrency['currency_symbol']);
    }

    /**
     * Get System Currency Negative Format
     *
     * @return string
     */
    public static function getSystemCurrencyNegativeFormat(): string
    {
        return trim(self::$systemCurrency['currency_negative_format']);
    }

    /**
     * Get System Currency Positive Format
     *
     * @return string
     */
    public static function getSystemCurrencyPositiveFormat(): string
    {
        return trim(self::$systemCurrency['currency_positive_format']);
    }

    /**
     * Get System Currency Decimal Symbol
     *
     * @return string
     */
    public static function getSystemCurrencyDecimalSymbol(): string
    {
        return trim(self::$systemCurrency['currency_decimal_symbol']);
    }

    /**
     * Get System Currency Grouping Symbol
     *
     * @return string
     */
    public static function getSystemCurrencyGroupingSymbol(): string
    {
        return trim(self::$systemCurrency['currency_grouping_symbol']);
    }

    /**
     * Get Currency Symbol
     *
     * @return string
     */
    public static function getCurrencySymbol(): string
    {
        return trim(self::$siteCurrency['currency_symbol']);
    }

    /**
     * Get Currency Negative Format
     *
     * @return string
     */
    public static function getCurrencyNegativeFormat(): string
    {
        return trim(self::$siteCurrency['currency_negative_format']);
    }

    /**
     * Get Currency Positive Format
     *
     * @return string
     */
    public static function getCurrencyPositiveFormat(): string
    {
        return trim(self::$siteCurrency['currency_positive_format']);
    }

    /**
     * Get Currency Decimal Symbol
     *
     * @return string
     */
    public static function getCurrencyDecimalSymbol(): string
    {
        return trim(self::$siteCurrency['currency_decimal_symbol']);
    }

    /**
     * Get Currency Grouping Symbol
     *
     * @return string
     */
    public static function getCurrencyGroupingSymbol(): string
    {
        return trim(self::$siteCurrency['currency_grouping_symbol']);
    }

    /**
     * Get Layout Direction
     *
     * @return string
     */
    public static function getLayoutDirection(): string
    {
        return self::$siteLanguage['language_direction'];
    }

    /**
     * Get Site Languages
     *
     * @return array
     */
    public static function getSiteLanguages()
    {
        $srch = new SearchBase(Language::DB_TBL);
        $srch->addMultipleFields(['language_id', 'language_code', 'language_name']);
        $srch->addCondition('language_active', '=', AppConstant::YES);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Site Currencies
     *
     * @param int $langId
     * @return array
     */
    public static function getSiteCurrencies(int $langId = 0)
    {
        $langId = empty($langId) ? self::$siteLangId : $langId;
        $srch = new SearchBase(Currency::DB_TBL, 'currency');
        $srch->joinTable(Currency::DB_TBL_LANG, 'LEFT JOIN', 'curlang.currencylang_currency_id = '
            . 'currency.currency_id AND curlang.currencylang_lang_id = ' . $langId, 'curlang');
        $srch->addCondition('currency.currency_active', '=', AppConstant::YES);
        $srch->addMultipleFields(['currency_id', 'currency_code', 'currency_name']);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Remove User Cookies
     */
    public function removeUserCookies()
    {
        static::setCookie('CONF_SITE_TIMEZONE', '', -604800, CONF_WEBROOT_FRONT_URL, false);
        static::setCookie('CONF_SITE_CONSENTS', '', -604800);
        static::setCookie('CONF_SITE_LANGUAGE', '', -604800);
    }

    /**
     * Get Common JS Labels
     *
     * @return array
     */
    public static function getCommonLabels(array $siteLanguages): array
    {
        $jsVariables = [
            'layoutDirection' => MyUtility::getLayoutDirection(),
            'isMandatory' => Label::getLabel('LBL_IS_MANDATORY'),
            'processing' => Label::getLabel('LBL_PROCESSING_PLEASE_WAIT'),
            'confirmRemove' => Label::getLabel('LBL_DO_YOU_WANT_TO_REMOVE'),
            'confirmCancel' => Label::getLabel('LBL_DO_YOU_WANT_TO_CANCEL'),
            'pleaseEnterValidEmailId' => Label::getLabel('VLBL_PLEASE_ENTER_VALID_EMAIL_ID_FOR'),
            'charactersSupportedFor' => Label::getLabel('VLBL_ONLY_CHARACTERS_ARE_SUPPORTED_FOR'),
            'pleaseEnterIntegerValue' => Label::getLabel('VLBL_PLEASE_ENTER_INTEGER_VALUE_FOR'),
            'pleaseEnterNumericValue' => Label::getLabel('VLBL_PLEASE_ENTER_NUMERIC_VALUE_FOR'),
            'startWithLetterOnlyAlphanumeric' => Label::getLabel('VLBL_START_WITH_LETTER_ONLY_ALPHANUMERIC'),
            'mustBeBetweenCharacters' => Label::getLabel('VLBL_LENGTH_MUST_BE_BETWEEN_6_TO_20_CHARACTERS'),
            'invalidValues' => Label::getLabel('VLBL_LENGTH_INVALID_VALUE_FOR'),
            'shouldNotBeSameAs' => Label::getLabel('VLBL_SHOULD_NOT_BE_SAME_AS'),
            'mustBeSameAs' => Label::getLabel('VLBL_MUST_BE_SAME_AS'),
            'mustBeGreaterOrEqual' => Label::getLabel('VLBL_MUST_BE_GREATER_THAN_OR_EQUAL_TO'),
            'mustBeGreaterThan' => Label::getLabel('VLBL_MUST_BE_GREATER_THAN'),
            'mustBeLessOrEqual' => Label::getLabel('VLBL_MUST_BE_LESS_THAN_OR_EQUAL_TO'),
            'mustBeLessThan' => Label::getLabel('VLBL_MUST_BE_LESS_THAN'),
            'mustBeBetween' => Label::getLabel('VLBL_MUST_BE_BETWEEN'),
            'selectRangeValidation' => Label::getLabel('VLBL_PLEASE_SELECT_From_{minval}_To_{maxval}_Option'),
            'lengthRangeValidation' => Label::getLabel('VLBL_LENGTH_OF_{caption}_MUST_BE_BETWEEN_{minlength}_AND_{maxlength}.'),
            'rangeValidation' => Label::getLabel('VLBL_VALUE_OF_{caption}_MUST_BE_BETWEEN_{minval}_AND_{maxval}.'),
            'pleaseSelect' => Label::getLabel('VLBL_PLEASE_SELECT'),
            'lengthOf' => Label::getLabel('VLBL_LENGTH_OF'),
            'valueOf' => Label::getLabel('VLBL_VALUE_OF'),
            'and' => Label::getLabel('VLBL_AND'),
            'Quit' => Label::getLabel('LBL_QUIT'),
            'Proceed' => Label::getLabel('LBL_PROCEED'),
            'Confirm' => Label::getLabel('LBL_CONFIRM'),
            'language' => Label::getLabel('LBL_TEACH_LANGUAGE'),
            'timezoneString' => Label::getLabel('LBL_TIMEZONE_STRING'),
            'myTimeZoneLabel' => Label::getLabel('LBL_MY_CURRENT_TIME'),
            'gdprDeleteAccDesc' => Label::getLabel('LBL_GDPR_DELETE_ACCOUNT_REQUEST_DESCRIPTION'),
            'LessonTitle' => Label::getLabel('LBL_Lesson_Title'),
            'LessonStartTime' => Label::getLabel('LBL_Lesson_Start_Time'),
            'today' => Label::getLabel('LBL_Today'),
            'prev' => Label::getLabel('LBL_Prev'),
            'next' => Label::getLabel('LBL_Next'),
            'done' => Label::getLabel('LBL_Done'),
            'confirmActivate' => Label::getLabel('LBL_ARE_YOU_SURE_YOU_WANT_TO_ACTIVATE'),
            'invalidRequest' => Label::getLabel('LBL_INVALID_REQUEST'),
            'delete' => Label::getLabel('LBL_DELETE'),
            'addClass' => Label::getLabel('LBL_ADD_CLASS'),
            'lessonNotAvailable' => Label::getLabel('LBL_LESSON_NOT_AVAILABLE'),
            'courseSrchPlaceholder' => Label::getLabel('LBL_BY_COURSE_NAME,_TEACHER_NAME,_TAGS'),
            'confirmRetake' => Label::getLabel('LBL_IF_YOU_RETAKE,_THE_EXISTING_PROGRESS_WILL_BE_RESET._CONTINUE?'),
            'courseProgressPercent' => Label::getLabel('LBL_{percent}%_COMPLETED'),
            'confirmCourseSubmission' => Label::getLabel('LBL_PLEASE_CONFIRM_YOU_WANT_TO_SUBMIT_COURSE_FOR_APPROVAL?'),
            'searching' => Label::getLabel('LBL_Searching'),
            'currencSymbol' => self::getCurrencySymbol(),
            'currencyDecimalSymbol' => self::getCurrencyDecimalSymbol(),
            'currencyGroupingSymbol' => self::getCurrencyGroupingSymbol(),
            'currencyNegativeFormat' => self::getCurrencyNegativeFormat(),
            'currencyPositiveFormat' => self::getCurrencyPositiveFormat(),
            'currencSystemSymbol' => self::getSystemCurrencySymbol(),
            'currencySystemDecimalSymbol' => self::getSystemCurrencyDecimalSymbol(),
            'currencySystemGroupingSymbol' => self::getSystemCurrencyGroupingSymbol(),
            'currencySystemNegativeFormat' => self::getSystemCurrencyNegativeFormat(),
            'currencySystemPositiveFormat' => self::getSystemCurrencyPositiveFormat(),
            'isNotAvailable' => Label::getLabel('LBL_IS_NOT_AVAILABLE'),
            'confirmUnsubscribeTag' => Label::getLabel('LBL_ARE_YOU_SURE_TO_UNSUBSCRIBE'),
            'confirmUnsubscribeAllTags' => Label::getLabel('LBL_ARE_YOU_SURE_TO_UNSUBSCRIBE_ALL'),
            'invalidExtension' => Label::getLabel('LBL_INVALID_EXTENSION'),
            'selectQuestions' => Label::getLabel('LBL_PLEASE_ADD_QUESTION(S)'),
            'confirmBindedQuesRemoval' => Label::getLabel('LBL_BINDED_QUESTION_REMOVAL_CONFIRMATION'),
            'confirmQuizComplete' => Label::getLabel('LBL_ARE_YOU_SURE_YOU_WANT_TO_MARK_QUIZ_COMPLETE?'),
            'defaultTimeZone' => self::getSystemTimezone(),
            'videoProcessing' => Label::getLabel('LBL_Video_Processing'),
            'fileUpload' => Label::getLabel('LBL_File_Uploaded'),
            'copied' => Label::getLabel('LBL_COPIED!'),
            'profileImageHeading' => Label::getLabel('LBL_SETUP_PROFILE_IMAGE'),
            'infoVideo' => Label::getLabel('LBL_INFO_VIDEO'),
            'noresultfound' => Label::getLabel('LBL_NO_RESULT_FOUND'),
            'planRenew' => Label::getLabel('LBL_YOU_CAN_RENEW_OR_UPGRADE_TO_NEW_PLAN'),
            'renew' => Label::getLabel('LBL_RENEW'),
            'upgrade' => Label::getLabel('LBL_UPGRADE'),
            'noSlotAvailable' => Label::getLabel('LBL_NO_SLOT_AVAILABLE'),
            'cancelSubscription' => Label::getLabel('LBL_SUBSCRIPTION_CANCEL_TEXT'),
            'confirmQuizReviewComplete' => Label::getLabel('LBL_ARE_YOU_SURE_YOU_WANT_TO_SUBMIT_EVALUATION?'),
            'confirmRetake' => Label::getLabel('LBL_IF_YOU_RETAKE,_THE_EXISTING_PROGRESS_WILL_BE_RESET._CONTINUE?'),
            'confirmUpdateStatus' => Label::getLabel('LBL_ARE_YOU_SURE_YOU_WANT_TO_UPDATE_STATUS?'),
            'confirmDelete' => Label::getLabel('LBL_DO_YOU_WANT_TO_DELETE'),
            'maxSlotsSelected' => Label::getLabel('LBL_MAX_SLOTS_SELECTED'),
            'maxSlotsFreeTiral' => Label::getLabel('LBL_ONLY_ONE_SLOT_CAN_BE_SELECTED_FOR_FREE_TRIAL'),
            'confirmCourseEditSubmission' => Label::getLabel('LBL_ARE_YOU_SURE_YOU_WANT_TO_SUBMIT_THE_UPDATES?'),
        ];
        foreach ($siteLanguages as $val) {
            $jsVariables['language' . $val['language_id']] = $val['language_direction'];
        }
        return $jsVariables;
    }

    /**
     * Get User IP
     *
     * @return string
     */
    public static function getUserIp(): string
    {
        if (!empty(self::$userIp)) {
            return self::$userIp;
        }
        if (getenv('HTTP_CLIENT_IP')) {
            self::$userIp = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            self::$userIp = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            self::$userIp = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            self::$userIp = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            self::$userIp = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            self::$userIp = getenv('REMOTE_ADDR');
        } else {
            self::$userIp = 'UNKNOWN';
        }
        return self::$userIp;
    }

    /**
     * Set Cookie
     *
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @return bool
     */
    public static function setCookie(string $name, string $value, int $expires = 604800, string $path = CONF_WEBROOT_FRONT_URL, bool $httponly = true): bool
    {
        $secure = (bool) FatApp::getConfig('CONF_USE_SSL');
        return setCookie($name, $value, [
            'path' => $path,
            'httponly' => $httponly,
            'secure' => $secure,
            'expires' => time() + $expires,
            'domain' => $_SERVER['HTTP_HOST'],
            'samesite' => $secure ? 'none' : '',
        ]);
    }

    /**
     * Make URL
     *
     * @param string $controller
     * @param string $action
     * @param array $queryData
     * @param string $root
     * @return string
     */
    public static function makeUrl($controller = '', $action = '', $queryData = [], $root = CONF_WEBROOT_URL): string
    {
        $url = FatUtility::generateUrl($controller, $action, $queryData, $root, CONF_URL_REWRITING_ENABLED);
        if (in_array(strtolower($controller), SeoUrl::staticControllers()) || !defined('SYSTEM_FRONT')) {
            return $url;
        }
        $langCode = '';
        if (!API_CALL && CONF_LANGCODE_URL && CONF_DEFAULT_LANG != self::$siteLangId) {
            $langCode = '/' . Language::getCodes(self::$siteLangId);
        }
        $row = SeoUrl::getCustomUrl(self::$siteLangId, trim($url, "/"));
        if (!empty($row['seourl_custom'])) {
            $urlArr = array_values(array_filter(explode("/", $url)));
            if (($urlArr[0] ?? '') == trim(CONF_WEBROOT_FRONTEND, '/')) {
                array_shift($urlArr);
                $url = implode("/", $urlArr);
            }
            $url = CONF_WEBROOT_FRONTEND . $row['seourl_custom'];
        }
        return urldecode($langCode . $url);
    }

    /**
     * Make Full URL
     *
     * @param string $controller
     * @param string $action
     * @param array $queryData
     * @param string $rootUrl
     * @return string
     */
    public static function makeFullUrl($controller = '', $action = '', $queryData = [], $rootUrl = '')
    {
        $url = static::generateUrl($controller, $action, $queryData, $rootUrl, CONF_URL_REWRITING_ENABLED);
        $protocol = (FatApp::getConfig('CONF_USE_SSL')) ? 'https://' : 'http://';
        return $protocol . $_SERVER['SERVER_NAME'] . urldecode($url);
    }

    /**
     * Generate Full Url
     *
     * @param string $controller
     * @param string $action
     * @param array $queryData
     * @param string $use_root_url
     * @param bool $url_rewriting
     * @return string
     */
    // public static function generateFullUrl($controller = '', $action = '', $queryData = [], $rootUrl = '', $url_rewriting = CONF_URL_REWRITING_ENABLED)
    public static function generateFullUrl($controller = '', $action = '', $queryData = [], $rootUrl = '', $url_rewriting = CONF_URL_REWRITING_ENABLED): string
    {
        $url = static::makeUrl($controller, $action, $queryData, $rootUrl, $url_rewriting);
        $protocol = (FatApp::getConfig('CONF_USE_SSL')) ? 'https://' : 'http://';
        return $protocol . $_SERVER['SERVER_NAME'] . urldecode($url);
    }

    /**
     * Format money
     *
     * @param float $value
     * @param bool $addsymbol
     * @param int $value
     * @return string
     */
    public static function formatMoney($value, bool $addsymbol = true, int $decimalPlace = 2): string
    {
        $value = static::convertToSiteCurrency(static::float($value));
        if (!$addsymbol) {
            return $value;
        }
        return static::addSymbolToMoney($value, 2);
    }

    public static function  addSymbolToMoney($value, $decimalPlace)
    {
        $format = (0 > $value) ? self::$siteCurrency['currency_negative_format'] : self::$siteCurrency['currency_positive_format'];
        $value = round(abs($value), $decimalPlace);
        $value = number_format($value, $decimalPlace, self::$siteCurrency['currency_decimal_symbol'], self::$siteCurrency['currency_grouping_symbol']);
        return Currency::format($format, $value, self::$siteCurrency['currency_symbol']);
    }

    public static function formatSystemMoney($value, bool $addsymbol = true): string
    {
        if (!$addsymbol) {
            return $value;
        }
        $format = (0 > $value) ? self::$systemCurrency['currency_negative_format'] : self::$systemCurrency['currency_positive_format'];
        $value = round(abs($value), 2);
        $value = number_format($value, 2, self::$systemCurrency['currency_decimal_symbol'], self::$systemCurrency['currency_grouping_symbol']);
        return Currency::format($format, $value, self::$systemCurrency['currency_symbol']);
    }

    /**
     * Convert To System Currency
     *
     * @param float $value
     * @return float
     */
    public static function convertToSystemCurrency(float $value): float
    {
        $value = static::float($value);
        return static::float($value / static::float(self::$siteCurrency['currency_value']));
    }

    /**
     * Convert To Site Currency
     *
     * @param float $value
     * @return float
     */
    public static function convertToSiteCurrency(float $value): float
    {
        return static::float($value) * static::float(static::$siteCurrency['currency_value']);
    }

    /**
     * Format Percent
     *
     * @param float $value
     * @return string
     */
    public static function formatPercent(float $value): string
    {
        return $value . '%';
    }

    /**
     * Get Currency Disclaimer
     *
     * @param float $amount
     * @return string
     */
    public static function getCurrencyDisclaimer(float $amount): string
    {
        $str = Label::getLabel('LBL_*_Note_:_charged_in_currency_disclaimer_{default-currency-symbol}');
        if ($amount) {
            $str = str_replace('{default-currency-symbol}', MyUtility::formatMoney($amount), $str);
        } else {
            $str = str_replace('{default-currency-symbol}', ' $ ', $str);
        }
        return $str;
    }

    /**
     * Get Active Slots
     *
     * @return array
     */
    public static function getActiveSlots(): array
    {
        return explode(',', FatApp::getConfig('CONF_PAID_LESSON_DURATION'));
    }

    /**
     * Validate YouTube URL
     *
     * @param string $link
     * @return string
     */
    public static function validateYoutubeUrl($link): string
    {
        if (empty($link)) {
            return '';
        }
        $pattern = "#" . AppConstant::INTRODUCTION_VIDEO_LINK_REGEX . "#";
        if (!preg_match($pattern, $link, $matches)) {
            return '';
        }
        if (empty($matches[1])) {
            $link = "//" . $link;
        }
        return $link;
    }

    /**
     * Mask and Disable Form Fields
     *
     * @param Form $frm
     * @param array $fieldsToSkip
     */
    public static function maskAndDisableFormFields(Form $frm, array $fieldsToSkip)
    {
        $flds = $frm->getAllFields();
        foreach ($flds as $fld) {
            if (!in_array($fld->getName(), $fieldsToSkip) && ('submit' != $fld->fldType)) {
                $fld->addFieldTagAttribute('disabled', 'disabled');
            }
            if (!in_array($fld->getName(), $fieldsToSkip) && 'text' == $fld->fldType || $fld->fldType == "textarea") {
                $fld->value = '***********';
            }
        }
        $frm->addHTML(Label::getLabel('LBL_Note'), 'note', '<span class="spn_must_field">' . Label::getLabel('NOTE_SETTINGS_NOT_ALLOWED_TO_BE_MODIFIED_ON_DEMO_VERSION') . '</span>')->setWrapperAttribute('class', 'text--center');
    }

    /**
     * Is Demo URL
     *
     * @return bool
     */
    public static function isDemoUrl(): bool
    {
        return (strtolower($_SERVER['SERVER_NAME']) === 'elearning.w3mentors.com');
    }

    /**
     * Format Time Slot Array
     *
     * @param array $arr
     * @return array
     */
    public static function formatTimeSlotArr(array $arr): array
    {
        $timeSlotArr = array_intersect_key(static::timeSlots(), array_flip($arr));
        $formattedArr = [];
        foreach ($timeSlotArr as $k => $timeSlot) {
            $breakTimeStrng = explode('-', $timeSlot);
            $formattedArr[$k]['startTime'] = $breakTimeStrng[0];
            $formattedArr[$k]['endTime'] = $breakTimeStrng[1];
        }
        return array_values($formattedArr);
    }

    /**
     * Validate Password
     *
     * @param string $string
     * @return bool
     */
    public static function validatePassword(string $string = ''): bool
    {
        if (strlen($string) < 1) {
            return false;
        }
        if (!preg_match('/' . AppConstant::PASSWORD_REGEX . '/', $string)) {
            return false;
        }
        return true;
    }

    /**
     * Time Slots
     *
     * @return array
     */
    public static function timeSlots(): array
    {
        return [
            0 => '00:00 - 04:00',
            1 => '04:00 - 08:00',
            2 => '08:00 - 12:00',
            3 => '12:00 - 16:00',
            4 => '16:00 - 20:00',
            5 => '20:00 - 24:00',
        ];
    }

    /**
     * Time Slot Array
     *
     * @return array
     */
    public static function timeSlotArr(): array
    {
        if(FatApp::getConfig('FRONTEND_TIME_FORMAT') == MyDate::FORMAT_12_HR) {
            $amLabel = Label::getLabel('LBL_AM');
            $pmLabel = Label::getLabel('LBL_PM');
            return [
                0 => '12 ' . $amLabel . ' - 04 ' . $amLabel,
                1 => '04 ' . $amLabel . ' - 08 ' . $amLabel,
                2 => '08 ' . $amLabel . ' - 12 ' . $pmLabel,
                3 => '12 ' . $pmLabel . ' - 04 ' . $pmLabel,
                4 => '04 ' . $pmLabel . ' - 08 ' . $pmLabel,
                5 => '08 ' . $pmLabel . ' - 12 ' . $amLabel,
            ];
        } else {
            return [
                0 => '00 - 04',
                1 => '04 - 08',
                2 => '08 - 12',
                3 => '12 - 16',
                4 => '16 - 20',
                5 => '20 - 24',
            ];
        }
    }

    /**
     * Write File
     *
     * @param string $name
     * @param type $data
     * @param type $response
     * @return bool
     */
    public static function writeFile(string $name, $data, &$response): bool
    {
        $fName = CONF_UPLOADS_PATH . preg_replace('/[^a-zA-Z0-9\/\-\_\.]/', '', $name);
        $dest = dirname($fName);
        if (!file_exists($dest)) {
            mkdir($dest, 0777, true);
        }
        $file = fopen($fName, 'w');
        if (!fwrite($file, $data)) {
            $response = Label::getLabel('MSG_Could_not_save_file.');
            return false;
        }
        fclose($file);
        $response = $fName;
        return true;
    }

    /**
     * Convert Bites To MBs
     *
     * @param float $size
     * @return string
     */
    public static function convertBitesToMb(float $size): string
    {
        return number_format($size / 1048576, 2);
    }

    /**
     * Get News Letter Form
     *
     * @return Form
     */
    public static function getNewsLetterForm()
    {
        $frm = new Form('frmNewsLetter');
        $frm = CommonHelper::setFormProperties($frm);
        $fld1 = $frm->addEmailField('', 'email');
        $fld1->requirements()->setRequired();
        $fld1->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_WITH_NONE);
        $frm->addSubmitButton('', 'btnSubmit', Label::getLabel('LBL_SUBSCRIBE'));
        return $frm;
    }

    /**
     * Outputs a success message $data in json format.
     *
     * @param mixed<string|array> $data
     *
     */
    public static function dieJsonError($data)
    {
        if (!API_CALL) {
            FatUtility::dieJsonError($data);
        }
        static::dieJson(AppConstant::ERROR, $data);
    }

    /**
     * Outputs a error message $data in json format.
     *
     * @param mixed<string|array> $data
     *
     */
    public static function dieJsonSuccess($data)
    {
        if (!API_CALL) {
            FatUtility::dieJsonSuccess($data);
        }
        static::dieJson(AppConstant::SUCCESS, $data);
    }

    public static function dieUnverified($data)
    {
        if (!API_CALL) {
            FatUtility::dieJsonError($data);
        }
        static::dieJson(AppConstant::UNVERIFY, $data);
    }

    public static function dieUnauthorised($data)
    {
        if (!API_CALL) {
            FatUtility::dieJsonError($data);
        }
        static::dieJson(AppConstant::UNAUTH, $data);
    }

    /**
     * Die the data in json format
     *
     * @param int $status
     * @param mixed<string|array> $data
     */
    private static function dieJson(int $status, $data)
    {
        $res = [
            'msg' => '',
            'status' => $status,
            'data' => new stdClass()
        ];
        if (is_string($data)) {
            $res['msg'] = $data;
        } elseif (is_array($data) && !empty($data)) {
            $res['msg'] = $data['msg'] ?? '';
            unset($data['msg']);
            $res['data'] = $data;
        }
        die(json_encode($res, JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * get Apache Request Headers
     *
     * @return void
     */
    public static function getApacheRequestHeaders()
    {
        return array_change_key_case(FatApp::getApacheRequestHeaders(), CASE_LOWER);
    }

    public static function convertFormToJson(Form $form, array $fieldsToSkip = [], bool $fieldNameAsKey = false): array
    {
        $fields = [];
        $allFields = $form->getAllFields();
        foreach ($allFields as $field) {
            $options = [];
            if (in_array($field->getName(), $fieldsToSkip)) {
                continue;
            }
            if (in_array($field->fldType, ['radio', 'checkboxes', 'select'])) {
                foreach ($field->options as $value => $title) {
                    array_push($options, [
                        'title' => $title,
                        'value' => $value
                    ]);
                }
            }
            $fieldArray = [
                'caption' => $field->getCaption(),
                'name' => $field->getName(),
                'type' => $field->fldType,
                'value' => $field->value,
                'options' => $options,
                'checked' => $field->checked,
            ];
            ($fieldNameAsKey) ? $fields[$field->getName()] = $fieldArray : array_push($fields, $fieldArray);
        }
        return $fields;
    }

    public static function getFavicon(int $langId = 0): string
    {
        $langId = empty($langId) ? MyUtility::getSiteLangId() : $langId;
        return MyUtility::makeUrl('image', 'show', [Afile::TYPE_FAVICON, 0, Afile::SIZE_ORIGINAL, $langId], CONF_WEBROOT_FRONTEND);
    }

    public static function getLogo(int $langId = 0): string
    {
        $langId = empty($langId) ? MyUtility::getSiteLangId() : $langId;
        $src = CONF_WEBROOT_FRONTEND.'images/logo.svg';
        $siteName = FatApp::getConfig('CONF_WEBSITE_NAME_'.$langId, FatUtility::VAR_STRING, '');

        return '<img alt="'.$siteName.'" src="'.$src.'" />';
    }

    /**
     * Create Slug
     *
     * @param string $title
     * @return string
     */
    public static function createSlug(string $title): string
    {
        $slug = str_replace("'", "", $title);
        $slug = preg_replace('/[^\p{L}\p{N}]/u', '_', $slug);
        $slug = self::removeUnderscore($slug);
        return self::removeHyphens($slug);
    }

    /**
     * Remove Hyphens
     *
     * @param string $slug
     * @return string
     */
    private static function removeHyphens(string $slug): string
    {
        $slug = str_replace('--', '-', $slug);
        if (strpos($slug, '--') !== false) {
            $slug = self::removeHyphens($slug);
        }
        return trim($slug, "-");
    }

    public static function openGraphImage(int $id): string
    {
        if (static::isDemoUrl()) {
            return MyUtility::makeFullUrl('images', 'og.png');
        } else {
            return MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_OPENGRAPH_IMAGE, $id]);
        }
    }

    /**
     * Remove Underscore
     *
     * @param string $slug
     * @return string
     */
    private static function removeUnderscore(string $slug): string
    {
        $slug = str_replace('__', '_', $slug);
        if (strpos($slug, '__') !== false) {
            $slug = self::removeUnderscore($slug);
        }
        return trim($slug, "_");
    }

    /**
     * Teacher(online|away) Status
     *
     * @return array $status
     */
    public static function getOnlineStatus($date): array
    {
        $status = ['class' => '', 'tooltip' => '', 'show' => false];
        if (empty($date)) {
            return $status;
        }
        if (!DateTime::createFromFormat('Y-m-d H:i:s', $date)) {
            return $status;
        }
        $currentDate = date('Y-m-d H:i:s');
        $diffInMins = round(abs(strtotime($currentDate) - strtotime($date)) / 60, 2);
        if ($diffInMins > 60) {
            return $status;
        }
        $status['show'] = true;
        if ($diffInMins < 1) {
            $status['class'] = 'online';
            $status['tooltip'] = Label::getLabel('LBL_ONLINE_NOW');
            return $status;
        }
        $status['class'] = 'away';
        $status['tooltip'] = MyDate::getDateTimeDifference($date, $currentDate, true);
        return $status;
    }

    /**
     * Calculate slot price
     *
     * @param float $price
     * @param int $duration
     * @return float
     */
    public static function slotPrice(float $price, int $duration): float
    {
        return round($price / 60 * $duration, 2);
    }

    /**
     * Get Mobile Apps
     * 
     * @param int $type
     * @return type
     */
    public static function getApps(int $type = null)
    {
        $srch = new SearchBase('tbl_app_versions');
        $srch->doNotCalculateRecords();
        if ($type != null) {
            $srch->addCondition('app_type', '=', $type);
        }
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($rows as $key => $row) {
            $row['app_updated'] = MyDate::formatDate($row['app_updated']);
            $rows[$key] = $row;
        }
        if ($type != null) {
            $rows = current($rows);
        }
        return $rows;
    }

    public static  function getSuperAdminTimeZone()
    {
        $srch = new SearchBase(Admin::DB_TBL);
        $srch->addCondition('admin_id', '=', 1);
        $srch->doNotCalculateRecords();
        $srch->addFld('admin_timezone');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row['admin_timezone'] ?? 'UTC';
    }

    /**
     * Validate the timezone
     *
     * @param string $timezone
     * @return boolean
     */
    public static function isValidTimezone(string $timezone)
    {
        return in_array($timezone, timezone_identifiers_list());
    }

}
