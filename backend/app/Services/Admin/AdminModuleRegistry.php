<?php

namespace App\Services\Admin;

use App\Services\Admin\Listings\AdminForumListingService;
use App\Services\Admin\Listings\AdminGenericListingService;
use App\Services\Admin\Listings\AdminBlogContributionsListingService;
use App\Services\Admin\Listings\AdminBlogCommentsListingService;
use App\Services\Admin\Listings\AdminBlogPostCategoriesListingService;
use App\Services\Admin\Listings\AdminBlogPostsListingService;
use App\Services\Admin\Listings\AdminCategoriesListingService;
use App\Services\Admin\Listings\AdminCourseEditRequestsListingService;
use App\Services\Admin\Listings\AdminCourseLanguageListingService;
use App\Services\Admin\Listings\AdminCourseRefundRequestsListingService;
use App\Services\Admin\Listings\AdminCourseRequestsListingService;
use App\Services\Admin\Listings\AdminCoursesListingService;
use App\Services\Admin\Listings\AdminOrderSubListingsService;
use App\Services\Admin\Listings\AdminOrdersListingService;
use App\Services\Admin\Listings\AdminCommissionListingService;
use App\Services\Admin\Listings\AdminCouponsListingService;
use App\Services\Admin\Listings\AdminMeetingToolsListingService;
use App\Services\Admin\Listings\AdminPaymentMethodsListingService;
use App\Services\Admin\Listings\AdminSocialPlatformsListingService;
use App\Services\Admin\Listings\AdminCurrencyListingService;
use App\Services\Admin\Listings\AdminPageLangDataListingService;
use App\Services\Admin\Listings\AdminThemesListingService;
use App\Services\Admin\Listings\AdminQuestionsListingService;
use App\Services\Admin\Listings\AdminQuizzesListingService;
use App\Services\Admin\Listings\AdminRatingReviewsListingService;
use App\Services\Admin\Listings\AdminReportedIssuesListingService;
use App\Services\Admin\Listings\AdminLessonStatsListingService;
use App\Services\Admin\Listings\AdminAdminEarningsListingService;
use App\Services\Admin\Listings\AdminAffiliateCommissionListingService;
use App\Services\Admin\Listings\AdminAffiliateReportListingService;
use App\Services\Admin\Listings\AdminMetaTagsListingService;
use App\Services\Admin\Listings\AdminHoursTaughtReportListingService;
use App\Services\Admin\Listings\AdminTeacherPayoutsReportListingService;
use App\Services\Admin\Listings\AdminWalletBalanceReportListingService;
use App\Services\Admin\Listings\AdminSalesReportListingService;
use App\Services\Admin\Listings\AdminSettlementsListingService;
use App\Services\Admin\Listings\AdminTeacherPerformanceListingService;
use App\Services\Admin\Listings\AdminTeacherPreferencesListingService;
use App\Services\Admin\Listings\AdminTopLanguagesListingService;
use App\Services\Admin\Listings\AdminUsersListingService;
use Illuminate\Http\Request;

