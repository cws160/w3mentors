import { useLocation, useParams } from 'react-router-dom';
import { AdminListPage } from '../components/AdminListPage';
import { getAdminModuleConfig } from '../config/adminModules';
import { AdminCourseLanguagesPage } from './AdminCourseLanguagesPage';
import { AdminCategoriesPage } from './AdminCategoriesPage';
import { AdminPreferencesPage } from './AdminPreferencesPage';
import { AdminSpeakLanguageLevelsPage } from './AdminSpeakLanguageLevelsPage';
import { AdminSpeakLanguagesPage } from './AdminSpeakLanguagesPage';
import { AdminTeachLanguagesPage } from './AdminTeachLanguagesPage';
import { AdminIssueReportOptionsPage } from './AdminIssueReportOptionsPage';
import { AdminForumReportReasonsPage } from './AdminForumReportReasonsPage';
import { AdminForumQuestionsPage } from './AdminForumQuestionsPage';
import { AdminForumReportedQuestionsPage } from './AdminForumReportedQuestionsPage';
import { AdminLessonLanguagesPage } from './AdminTopLanguagesReportPage';
import { AdminClassLanguagesPage } from './AdminTopLanguagesReportPage';
import { AdminTeacherPerformancePage } from './AdminTeacherPerformancePage';
import { AdminLessonStatsPage } from './AdminLessonStatsPage';
import { AdminSalesReportPage } from './AdminSalesReportPage';
import { AdminSettlementsPage } from './AdminSettlementsPage';
import { AdminAdminEarningsPage } from './AdminAdminEarningsPage';
import { AdminAffiliateReportPage } from './AdminAffiliateReportPage';
import { AdminWalletBalanceReportPage } from './AdminWalletBalanceReportPage';
import { AdminTeacherPayoutsReportPage } from './AdminTeacherPayoutsReportPage';
import { AdminHoursTaughtReportPage } from './AdminHoursTaughtReportPage';
import { AdminMetaTagsPage } from './AdminMetaTagsPage';
import { AdminBotsPage } from './AdminBotsPage';
import { AdminXmlSitemapPage } from './AdminXmlSitemapPage';
import { AdminHtmlSitemapPage } from './AdminHtmlSitemapPage';
import { AdminBlogPostCategoriesPage } from './AdminBlogPostCategoriesPage';
import { AdminBlogPostsPage } from './AdminBlogPostsPage';
import { AdminBlogCommentsPage } from './AdminBlogCommentsPage';
import { AdminAffiliateCommissionPage } from './AdminAffiliateCommissionPage';
import { AdminThemesPage } from './AdminThemesPage';
import { AdminCurrencyManagementPage } from './AdminCurrencyManagementPage';
import { AdminCommissionPage } from './AdminCommissionPage';
import { AdminCouponsPage } from './AdminCouponsPage';
import { AdminSocialPlatformsPage } from './AdminSocialPlatformsPage';
import { AdminPaymentMethodsPage } from './AdminPaymentMethodsPage';
import { AdminMeetingToolsPage } from './AdminMeetingToolsPage';
import { AdminBlogContributionsPage } from './AdminBlogContributionsPage';
import { AdminSlidesPage } from './AdminSlidesPage';
import { AdminProfilePage } from './AdminProfilePage';
import { AdminChangePasswordPage } from './AdminChangePasswordPage';
import { FORUM_MODULES } from '../config/adminForumModules';

function resolveModuleSlug(pathname: string): string {
  const parts = pathname.replace(/^\/admin\/?/, '').split('/').filter(Boolean);
  if (parts[0] === 'preferences') return 'preferences';
  if (parts[0] === 'categories' && parts[1] === 'quiz') return 'categories';
  if (parts[0] === 'profile') return 'profile';
  return parts[0] ?? '';
}

