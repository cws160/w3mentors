import { Navigate, Route } from 'react-router-dom';
import { AdminGuestRoute, AdminProtectedRoute } from '../components/AdminProtectedRoute';
import { ADMIN_LOGIN_PATH } from '../config/adminGuestPaths';
import { AdminGuestLayout } from '../layout/AdminGuestLayout';
import { AdminLayout } from '../layout/AdminLayout';
import { AdminPageMetaProvider } from '../context/AdminPageMetaContext';
import { AdminDashboardPage } from '../pages/AdminDashboardPage';
import { AdminForgotPasswordPage } from '../pages/AdminForgotPasswordPage';
import { AdminLoginPage } from '../pages/AdminLoginPage';
import { AdminModuleRoutePage } from '../pages/AdminModuleRoutePage';
import { AdminTeacherRequestQualificationsPage } from '../pages/AdminTeacherRequestQualificationsPage';
import { AdminAdminPermissionsPage } from '../pages/AdminAdminPermissionsPage';
import { AdminGroupClassLearnersPage } from '../pages/AdminGroupClassLearnersPage';
import { AdminConfigurationsPage } from '../pages/AdminConfigurationsPage';
import { AdminUserImpersonatePage } from '../pages/AdminUserImpersonatePage';
import { AdminCoursePreviewPage } from '../pages/AdminCoursePreviewPage';
import { AdminEmailTemplatePreviewPage } from '../pages/AdminEmailTemplatePreviewPage';
import { AdminCertificatePreviewPage } from '../pages/AdminCertificatePreviewPage';
import { AdminOrderViewPage } from '../pages/AdminOrderViewPage';
import { AdminForumQuestionCommentsPage } from '../pages/AdminForumQuestionCommentsPage';
import { AdminCommissionHistoryPage } from '../pages/AdminCommissionHistoryPage';
import { AdminCouponUsesPage } from '../pages/AdminCouponUsesPage';
import { AdminLessonStatsLogsPage } from '../pages/AdminLessonStatsLogsPage';
import { AdminNavigationPagesPage } from '../pages/AdminNavigationPagesPage';

const ADMIN_MODULE_SLUGS = [
  'users',
  'teacher-requests',
  'withdraw-requests',
  'rating-reviews',
  'gdpr-requests',
  'admin-users',
  'group-classes',
  'package-classes',
  'course-languages',
  'categories',
  'courses',
  'course-requests',
  'course-edit-requests',
  'course-refund-requests',
  'questions',
  'quizzes',
  'orders',
  'lessons',
  'subscriptions',
  'order-subscription-plans',
  'classes',
  'course-orders',
  'packages',
  'giftcards',
  'wallet',
  'reported-issues',
  'speak-language',
  'speak-language-levels',
  'teach-language',
  'issue-report-options',
  'slides',
  'content-pages',
  'content-block',
  'navigations',
  'countries',
  'states',
  'video-content',
  'testimonials',
  'label',
  'faq-categories',
  'faq',
  'email-templates',
  'abusive-words',
  'certificates',
  'meeting-tools',
  'payment-methods',
  'social-platform',
  'coupons',
  'commission',
  'currency-management',
  'themes',
  'page-lang-data',
  'subscription-plans',
  'affiliate-commission',
  'blog-post-categories',
  'blog-posts',
  'blog-comments',
  'blog-contributions',
  'meta-tags',
  'url-rewriting',
  'bots',
  'xml-sitemap',
  'html-sitemap',
  'lesson-languages',
  'class-languages',
  'teacher-performance',
  'lesson-stats',
  'sales-report',
  'settlements',
  'admin-earnings',
  'affiliate-report',
  'wallet-balance-report',
  'teacher-payouts-report',
  'hours-taught-report',
  'forum',
  'forum-reported-questions',
  'forum-tags',
  'forum-tag-requests',
  'forum-report-issue-reasons',
] as const;

export const adminRouteTree = (
  <>
    <Route path="admin/user-login" element={<AdminUserImpersonatePage />} />
    <Route element={<AdminProtectedRoute />}>
      <Route path="admin/courses/:courseId/preview" element={<AdminCoursePreviewPage />} />
      <Route path="admin/email-templates/:code/preview/:langId" element={<AdminEmailTemplatePreviewPage />} />
      <Route path="admin/certificates/:code/preview/:langId" element={<AdminCertificatePreviewPage />} />
    </Route>
    <Route element={<AdminGuestRoute />}>
      <Route element={<AdminGuestLayout />}>
        <Route path="admin/admin-guest/login-form" element={<AdminLoginPage />} />
        <Route path="admin/admin-guest/forgot-password-form" element={<AdminForgotPasswordPage />} />
        <Route path="admin/login" element={<Navigate to={ADMIN_LOGIN_PATH} replace />} />
        <Route path="admin/admin-guest" element={<Navigate to={ADMIN_LOGIN_PATH} replace />} />
      </Route>
    </Route>
    <Route element={<AdminProtectedRoute />}>
      <Route element={<AdminPageMetaProvider><AdminLayout /></AdminPageMetaProvider>}>
        <Route path="admin" element={<AdminDashboardPage />} />
        <Route path="admin/configurations" element={<AdminConfigurationsPage />} />
        <Route path="admin/configurations/third-party-apis" element={<AdminConfigurationsPage />} />
        <Route
          path="admin/teacher-requests/qualifications/:userId"
          element={<AdminTeacherRequestQualificationsPage />}
        />
        <Route
          path="admin/admin-users/:adminId/permissions"
          element={<AdminAdminPermissionsPage />}
        />
        <Route
          path="admin/group-classes/:classId/learners"
          element={<AdminGroupClassLearnersPage />}
        />
        <Route path="admin/orders/:orderId/view" element={<AdminOrderViewPage />} />
        <Route path="admin/forum/:questionId/comments" element={<AdminForumQuestionCommentsPage />} />
        <Route path="admin/lesson-stats/:userId/logs/:reportType" element={<AdminLessonStatsLogsPage />} />
        <Route path="admin/commission/:userId/history" element={<AdminCommissionHistoryPage />} />
        <Route path="admin/coupons/:couponId/uses" element={<AdminCouponUsesPage />} />
        <Route path="admin/navigations/:navigationId/pages" element={<AdminNavigationPagesPage />} />
        {ADMIN_MODULE_SLUGS.map((slug) => (
          <Route key={slug} path={`admin/${slug}`} element={<AdminModuleRoutePage />} />
        ))}
        <Route path="admin/preferences/:typeId" element={<AdminModuleRoutePage />} />
        <Route path="admin/categories/quiz" element={<AdminModuleRoutePage />} />
        <Route path="admin/profile" element={<AdminModuleRoutePage />} />
        <Route path="admin/profile/change-password" element={<AdminModuleRoutePage />} />
      </Route>
    </Route>
  </>
);
