<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminPrivilegeService
{
    public const PRIVILEGE_NONE = 0;
    public const PRIVILEGE_READ = 1;
    public const PRIVILEGE_WRITE = 2;

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

    /** @var array<int, int> */
    private array $cache = [];

    public function isSuperAdmin(int $adminId): bool
    {
        return $adminId === 1;
    }

    public function canView(int $adminId, int $sectionId): bool
    {
        return $this->permissionLevel($adminId, $sectionId) >= self::PRIVILEGE_READ;
    }

    public function canEdit(int $adminId, int $sectionId): bool
    {
        return $this->permissionLevel($adminId, $sectionId) >= self::PRIVILEGE_WRITE;
    }

    public function permissionLevel(int $adminId, int $sectionId): int
    {
        if ($this->isSuperAdmin($adminId)) {
            return self::PRIVILEGE_WRITE;
        }

        if (isset($this->cache[$sectionId])) {
            return $this->cache[$sectionId];
        }

        $value = DB::table('tbl_admin_permissions')
            ->where('admperm_admin_id', $adminId)
            ->where('admperm_section_id', $sectionId)
            ->value('admperm_value');

        $level = (int) ($value ?? self::PRIVILEGE_NONE);
        $this->cache[$sectionId] = $level;

        return $level;
    }

    /** @return array<string, bool> */
    public function privilegeFlags(int $adminId): array
    {
        return [
            'canViewAdminDashboard' => $this->canView($adminId, self::SECTION_ADMIN_DASHBOARD),
            'canViewUsers' => $this->canView($adminId, self::SECTION_USERS),
            'canEditUsers' => $this->canEdit($adminId, self::SECTION_USERS),
            'canViewTeacherRequests' => $this->canView($adminId, self::SECTION_TEACHER_REQUEST),
            'canEditTeacherRequests' => $this->canEdit($adminId, self::SECTION_TEACHER_REQUEST),
            'canViewWithdrawRequests' => $this->canView($adminId, self::SECTION_WITHDRAW_REQUESTS),
            'canEditWithdrawRequests' => $this->canEdit($adminId, self::SECTION_WITHDRAW_REQUESTS),
            'canViewTeacherReviews' => $this->canView($adminId, self::SECTION_TEACHER_REVIEWS),
            'canEditTeacherReviews' => $this->canEdit($adminId, self::SECTION_TEACHER_REVIEWS),
            'canViewGdprRequests' => $this->canView($adminId, self::SECTION_GDPR_REQUESTS),
            'canEditGdprRequests' => $this->canEdit($adminId, self::SECTION_GDPR_REQUESTS),
            'canViewAdminUsers' => $this->canView($adminId, self::SECTION_ADMIN_USERS),
            'canEditAdminUsers' => $this->canEdit($adminId, self::SECTION_ADMIN_USERS),
            'canViewAdminPermissions' => $this->canView($adminId, self::SECTION_ADMIN_PERMISSIONS),
            'canEditAdminPermissions' => $this->canEdit($adminId, self::SECTION_ADMIN_PERMISSIONS),
            'canViewGroupClasses' => $this->canView($adminId, self::SECTION_GROUP_CLASSES),
            'canEditGroupClasses' => $this->canEdit($adminId, self::SECTION_GROUP_CLASSES),
            'canViewPackageClasses' => $this->canView($adminId, self::SECTION_PACKAGE_CLASSES),
            'canEditPackageClasses' => $this->canEdit($adminId, self::SECTION_PACKAGE_CLASSES),
            'canViewCourseLanguage' => $this->canView($adminId, self::SECTION_COURSE_LANGUAGES),
            'canEditCourseLanguage' => $this->canEdit($adminId, self::SECTION_COURSE_LANGUAGES),
            'canViewCourseCategories' => $this->canView($adminId, self::SECTION_COURSE_CATEGORIES),
            'canEditCourseCategories' => $this->canEdit($adminId, self::SECTION_COURSE_CATEGORIES),
            'canViewCourses' => $this->canView($adminId, self::SECTION_COURSE),
            'canEditCourses' => $this->canEdit($adminId, self::SECTION_COURSE),
            'canViewCourseRequests' => $this->canView($adminId, self::SECTION_COURSE_REQUESTS),
            'canEditCourseRequests' => $this->canEdit($adminId, self::SECTION_COURSE_REQUESTS),
            'canViewCourseEditRequests' => $this->canView($adminId, self::SECTION_COURSE_EDIT_REQUESTS),
            'canEditCourseEditRequests' => $this->canEdit($adminId, self::SECTION_COURSE_EDIT_REQUESTS),
            'canViewCourseReviews' => $this->canView($adminId, self::SECTION_COURSE_REVIEWS),
            'canEditCourseReviews' => $this->canEdit($adminId, self::SECTION_COURSE_REVIEWS),
            'canViewCourseRefundRequests' => $this->canView($adminId, self::SECTION_COURSE_REFUND_REQUESTS),
            'canEditCourseRefundRequests' => $this->canEdit($adminId, self::SECTION_COURSE_REFUND_REQUESTS),
            'canViewQuizCategories' => $this->canView($adminId, self::SECTION_QUIZ_CATEGORIES),
            'canEditQuizCategories' => $this->canEdit($adminId, self::SECTION_QUIZ_CATEGORIES),
            'canViewQuestions' => $this->canView($adminId, self::SECTION_QUESTIONS),
            'canViewQuizzes' => $this->canView($adminId, self::SECTION_QUIZZES),
            'canViewOrders' => $this->canView($adminId, self::SECTION_MANAGE_ORDERS),
            'canEditOrders' => $this->canEdit($adminId, self::SECTION_MANAGE_ORDERS),
            'canViewLessonsOrders' => $this->canView($adminId, self::SECTION_LESSONS_ORDERS),
            'canViewSubscriptionOrders' => $this->canView($adminId, self::SECTION_SUBSCRI_ORDERS),
            'canViewSubscriptionPlanOrders' => $this->canView($adminId, self::SECTION_ORDER_SUBSCRIPTION_PLAN),
            'canViewClassesOrders' => $this->canView($adminId, self::SECTION_CLASSES_ORDERS),
            'canViewCoursesOrders' => $this->canView($adminId, self::SECTION_COURSES_ORDERS),
            'canViewPackagesOrders' => $this->canView($adminId, self::SECTION_PACKAGS_ORDERS),
            'canViewGiftcardOrders' => $this->canView($adminId, self::SECTION_GIFTCARD_ORDERS),
            'canViewWalletOrders' => $this->canView($adminId, self::SECTION_WALLETS_ORDERS),
            'canViewIssuesReported' => $this->canView($adminId, self::SECTION_ISSUES_REPORTED),
            'canViewPreferences' => $this->canView($adminId, self::SECTION_TEACHER_PREFFERENCES),
            'canEditPreferences' => $this->canEdit($adminId, self::SECTION_TEACHER_PREFFERENCES),
            'canViewSpeakLanguage' => $this->canView($adminId, self::SECTION_SPEAK_LANGUAGES),
            'canEditSpeakLanguage' => $this->canEdit($adminId, self::SECTION_SPEAK_LANGUAGES),
            'canViewSpeakLanguageLevels' => $this->canView($adminId, self::SECTION_SPEAK_LANGUAGE_LEVELS),
            'canEditSpeakLanguageLevels' => $this->canEdit($adminId, self::SECTION_SPEAK_LANGUAGE_LEVELS),
            'canViewTeachLanguage' => $this->canView($adminId, self::SECTION_TEACH_LANGUAGES),
            'canEditTeachLanguage' => $this->canEdit($adminId, self::SECTION_TEACH_LANGUAGES),
            'canViewIssueReportOptions' => $this->canView($adminId, self::SECTION_ISSUE_REPORT_OPTIONS),
            'canEditIssueReportOptions' => $this->canEdit($adminId, self::SECTION_ISSUE_REPORT_OPTIONS),
            'canViewSlides' => $this->canView($adminId, self::SECTION_SLIDES),
            'canEditSlides' => $this->canEdit($adminId, self::SECTION_SLIDES),
            'canViewContentPages' => $this->canView($adminId, self::SECTION_CONTENT_PAGES),
            'canViewContentBlocks' => $this->canView($adminId, self::SECTION_CONTENT_BLOCKS),
            'canViewNavigationManagement' => $this->canView($adminId, self::SECTION_NAVIGATION_MANAGEMENT),
            'canEditNavigationManagement' => $this->canEdit($adminId, self::SECTION_NAVIGATION_MANAGEMENT),
            'canViewCountries' => $this->canView($adminId, self::SECTION_COUNTRIES),
            'canEditCountries' => $this->canEdit($adminId, self::SECTION_COUNTRIES),
            'canViewStates' => $this->canView($adminId, self::SECTION_STATES),
            'canEditStates' => $this->canEdit($adminId, self::SECTION_STATES),
            'canViewVideoContent' => $this->canView($adminId, self::SECTION_VIDEO_CONTENT),
            'canEditVideoContent' => $this->canEdit($adminId, self::SECTION_VIDEO_CONTENT),
            'canViewTestimonial' => $this->canView($adminId, self::SECTION_TESTIMONIAL),
            'canEditTestimonial' => $this->canEdit($adminId, self::SECTION_TESTIMONIAL),
            'canViewLanguageLabel' => $this->canView($adminId, self::SECTION_LANGUAGE_LABELS),
            'canEditLanguageLabel' => $this->canEdit($adminId, self::SECTION_LANGUAGE_LABELS),
            'canViewFaqCategory' => $this->canView($adminId, self::SECTION_FAQ_CATEGORY),
            'canEditFaqCategory' => $this->canEdit($adminId, self::SECTION_FAQ_CATEGORY),
            'canViewFaq' => $this->canView($adminId, self::SECTION_FAQ),
            'canEditFaq' => $this->canEdit($adminId, self::SECTION_FAQ),
            'canViewEmailTemplates' => $this->canView($adminId, self::SECTION_EMAIL_TEMPLATES),
            'canEditEmailTemplates' => $this->canEdit($adminId, self::SECTION_EMAIL_TEMPLATES),
            'canViewAbusiveWords' => $this->canView($adminId, self::SECTION_ABUSIVE_WORDS),
            'canEditAbusiveWords' => $this->canEdit($adminId, self::SECTION_ABUSIVE_WORDS),
            'canViewCertificates' => $this->canView($adminId, self::SECTION_MANAGE_CERTIFICATES),
            'canEditCertificates' => $this->canEdit($adminId, self::SECTION_MANAGE_CERTIFICATES),
            'canViewGeneralSettings' => $this->canView($adminId, self::SECTION_GENERAL_SETTINGS),
            'canEditGeneralSettings' => $this->canEdit($adminId, self::SECTION_GENERAL_SETTINGS),
            'canViewMeetingTool' => $this->canView($adminId, self::SECTION_MEETING_TOOL),
            'canEditMeetingTool' => $this->canEdit($adminId, self::SECTION_MEETING_TOOL),
            'canViewPaymentMethods' => $this->canView($adminId, self::SECTION_PAYMENT_METHODS),
            'canEditPaymentMethods' => $this->canEdit($adminId, self::SECTION_PAYMENT_METHODS),
            'canViewSocialPlatforms' => $this->canView($adminId, self::SECTION_SOCIALPLATFORM),
            'canEditSocialPlatforms' => $this->canEdit($adminId, self::SECTION_SOCIALPLATFORM),
            'canViewDiscountCoupons' => $this->canView($adminId, self::SECTION_DISCOUNT_COUPONS),
            'canEditDiscountCoupons' => $this->canEdit($adminId, self::SECTION_DISCOUNT_COUPONS),
            'canViewCommissionSettings' => $this->canView($adminId, self::SECTION_COMMISSION),
            'canEditCommissionSettings' => $this->canEdit($adminId, self::SECTION_COMMISSION),
            'canViewCurrencyManagement' => $this->canView($adminId, self::SECTION_CURRENCY_MANAGEMENT),
            'canEditCurrencyManagement' => $this->canEdit($adminId, self::SECTION_CURRENCY_MANAGEMENT),
            'canViewThemeManagement' => $this->canView($adminId, self::SECTION_THEME_MANAGEMENT),
            'canEditThemeManagement' => $this->canEdit($adminId, self::SECTION_THEME_MANAGEMENT),
            'canViewPageLangData' => $this->canView($adminId, self::SECTION_PAGE_LANG_DATA),
            'canEditPageLangData' => $this->canEdit($adminId, self::SECTION_PAGE_LANG_DATA),
            'canViewSubscriptionPlan' => $this->canView($adminId, self::SECTION_SUBSCRIPTION_PLAN),
            'canViewAffiliateCommission' => $this->canView($adminId, self::SECTION_AFFILIATE_COMMISSION),
            'canEditAffiliateCommission' => $this->canEdit($adminId, self::SECTION_AFFILIATE_COMMISSION),
            'canViewBlogPostCategories' => $this->canView($adminId, self::SECTION_BLOG_POST_CATEGORIES),
            'canEditBlogPostCategories' => $this->canEdit($adminId, self::SECTION_BLOG_POST_CATEGORIES),
            'canViewBlogPosts' => $this->canView($adminId, self::SECTION_BLOG_POSTS),
            'canEditBlogPosts' => $this->canEdit($adminId, self::SECTION_BLOG_POSTS),
            'canViewBlogComments' => $this->canView($adminId, self::SECTION_BLOG_COMMENTS),
            'canEditBlogComments' => $this->canEdit($adminId, self::SECTION_BLOG_COMMENTS),
            'canViewBlogContributions' => $this->canView($adminId, self::SECTION_BLOG_CONTRIBUTIONS),
            'canEditBlogContributions' => $this->canEdit($adminId, self::SECTION_BLOG_CONTRIBUTIONS),
            'canViewMetaTags' => $this->canView($adminId, self::SECTION_META_TAGS),
            'canEditMetaTags' => $this->canEdit($adminId, self::SECTION_META_TAGS),
            'canViewSeoUrl' => $this->canView($adminId, self::SECTION_URL_REWRITE),
            'canEditSeoUrl' => $this->canEdit($adminId, self::SECTION_URL_REWRITE),
            'canViewRobotsSection' => $this->canView($adminId, self::SECTION_ROBOTS),
            'canEditRobotsSection' => $this->canEdit($adminId, self::SECTION_ROBOTS),
            'canEditSiteMap' => $this->canEdit($adminId, self::SECTION_SITE_MAPS),
            'canViewSiteMap' => $this->canView($adminId, self::SECTION_SITE_MAPS),
            'canViewLessonLanguages' => $this->canView($adminId, self::SECTION_LESSON_TOP_LANGUAGES),
            'canViewClassLanguages' => $this->canView($adminId, self::SECTION_CLASS_TOP_LANGUAGES),
            'canViewTeacherPerformance' => $this->canView($adminId, self::SECTION_TEACHER_PERFORMANCE),
            'canViewLessonStatsReport' => $this->canView($adminId, self::SECTION_LESSON_STATS),
            'canViewSalesReport' => $this->canView($adminId, self::SECTION_SALES_REPORT),
            'canViewSettlementsReport' => $this->canView($adminId, self::SECTION_SETTLEMENTS_REPORT),
            'canViewAdminEarningsReport' => $this->canView($adminId, self::SECTION_ADMIN_EARNINGS),
            'canViewAffiliateReport' => $this->canView($adminId, self::SECTION_AFFILIATE_REPORT),
            'canViewWalletBalanceReport' => $this->canView($adminId, self::SECTION_WALLET_BALANCE_REPORT),
            'canViewPayoutsReport' => $this->canView($adminId, self::SECTION_TEACHER_PAYOUTS_REPORT),
            'canViewHoursTaughtReport' => $this->canView($adminId, self::SECTION_HOURS_TAUGHT_REPORT),
            'canViewDiscussionForum' => $this->canView($adminId, self::SECTION_DISCUSSION_FORUM),
            'canEditDiscussionForum' => $this->canEdit($adminId, self::SECTION_DISCUSSION_FORUM),
            'canViewSalesReportRegenerate' => $this->canEdit($adminId, self::SECTION_REPORT_STATS_REGENERATE),
        ];
    }
}
