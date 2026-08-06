import { Navigate, Outlet } from 'react-router-dom';
import { ADMIN_LOGIN_PATH } from '../config/adminGuestPaths';
import { useAdminAuth } from '../context/AdminAuthContext';

export function AdminProtectedRoute() {
  const { admin, loading } = useAdminAuth();

  if (loading) {
    return (
      <div className="table-processing loaderJs" style={{ minHeight: '100vh' }}>
        <div className="spinner spinner--sm spinner--brand" />
      </div>
    );
  }

  if (!admin) {
    return <Navigate to={ADMIN_LOGIN_PATH} replace />;
  }

  return <Outlet />;
}

export function AdminGuestRoute() {
  const { admin, loading } = useAdminAuth();

  if (loading) {
    return (
      <div className="table-processing loaderJs" style={{ minHeight: '100vh' }}>
        <div className="spinner spinner--sm spinner--brand" />
      </div>
    );
  }

  if (admin) {
    return <Navigate to="/admin" replace />;
  }

  return <Outlet />;
}