class AdminModuleRegistry
{
    public function __construct(
        private AdminUsersListingService $users,
        private AdminBlogPostsListingService $blogPosts,
        private AdminBlogCommentsListingService $blogComments,
        private AdminBlogContributionsListingService $blogContributions,
        private AdminBlogPostCategoriesListingService $blogPostCategories,
        private AdminOrdersListingService $orders,
        private AdminPageLangDataListingService $pageLangData,
        private AdminCurrencyListingService $currencies,
        private AdminCommissionListingService $commission,
        private AdminCouponsListingService $coupons,
        private AdminSocialPlatformsListingService $socialPlatforms,
        private AdminPaymentMethodsListingService $paymentMethods,
        private AdminMeetingToolsListingService $meetingTools,
        private AdminThemesListingService $themes,
        private AdminOrderSubListingsService $orderSubListings,
        private AdminCoursesListingService $courses,
        private AdminCourseLanguageListingService $courseLanguages,
        private AdminCategoriesListingService $categories,
        private AdminCourseRequestsListingService $courseRequests,
        private AdminCourseEditRequestsListingService $courseEditRequests,
        private AdminCourseRefundRequestsListingService $courseRefundRequests,
        private AdminRatingReviewsListingService $ratingReviews,
        private AdminQuestionsListingService $questions,
        private AdminQuizzesListingService $quizzes,
        private AdminReportedIssuesListingService $reportedIssues,
        private AdminTeacherPreferencesListingService $teacherPreferences,
        private AdminForumListingService $forum,
        private AdminTopLanguagesListingService $topLanguages,
        private AdminTeacherPerformanceListingService $teacherPerformance,
        private AdminLessonStatsListingService $lessonStats,
        private AdminSalesReportListingService $salesReport,
        private AdminAdminEarningsListingService $adminEarnings,
        private AdminAffiliateCommissionListingService $affiliateCommissions,
        private AdminAffiliateReportListingService $affiliateReport,
        private AdminWalletBalanceReportListingService $walletBalanceReport,
        private AdminTeacherPayoutsReportListingService $teacherPayoutsReport,
        private AdminHoursTaughtReportListingService $hoursTaughtReport,
        private AdminMetaTagsListingService $metaTags,
        private AdminSettlementsListingService $settlements,
        private AdminGenericListingService $generic,
    ) {
    }

