import { type FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { AdminLogo } from '../components/AdminLogo';
import { ADMIN_FORGOT_PASSWORD_PATH, ADMIN_LOGIN_PATH } from '../config/adminGuestPaths';

export function AdminForgotPasswordPage() {
  const { lbl } = useSite();
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');
    setMessage('');
    setSubmitting(true);
    try {
      // Admin forgot-password API to be wired when backend endpoint is added.
      setMessage(
        lbl(
          'LBL_RESET_PASSWORD_LINK_SENT',
          'If an account exists for this email, a reset link has been sent.'
        )
      );
    } catch {
      setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="login-page login-1">
      <div className="container">
        <div className="login-block">
          <div className="card">
            <div className="card-head d-block">
              <figure className="logo">
                <AdminLogo />
              </figure>
              <h3>{lbl('LBL_Forgot_Your_Password?', 'Forgot Your Password?')}</h3>
              <p>
                {lbl(
                  'LBL_Enter_The_E-mail_Address_Associated_With_Your_Account',
                  'Enter the e-mail address associated with your account'
                )}
              </p>
            </div>
            <div className="card-body">
              <form className="form form-login" onSubmit={onSubmit}>
                {error ? <div className="alert alert-danger">{error}</div> : null}
                {message ? <div className="alert alert-success">{message}</div> : null}
                <div className="form-group">
                  <input
                    className="form-control"
                    type="email"
                    name="admin_email"
                    placeholder={lbl('LBL_EMAIL_ADDRESS', 'Email Address')}
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <button type="submit" className="btn btn-brand btn-lg btn-block" disabled={submitting}>
                    {submitting ? lbl('LBL_PLEASE_WAIT', 'Please wait...') : lbl('LBL_SUBMIT', 'Submit')}
                  </button>
                </div>
              </form>
            </div>
            <div className="card-foot">
              <ul className="other-links">
                <li>
                  <Link to={ADMIN_LOGIN_PATH} className="link">
                    {lbl('LBL_Back_to_Login', 'Back to Login')}
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
