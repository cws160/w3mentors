<?php

/**
 * Admin Class is used to handle Admin Privilege
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminPrivilege
{
    public const DB_TBL = 'tbl_admin_permissions';
    public const SECTION_ADMIN_DASHBOARD = 1;
    public const SECTION_USERS = 2;
    public const SECTION_TEACHER_REQUEST = 3;
    public const SECTION_WITHDRAW_REQUESTS = 4;
    public const SECTION_TEACHER_REVIEWS = 5;
    public const SECTION_GROUP_CLASSES = 6;
    public const SECTION_MANAGE_ORDERS = 7;
    public const SECTION_LESSONS_ORDERS = 8;
    public const SECTION_SUBSCRI_ORDERS = 9;
    public const SECTION_CLASSES_ORDERS = 10;
    public const SECTION_PACKAGS_ORDERS = 11;
    public const SECTION_COURSES_ORDERS = 12;
    public const SECTION_WALLETS_ORDERS = 13;
    public const SECTION_GIFTCARD_ORDERS = 14;
    public const SECTION_ISSUES_REPORTED = 15;
    public const SECTION_TEACHER_PREFFERENCES = 16;
    public const SECTION_SPEAK_LANGUAGES = 17;
    public const SECTION_TEACH_LANGUAGES = 18;
    public const SECTION_ISSUE_REPORT_OPTIONS = 19;
    public const SECTION_CONTENT_PAGES = 20;
    public const SECTION_CONTENT_BLOCKS = 21;
    public const SECTION_NAVIGATION_MANAGEMENT = 22;
    public const SECTION_COUNTRIES = 24;
    public const SECTION_SOCIALPLATFORM = 25;
    public const SECTION_PRICE_SLAB = 26;
    public const SECTION_VIDEO_CONTENT = 27;
    public const SECTION_SLIDES = 28;
    public const SECTION_TESTIMONIAL = 30;
    public const SECTION_LANGUAGE_LABELS = 31;
    public const SECTION_FAQ = 32;
    public const SECTION_FAQ_CATEGORY = 33;
    public const SECTION_BLOG_POSTS = 34;
    public const SECTION_BLOG_POST_CATEGORIES = 35;
    public const SECTION_BLOG_CONTRIBUTIONS = 36;
    public const SECTION_BLOG_COMMENTS = 37;
    public const SECTION_GENERAL_SETTINGS = 38;
    public const SECTION_MEETING_TOOL = 40;
    public const SECTION_PAYMENT_METHODS = 41;
    public const SECTION_COMMISSION = 42;
    public const SECTION_CURRENCY_MANAGEMENT = 43;
    public const SECTION_EMAIL_TEMPLATES = 44;
    public const SECTION_META_TAGS = 45;
    public const SECTION_URL_REWRITE = 46;
    public const SECTION_ROBOTS = 47;
    public const SECTION_LESSON_TOP_LANGUAGES = 48;
    public const SECTION_CLASS_TOP_LANGUAGES = 49;
    public const SECTION_TEACHER_PERFORMANCE = 50;
    public const SECTION_LESSON_STATS = 52;
    public const SECTION_SALES_REPORT = 53;
    public const SECTION_SITE_MAPS = 54;
    public const SECTION_DISCOUNT_COUPONS = 55;
    public const SECTION_ADMIN_USERS = 56;
    public const SECTION_ADMIN_PERMISSIONS = 57;
    public const SECTION_GDPR_REQUESTS = 59;
    public const SECTION_LANGUAGE = 60;
    public const SECTION_THEME_MANAGEMENT = 61;
    public const SECTION_COURSE_CATEGORIES = 62;
    public const SECTION_COURSE = 63;
    public const SECTION_MANAGE_CERTIFICATES = 64;
    public const SECTION_COURSE_REQUESTS = 65;
    public const SECTION_COURSE_REFUND_REQUESTS = 66;
    public const SECTION_PACKAGE_CLASSES = 67;
    public const SECTION_COURSE_REVIEWS = 68;
    public const SECTION_SETTLEMENTS_REPORT = 69;
    public const SECTION_COURSE_LANGUAGES = 70;
    public const SECTION_MOBILE_APPS = 71;
    public const SECTION_APP_LABELS = 72;
    public const SECTION_ADMIN_EARNINGS = 75;
    public const SECTION_PAGE_LANG_DATA = 76;
    public const SECTION_STATES = 77;
    public const SECTION_REPORT_STATS_REGENERATE = 78;
    public const SECTION_AFFILIATE_COMMISSION = 79;
    public const SECTION_AFFILIATE_REPORT = 80;
    public const SECTION_ABUSIVE_WORDS = 81;
    public const SECTION_SUBSCRIPTION_PLAN = 82;
    public const SECTION_ORDER_SUBSCRIPTION_PLAN = 83;
    public const SECTION_QUESTIONS = 84;
    public const SECTION_QUIZZES = 85;
    public const SECTION_QUIZ_CATEGORIES = 86;
    public const SECTION_COURSE_EDIT_REQUESTS = 87;
    public const SECTION_DISCUSSION_FORUM = 101;
    public const SECTION_SPEAK_LANGUAGE_LEVELS = 102;
    public const SECTION_WALLET_BALANCE_REPORT = 103;
    public const SECTION_TEACHER_PAYOUTS_REPORT = 104;
    public const SECTION_HOURS_TAUGHT_REPORT = 105;

    const PRIVILEGE_NONE = 0;
    const PRIVILEGE_READ = 1;
    const PRIVILEGE_WRITE = 2;

    private static $instance = null;
    private $loadedPermissions = [];

    /**
     * Get Instance
     *
     * @return type
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Is Admin Super Admin
     *
     * @param int $adminId
     * @return bool
     */
    public static function isAdminSuperAdmin(int $adminId): bool
    {
        return (1 == $adminId);
    }

    /**
     * Get Permissions
     *
     * @return array
     */
    public static function getPermissions(): array
    {
        $langId = MyUtility::getSiteLangId();
        $arr = [
            static::PRIVILEGE_NONE => Label::getLabel('MSG_None', $langId),
            static::PRIVILEGE_READ => Label::getLabel('MSG_Read_Only', $langId),
            static::PRIVILEGE_WRITE => Label::getLabel('MSG_Read_and_Write', $langId)
        ];
        return $arr;
    }

    /**
     * Get Permission Modules
     *
     * @return array
     */
    public static function getPermissionModules($adminId = 0): array
    {
        $langId = MyUtility::getSiteLangId();
        $arr = [
            static::SECTION_ADMIN_DASHBOARD => Label::getLabel('MSG_ADMIN_DASHBOARD', $langId),
            static::SECTION_USERS => Label::getLabel('MSG_MANAGE_USERS', $langId),
            static::SECTION_TEACHER_REQUEST => Label::getLabel('MSG_TEACHER_REQUESTS', $langId),
            static::SECTION_WITHDRAW_REQUESTS => Label::getLabel('MSG_WITHDRAW_REQUESTS', $langId),
            static::SECTION_TEACHER_REVIEWS => Label::getLabel('MSG_TEACHER_REVIEWS', $langId),
            static::SECTION_GDPR_REQUESTS => Label::getLabel('MSG_GDPR_REQUESTS', $langId),
            static::SECTION_ADMIN_USERS => Label::getLabel('MSG_ADMIN_USERS', $langId),
            static::SECTION_MANAGE_ORDERS => Label::getLabel('MSG_MANAGE_ORDERS', $langId),
            static::SECTION_LESSONS_ORDERS => Label::getLabel('MSG_LESSONS_ORDERS', $langId),
            static::SECTION_SUBSCRI_ORDERS => Label::getLabel('MSG_RECURRING_LESSON_ORDERS', $langId),
            static::SECTION_CLASSES_ORDERS => Label::getLabel('MSG_CLASSES_ORDERS', $langId),
            static::SECTION_PACKAGS_ORDERS => Label::getLabel('MSG_PACKAGES_ORDERS', $langId),
            static::SECTION_GIFTCARD_ORDERS => Label::getLabel('MSG_GIFTCARD_ORDERS', $langId),
            static::SECTION_WALLETS_ORDERS => Label::getLabel('MSG_WALLET_ORDERS', $langId),
            static::SECTION_ISSUES_REPORTED => Label::getLabel('MSG_REPORTED_ISSUES', $langId),
            static::SECTION_TEACHER_PREFFERENCES => Label::getLabel('MSG_TEACHER_PREFERENCES', $langId),
            static::SECTION_SPEAK_LANGUAGES => Label::getLabel('MSG_SPOKEN_LANGUAGES', $langId),
            static::SECTION_SPEAK_LANGUAGE_LEVELS => Label::getLabel('MSG_SPOKEN_LANGUAGES_LEVELS', $langId),
            static::SECTION_TEACH_LANGUAGES => Label::getLabel('MSG_TEACHING_LANGUAGES', $langId),
            static::SECTION_ISSUE_REPORT_OPTIONS => Label::getLabel('MSG_ISSUE_REPORT_OPTIONS', $langId),
            static::SECTION_SLIDES => Label::getLabel('MSG_HOMEPAGE_SLIDES', $langId),
            static::SECTION_CONTENT_PAGES => Label::getLabel('MSG_CONTENT_PAGES', $langId),
            static::SECTION_CONTENT_BLOCKS => Label::getLabel('MSG_CONTENT_BLOCKS', $langId),
            static::SECTION_NAVIGATION_MANAGEMENT => Label::getLabel('MSG_NAVIGATION_MANAGEMENT', $langId),
            static::SECTION_COUNTRIES => Label::getLabel('MSG_COUNTRIES', $langId),
            static::SECTION_VIDEO_CONTENT => Label::getLabel('MSG_VIDEO_CONTENT', $langId),
            static::SECTION_TESTIMONIAL => Label::getLabel('MSG_TESTIMONIAL', $langId),
            static::SECTION_LANGUAGE_LABELS => Label::getLabel('MSG_LANGUAGE_LABELS', $langId),
            static::SECTION_FAQ_CATEGORY => Label::getLabel('MSG_MANAGE_FAQ_CATEGORIES', $langId),
            static::SECTION_FAQ => Label::getLabel('MSG_MANAGE_FAQS', $langId),
            static::SECTION_EMAIL_TEMPLATES => Label::getLabel('MSG_EMAIL_TEMPLATES', $langId),
            static::SECTION_GENERAL_SETTINGS => Label::getLabel('MSG_GENERAL_SETTINGS', $langId),
            static::SECTION_MEETING_TOOL => Label::getLabel('MSG_MEETING_TOOL', $langId),
            static::SECTION_PAYMENT_METHODS => Label::getLabel('MSG_PAYMENT_METHODS', $langId),
            static::SECTION_SOCIALPLATFORM => Label::getLabel('MSG_SOCIAL_PLATFORM', $langId),
            static::SECTION_DISCOUNT_COUPONS => Label::getLabel('MSG_DISCOUNT_COUPONS', $langId),
            static::SECTION_COMMISSION => Label::getLabel('MSG_COMMISSION', $langId),
            static::SECTION_CURRENCY_MANAGEMENT => Label::getLabel('MSG_CURRENCY_MANAGEMENT', $langId),
            static::SECTION_THEME_MANAGEMENT => Label::getLabel('Msg_THEME_MANAGEMENT', $langId),
            static::SECTION_BLOG_POST_CATEGORIES => Label::getLabel('MSG_BLOG_CATEGORIES', $langId),
            static::SECTION_BLOG_POSTS => Label::getLabel('MSG_BLOG_POSTS', $langId),
            static::SECTION_BLOG_COMMENTS => Label::getLabel('MSG_BLOG_COMMENTS', $langId),
            static::SECTION_BLOG_CONTRIBUTIONS => Label::getLabel('MSG_BLOG_CONTRIBUTIONS', $langId),
            static::SECTION_META_TAGS => Label::getLabel('MSG_META_TAGS', $langId),
            static::SECTION_URL_REWRITE => Label::getLabel('MSG_URL_REWRITING', $langId),
            static::SECTION_ROBOTS => Label::getLabel('MSG_ROBOTS_TXT', $langId),
            static::SECTION_SITE_MAPS => Label::getLabel('MSG_SITE_MAPS', $langId),
            static::SECTION_LESSON_TOP_LANGUAGES => Label::getLabel('MSG_LESSON_TOP_LANGUAGES', $langId),
            static::SECTION_TEACHER_PERFORMANCE => Label::getLabel('MSG_TEACHER_PERFORMANCE', $langId),
            static::SECTION_LESSON_STATS => Label::getLabel('MSG_LESSON_STATS', $langId),
            static::SECTION_SALES_REPORT => Label::getLabel('MSG_SALE_REPORT', $langId),
            static::SECTION_SETTLEMENTS_REPORT => Label::getLabel('MSG_SETTLEMENTS_REPORT', $langId),
            static::SECTION_WALLET_BALANCE_REPORT => Label::getLabel('MSG_WALLET_BALANCE_REPORT', $langId),
            static::SECTION_TEACHER_PAYOUTS_REPORT => Label::getLabel('MSG_TEACHER_PAYOUTS_REPORT', $langId),
            static::SECTION_HOURS_TAUGHT_REPORT => Label::getLabel('MSG_HOURS_TAUGHT_REPORT', $langId),
            static::SECTION_DISCUSSION_FORUM => Label::getLabel('LBL_Discussion_Forum', $langId),
            /* static::SECTION_MOBILE_APPS => Label::getLabel('MSG_MOBILE_APPS', $langId),
            static::SECTION_APP_LABELS => Label::getLabel('LBL_APP_LABELS', $langId), */
            static::SECTION_QUESTIONS => Label::getLabel('MSG_QUESTIONS', $langId),
            static::SECTION_QUIZZES => Label::getLabel('MSG_QUIZZES', $langId),
            static::SECTION_QUIZ_CATEGORIES => Label::getLabel('MSG_QUIZ_CATEGORIES', $langId),
            static::SECTION_ADMIN_EARNINGS => Label::getLabel('MSG_ADMIN_EARNINGS', $langId),
            static::SECTION_PAGE_LANG_DATA => Label::getLabel('MSG_PAGE_LANG_DATA', $langId),
            static::SECTION_STATES => Label::getLabel('MSG_STATES', $langId),
            static::SECTION_REPORT_STATS_REGENERATE => Label::getLabel('MSG_REPORT_STATS_REGENERATE', $langId),
            static::SECTION_ABUSIVE_WORDS => Label::getLabel('MSG_ABUSIVE_WORD', $langId),
            static::SECTION_MANAGE_CERTIFICATES => Label::getLabel('MSG_MANAGE_CERTIFICATES', $langId)
        ];
        if (static::isAdminSuperAdmin($adminId) || $adminId == 0) {
            $arr[static::SECTION_ADMIN_PERMISSIONS] = Label::getLabel('MSG_ADMIN_PERMISSIONS', $langId);
        }
        if (Course::isEnabled()) {
            $arr[static::SECTION_COURSE_CATEGORIES] = Label::getLabel('MSG_COURSE_CATEGORIES', $langId);
            $arr[static::SECTION_COURSE] = Label::getLabel('MSG_COURSE', $langId);
            $arr[static::SECTION_COURSE_REQUESTS] = Label::getLabel('MSG_COURSE_REQUESTS', $langId);
            $arr[static::SECTION_COURSE_REFUND_REQUESTS] = Label::getLabel('MSG_COURSE_REFUND_REQUESTS', $langId);
            $arr[static::SECTION_COURSE_REVIEWS] = Label::getLabel('MSG_COURSE_REVIEWS', $langId);
            $arr[static::SECTION_COURSE_LANGUAGES] = Label::getLabel('MSG_COURSE_LANGUAGES', $langId);
            $arr[static::SECTION_COURSES_ORDERS] = Label::getLabel('MSG_COURSE_ORDERS', $langId);
            $arr[static::SECTION_COURSE_EDIT_REQUESTS] = Label::getLabel('MSG_COURSE_EDIT_REQUESTS', $langId);
        }
        if (GroupClass::isEnabled()) {
            $arr[static::SECTION_GROUP_CLASSES] = Label::getLabel('MSG_GROUP_CLASSES', $langId);
            $arr[static::SECTION_PACKAGE_CLASSES] = Label::getLabel('MSG_PACKAGE_CLASSES', $langId);
            $arr[static::SECTION_CLASSES_ORDERS] = Label::getLabel('MSG_CLASSES_ORDERS', $langId);
            $arr[static::SECTION_PACKAGS_ORDERS] = Label::getLabel('MSG_PACKAGES_ORDERS', $langId);
            $arr[static::SECTION_CLASS_TOP_LANGUAGES] = Label::getLabel('MSG_CLASS_TOP_LANGUAGES', $langId);
        }
        if (User::isAffiliateEnabled()) {
            $arr[static::SECTION_AFFILIATE_COMMISSION] = Label::getLabel('MSG_AFFILIATE_COMMISSION', $langId);
            $arr[static::SECTION_AFFILIATE_REPORT] = Label::getLabel('MSG_AFFILIATE_REPORT', $langId);
        }
        if (SubscriptionPlan::isEnabled()) {
            $arr[static::SECTION_SUBSCRIPTION_PLAN] = Label::getLabel('MSG__SUBSCRIPTION_PLAN', $langId);
            $arr[static::SECTION_ORDER_SUBSCRIPTION_PLAN] = Label::getLabel('MSG__SUBSCRIPTION_PLAN_ORDERS', $langId);
        }
        return $arr;
    }

    /**
     * Get Admin Permission Level
     *
     * @param int $adminId
     * @param int $sectionId
     * @return int
     */
    private function getLevel(int $adminId, int $sectionId): int
    {
        if ($this->isAdminSuperAdmin($adminId)) {
            return static::PRIVILEGE_WRITE;
        }

        if (isset($this->loadedPermissions[$sectionId])) {
            return $this->loadedPermissions[$sectionId];
        }
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('admperm_admin_id', '=', $adminId);
        $srch->addCondition('admperm_section_id', '=', $sectionId);
        $srch->addFld('admperm_value');
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return !empty($record['admperm_value']) ? $record['admperm_value'] : static::PRIVILEGE_NONE;
    }

    /**
     * Check Permissions
     *
     * @param int $secId
     * @param int $level
     * @param bool $returnResult
     * @return mix boolean|string
     */
    private function checkPermission(int $secId, int $level, bool $returnResult = false)
    {
        if (!in_array($level, [static::PRIVILEGE_READ, static::PRIVILEGE_WRITE])) {
            trigger_error(Label::getLabel('MSG_INVALID_PERMISSION_LEVEL_CHECKED') . ' ' . $level, E_USER_ERROR);
        }
        $permissionLevel = $this->getLevel(AdminAuth::getLoggedAdminId(), $secId);
        $this->loadedPermissions[$secId] = $permissionLevel;
        if ($level > $permissionLevel) {
            if ($returnResult) {
                return false;
            }

            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('MSG_UNAUTHORIZED_ACCESS!'));
            }

            FatUtility::dieWithError(Label::getLabel('MSG_UNAUTHORIZED_ACCESS!'));
        }
        return true;
    }

    /**
     * Can View Admin Dashboard
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewAdminDashboard(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ADMIN_DASHBOARD, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewUsers(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_USERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditUsers(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_USERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Teacher Reviews
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewTeacherReviews(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_REVIEWS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Teacher Reviews
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditTeacherReviews(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_REVIEWS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Teacher Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewTeacherRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_REQUEST, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Teacher Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditTeacherRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_REQUEST, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Withdraw Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewWithdrawRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_WITHDRAW_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Withdraw Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditWithdrawRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_WITHDRAW_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Group Classes
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewGroupClasses(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GROUP_CLASSES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Group Classes
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditGroupClasses(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GROUP_CLASSES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_MANAGE_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_MANAGE_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Lessons Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewLessonsOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LESSONS_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Lessons Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditLessonsOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LESSONS_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Subscription Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSubscriptionOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SUBSCRI_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Subscription Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSubscriptionOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SUBSCRI_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Classes Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewClassesOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CLASSES_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Classes Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditClassesOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CLASSES_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Packages Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewPackagesOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PACKAGS_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Packages Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditPackagesOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PACKAGS_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Courses Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCoursesOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSES_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Courses Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCoursesOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSES_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Wallet Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewWalletOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_WALLETS_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Wallet Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditWalletOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_WALLETS_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Giftcard Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewGiftcardOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GIFTCARD_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Giftcard Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditGiftcardOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GIFTCARD_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Issues Reported
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewIssuesReported(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ISSUES_REPORTED, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Issues Reported
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditIssuesReported(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ISSUES_REPORTED, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Preferences
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewPreferences(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_PREFFERENCES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Preferences
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditPreferences(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_PREFFERENCES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Speak Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSpeakLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SPEAK_LANGUAGES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Speak Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSpeakLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SPEAK_LANGUAGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /* code added on 30-07-2019 TEACHING LANGUAGES SEPERATE OPTION */

    /**
     * Can View Teach Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewTeachLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACH_LANGUAGES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Teach Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditTeachLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACH_LANGUAGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Issue Report Options
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewIssueReportOptions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ISSUE_REPORT_OPTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Issue Report Options
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditIssueReportOptions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ISSUE_REPORT_OPTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Content Pages
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewContentPages(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CONTENT_PAGES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Content Pages
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditContentPages(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CONTENT_PAGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Content Blocks
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewContentBlocks(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CONTENT_BLOCKS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Content Blocks
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditContentBlocks(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CONTENT_BLOCKS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Navigation Management
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewNavigationManagement(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_NAVIGATION_MANAGEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Navigation Management
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditNavigationManagement(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_NAVIGATION_MANAGEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Countries
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCountries(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COUNTRIES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Countries
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCountries(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COUNTRIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Social Platforms
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSocialPlatforms(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SOCIALPLATFORM, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Social Platforms
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSocialPlatforms(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SOCIALPLATFORM, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Video Content
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewVideoContent(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_VIDEO_CONTENT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Video Content
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditVideoContent(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_VIDEO_CONTENT, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Slides
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSlides(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SLIDES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Slides
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSlides(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SLIDES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Testimonial
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewTestimonial(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TESTIMONIAL, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Testimonial
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditTestimonial(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TESTIMONIAL, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Language Label
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewLanguageLabel(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LANGUAGE_LABELS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Language Label
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditLanguageLabel(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LANGUAGE_LABELS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View FAQs
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewFaq(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_FAQ, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit FAQs
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditFaq(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_FAQ, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Faq Category
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewFaqCategory(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_FAQ_CATEGORY, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Faq Category
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditFaqCategory(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_FAQ_CATEGORY, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Blog Posts
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewBlogPosts(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_POSTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Blog Posts
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditBlogPosts(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_POSTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Blog Post Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewBlogPostCategories(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_POST_CATEGORIES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Blog Post Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditBlogPostCategories(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_POST_CATEGORIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Blog Contributions
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewBlogContributions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_CONTRIBUTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Blog Contributions
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditBlogContributions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_CONTRIBUTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Blog Comments
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewBlogComments(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_COMMENTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Blog Comments
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditBlogComments(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_BLOG_COMMENTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View General Settings
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewGeneralSettings(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GENERAL_SETTINGS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit General Settings
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditGeneralSettings(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GENERAL_SETTINGS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Meeting Tool
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewMeetingTool(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_MEETING_TOOL, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Meeting Tool
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditMeetingTool(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_MEETING_TOOL, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Payment Methods
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewPaymentMethods(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PAYMENT_METHODS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Payment Methods
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditPaymentMethods(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PAYMENT_METHODS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Commission Settings
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCommissionSettings(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COMMISSION, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Commission Settings
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCommissionSettings(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COMMISSION, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Currency Management
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCurrencyManagement(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CURRENCY_MANAGEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Currency Management
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCurrencyManagement(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CURRENCY_MANAGEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Email Templates
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewEmailTemplates(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_EMAIL_TEMPLATES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Email Templates
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditEmailTemplates(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_EMAIL_TEMPLATES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Meta Tags
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewMetaTags(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_META_TAGS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Meta Tags
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditMetaTags(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_META_TAGS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Url Rewrites
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSeoUrl(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_URL_REWRITE, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Url Rewrites
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSeoUrl(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_URL_REWRITE, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Robots Section
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewRobotsSection(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ROBOTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Robots Section
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditRobotsSection(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ROBOTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Lesson Languages
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewLessonLanguages(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LESSON_TOP_LANGUAGES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Class Languages
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewClassLanguages(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_CLASS_TOP_LANGUAGES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Teacher Performance
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewTeacherPerformance(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_PERFORMANCE, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Lesson Stats Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewLessonStatsReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LESSON_STATS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Sales Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSalesReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SALES_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Settlements Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSettlementsReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SETTLEMENTS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Site Map
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSiteMap(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SITE_MAPS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Site Map
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSiteMap(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SITE_MAPS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Discount Coupons
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewDiscountCoupons(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_DISCOUNT_COUPONS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Discount Coupons
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditDiscountCoupons(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_DISCOUNT_COUPONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Admin Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewAdminUsers(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ADMIN_USERS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Admin Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditAdminUsers(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ADMIN_USERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Admin Permissions
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewAdminPermissions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ADMIN_PERMISSIONS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Admin Permissions
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditAdminPermissions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ADMIN_PERMISSIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Gdpr Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewGdprRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GDPR_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Gdpr Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditGdprRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_GDPR_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LANGUAGE, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_LANGUAGE, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Themes
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewThemeManagement(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_THEME_MANAGEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Themes
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditThemeManagement(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_THEME_MANAGEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCourseCategories(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_CATEGORIES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCourseCategories(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_CATEGORIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewQuizCategories(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_QUIZ_CATEGORIES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditQuizCategories(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_QUIZ_CATEGORIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Courses
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCourses(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Courses
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCourses(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Course Requests
     */
    public function canViewCourseRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Course Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCourseRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Certificates
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCertificates(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_MANAGE_CERTIFICATES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Certificates
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCertificates(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_MANAGE_CERTIFICATES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Course Cancellation Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCourseRefundRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_REFUND_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Course Cancellation Requests
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCourseRefundRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_REFUND_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Package Classes
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewPackageClasses(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PACKAGE_CLASSES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Package Classes
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditPackageClasses(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PACKAGE_CLASSES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Course Reviews
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCourseReviews(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_REVIEWS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Course Reviews
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCourseReviews(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_REVIEWS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Course Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewCourseLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_LANGUAGES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Course Language
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditCourseLanguage(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_LANGUAGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Mobile Apps
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewAppPackages(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_MOBILE_APPS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Discussion Forum
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewDiscussionForum(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_DISCUSSION_FORUM, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Discussion Forum
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditDiscussionForum(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_DISCUSSION_FORUM, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View App Labels
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewAppLabels(bool $returnResult = false)
    {
        /* return $this->checkPermission(static::SECTION_APP_LABELS, static::PRIVILEGE_READ, $returnResult); */
        return false;
    }

    /**
     * Can Edit App Labels
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditAppLabels(bool $returnResult = false)
    {
        /* return $this->checkPermission(static::SECTION_APP_LABELS, static::PRIVILEGE_WRITE, $returnResult); */
        return false;
    }

    /**
     * Can View Questions
     * 
     * @param bool $returnResult
     * @return type
     */
    public function canViewQuestions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_QUESTIONS, static::PRIVILEGE_READ, $returnResult);
    }
    /**
     * Can Edit Questions
     * 
     * @param bool $returnResult
     * @return type
     */
    public function canEditQuestions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_QUESTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Quizzes
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewQuizzes(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_QUIZZES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Admin Earnings Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewAdminEarningsReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ADMIN_EARNINGS, static::PRIVILEGE_READ, $returnResult);
    }


    /**
     * Can View Admin Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewPageLangData(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PAGE_LANG_DATA, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Admin Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditPageLangData(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_PAGE_LANG_DATA, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Admin Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewStates(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_STATES, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Admin Users
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditStates(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_STATES, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Subscription Plan
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSubscriptionPlan(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SUBSCRIPTION_PLAN, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Subscription Plan Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSubscriptionPlanOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ORDER_SUBSCRIPTION_PLAN, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Subscription Plan Orders
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSubscriptionPlanOrders(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ORDER_SUBSCRIPTION_PLAN, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Subscription Plan
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSubscriptionPlan(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SUBSCRIPTION_PLAN, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Report Stats Regenerate 
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewReportStatsRegenerate(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_REPORT_STATS_REGENERATE, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can Edit Report Stats Regenerate 
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditReportStatsRegenerate(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_REPORT_STATS_REGENERATE, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Affiliate Commission
     * 
     * @param bool $returnResult
     * @return type
     */
    public function canViewAffiliateCommission(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_AFFILIATE_COMMISSION, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Affiliate Commission
     * 
     * @param bool $returnResult
     * @return type
     */
    public function canEditAffiliateCommission(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_AFFILIATE_COMMISSION, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Affiliate Commission
     * 
     * @param bool $returnResult
     * @return type
     */
    public function canViewAffiliateReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_AFFILIATE_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Abusive Words
     * 
     * @param bool $returnResult
     * @return type
     */
    public function canEditAbusiveWords(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ABUSIVE_WORDS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View Abusive Words
     * 
     * @param bool $returnResult
     * @return type
     */
    public function canViewAbusiveWords(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_ABUSIVE_WORDS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can View Speak Language Levels
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewSpeakLanguageLevels(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SPEAK_LANGUAGE_LEVELS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Speak Language Levels
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditSpeakLanguageLevels(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_SPEAK_LANGUAGE_LEVELS, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View User Wallet Balance Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewWalletBalanceReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_WALLET_BALANCE_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit User Wallet Balance Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditWalletBalanceReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_WALLET_BALANCE_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View User Payouts Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewPayoutsReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_PAYOUTS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit User Payouts Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditPayoutsReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_TEACHER_PAYOUTS_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    /**
     * Can View User Hours Taught Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewHoursTaughtReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_HOURS_TAUGHT_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit User Hours Taught Report
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditHoursTaughtReport(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_HOURS_TAUGHT_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }
    
    /* 
     * Can View Course Edit Requests
     */
    public function canViewCourseEditRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_EDIT_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Course Edit Requests 
     */
    public function canEditCourseEditRequests(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_COURSE_EDIT_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }
}
