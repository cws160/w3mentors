import { type FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { AdminLogo } from '../components/AdminLogo';
import { ADMIN_FORGOT_PASSWORD_PATH } from '../config/adminGuestPaths';
import { useAdminAuth } from '../context/AdminAuthContext';

export function AdminLoginPage() {
  const { lbl } = useSite();
  const { login } = useAdminAuth();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');
    setSubmitting(true);
    try {
      await login(username, password);
      if (remember) {
        localStorage.setItem('admin_remember', username);
      } else {
        localStorage.removeItem('admin_remember');
      }
    } catch {
      setError(lbl('LBL_INVALID_CREDENTIALS', 'Invalid credentials'));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="login-page login-1">
      <div className="container">
        <div className="login-block">
          <div className="card">
            <div className="card-head">
              <figure className="logo">
                <AdminLogo />
              </figure>
            </div>
            <div className="card-body">
              <form className="form form-login" onSubmit={onSubmit}>
                {error ? <div className="alert alert-danger">{error}</div> : null}
                <div className="form-group">
                  <label className="label">Username</label>
                  <input
                    className="form-control"
                    type="text"
                    placeholder={lbl('LBL_Username', 'Username')}
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    autoComplete="username"
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="label">Password</label>
                  <input
                    className="form-control"
                    type="password"
                    placeholder={lbl('LBL_Password', 'Password')}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    autoComplete="current-password"
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="switch switch-sm switch-icon remember-me">
                    <input type="checkbox" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
                    <span className="input-helper" />
                    {lbl('LBL_Remember_me', 'Remember me')}
                  </label>
                </div>
                <div className="form-group">
                  <button type="submit" className="btn btn-brand btn-lg btn-block" disabled={submitting}>
                    {submitting ? lbl('LBL_PLEASE_WAIT', 'Please wait...') : lbl('LBL_LOGIN', 'Login')}
                  </button>
                </div>
              </form>
            </div>
            <div className="card-foot">
              <ul className="other-links">
                <li>
                  <Link to={ADMIN_FORGOT_PASSWORD_PATH} className="link">
                    {lbl('LBL_Forgot_Password?', 'Forgot Password?')}
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