    /** @return array<string, array{section: int, service?: string}> */
    public function modules(): array
    {
        return [
            'users' => ['section' => AdminPrivilegeService::SECTION_USERS, 'service' => 'users'],
            'teacher-requests' => ['section' => AdminPrivilegeService::SECTION_TEACHER_REQUEST],
            'withdraw-requests' => ['section' => AdminPrivilegeService::SECTION_WITHDRAW_REQUESTS],
            'rating-reviews' => ['section' => AdminPrivilegeService::SECTION_TEACHER_REVIEWS, 'service' => 'rating-reviews'],
            'gdpr-requests' => ['section' => AdminPrivilegeService::SECTION_GDPR_REQUESTS],
            'admin-users' => ['section' => AdminPrivilegeService::SECTION_ADMIN_USERS],
            'group-classes' => ['section' => AdminPrivilegeService::SECTION_GROUP_CLASSES],
            'package-classes' => ['section' => AdminPrivilegeService::SECTION_PACKAGE_CLASSES],
            'course-languages' => ['section' => AdminPrivilegeService::SECTION_COURSE_LANGUAGES, 'service' => 'course-languages'],
            'categories' => ['section' => AdminPrivilegeService::SECTION_COURSE_CATEGORIES, 'service' => 'categories'],
            'courses' => ['section' => AdminPrivilegeService::SECTION_COURSE, 'service' => 'courses'],
            'course-requests' => ['section' => AdminPrivilegeService::SECTION_COURSE_REQUESTS, 'service' => 'course-requests'],
            'course-edit-requests' => ['section' => AdminPrivilegeService::SECTION_COURSE_EDIT_REQUESTS, 'service' => 'course-edit-requests'],
            'course-refund-requests' => ['section' => AdminPrivilegeService::SECTION_COURSE_REFUND_REQUESTS, 'service' => 'course-refund-requests'],
            'questions' => ['section' => AdminPrivilegeService::SECTION_QUESTIONS, 'service' => 'questions'],
            'quizzes' => ['section' => AdminPrivilegeService::SECTION_QUIZZES, 'service' => 'quizzes'],
            'orders' => ['section' => AdminPrivilegeService::SECTION_MANAGE_ORDERS, 'service' => 'orders'],
            'lessons' => ['section' => AdminPrivilegeService::SECTION_LESSONS_ORDERS, 'service' => 'lesson-orders'],
            'subscriptions' => ['section' => AdminPrivilegeService::SECTION_SUBSCRI_ORDERS, 'service' => 'subscription-orders'],
            'order-subscription-plans' => ['section' => AdminPrivilegeService::SECTION_ORDER_SUBSCRIPTION_PLAN, 'service' => 'order-subscription-plans'],
            'classes' => ['section' => AdminPrivilegeService::SECTION_CLASSES_ORDERS, 'service' => 'class-orders'],
            'course-orders' => ['section' => AdminPrivilegeService::SECTION_COURSES_ORDERS, 'service' => 'course-orders'],
            'packages' => ['section' => AdminPrivilegeService::SECTION_PACKAGS_ORDERS, 'service' => 'package-orders'],
            'giftcards' => ['section' => AdminPrivilegeService::SECTION_GIFTCARD_ORDERS, 'service' => 'giftcard-orders'],
            'wallet' => ['section' => AdminPrivilegeService::SECTION_WALLETS_ORDERS, 'service' => 'wallet-orders'],
            'reported-issues' => ['section' => AdminPrivilegeService::SECTION_ISSUES_REPORTED, 'service' => 'reported-issues'],
            'preferences' => ['section' => AdminPrivilegeService::SECTION_TEACHER_PREFFERENCES, 'service' => 'preferences'],
            'speak-language' => ['section' => AdminPrivilegeService::SECTION_SPEAK_LANGUAGES, 'service' => 'speak-language'],
            'speak-language-levels' => ['section' => AdminPrivilegeService::SECTION_SPEAK_LANGUAGE_LEVELS, 'service' => 'speak-language-levels'],
            'teach-language' => ['section' => AdminPrivilegeService::SECTION_TEACH_LANGUAGES, 'service' => 'teach-language'],
            'issue-report-options' => ['section' => AdminPrivilegeService::SECTION_ISSUE_REPORT_OPTIONS, 'service' => 'issue-report-options'],
            'slides' => ['section' => AdminPrivilegeService::SECTION_SLIDES],
            'content-pages' => ['section' => AdminPrivilegeService::SECTION_CONTENT_PAGES],
            'content-block' => ['section' => AdminPrivilegeService::SECTION_CONTENT_BLOCKS],
            'navigations' => ['section' => AdminPrivilegeService::SECTION_NAVIGATION_MANAGEMENT],
            'countries' => ['section' => AdminPrivilegeService::SECTION_COUNTRIES],
            'states' => ['section' => AdminPrivilegeService::SECTION_STATES],
            'video-content' => ['section' => AdminPrivilegeService::SECTION_VIDEO_CONTENT],
            'testimonials' => ['section' => AdminPrivilegeService::SECTION_TESTIMONIAL],
            'label' => ['section' => AdminPrivilegeService::SECTION_LANGUAGE_LABELS],
            'faq-categories' => ['section' => AdminPrivilegeService::SECTION_FAQ_CATEGORY],
            'faq' => ['section' => AdminPrivilegeService::SECTION_FAQ],
            'email-templates' => ['section' => AdminPrivilegeService::SECTION_EMAIL_TEMPLATES],
            'abusive-words' => ['section' => AdminPrivilegeService::SECTION_ABUSIVE_WORDS],
            'certificates' => ['section' => AdminPrivilegeService::SECTION_MANAGE_CERTIFICATES],
            'configurations' => ['section' => AdminPrivilegeService::SECTION_GENERAL_SETTINGS],
            'meeting-tools' => ['section' => AdminPrivilegeService::SECTION_MEETING_TOOL, 'service' => 'meeting-tools'],
            'payment-methods' => ['section' => AdminPrivilegeService::SECTION_PAYMENT_METHODS, 'service' => 'payment-methods'],
            'social-platform' => ['section' => AdminPrivilegeService::SECTION_SOCIALPLATFORM, 'service' => 'social-platform'],
            'coupons' => ['section' => AdminPrivilegeService::SECTION_DISCOUNT_COUPONS, 'service' => 'coupons'],
            'commission' => ['section' => AdminPrivilegeService::SECTION_COMMISSION, 'service' => 'commission'],
            'currency-management' => ['section' => AdminPrivilegeService::SECTION_CURRENCY_MANAGEMENT, 'service' => 'currencies'],
            'themes' => ['section' => AdminPrivilegeService::SECTION_THEME_MANAGEMENT, 'service' => 'themes'],
            'page-lang-data' => ['section' => AdminPrivilegeService::SECTION_PAGE_LANG_DATA, 'service' => 'page-lang-data'],
            'subscription-plans' => ['section' => AdminPrivilegeService::SECTION_SUBSCRIPTION_PLAN],
            'affiliate-commission' => ['section' => AdminPrivilegeService::SECTION_AFFILIATE_COMMISSION, 'service' => 'affiliate-commission'],
            'blog-post-categories' => ['section' => AdminPrivilegeService::SECTION_BLOG_POST_CATEGORIES, 'service' => 'blog-post-categories'],
            'blog-posts' => ['section' => AdminPrivilegeService::SECTION_BLOG_POSTS, 'service' => 'blog-posts'],
            'blog-comments' => ['section' => AdminPrivilegeService::SECTION_BLOG_COMMENTS, 'service' => 'blog-comments'],
            'blog-contributions' => ['section' => AdminPrivilegeService::SECTION_BLOG_CONTRIBUTIONS, 'service' => 'blog-contributions'],
            'meta-tags' => ['section' => AdminPrivilegeService::SECTION_META_TAGS, 'service' => 'meta-tags'],
            'url-rewriting' => ['section' => AdminPrivilegeService::SECTION_URL_REWRITE],
            'bots' => ['section' => AdminPrivilegeService::SECTION_ROBOTS],
            'lesson-languages' => ['section' => AdminPrivilegeService::SECTION_LESSON_TOP_LANGUAGES, 'service' => 'lesson-languages'],
            'class-languages' => ['section' => AdminPrivilegeService::SECTION_CLASS_TOP_LANGUAGES, 'service' => 'class-languages'],
            'teacher-performance' => ['section' => AdminPrivilegeService::SECTION_TEACHER_PERFORMANCE, 'service' => 'teacher-performance'],
            'lesson-stats' => ['section' => AdminPrivilegeService::SECTION_LESSON_STATS, 'service' => 'lesson-stats'],
            'sales-report' => ['section' => AdminPrivilegeService::SECTION_SALES_REPORT, 'service' => 'sales-report'],
            'settlements' => ['section' => AdminPrivilegeService::SECTION_SETTLEMENTS_REPORT, 'service' => 'settlements'],
            'admin-earnings' => ['section' => AdminPrivilegeService::SECTION_ADMIN_EARNINGS, 'service' => 'admin-earnings'],
            'affiliate-report' => ['section' => AdminPrivilegeService::SECTION_AFFILIATE_REPORT, 'service' => 'affiliate-report'],
            'wallet-balance-report' => ['section' => AdminPrivilegeService::SECTION_WALLET_BALANCE_REPORT, 'service' => 'wallet-balance-report'],
            'teacher-payouts-report' => ['section' => AdminPrivilegeService::SECTION_TEACHER_PAYOUTS_REPORT, 'service' => 'teacher-payouts-report'],
            'hours-taught-report' => ['section' => AdminPrivilegeService::SECTION_HOURS_TAUGHT_REPORT, 'service' => 'hours-taught-report'],
            'forum' => ['section' => AdminPrivilegeService::SECTION_DISCUSSION_FORUM, 'service' => 'forum'],
            'forum-reported-questions' => ['section' => AdminPrivilegeService::SECTION_DISCUSSION_FORUM, 'service' => 'forum-reported-questions'],
            'forum-tags' => ['section' => AdminPrivilegeService::SECTION_DISCUSSION_FORUM, 'service' => 'forum-tags'],
            'forum-tag-requests' => ['section' => AdminPrivilegeService::SECTION_DISCUSSION_FORUM, 'service' => 'forum-tag-requests'],
            'forum-report-issue-reasons' => ['section' => AdminPrivilegeService::SECTION_DISCUSSION_FORUM, 'service' => 'forum-report-issue-reasons'],
        ];
    }

