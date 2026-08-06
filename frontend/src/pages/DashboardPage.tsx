import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

/** Redirects /dashboard to role-specific dashboard home. */
export function DashboardPage() {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="page-loading">Loading...</div>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (user.is_teacher) {
    return <Navigate to="/dashboard/teacher" replace />;
  }

  return <Navigate to="/dashboard/learner" replace />;
}
