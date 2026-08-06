<?php

namespace App\Services\Admin;

class AdminNavigationService
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminDashboardService $dashboard,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function menu(int $adminId): array
    {
        $p = $this->privileges->privilegeFlags($adminId);
        $f = $this->dashboard->featureFlags();
        $menu = [];

        $menu[] = [
            'type' => 'link',
            'labelKey' => 'LBL_DASHBOARD',
            'icon' => 'icon-dashboard',
            'path' => '/admin',
        ];

        $usersChildren = $this->filterChildren($p, [
            ['labelKey' => 'LBL_USERS', 'labelFallback' => 'Users', 'path' => '/admin/users', 'privilege' => 'canViewUsers'],
            ['labelKey' => 'LBL_TEACHER_REQUESTS', 'labelFallback' => 'Teacher requests', 'path' => '/admin/teacher-requests', 'privilege' => 'canViewTeacherRequests'],
            ['labelKey' => 'LBL_WITHDRAW_REQUESTS', 'labelFallback' => 'Wallet withdrawal requests', 'path' => '/admin/withdraw-requests', 'privilege' => 'canViewWithdrawRequests'],
            ['labelKey' => 'LBL_TEACHER_REVIEWS', 'labelFallback' => 'Teacher reviews & ratings', 'path' => '/admin/rating-reviews', 'privilege' => 'canViewTeacherReviews'],
            ['labelKey' => 'LBL_GDPR_REQUESTS', 'labelFallback' => 'GDPR requests', 'path' => '/admin/gdpr-requests', 'privilege' => 'canViewGdprRequests'],
            ['labelKey' => 'LBL_Manage_Admins', 'labelFallback' => 'Manage Admins', 'path' => '/admin/admin-users', 'privilege' => 'canViewAdminUsers'],
        ]);
        if ($usersChildren) {
            $menu[] = $this->dropdown('manage-user', 'LBL_MANAGE_USERS', 'icon-users', $usersChildren, 'Manage users');
        }

        if ($f['group_classes_enabled']) {
            $classChildren = $this->filterChildren($p, [
                ['labelKey' => 'LBL_GROUP_CLASSES', 'labelFallback' => 'Group classes', 'path' => '/admin/group-classes', 'privilege' => 'canViewGroupClasses'],
                ['labelKey' => 'LBL_PACKAGE_CLASSES', 'labelFallback' => 'Package classes', 'path' => '/admin/package-classes', 'privilege' => 'canViewPackageClasses'],
            ]);
            if ($classChildren) {
                $menu[] = $this->dropdown('group-classes', 'LBL_ADMIN_CLASSES', 'icon-group-class', $classChildren, 'Classes');
            }
        }

        if ($f['courses_enabled']) {
            $courseChildren = $this->filterChildren($p, [
                ['labelKey' => 'LBL_COURSE_LANGUAGES', 'path' => '/admin/course-languages', 'privilege' => 'canViewCourseLanguage'],
                ['labelKey' => 'LBL_CATEGORIES', 'path' => '/admin/categories', 'privilege' => 'canViewCourseCategories'],
                ['labelKey' => 'LBL_COURSES', 'path' => '/admin/courses', 'privilege' => 'canViewCourses'],
                ['labelKey' => 'LBL_APPROVAL_REQUESTS', 'path' => '/admin/course-requests', 'privilege' => 'canViewCourseRequests'],
                ['labelKey' => 'LBL_EDIT_REQUESTS', 'path' => '/admin/course-edit-requests', 'privilege' => 'canViewCourseEditRequests'],
                ['labelKey' => 'LBL_COURSE_REVIEWS', 'path' => '/admin/rating-reviews?type=course', 'privilege' => 'canViewCourseReviews'],
                ['labelKey' => 'LBL_REFUND_REQUESTS', 'path' => '/admin/course-refund-requests', 'privilege' => 'canViewCourseRefundRequests'],
            ]);
            if ($courseChildren) {
                $menu[] = $this->dropdown('manage-courses', 'LBL_MANAGE_COURSES', 'icon-courses', $courseChildren);
            }
        }

        $quizChildren = $this->filterChildren($p, [
            ['labelKey' => 'LBL_CATEGORIES', 'path' => '/admin/categories/quiz', 'privilege' => 'canViewQuizCategories'],
            ['labelKey' => 'LBL_QUESTIONS', 'path' => '/admin/questions', 'privilege' => 'canViewQuestions'],
            ['labelKey' => 'LBL_QUIZZES', 'path' => '/admin/quizzes', 'privilege' => 'canViewQuizzes'],
        ]);
        if ($quizChildren) {
            $menu[] = $this->dropdown('manage-quiz', 'LBL_MANAGE_QUIZZES', 'icon-quizzes', $quizChildren);
        }

        $orderChildren = $this->filterChildren($p, array_values(array_filter([
            ['labelKey' => 'LBL_ALL_ORDERS', 'path' => '/admin/orders', 'privilege' => 'canViewOrders'],
            ['labelKey' => 'LBL_LESSONS_ORDERS', 'path' => '/admin/lessons', 'privilege' => 'canViewLessonsOrders'],
            ['labelKey' => 'LBL_RECURRING_LESSON_ORDERS', 'path' => '/admin/subscriptions', 'privilege' => 'canViewSubscriptionOrders'],
            $f['subscription_plan_enabled']
                ? ['labelKey' => 'LBL_SUBSCRIPTION_PLAN_ORDERS', 'path' => '/admin/order-subscription-plans', 'privilege' => 'canViewSubscriptionPlanOrders']
                : null,
            $f['group_classes_enabled']
                ? ['labelKey' => 'LBL_CLASSES_ORDERS', 'path' => '/admin/classes', 'privilege' => 'canViewClassesOrders']
                : null,
            $f['courses_enabled']
                ? ['labelKey' => 'LBL_COURSE_ORDERS', 'path' => '/admin/course-orders', 'privilege' => 'canViewCoursesOrders']
                : null,
            ['labelKey' => 'LBL_PACKAGES_ORDERS', 'path' => '/admin/packages', 'privilege' => 'canViewPackagesOrders'],
            ['labelKey' => 'LBL_GIFTCARD_ORDERS', 'path' => '/admin/giftcards', 'privilege' => 'canViewGiftcardOrders'],
            ['labelKey' => 'LBL_WALLET_RECHARGE_ORDERS', 'path' => '/admin/wallet', 'privilege' => 'canViewWalletOrders'],
        ])));
        if ($orderChildren) {
            $menu[] = $this->dropdown('manage-orders', 'LBL_MANAGE_ORDERS', 'icon-orders', $orderChildren);
        }

        $issueChildren = $this->filterChildren($p, [
            ['labelKey' => 'LBL_ESCALATED_ISSUES', 'path' => '/admin/reported-issues?escalated=1', 'privilege' => 'canViewIssuesReported'],
            ['labelKey' => 'LBL_ALL_REPORTED_ISSUES', 'path' => '/admin/reported-issues', 'privilege' => 'canViewIssuesReported'],
        ]);
        if ($issueChildren) {
            $menu[] = $this->dropdown('issue-reported', 'LBL_ISSUES_REPORTED', 'icon-bug', $issueChildren);
        }

        $prefChildren = $this->filterChildren($p, [
            ['labelKey' => 'LBL_ACCENTS', 'path' => '/admin/preferences/1', 'privilege' => 'canViewPreferences'],
            ['labelKey' => 'LBL_TEACHES_LEVEL', 'path' => '/admin/preferences/2', 'privilege' => 'canViewPreferences'],
            ['labelKey' => 'LBL_LEARNERS_AGES', 'path' => '/admin/preferences/3', 'privilege' => 'canViewPreferences'],
            ['labelKey' => 'LBL_LESSONS_INCLUDE', 'path' => '/admin/preferences/4', 'privilege' => 'canViewPreferences'],
            ['labelKey' => 'LBL_TEST_PREPARATION', 'path' => '/admin/preferences/6', 'privilege' => 'canViewPreferences'],
            ['labelKey' => 'LBL_SPOKEN_LANGUAGE', 'path' => '/admin/speak-language', 'privilege' => 'canViewSpeakLanguage'],
            ['labelKey' => 'LBL_SPOKEN_LANGUAGE_LEVELS', 'path' => '/admin/speak-language-levels', 'privilege' => 'canViewSpeakLanguageLevels'],
            ['labelKey' => 'LBL_TEACHING_LANGUAGE', 'path' => '/admin/teach-language', 'privilege' => 'canViewTeachLanguage'],
            ['labelKey' => 'LBL_ISSUE_REPORT_OPTIONS', 'path' => '/admin/issue-report-options', 'privilege' => 'canViewIssueReportOptions'],
        ]);
        if ($prefChildren) {
            $menu[] = $this->dropdown('teacher-preferences', 'LBL_Teacher_Preferences', 'icon-teacher', $prefChildren);
        }

        $cmsChildren = $this->filterChildren($p, [
            ['labelKey' => 'LBL_HOMEPAGE_SLIDES', 'path' => '/admin/slides', 'privilege' => 'canViewSlides'],
            ['labelKey' => 'LBL_CONTENT_PAGES', 'path' => '/admin/content-pages', 'privilege' => 'canViewContentPages'],
            ['labelKey' => 'LBL_CONTENT_BLOCKS', 'path' => '/admin/content-block', 'privilege' => 'canViewContentBlocks'],
            ['labelKey' => 'LBL_NAVIGATION', 'path' => '/admin/navigations', 'privilege' => 'canViewNavigationManagement'],
            ['labelKey' => 'LBL_COUNTRIES', 'path' => '/admin/countries', 'privilege' => 'canViewCountries'],
            ['labelKey' => 'LBL_States', 'path' => '/admin/states', 'privilege' => 'canViewStates'],
            ['labelKey' => 'LBL_VIDEO_CONTENT', 'path' => '/admin/video-content', 'privilege' => 'canViewVideoContent'],
            ['labelKey' => 'LBL_TESTIMONIALS', 'path' => '/admin/testimonials', 'privilege' => 'canViewTestimonial'],
            ['labelKey' => 'LBL_LANGUAGE_LABEL', 'path' => '/admin/label', 'privilege' => 'canViewLanguageLabel'],
            ['labelKey' => 'LBL_FAQ_CATEGORIES', 'path' => '/admin/faq-categories', 'privilege' => 'canViewFaqCategory'],
            ['labelKey' => 'LBL_MANAGE_FAQS', 'path' => '/admin/faq', 'privilege' => 'canViewFaq'],
            ['labelKey' => 'LBL_EMAIL_TEMPLATES', 'path' => '/admin/email-templates', 'privilege' => 'canViewEmailTemplates'],
            ['labelKey' => 'LBL_ABUSIVE_WORDS', 'path' => '/admin/abusive-words', 'privilege' => 'canViewAbusiveWords'],
            ['labelKey' => 'LBL_CERTIFICATES', 'path' => '/admin/certificates', 'privilege' => 'canViewCertificates'],
        ]);
        if ($cmsChildren) {
            $menu[] = $this->dropdown('manage-cms', 'LBL_MANAGE_CMS', 'icon-CMS', $cmsChildren);
        }

        $settingsChildren = $this->filterChildren($p, array_values(array_filter([
            ['labelKey' => 'LBL_General_Settings', 'path' => '/admin/configurations', 'privilege' => 'canViewGeneralSettings'],
            ['labelKey' => 'LBL_Meeting_Tools', 'path' => '/admin/meeting-tools', 'privilege' => 'canViewMeetingTool'],
            ['labelKey' => 'LBL_Payment_Methods', 'path' => '/admin/payment-methods', 'privilege' => 'canViewPaymentMethods'],
            ['labelKey' => 'LBL_SOCIAL_PLATFORMS', 'path' => '/admin/social-platform', 'privilege' => 'canViewSocialPlatforms'],
            ['labelKey' => 'LBL_Discount_Coupons', 'path' => '/admin/coupons', 'privilege' => 'canViewDiscountCoupons'],
            ['labelKey' => 'LBL_Commission_Settings', 'path' => '/admin/commission', 'privilege' => 'canViewCommissionSettings'],
            ['labelKey' => 'LBL_Currency_Management', 'path' => '/admin/currency-management', 'privilege' => 'canViewCurrencyManagement'],
            ['labelKey' => 'LBL_Theme_Management', 'path' => '/admin/themes', 'privilege' => 'canViewThemeManagement'],
            ['labelKey' => 'LBL_Page_Language_Data', 'path' => '/admin/page-lang-data', 'privilege' => 'canViewPageLangData'],
            $f['subscription_plan_enabled']
                ? ['labelKey' => 'LBL_Manage_Subscription_Plans', 'path' => '/admin/subscription-plans', 'privilege' => 'canViewSubscriptionPlan']
                : null,
            $f['affiliate_enabled']
                ? ['labelKey' => 'LBL_AFFILIATE_COMMISSION', 'path' => '/admin/affiliate-commission', 'privilege' => 'canViewAffiliateCommission']
                : null,
        ])));
        if ($settingsChildren) {
            $menu[] = $this->dropdown('manage-setting', 'LBL_Manage_Settings', 'icon-system-settings', $settingsChildren, 'Manage settings');
        }

        $blogChildren = $this->filterChildren($p, [
            ['labelKey' => 'LBL_BLOG_Categories', 'path' => '/admin/blog-post-categories', 'privilege' => 'canViewBlogPostCategories'],
            ['labelKey' => 'LBL_Blog_Posts', 'path' => '/admin/blog-posts', 'privilege' => 'canViewBlogPosts'],
            ['labelKey' => 'LBL_Blog_Comments', 'path' => '/admin/blog-comments', 'privilege' => 'canViewBlogComments'],
            ['labelKey' => 'LBL_Blog_Contributions', 'path' => '/admin/blog-contributions', 'privilege' => 'canViewBlogContributions'],
        ]);
        if ($blogChildren) {
            $menu[] = $this->dropdown('manage-blogs', 'LBL_Manage_Blogs', 'icon-blog', $blogChildren, 'Manage blogs');
        }

        $frontUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
        $seoChildren = $this->filterChildren($p, [
            ['labelKey' => 'LBL_META_TAGS', 'path' => '/admin/meta-tags', 'privilege' => 'canViewMetaTags'],
            ['labelKey' => 'LBL_SEO_URLS', 'path' => '/admin/url-rewriting', 'privilege' => 'canViewSeoUrl'],
            ['labelKey' => 'LBL_ROBOTS.TXT', 'path' => '/admin/bots', 'privilege' => 'canViewRobotsSection'],
            ['labelKey' => 'LBL_UPDATE_SITEMAP', 'action' => 'generate-sitemap', 'privilege' => 'canEditSiteMap'],
            ['labelKey' => 'LBL_XML_SITEMAP', 'path' => $frontUrl.'/sitemap.xml', 'external' => true, 'privilege' => 'canViewSiteMap'],
            ['labelKey' => 'LBL_HTML_SITEMAP', 'path' => $frontUrl.'/sitemap', 'external' => true, 'privilege' => 'canViewSiteMap'],
        ]);
        if ($seoChildren) {
            $menu[] = $this->dropdown('manage-seo', 'LBL_MANAGE_SEO', 'icon-SEO', $seoChildren);
        }

        $reportChildren = $this->filterChildren($p, array_values(array_filter([
            ['labelKey' => 'LBL_Lessons_Top_Languages', 'path' => '/admin/lesson-languages', 'privilege' => 'canViewLessonLanguages'],
            $f['group_classes_enabled']
                ? ['labelKey' => 'LBL_Classes_Top_Languages', 'path' => '/admin/class-languages', 'privilege' => 'canViewClassLanguages']
                : null,
            ['labelKey' => 'LBL_Teacher_Performance', 'path' => '/admin/teacher-performance', 'privilege' => 'canViewTeacherPerformance'],
            ['labelKey' => 'LBL_LESSON_STATS', 'path' => '/admin/lesson-stats', 'privilege' => 'canViewLessonStatsReport'],
            ['labelKey' => 'LBL_SALES_REPORT', 'path' => '/admin/sales-report', 'privilege' => 'canViewSalesReport'],
            ['labelKey' => 'LBL_SETTLEMENTS', 'path' => '/admin/settlements', 'privilege' => 'canViewSettlementsReport'],
            ['labelKey' => 'LBL_ADMIN_EARNINGS', 'path' => '/admin/admin-earnings', 'privilege' => 'canViewAdminEarningsReport'],
            $f['affiliate_enabled']
                ? ['labelKey' => 'LBL_AFFILIATE_REPORT', 'path' => '/admin/affiliate-report', 'privilege' => 'canViewAffiliateReport']
                : null,
            ['labelKey' => 'LBL_WALLET_BALANCE_REPORT', 'path' => '/admin/wallet-balance-report', 'privilege' => 'canViewWalletBalanceReport'],
            ['labelKey' => 'LBL_TEACHER_PAYOUTS_REPORT', 'path' => '/admin/teacher-payouts-report', 'privilege' => 'canViewPayoutsReport'],
            ['labelKey' => 'LBL_HOURS_TAUGHT_REPORT', 'path' => '/admin/hours-taught-report', 'privilege' => 'canViewHoursTaughtReport'],
        ])));
        if ($reportChildren) {
            $menu[] = $this->dropdown('view-reports', 'LBL_VIEW_REPORTS', 'icon-reports', $reportChildren);
        }

        if ($p['canViewDiscussionForum']) {
            $menu[] = $this->dropdown('discussion-forum', 'LBL_Discussion_Forum', 'icon-forum', [
                ['labelKey' => 'LBL_All_Questions', 'labelFallback' => 'All questions', 'path' => '/admin/forum'],
                ['labelKey' => 'LBL_Reported_Questions', 'labelFallback' => 'Reported questions', 'path' => '/admin/forum-reported-questions'],
                ['labelKey' => 'LBL_Forum_Tags', 'labelFallback' => 'Forum tags', 'path' => '/admin/forum-tags'],
                ['labelKey' => 'LBL_Requested_Tags', 'labelFallback' => 'Requested tags', 'path' => '/admin/forum-tag-requests'],
                ['labelKey' => 'LBL_Report_Reasons', 'labelFallback' => 'Report reasons', 'path' => '/admin/forum-report-issue-reasons'],
            ], 'Forum');
        }

        return $menu;
    }

    /** @param array<int, array<string, string>> $children */
    private function dropdown(string $id, string $labelKey, string $icon, array $children, ?string $labelFallback = null): array
    {
        $item = [
            'type' => 'dropdown',
            'id' => $id,
            'labelKey' => $labelKey,
            'icon' => $icon,
            'children' => $children,
        ];
        if ($labelFallback !== null) {
            $item['labelFallback'] = $labelFallback;
        }

        return $item;
    }

    /**
     * @param  array<string, bool>  $privileges
     * @param  array<int, array<string, string>>  $children
     * @return array<int, array<string, string>>
     */
    private function filterChildren(array $privileges, array $children): array
    {
        return array_values(array_filter($children, function (array $child) use ($privileges) {
            $key = $child['privilege'] ?? null;
            if (! $key) {
                return true;
            }

            return ! empty($privileges[$key]);
        }));
    }
}
