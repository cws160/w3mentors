import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../../context/AuthContext';
import { DashboardListPage } from '../DashboardListPage';
import { dashboardListConfigs } from '../dashboardListConfigs';
import { useDashboardRole } from '../DashboardShell';

export function DashboardListRoutePage() {
  const location = useLocation();
  const role = useDashboardRole();
  const { user } = useAuth();

  const match = location.pathname.match(/\/dashboard\/(?:teacher|learner)\/(.+)$/);
  const pathKey = match?.[1] ?? '';
  const config = dashboardListConfigs[pathKey];
  if (!config || !config.endpoint) {
    return <Navigate to={role === 'teacher' ? '/dashboard/teacher' : '/dashboard/learner'} replace />;
  }

  if (config.teacherOnly && !user?.is_teacher) {
    return <Navigate to="/dashboard/learner" replace />;
  }
  if (config.learnerOnly && user?.is_teacher) {
    return <Navigate to="/dashboard/teacher" replace />;
  }

  return <DashboardListPage config={config} />;
}
