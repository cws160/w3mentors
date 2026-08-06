import { Route } from 'react-router-dom';
import { DashboardShell } from './DashboardShell';
import { DashboardAccountPage } from './pages/DashboardAccountPage';
import { DashboardCoursesPage } from './pages/DashboardCoursesPage';
import { DashboardCourseResourcesPage } from './pages/DashboardCourseResourcesPage';
import { DashboardCourseEditRequestsPage } from './pages/DashboardCourseEditRequestsPage';
import { DashboardLearnerHome } from './pages/DashboardLearnerHome';
import { DashboardLessonsPage } from './pages/DashboardLessonsPage';
import { DashboardAvailabilityPage } from './pages/DashboardAvailabilityPage';
import { DashboardGroupSessionsPage } from './pages/DashboardGroupSessionsPage';
import { DashboardPackagesPage } from './pages/DashboardPackagesPage';
import { DashboardIssuesPage } from './pages/DashboardIssuesPage';
import { DashboardListRoutePage } from './pages/DashboardListRoutePage';
import { DashboardOrdersPage } from './pages/DashboardOrdersPage';
import { DashboardPlansPage } from './pages/DashboardPlansPage';
import { DashboardStudentsPage } from './pages/DashboardStudentsPage';
import { DashboardSubscriptionsPage } from './pages/DashboardSubscriptionsPage';
import { DashboardTeacherHome } from './pages/DashboardTeacherHome';
import { DashboardTeachersPage } from './pages/DashboardTeachersPage';
import { DashboardQuestionsPage } from './pages/DashboardQuestionsPage';
import { DashboardQuizzesPage } from './pages/DashboardQuizzesPage';
import { DashboardQuizFormPage } from './pages/DashboardQuizFormPage';
import { DashboardWalletPage } from './pages/DashboardWalletPage';
import { DashboardWithdrawRequestsPage } from './pages/DashboardWithdrawRequestsPage';
import { DashboardForumQuestionsPage } from './pages/DashboardForumQuestionsPage';
import { DashboardForumQuestionFormPage } from './pages/DashboardForumQuestionFormPage';
import { DashboardForumTagRequestsPage } from './pages/DashboardForumTagRequestsPage';
import { DashboardGiftCardsPage } from './pages/DashboardGiftCardsPage';
import { DashboardForumSubscribedTagsPage } from './pages/DashboardForumSubscribedTagsPage';

const listPagePaths = [
  'subscription-plans',
  'refer',
  'chats',
  'notifications',
  'favorite-courses',
  'certificates',
] as const;

const listRoutes = listPagePaths.map((path) => (
  <Route key={path} path={path} element={<DashboardListRoutePage />} />
));

const sharedChildRoutes = (
  <>
    <Route path="availability" element={<DashboardAvailabilityPage />} />
    <Route path="lessons" element={<DashboardLessonsPage />} />
    <Route path="lessons/:lessonId" element={<DashboardLessonsPage />} />
    <Route path="students" element={<DashboardStudentsPage />} />
    <Route path="teachers" element={<DashboardTeachersPage />} />
    <Route path="wallet" element={<DashboardWalletPage />} />
    <Route path="wallet/withdraw-requests" element={<DashboardWithdrawRequestsPage />} />
    <Route path="orders" element={<DashboardOrdersPage />} />
    <Route path="courses" element={<DashboardCoursesPage />} />
    <Route path="resources" element={<DashboardCourseResourcesPage />} />
    <Route path="course-edit-requests" element={<DashboardCourseEditRequestsPage />} />
    <Route path="account" element={<DashboardAccountPage />} />
    <Route path="plans" element={<DashboardPlansPage />} />
    <Route path="classes" element={<DashboardGroupSessionsPage mode="classes" />} />
    <Route path="packages" element={<DashboardPackagesPage />} />
    <Route path="issues" element={<DashboardIssuesPage />} />
    <Route path="subscriptions" element={<DashboardSubscriptionsPage />} />
    <Route path="questions" element={<DashboardQuestionsPage />} />
    <Route path="quizzes" element={<DashboardQuizzesPage />} />
    <Route path="quizzes/form" element={<DashboardQuizFormPage />} />
    <Route path="quizzes/form/:quizId" element={<DashboardQuizFormPage />} />
    <Route path="forum/form" element={<DashboardForumQuestionFormPage />} />
    <Route path="forum/form/:questionId" element={<DashboardForumQuestionFormPage />} />
    <Route path="forum" element={<DashboardForumQuestionsPage />} />
    <Route path="forum-subscribed-tags" element={<DashboardForumSubscribedTagsPage />} />
    <Route path="forum-tag-requests" element={<DashboardForumTagRequestsPage />} />
    <Route path="giftcard" element={<DashboardGiftCardsPage />} />
    {listRoutes}
  </>
);

export const dashboardTeacherRouteTree = (
  <Route path="dashboard/teacher" element={<DashboardShell />}>
    <Route index element={<DashboardTeacherHome />} />
    {sharedChildRoutes}
  </Route>
);

export const dashboardLearnerRouteTree = (
  <Route path="dashboard/learner" element={<DashboardShell />}>
    <Route index element={<DashboardLearnerHome />} />
    {sharedChildRoutes}
  </Route>
);
