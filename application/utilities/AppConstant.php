<?php

/**
 * A Common Utility Class
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class AppConstant
{
    /* YES|NO Flag */

    public const NO = 0;
    public const YES = 1;
    /* Active Status */
    public const ACTIVE = 1;
    public const INACTIVE = 0;
    /* Default Pagesize */
    public const PAGESIZE = 12;
    /* Payment Status */
    public const UNPAID = 0;
    public const ISPAID = 1;
    /* Class Types */
    public const CLASS_1TO1 = 1;
    public const CLASS_GROUP = 2;
    /* Entity Types */
    const LESSON = 1;
    const GCLASS = 2;
    const COURSE = 3;
    const SUBPLAN = 4;
    /* weekdays */
    public const DAY_SUNDAY = 0;
    public const DAY_MONDAY = 1;
    public const DAY_TUESDAY = 2;
    public const DAY_WEDNESDAY = 3;
    public const DAY_THURSDAY = 4;
    public const DAY_FRIDAY = 5;
    public const DAY_SATURDAY = 6;
    /* Genders */
    public const GEN_MALE = 1;
    public const GEN_FEMALE = 2;
    public const GEN_NON_BINARY = 3;
    public const GEN_NOT_SAY = 4;
    /* Layouts */
    public const LAYOUT_LTR = 'ltr';
    public const LAYOUT_RTL = 'rtl';
    /* Buttons Style */
    public const THEME_BUTTONS_SHARP = 1;
    public const THEME_BUTTONS_ROUNDED = 2;
    public const THEME_BUTTONS_PILLS = 3;
    public const THEME_BUTTONS_LEAF = 4;

    /* Sorting */
    public const SORT_POPULARITY = 1;
    public const SORT_PRICE_ASC = 2;
    public const SORT_PRICE_DESC = 3;
    public const TARGET_CURRENT_WINDOW = "_self";
    public const TARGET_BLANK_WINDOW = "_blank";
    public const PERCENTAGE = 1;
    public const FLAT_VALUE = 2;
    public const SCREEN_DESKTOP = 1;
    public const SCREEN_IPAD = 2;
    public const SCREEN_MOBILE = 3;
    public const SMTP_TLS = 'tls';
    public const SMTP_SSL = 'ssl';
    public const PHONE_NO_REGEX = "^[0-9(\)-\-{\}  +-+]{4,16}$";
    public const SLUG_REGEX = "^[0-9a-z-\-]{4,200}$";
    public const CREDIT_CARD_NO_REGEX = "^(?:(4[0-9]{12}(?:[0-9]{3})?)|(5[1-5][0-9]{14})|(6(?:011|5[0-9]{2})[0-9]{12})|(3[47][0-9]{13})|(3(?:0[0-5]|[68][0-9])[0-9]{11})|((?:2131|1800|35[0-9]{3})[0-9]{11}))$";
    public const CVV_NO_REGEX = "^[0-9]{3,4}$";
    public const CLASS_TYPE_GROUP = 'group';
    public const CLASS_TYPE_1_TO_1 = '1to1';
    public const INTRODUCTION_VIDEO_LINK_REGEX = "^(?:https?:)?(?:\/\/)?(?:youtu\.be\/|(?:www\.|m\.)?youtube\.com\/(?:watch|v|embed)(?:\.php)?(?:\?.*v=|\/))([a-zA-Z0-9\_-]{7,15})(?:[\?&][a-zA-Z0-9\_-]+=[a-zA-Z0-9\_-]+)*(?:[&\/\#].*)?$";
    public const DATE_TIME_REGEX = "(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})";
    public const USERNAME_REGEX = "^[a-zA-Z0-9-]*$";
    public const PASSWORD_REGEX = "^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%-_]{8,15}$";
    public const URL_REGEX = "(?=.{5,253}$)((http|https):\/\/)(www.)?[a-zA-Z0-9@:%._\\+~#?&\/\/=-]{2,253}\\.[a-z]{2,6}\\b([-a-zA-Z0-9@:%._\\+~#?&\/\/=]*)";
    public const PASSWORD_CUSTOM_ERROR_MSG = "MSG_PASSWORD_MUST_BE_EIGHT_ALPHANUMERIC";

    /* Display View */
    public const VIEW_LISTING = 1;
    public const VIEW_CALENDAR = 2;
    public const VIEW_SHORT = 3;
    public const VIEW_DASHBOARD_LISTING = 4;
    public const SEARCH_SESSION = 'SEARCH_SESSION';
    /* Max inserts at a time (Inster in Batches) in DB */
    public const MAX_RECORDS_INSERT_PER_BATCH = 50;
    /* App Response Statuses */
    public const UNAUTH = -1;
    public const ERROR = 0;
    public const SUCCESS = 1;
    public const UNVERIFY = 2;
    /* Manage Prices */
    public const MANAGE_PRICE_ADMIN = 1;
    public const MANAGE_PRICE_TEACHER = 0;
    /* App Types */
    public const APP_ANDROID = 1;
    public const APP_IOS = 2;

    /* Global Search Filter Types */
    public const FILTER_ALL = 0;
    public const FILTER_GCLASS = 1;
    public const FILTER_COURSE = 2;
    public const FILTER_TEACHER = 3;
    public const FILTER_LANGUAGE = 4;


    /* Offline SESSIONS */
    const OFFLINE_LESSON = 1;
    const OFFLINE_CLASS = 2;

    const ABUSIVE_REGEX = '^\S*$';

    /* Availability View Types */
    const AVAIL_VIEW_ONLY = 1;
    const AVAIL_VIEW_BOOKING = 2;
    const AVAIL_VIEW_SCHEDULE = 3;
    /**
     * Return Array Value
     *
     * @param array $arr
     * @param int|string $key
     * @return array
     */
    public static function returArrValue(array $arr, $key = null)
    {
        if ($key === null) {
            return $arr;
        }
        return $arr[$key] ?? Label::getLabel('LBL_NA');
    }

    /**
     * Get Yes No Array
     *
     * @param int $key
     * @param int $langId
     * @return string|array
     */
    public static function getYesNoArr(int $key =null, int $langId = 0)
    {
        $arr = [
            static::YES => Label::getLabel('LBL_YES', $langId),
            static::NO => Label::getLabel('LBL_NO', $langId)
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Active Array
     *
     * @param int $key
     * @param int $langId
     * @return string|array
     */
    public static function getActiveArr(int $key = null, int $langId = 0)
    {
        $arr = [
            static::ACTIVE => Label::getLabel('LBL_ACTIVE', $langId),
            static::INACTIVE => Label::getLabel('LBL_INACTIVE', $langId)
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Genders
     *
     * @param int $key
     * @return string|array
     */
    public static function getGenders(int $key = null)
    {
        $arr = [
            static::GEN_MALE => Label::getLabel('LBL_MALE'),
            static::GEN_FEMALE => Label::getLabel('LBL_FEMALE'),
            static::GEN_NON_BINARY => Label::getLabel('LBL_NON_BINARY'),
            static::GEN_NOT_SAY => Label::getLabel('LBL_PREFER_NOT_TO_SAY'),
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Class Types
     *
     * @param int $key
     * @return string|array
     */
    public static function getClassTypes(int $key = null)
    {
        $arr = [
            static::CLASS_1TO1 => Label::getLabel('LBL_ONE_TO_ONE'),
            static::CLASS_GROUP => Label::getLabel('LBL_GROUP_CLASS')
        ];
        if (!GroupClass::isEnabled()) {
            unset($arr[static::CLASS_GROUP]);
        }
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Layout Directions
     *
     * @param string $key
     * @return string|array
     */
    public static function getLayoutDirections(string $key = null)
    {
        $arr = [
            static::LAYOUT_LTR => Label::getLabel('LBL_LEFT_TO_RIGHT'),
            static::LAYOUT_RTL => Label::getLabel('LBL_RIGHT_TO_LEFT'),
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Week Days
     *
     * @return array
     */
    public static function getWeekDays(): array
    {
        return [
            static::DAY_SUNDAY => Label::getLabel('LBL_Sun'),
            static::DAY_MONDAY => Label::getLabel('LBL_Mon'),
            static::DAY_TUESDAY => Label::getLabel('LBL_Tue'),
            static::DAY_WEDNESDAY => Label::getLabel('LBL_Wed'),
            static::DAY_THURSDAY => Label::getLabel('LBL_Thu'),
            static::DAY_FRIDAY => Label::getLabel('LBL_Fri'),
            static::DAY_SATURDAY => Label::getLabel('LBL_Sat')
        ];
    }

    /**
     * Get Sort by Array
     *
     * @param int $key
     * @return string|array
     */
    public static function getSortbyArr(int $key = null)
    {
        $arr = [
            static::SORT_POPULARITY => Label::getLabel('LBL_BY_POPULARITY'),
            static::SORT_PRICE_ASC => Label::getLabel('LBL_BY_PRICE_LOW_TO_HIGH'),
            static::SORT_PRICE_DESC => Label::getLabel('LBL_BY_PRICE_HIGH_TO_LOW'),
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Banner Type Array
     *
     * @param int $key
     * @return string|array
     */
    public static function bannerTypeArr(int $key = null)
    {
        $bannerTypeArr = Language::getAllNames();
        $arr = [0 => Label::getLabel('LBL_All_Languages')] + $bannerTypeArr;
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Link Targets Array
     *
     * @param int $key
     * @return string|array
     */
    public static function getLinkTargetsArr(int $key = null)
    {
        $arr = [
            static::TARGET_CURRENT_WINDOW => Label::getLabel('LBL_Same_Window'),
            static::TARGET_BLANK_WINDOW => Label::getLabel('LBL_New_Window')
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Percentage Flat Array
     *
     * @param int $key
     * @return string|array
     */
    public static function getPercentageFlatArr(int $key = null)
    {
        $arr = [
            static::FLAT_VALUE => Label::getLabel('LBL_FLAT_VALUE'),
            static::PERCENTAGE => Label::getLabel('LBL_PERCENTAGE')
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get SMTP Secure
     *
     * @param int $key
     * @return string|array
     */
    public static function getSmtpSecureArr(int $key = null)
    {
        $arr = [
            static::SMTP_TLS => Label::getLabel('LBL_tls'),
            static::SMTP_SSL => Label::getLabel('LBL_ssl'),
        ];
        return static::returArrValue($arr, $key);
    }
    /**
     * Get Buttons Style
     *
     * @param int $key
     * @return string|array
     */
    public static function getButtonStyles(int $key = null)
    {
        $arr = [
            static::THEME_BUTTONS_SHARP => Label::getLabel('LBL_SHARP_EDGES'),
            static::THEME_BUTTONS_ROUNDED => Label::getLabel('LBL_ROUND_EDGES_(SLIGHTLY_ROUNDED)'),
            static::THEME_BUTTONS_PILLS => Label::getLabel('LBL_PILL_STYLE_(FULLY_ROUNDED)'),
            static::THEME_BUTTONS_LEAF => Label::getLabel('LBL_LEAF_STYLE'),
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Empty Day Slots
     *
     * @return array
     */
    public static function getEmptyDaySlots(): array
    {
        return [
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
        ];
    }

    /**
     * Get Booking Slots
     *
     * @return array
     */
    public static function getBookingSlots(): array
    {
        return [
            15 => 15, 30 => 30,
            45 => 45, 60 => 60,
            90 => 90, 120 => 120
        ];
    }

    /**
     * Get Group Class Slots
     *
     * @return array
     */
    public static function getGroupClassSlots(): array
    {
        return [
            15 => 15, 30 => 30,
            45 => 45, 60 => 60,
            90 => 90, 120 => 120
        ];
    }

    /**
     * Format Class Slots
     *
     * @param array $durations
     * @return array
     */
    public static function fromatClassSlots(array $durations = null): array
    {
        $durations = is_null($durations) ? explode(',', FatApp::getConfig('CONF_GROUP_CLASS_DURATION')) : $durations;
        $returnArray = [];
        foreach ($durations as $value) {
            $returnArray[$value] = $value . ' ' . Label::getLabel('LBL_MINUTES');
        }
        return $returnArray;
    }

    /**
     * Get Months Array
     *
     * @return array
     */
    public static function getMonthsArr(): array
    {
        return [
            '01' => Label::getLabel('LBL_January'),
            '02' => Label::getLabel('LBL_Februry'),
            '03' => Label::getLabel('LBL_March'),
            '04' => Label::getLabel('LBL_April'),
            '05' => Label::getLabel('LBL_May'),
            '06' => Label::getLabel('LBL_June'),
            '07' => Label::getLabel('LBL_July'),
            '08' => Label::getLabel('LBL_August'),
            '09' => Label::getLabel('LBL_September'),
            '10' => Label::getLabel('LBL_October'),
            '11' => Label::getLabel('LBL_November'),
            '12' => Label::getLabel('LBL_December'),
        ];
    }

    /**
     * Rating Array
     *
     * @return array
     */
    public static function ratingArr(): array
    {
        return ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'];
    }

    /**
     * Get Display Views
     *
     * @return array
     */
    public static function getDisplayViews(): array
    {
        return [
            static::VIEW_LISTING => Label::getLabel('VIEW_LISTING'),
            static::VIEW_CALENDAR => Label::getLabel('VIEW_CALENDAR'),
            static::VIEW_DASHBOARD_LISTING => Label::getLabel('VIEW_DASHBOARD_LISTING'),
        ];
    }

    /**
     * Get Session Types
     *
     * @param int $key
     * @return string|array
     */
    public static function getSessionTypes(int $key = null)
    {
        $arr = [
            static::LESSON => Label::getLabel('LBL_LESSON'),
            static::GCLASS => Label::getLabel('LBL_GROUP_CLASS'),
            static::COURSE => Label::getLabel('LBL_COURSE'),
        ];
        return static::returArrValue($arr, $key);
    }
    
    /**
     * Get Homepage Search Filter Types
     *
     * @param integer $key
     * @return string|array
     */
    public static function getFilterTypes(int $key = null)
    {
        $arr = [
            AppConstant::FILTER_ALL => Label::getLabel('LBL_ALL'),
            AppConstant::FILTER_COURSE => Label::getLabel('LBL_COURSES'),
            AppConstant::FILTER_LANGUAGE => Label::getLabel('LBL_LANGUAGES'),
            AppConstant::FILTER_TEACHER => Label::getLabel('LBL_TEACHERS'),
            AppConstant::FILTER_GCLASS => Label::getLabel('LBL_GROUP_CLASSES'),
        ];
        if (!Course::isEnabled()) {
            unset($arr[AppConstant::FILTER_COURSE]);
        }
        if (!GroupClass::isEnabled()) {
            unset($arr[AppConstant::FILTER_GCLASS]);
        }
        return AppConstant::returArrValue($arr, $key);
    }

    public static function managePrices(int $key = null)
    {
        $arr = [
            static::MANAGE_PRICE_ADMIN => Label::getLabel('LBL_ADMIN_MANAGEABLE_PRICING'),
            static::MANAGE_PRICE_TEACHER => Label::getLabel('LBL_TEACHER_MANAGEABLE_PRICING')
        ];
        return static::returArrValue($arr, $key);
    }

    public static function getAppTypes(int $key = null)
    {
        $arr = [
            static::APP_ANDROID => Label::getLabel('LBL_APP_ANDROID'),
            static::APP_IOS => Label::getLabel('LBL_APP_IOS')
        ];
        return static::returArrValue($arr, $key);
    }

    /**
     * Get Service Type
     *
     * @param string $key
     * @return string|array
     */
    public static function getServiceType(string $key = null)
    {
        $arr = [
            static::NO => Label::getLabel('LBL_ONLINE'),
            static::YES => Label::getLabel('LBL_OFFLINE'),
        ];
        return static::returArrValue($arr, $key);
    }

}
