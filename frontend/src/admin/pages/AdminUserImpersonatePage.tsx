import { useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';

/** Stores admin impersonation token and redirects to user dashboard (legacy UsersController::login). */
export function AdminUserImpersonatePage() {
  const [params] = useSearchParams();

  useEffect(() => {
    const token = params.get('token');
    const redirect = params.get('redirect') || '/dashboard/learner';
    if (token) {
      localStorage.setItem('auth_token', token);
      window.dispatchEvent(new Event('auth:updated'));
    }
    window.location.replace(redirect);
  }, [params]);

  return (
    <div className="table-processing loaderJs" style={{ minHeight: '100vh' }}>
      <div className="spinner spinner--sm spinner--brand" />
    </div>
  );
}