    public function sectionFor(string $module): ?int
    {
        return $this->modules()[$module]['section'] ?? null;
    }

    /** @return array{data: array<int, mixed>, meta: array<string, int>}|null */
    public function search(string $module, Request $request): ?array
    {
        $service = $this->modules()[$module]['service'] ?? null;

        $result = match ($service) {
            'users' => $this->users->search($request),
            'blog-posts' => $this->blogPosts->search($request),
            'blog-comments' => $this->blogComments->search($request),
            'blog-contributions' => $this->blogContributions->search($request),
            'blog-post-categories' => $this->blogPostCategories->search($request),
            'orders' => $this->orders->search($request),
            'page-lang-data' => $this->pageLangData->search($request),
            'currencies' => $this->currencies->search($request),
            'commission' => $this->commission->search($request),
            'coupons' => $this->coupons->search($request),
            'social-platform' => $this->socialPlatforms->search($request),
            'payment-methods' => $this->paymentMethods->search($request),
            'meeting-tools' => $this->meetingTools->search($request),
            'themes' => $this->themes->search($request),
            'lesson-orders' => $this->orderSubListings->searchLessons($request),
            'subscription-orders' => $this->orderSubListings->searchSubscriptions($request),
            'class-orders' => $this->orderSubListings->searchClasses($request),
            'course-orders' => $this->orderSubListings->searchCourseOrders($request),
            'package-orders' => $this->orderSubListings->searchPackages($request),
            'giftcard-orders' => $this->orderSubListings->searchGiftcards($request),
            'wallet-orders' => $this->orderSubListings->searchWallet($request),
            'order-subscription-plans' => $this->orderSubListings->searchOrderSubscriptionPlans($request),
            'courses' => $this->courses->search($request),
            'course-languages' => $this->courseLanguages->search($request),
            'categories' => $this->categories->search($request),
            'course-requests' => $this->courseRequests->search($request),
            'course-edit-requests' => $this->courseEditRequests->search($request),
            'course-refund-requests' => $this->courseRefundRequests->search($request),
            'rating-reviews' => $this->ratingReviews->search($request),
            'questions' => $this->questions->search($request),
            'quizzes' => $this->quizzes->search($request),
            'reported-issues' => $this->reportedIssues->search($request),
            'preferences' => $this->teacherPreferences->preferences($request),
            'speak-language' => $this->teacherPreferences->speakLanguages($request),
            'speak-language-levels' => $this->teacherPreferences->speakLanguageLevels($request),
            'teach-language' => $this->teacherPreferences->teachLanguages($request),
            'issue-report-options' => $this->teacherPreferences->issueReportOptions($request),
            'forum' => $this->forum->questions($request),
            'forum-reported-questions' => $this->forum->reportedQuestions($request),
            'forum-tags' => $this->forum->tags($request),
            'forum-tag-requests' => $this->forum->tagRequests($request),
            'forum-report-issue-reasons' => $this->forum->reportReasons($request),
            'lesson-languages' => $this->topLanguages->lessonLanguages($request),
            'class-languages' => $this->topLanguages->classLanguages($request),
            'teacher-performance' => $this->teacherPerformance->search($request),
            'lesson-stats' => $this->lessonStats->search($request),
            'sales-report' => $this->salesReport->search($request),
            'settlements' => $this->settlements->search($request),
            'admin-earnings' => $this->adminEarnings->search($request),
            'affiliate-commission' => $this->affiliateCommissions->search($request),
            'affiliate-report' => $this->affiliateReport->search($request),
            'wallet-balance-report' => $this->walletBalanceReport->search($request),
            'teacher-payouts-report' => $this->teacherPayoutsReport->search($request),
            'hours-taught-report' => $this->hoursTaughtReport->search($request),
            'meta-tags' => $this->metaTags->search($request),
            default => $this->safeGenericSearch($module, $request),
        };

        return $result;
    }

    /** @return array{data: array<int, mixed>, meta: array<string, int>}|null */
    private function safeGenericSearch(string $module, Request $request): ?array
    {
        try {
            return $this->generic->search($module, $request);
        } catch (\Throwable) {
            return [
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $this->adminPageSizeFallback(),
                    'total' => 0,
                    'last_page' => 1,
                ],
            ];
        }
    }

    private function adminPageSizeFallback(): int
    {
        return 10;
    }
}
