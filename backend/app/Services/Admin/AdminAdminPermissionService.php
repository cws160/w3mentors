<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminAdminPermissionService
{
    /** @var array<int, string> */
    private const MODULE_LABELS = [
        AdminPrivilegeService::SECTION_ADMIN_DASHBOARD => 'MSG_ADMIN_DASHBOARD',
        AdminPrivilegeService::SECTION_USERS => 'MSG_MANAGE_USERS',
        AdminPrivilegeService::SECTION_TEACHER_REQUEST => 'MSG_TEACHER_REQUESTS',
        AdminPrivilegeService::SECTION_WITHDRAW_REQUESTS => 'MSG_WITHDRAW_REQUESTS',
        AdminPrivilegeService::SECTION_TEACHER_REVIEWS => 'MSG_TEACHER_REVIEWS',
        AdminPrivilegeService::SECTION_GDPR_REQUESTS => 'MSG_GDPR_REQUESTS',
        AdminPrivilegeService::SECTION_ADMIN_USERS => 'MSG_ADMIN_USERS',
        AdminPrivilegeService::SECTION_MANAGE_ORDERS => 'MSG_MANAGE_ORDERS',
        AdminPrivilegeService::SECTION_LESSONS_ORDERS => 'MSG_LESSONS_ORDERS',
        AdminPrivilegeService::SECTION_SUBSCRI_ORDERS => 'MSG_RECURRING_LESSON_ORDERS',
        AdminPrivilegeService::SECTION_CLASSES_ORDERS => 'MSG_CLASSES_ORDERS',
        AdminPrivilegeService::SECTION_PACKAGS_ORDERS => 'MSG_PACKAGES_ORDERS',
        AdminPrivilegeService::SECTION_GIFTCARD_ORDERS => 'MSG_GIFTCARD_ORDERS',
        AdminPrivilegeService::SECTION_WALLETS_ORDERS => 'MSG_WALLET_ORDERS',
        AdminPrivilegeService::SECTION_ISSUES_REPORTED => 'MSG_REPORTED_ISSUES',
        AdminPrivilegeService::SECTION_TEACHER_PREFFERENCES => 'MSG_TEACHER_PREFERENCES',
        AdminPrivilegeService::SECTION_SPEAK_LANGUAGES => 'MSG_SPOKEN_LANGUAGES',
        AdminPrivilegeService::SECTION_SPEAK_LANGUAGE_LEVELS => 'MSG_SPOKEN_LANGUAGES_LEVELS',
        AdminPrivilegeService::SECTION_TEACH_LANGUAGES => 'MSG_TEACHING_LANGUAGES',
        AdminPrivilegeService::SECTION_ISSUE_REPORT_OPTIONS => 'MSG_ISSUE_REPORT_OPTIONS',
        AdminPrivilegeService::SECTION_SLIDES => 'MSG_HOMEPAGE_SLIDES',
        AdminPrivilegeService::SECTION_CONTENT_PAGES => 'MSG_CONTENT_PAGES',
        AdminPrivilegeService::SECTION_CONTENT_BLOCKS => 'MSG_CONTENT_BLOCKS',
        AdminPrivilegeService::SECTION_NAVIGATION_MANAGEMENT => 'MSG_NAVIGATION_MANAGEMENT',
        AdminPrivilegeService::SECTION_COUNTRIES => 'MSG_COUNTRIES',
        AdminPrivilegeService::SECTION_VIDEO_CONTENT => 'MSG_VIDEO_CONTENT',
        AdminPrivilegeService::SECTION_TESTIMONIAL => 'MSG_TESTIMONIAL',
        AdminPrivilegeService::SECTION_LANGUAGE_LABELS => 'MSG_LANGUAGE_LABELS',
        AdminPrivilegeService::SECTION_FAQ_CATEGORY => 'MSG_MANAGE_FAQ_CATEGORIES',
        AdminPrivilegeService::SECTION_FAQ => 'MSG_MANAGE_FAQS',
        AdminPrivilegeService::SECTION_EMAIL_TEMPLATES => 'MSG_EMAIL_TEMPLATES',
        AdminPrivilegeService::SECTION_GENERAL_SETTINGS => 'MSG_GENERAL_SETTINGS',
        AdminPrivilegeService::SECTION_MEETING_TOOL => 'MSG_MEETING_TOOL',
        AdminPrivilegeService::SECTION_PAYMENT_METHODS => 'MSG_PAYMENT_METHODS',
        AdminPrivilegeService::SECTION_SOCIALPLATFORM => 'MSG_SOCIAL_PLATFORM',
        AdminPrivilegeService::SECTION_DISCOUNT_COUPONS => 'MSG_DISCOUNT_COUPONS',
        AdminPrivilegeService::SECTION_COMMISSION => 'MSG_COMMISSION',
        AdminPrivilegeService::SECTION_CURRENCY_MANAGEMENT => 'MSG_CURRENCY_MANAGEMENT',
        AdminPrivilegeService::SECTION_THEME_MANAGEMENT => 'Msg_THEME_MANAGEMENT',
        AdminPrivilegeService::SECTION_BLOG_POST_CATEGORIES => 'MSG_BLOG_CATEGORIES',
        AdminPrivilegeService::SECTION_BLOG_POSTS => 'MSG_BLOG_POSTS',
        AdminPrivilegeService::SECTION_BLOG_COMMENTS => 'MSG_BLOG_COMMENTS',
        AdminPrivilegeService::SECTION_BLOG_CONTRIBUTIONS => 'MSG_BLOG_CONTRIBUTIONS',
        AdminPrivilegeService::SECTION_META_TAGS => 'MSG_META_TAGS',
        AdminPrivilegeService::SECTION_URL_REWRITE => 'MSG_URL_REWRITING',
        AdminPrivilegeService::SECTION_ROBOTS => 'MSG_ROBOTS_TXT',
        AdminPrivilegeService::SECTION_SITE_MAPS => 'MSG_SITE_MAPS',
        AdminPrivilegeService::SECTION_LESSON_TOP_LANGUAGES => 'MSG_LESSON_TOP_LANGUAGES',
        AdminPrivilegeService::SECTION_TEACHER_PERFORMANCE => 'MSG_TEACHER_PERFORMANCE',
        AdminPrivilegeService::SECTION_LESSON_STATS => 'MSG_LESSON_STATS',
        AdminPrivilegeService::SECTION_SALES_REPORT => 'MSG_SALE_REPORT',
        AdminPrivilegeService::SECTION_SETTLEMENTS_REPORT => 'MSG_SETTLEMENTS_REPORT',
        AdminPrivilegeService::SECTION_WALLET_BALANCE_REPORT => 'MSG_WALLET_BALANCE_REPORT',
        AdminPrivilegeService::SECTION_TEACHER_PAYOUTS_REPORT => 'MSG_TEACHER_PAYOUTS_REPORT',
        AdminPrivilegeService::SECTION_HOURS_TAUGHT_REPORT => 'MSG_HOURS_TAUGHT_REPORT',
        AdminPrivilegeService::SECTION_DISCUSSION_FORUM => 'LBL_Discussion_Forum',
        AdminPrivilegeService::SECTION_QUESTIONS => 'MSG_QUESTIONS',
        AdminPrivilegeService::SECTION_QUIZZES => 'MSG_QUIZZES',
        AdminPrivilegeService::SECTION_QUIZ_CATEGORIES => 'MSG_QUIZ_CATEGORIES',
        AdminPrivilegeService::SECTION_ADMIN_EARNINGS => 'MSG_ADMIN_EARNINGS',
        AdminPrivilegeService::SECTION_PAGE_LANG_DATA => 'MSG_PAGE_LANG_DATA',
        AdminPrivilegeService::SECTION_STATES => 'MSG_STATES',
        AdminPrivilegeService::SECTION_REPORT_STATS_REGENERATE => 'MSG_REPORT_STATS_REGENERATE',
        AdminPrivilegeService::SECTION_ABUSIVE_WORDS => 'MSG_ABUSIVE_WORD',
        AdminPrivilegeService::SECTION_MANAGE_CERTIFICATES => 'MSG_MANAGE_CERTIFICATES',
        AdminPrivilegeService::SECTION_ADMIN_PERMISSIONS => 'MSG_ADMIN_PERMISSIONS',
        AdminPrivilegeService::SECTION_COURSE_CATEGORIES => 'MSG_COURSE_CATEGORIES',
        AdminPrivilegeService::SECTION_COURSE => 'MSG_COURSE',
        AdminPrivilegeService::SECTION_COURSE_REQUESTS => 'MSG_COURSE_REQUESTS',
        AdminPrivilegeService::SECTION_COURSE_REFUND_REQUESTS => 'MSG_COURSE_REFUND_REQUESTS',
        AdminPrivilegeService::SECTION_COURSE_REVIEWS => 'MSG_COURSE_REVIEWS',
        AdminPrivilegeService::SECTION_COURSE_LANGUAGES => 'MSG_COURSE_LANGUAGES',
        AdminPrivilegeService::SECTION_COURSES_ORDERS => 'MSG_COURSE_ORDERS',
        AdminPrivilegeService::SECTION_COURSE_EDIT_REQUESTS => 'MSG_COURSE_EDIT_REQUESTS',
        AdminPrivilegeService::SECTION_GROUP_CLASSES => 'MSG_GROUP_CLASSES',
        AdminPrivilegeService::SECTION_PACKAGE_CLASSES => 'MSG_PACKAGE_CLASSES',
        AdminPrivilegeService::SECTION_CLASS_TOP_LANGUAGES => 'MSG_CLASS_TOP_LANGUAGES',
        AdminPrivilegeService::SECTION_AFFILIATE_COMMISSION => 'MSG_AFFILIATE_COMMISSION',
        AdminPrivilegeService::SECTION_AFFILIATE_REPORT => 'MSG_AFFILIATE_REPORT',
        AdminPrivilegeService::SECTION_SUBSCRIPTION_PLAN => 'MSG__SUBSCRIPTION_PLAN',
        AdminPrivilegeService::SECTION_ORDER_SUBSCRIPTION_PLAN => 'MSG__SUBSCRIPTION_PLAN_ORDERS',
    ];

    /** @return array<string, mixed> */
    public function pageData(int $targetAdminId, int $loggedInAdminId): array
    {
        $this->assertTargetAdmin($targetAdminId, $loggedInAdminId);

        $admin = DB::table('tbl_admin')
            ->where('admin_id', $targetAdminId)
            ->first(['admin_id', 'admin_username', 'admin_name', 'admin_email']);

        if (! $admin) {
            throw new RuntimeException('Invalid request', 404);
        }

        $modules = $this->permissionModules($loggedInAdminId);
        $userPermissions = $this->userPermissions($targetAdminId);

        $rows = [];
        foreach ($modules as $sectionId => $labelKey) {
            $rows[] = [
                'section_id' => $sectionId,
                'label_key' => $labelKey,
                'permission' => (int) ($userPermissions[$sectionId] ?? AdminPrivilegeService::PRIVILEGE_NONE),
            ];
        }

        return [
            'admin' => [
                'id' => (int) $admin->admin_id,
                'username' => (string) $admin->admin_username,
                'full_name' => (string) $admin->admin_name,
                'email' => (string) $admin->admin_email,
            ],
            'modules' => $rows,
            'permission_options' => $this->permissionOptions(),
        ];
    }

    public function updatePermission(int $targetAdminId, int $sectionId, int $permission, int $loggedInAdminId): void
    {
        $this->assertTargetAdmin($targetAdminId, $loggedInAdminId);

        if ($targetAdminId < 2) {
            throw new RuntimeException('Invalid request', 422);
        }

        if (! array_key_exists($permission, $this->permissionLevels())) {
            throw new RuntimeException('Invalid request', 422);
        }

        if ($sectionId === 0) {
            $modules = $this->permissionModules($loggedInAdminId);
            foreach (array_keys($modules) as $moduleId) {
                $this->upsertPermission($targetAdminId, $moduleId, $permission);
            }

            return;
        }

        $modules = $this->permissionModules($loggedInAdminId);
        if (! array_key_exists($sectionId, $modules)) {
            throw new RuntimeException('Invalid request', 422);
        }

        $this->upsertPermission($targetAdminId, $sectionId, $permission);
    }

    private function upsertPermission(int $adminId, int $sectionId, int $permission): void
    {
        DB::table('tbl_admin_permissions')->upsert(
            [[
                'admperm_admin_id' => $adminId,
                'admperm_section_id' => $sectionId,
                'admperm_value' => $permission,
            ]],
            ['admperm_admin_id', 'admperm_section_id'],
            ['admperm_value'],
        );
    }

    /** @return array<int, int> */
    private function userPermissions(int $adminId): array
    {
        return DB::table('tbl_admin_permissions')
            ->where('admperm_admin_id', $adminId)
            ->pluck('admperm_value', 'admperm_section_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    /** @return array<int, string> */
    private function permissionModules(int $loggedInAdminId): array
    {
        $features = app(AdminDashboardService::class)->featureFlags();
        $modules = [];

        foreach ([
            AdminPrivilegeService::SECTION_ADMIN_DASHBOARD,
            AdminPrivilegeService::SECTION_USERS,
            AdminPrivilegeService::SECTION_TEACHER_REQUEST,
            AdminPrivilegeService::SECTION_WITHDRAW_REQUESTS,
            AdminPrivilegeService::SECTION_TEACHER_REVIEWS,
            AdminPrivilegeService::SECTION_GDPR_REQUESTS,
            AdminPrivilegeService::SECTION_ADMIN_USERS,
            AdminPrivilegeService::SECTION_MANAGE_ORDERS,
            AdminPrivilegeService::SECTION_LESSONS_ORDERS,
            AdminPrivilegeService::SECTION_SUBSCRI_ORDERS,
            AdminPrivilegeService::SECTION_CLASSES_ORDERS,
            AdminPrivilegeService::SECTION_PACKAGS_ORDERS,
            AdminPrivilegeService::SECTION_GIFTCARD_ORDERS,
            AdminPrivilegeService::SECTION_WALLETS_ORDERS,
            AdminPrivilegeService::SECTION_ISSUES_REPORTED,
            AdminPrivilegeService::SECTION_TEACHER_PREFFERENCES,
            AdminPrivilegeService::SECTION_SPEAK_LANGUAGES,
            AdminPrivilegeService::SECTION_SPEAK_LANGUAGE_LEVELS,
            AdminPrivilegeService::SECTION_TEACH_LANGUAGES,
            AdminPrivilegeService::SECTION_ISSUE_REPORT_OPTIONS,
            AdminPrivilegeService::SECTION_SLIDES,
            AdminPrivilegeService::SECTION_CONTENT_PAGES,
            AdminPrivilegeService::SECTION_CONTENT_BLOCKS,
            AdminPrivilegeService::SECTION_NAVIGATION_MANAGEMENT,
            AdminPrivilegeService::SECTION_COUNTRIES,
            AdminPrivilegeService::SECTION_VIDEO_CONTENT,
            AdminPrivilegeService::SECTION_TESTIMONIAL,
            AdminPrivilegeService::SECTION_LANGUAGE_LABELS,
            AdminPrivilegeService::SECTION_FAQ_CATEGORY,
            AdminPrivilegeService::SECTION_FAQ,
            AdminPrivilegeService::SECTION_EMAIL_TEMPLATES,
            AdminPrivilegeService::SECTION_GENERAL_SETTINGS,
            AdminPrivilegeService::SECTION_MEETING_TOOL,
            AdminPrivilegeService::SECTION_PAYMENT_METHODS,
            AdminPrivilegeService::SECTION_SOCIALPLATFORM,
            AdminPrivilegeService::SECTION_DISCOUNT_COUPONS,
            AdminPrivilegeService::SECTION_COMMISSION,
            AdminPrivilegeService::SECTION_CURRENCY_MANAGEMENT,
            AdminPrivilegeService::SECTION_THEME_MANAGEMENT,
            AdminPrivilegeService::SECTION_BLOG_POST_CATEGORIES,
            AdminPrivilegeService::SECTION_BLOG_POSTS,
            AdminPrivilegeService::SECTION_BLOG_COMMENTS,
            AdminPrivilegeService::SECTION_BLOG_CONTRIBUTIONS,
            AdminPrivilegeService::SECTION_META_TAGS,
            AdminPrivilegeService::SECTION_URL_REWRITE,
            AdminPrivilegeService::SECTION_ROBOTS,
            AdminPrivilegeService::SECTION_SITE_MAPS,
            AdminPrivilegeService::SECTION_LESSON_TOP_LANGUAGES,
            AdminPrivilegeService::SECTION_TEACHER_PERFORMANCE,
            AdminPrivilegeService::SECTION_LESSON_STATS,
            AdminPrivilegeService::SECTION_SALES_REPORT,
            AdminPrivilegeService::SECTION_SETTLEMENTS_REPORT,
            AdminPrivilegeService::SECTION_WALLET_BALANCE_REPORT,
            AdminPrivilegeService::SECTION_TEACHER_PAYOUTS_REPORT,
            AdminPrivilegeService::SECTION_HOURS_TAUGHT_REPORT,
            AdminPrivilegeService::SECTION_DISCUSSION_FORUM,
            AdminPrivilegeService::SECTION_QUESTIONS,
            AdminPrivilegeService::SECTION_QUIZZES,
            AdminPrivilegeService::SECTION_QUIZ_CATEGORIES,
            AdminPrivilegeService::SECTION_ADMIN_EARNINGS,
            AdminPrivilegeService::SECTION_PAGE_LANG_DATA,
            AdminPrivilegeService::SECTION_STATES,
            AdminPrivilegeService::SECTION_REPORT_STATS_REGENERATE,
            AdminPrivilegeService::SECTION_ABUSIVE_WORDS,
            AdminPrivilegeService::SECTION_MANAGE_CERTIFICATES,
        ] as $sectionId) {
            $modules[$sectionId] = self::MODULE_LABELS[$sectionId];
        }

        if ($this->privileges()->isSuperAdmin($loggedInAdminId)) {
            $modules[AdminPrivilegeService::SECTION_ADMIN_PERMISSIONS] = self::MODULE_LABELS[AdminPrivilegeService::SECTION_ADMIN_PERMISSIONS];
        }

        if ($features['courses_enabled']) {
            foreach ([
                AdminPrivilegeService::SECTION_COURSE_CATEGORIES,
                AdminPrivilegeService::SECTION_COURSE,
                AdminPrivilegeService::SECTION_COURSE_REQUESTS,
                AdminPrivilegeService::SECTION_COURSE_REFUND_REQUESTS,
                AdminPrivilegeService::SECTION_COURSE_REVIEWS,
                AdminPrivilegeService::SECTION_COURSE_LANGUAGES,
                AdminPrivilegeService::SECTION_COURSES_ORDERS,
                AdminPrivilegeService::SECTION_COURSE_EDIT_REQUESTS,
            ] as $sectionId) {
                $modules[$sectionId] = self::MODULE_LABELS[$sectionId];
            }
        }

        if ($features['group_classes_enabled']) {
            foreach ([
                AdminPrivilegeService::SECTION_GROUP_CLASSES,
                AdminPrivilegeService::SECTION_PACKAGE_CLASSES,
                AdminPrivilegeService::SECTION_CLASSES_ORDERS,
                AdminPrivilegeService::SECTION_PACKAGS_ORDERS,
                AdminPrivilegeService::SECTION_CLASS_TOP_LANGUAGES,
            ] as $sectionId) {
                $modules[$sectionId] = self::MODULE_LABELS[$sectionId];
            }
        }

        if ($features['affiliate_enabled']) {
            $modules[AdminPrivilegeService::SECTION_AFFILIATE_COMMISSION] = self::MODULE_LABELS[AdminPrivilegeService::SECTION_AFFILIATE_COMMISSION];
            $modules[AdminPrivilegeService::SECTION_AFFILIATE_REPORT] = self::MODULE_LABELS[AdminPrivilegeService::SECTION_AFFILIATE_REPORT];
        }

        if ($features['subscription_plan_enabled']) {
            $modules[AdminPrivilegeService::SECTION_SUBSCRIPTION_PLAN] = self::MODULE_LABELS[AdminPrivilegeService::SECTION_SUBSCRIPTION_PLAN];
            $modules[AdminPrivilegeService::SECTION_ORDER_SUBSCRIPTION_PLAN] = self::MODULE_LABELS[AdminPrivilegeService::SECTION_ORDER_SUBSCRIPTION_PLAN];
        }

        return $modules;
    }

    /** @return array<int, string> */
    private function permissionLevels(): array
    {
        return [
            AdminPrivilegeService::PRIVILEGE_NONE => 'MSG_None',
            AdminPrivilegeService::PRIVILEGE_READ => 'MSG_Read_Only',
            AdminPrivilegeService::PRIVILEGE_WRITE => 'MSG_Read_and_Write',
        ];
    }

    /** @return array<int, array{value: int, label_key: string}> */
    private function permissionOptions(): array
    {
        $options = [];
        foreach ($this->permissionLevels() as $value => $labelKey) {
            $options[] = ['value' => $value, 'label_key' => $labelKey];
        }

        return $options;
    }

    private function assertTargetAdmin(int $targetAdminId, int $loggedInAdminId): void
    {
        if ($targetAdminId < 1 || $targetAdminId === 1 || $targetAdminId === $loggedInAdminId) {
            throw new RuntimeException('Invalid request', 422);
        }
    }

    private function privileges(): AdminPrivilegeService
    {
        return app(AdminPrivilegeService::class);
    }
}