export function AdminModuleRoutePage() {
  const { pathname } = useLocation();
  const { typeId } = useParams();
  const module = resolveModuleSlug(pathname);

  if (module === 'profile' && pathname.replace(/\/$/, '') === '/admin/profile') {
    return <AdminProfilePage />;
  }

  if (module === 'profile' && pathname.replace(/\/$/, '') === '/admin/profile/change-password') {
    return <AdminChangePasswordPage />;
  }

  const config = getAdminModuleConfig(module, module === 'preferences' ? typeId : undefined);

  if (module === 'course-languages') {
    return <AdminCourseLanguagesPage />;
  }

  if (module === 'preferences') {
    return <AdminPreferencesPage key={typeId ?? '1'} />;
  }

  if (module === 'speak-language-levels') {
    return <AdminSpeakLanguageLevelsPage />;
  }

  if (module === 'speak-language') {
    return <AdminSpeakLanguagesPage />;
  }

  if (module === 'teach-language') {
    return <AdminTeachLanguagesPage />;
  }

  if (module === 'issue-report-options') {
    return <AdminIssueReportOptionsPage />;
  }

  if (module === 'forum-report-issue-reasons') {
    return <AdminForumReportReasonsPage />;
  }

  if (module === 'forum') {
    return <AdminForumQuestionsPage />;
  }

  if (module === 'forum-reported-questions') {
    return <AdminForumReportedQuestionsPage />;
  }

  if (module === 'lesson-languages') {
    return <AdminLessonLanguagesPage />;
  }

  if (module === 'class-languages') {
    return <AdminClassLanguagesPage />;
  }

  if (module === 'teacher-performance') {
    return <AdminTeacherPerformancePage />;
  }

  if (module === 'lesson-stats') {
    return <AdminLessonStatsPage />;
  }

  if (module === 'sales-report') {
    return <AdminSalesReportPage />;
  }

  if (module === 'settlements') {
    return <AdminSettlementsPage />;
  }

  if (module === 'admin-earnings') {
    return <AdminAdminEarningsPage />;
  }

  if (module === 'affiliate-report') {
    return <AdminAffiliateReportPage />;
  }

  if (module === 'wallet-balance-report') {
    return <AdminWalletBalanceReportPage />;
  }

  if (module === 'teacher-payouts-report') {
    return <AdminTeacherPayoutsReportPage />;
  }

  if (module === 'hours-taught-report') {
    return <AdminHoursTaughtReportPage />;
  }

  if (module === 'meta-tags') {
    return <AdminMetaTagsPage />;
  }

  if (module === 'bots') {
    return <AdminBotsPage />;
  }

  if (module === 'xml-sitemap') {
    return <AdminXmlSitemapPage />;
  }

  if (module === 'html-sitemap') {
    return <AdminHtmlSitemapPage />;
  }

  if (module === 'blog-post-categories') {
    return <AdminBlogPostCategoriesPage />;
  }

  if (module === 'blog-posts') {
    return <AdminBlogPostsPage />;
  }

  if (module === 'blog-comments') {
    return <AdminBlogCommentsPage />;
  }

  if (module === 'blog-contributions') {
    return <AdminBlogContributionsPage />;
  }

  if (module === 'affiliate-commission') {
    return <AdminAffiliateCommissionPage />;
  }

  if (module === 'themes') {
    return <AdminThemesPage />;
  }

  if (module === 'currency-management') {
    return <AdminCurrencyManagementPage />;
  }

  if (module === 'commission') {
    return <AdminCommissionPage />;
  }

  if (module === 'coupons') {
    return <AdminCouponsPage />;
  }

  if (module === 'social-platform') {
    return <AdminSocialPlatformsPage />;
  }

  if (module === 'slides') {
    return <AdminSlidesPage />;
  }

  if (module === 'payment-methods') {
    return <AdminPaymentMethodsPage />;
  }

  if (module === 'meeting-tools') {
    return <AdminMeetingToolsPage />;
  }

  if (FORUM_MODULES.has(module) && module !== 'forum-report-issue-reasons' && module !== 'forum') {
    return <AdminListPage config={config} />;
  }

  if (module === 'categories') {
    return <AdminCategoriesPage />;
  }

  return <AdminListPage config={config} />;
}
