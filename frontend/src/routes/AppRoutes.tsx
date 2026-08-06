import { Navigate, Route, Routes } from 'react-router-dom';
import { adminRouteTree } from '../admin/routes/adminRoutes';
import { ProtectedRoute } from '../components/ProtectedRoute';
import { DashboardRouteLayout } from '../w3mentors/layout/DashboardRouteLayout';
import { W3MentorsLayout } from '../w3mentors/layout/W3MentorsLayout';
import { W3MentorsBlogPage } from '../w3mentors/pages/W3MentorsBlogPage';
import { W3MentorsBlogPostPage } from '../w3mentors/pages/W3MentorsBlogPostPage';
import { W3MentorsCmsPage } from '../w3mentors/pages/W3MentorsCmsPage';
import { W3MentorsContactPage } from '../w3mentors/pages/W3MentorsContactPage';
import { W3MentorsCourseDetailPage } from '../w3mentors/pages/W3MentorsCourseDetailPage';
import { W3MentorsCoursesPage } from '../w3mentors/pages/W3MentorsCoursesPage';
import { W3MentorsFaqPage } from '../w3mentors/pages/W3MentorsFaqPage';
import { W3MentorsForumPage } from '../w3mentors/pages/W3MentorsForumPage';
import { W3MentorsForumQuestionPage } from '../w3mentors/pages/W3MentorsForumQuestionPage';
import { W3MentorsGroupClassesPage } from '../w3mentors/pages/W3MentorsGroupClassesPage';
import { W3MentorsHomePage } from '../w3mentors/pages/W3MentorsHomePage';
import { W3MentorsLoginPage } from '../w3mentors/pages/W3MentorsLoginPage';
import { W3MentorsRegisterPage } from '../w3mentors/pages/W3MentorsRegisterPage';
import { W3MentorsApplyToTeachPage } from '../w3mentors/pages/W3MentorsApplyToTeachPage';
import { W3MentorsAffiliateSignupPage } from '../w3mentors/pages/W3MentorsAffiliateSignupPage';
import { W3MentorsForgotPasswordPage } from '../w3mentors/pages/W3MentorsForgotPasswordPage';
import { W3MentorsResetPasswordPage } from '../w3mentors/pages/W3MentorsResetPasswordPage';
import { W3MentorsTeacherProfilePage } from '../w3mentors/pages/W3MentorsTeacherProfilePage';
import { W3MentorsTeachersPage } from '../w3mentors/pages/W3MentorsTeachersPage';
import { W3MentorsVideosPage } from '../w3mentors/pages/W3MentorsVideosPage';
import { W3MentorsSitemapPage } from '../w3mentors/pages/W3MentorsSitemapPage';
import { PagePlaceholder } from '../w3mentors/pages/PagePlaceholder';
import { CourseLearnPage } from '../pages/CourseLearnPage';
import { DashboardPage } from '../pages/DashboardPage';
import { MyCourseDetailPage } from '../pages/MyCourseDetailPage';
import { MyCoursesPage } from '../pages/MyCoursesPage';
import { dashboardLearnerRouteTree, dashboardTeacherRouteTree } from '../w3mentors/dashboard/dashboardRoutes';

export function AppRoutes() {
  return (
    <Routes>
      {adminRouteTree}
      <Route element={<ProtectedRoute />}>
        <Route element={<DashboardRouteLayout />}>
          <Route path="dashboard" element={<DashboardPage />} />
          {dashboardTeacherRouteTree}
          {dashboardLearnerRouteTree}
        </Route>
      </Route>
      <Route element={<W3MentorsLayout />}>
        <Route index element={<W3MentorsHomePage />} />
        <Route path="courses" element={<W3MentorsCoursesPage />} />
        <Route path="courses/:slugOrId" element={<W3MentorsCourseDetailPage />} />
        <Route path="courses/:courseId/learn/:lectureId" element={<CourseLearnPage />} />
        <Route path="teachers" element={<W3MentorsTeachersPage />} />
        <Route path="teachers/:slugOrId" element={<W3MentorsTeacherProfilePage />} />
        <Route path="group-classes" element={<W3MentorsGroupClassesPage />} />
        <Route path="group-classes/:slug" element={<PagePlaceholder title="Group Class" legacyPath="group-classes/view.php" />} />
        <Route path="blog" element={<W3MentorsBlogPage />} />
        <Route path="blog/:id" element={<W3MentorsBlogPostPage />} />
        <Route path="forum" element={<W3MentorsForumPage />} />
        <Route path="forum/:slug" element={<W3MentorsForumQuestionPage />} />
        <Route path="faq" element={<W3MentorsFaqPage />} />
        <Route path="sitemap" element={<W3MentorsSitemapPage />} />
        <Route path="video-content" element={<W3MentorsVideosPage />} />
        <Route path="videos" element={<W3MentorsVideosPage />} />
        <Route path="contact" element={<W3MentorsContactPage />} />
        <Route path="subscription-plans" element={<PagePlaceholder title="Subscription Plans" legacyPath="subscription-plans/index.php" />} />
        <Route
          path="teacher-request/form"
          element={<PagePlaceholder title="Teacher application" legacyPath="teacher-request/form.php" />}
        />
        <Route path="teacher-request" element={<W3MentorsApplyToTeachPage />} />
        <Route path="about" element={<W3MentorsCmsPage pageId="1" />} />
        <Route path="terms-and-conditions" element={<W3MentorsCmsPage legal="terms" />} />
        <Route path="privacy-policy" element={<W3MentorsCmsPage legal="privacy" />} />
        <Route path="cms/:id" element={<W3MentorsCmsPage />} />
        <Route path="login" element={<W3MentorsLoginPage />} />
        <Route path="register" element={<W3MentorsRegisterPage />} />
        <Route path="guest-user/affiliate-signup-form" element={<W3MentorsAffiliateSignupPage />} />
        <Route path="guest-user/forgot-password" element={<W3MentorsForgotPasswordPage />} />
        <Route path="guest-user/reset-password/:userId/:token" element={<W3MentorsResetPasswordPage />} />
        <Route element={<ProtectedRoute />}>
          <Route path="my/courses" element={<MyCoursesPage />} />
          <Route path="my/courses/:id" element={<MyCourseDetailPage />} />
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
}
