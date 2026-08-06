import axios from 'axios';
import { type FormEvent, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { authApi } from '../../api/client';
import { togglePasswordField } from '../modals/authFormUtils';
import { useSite } from '../context/SiteContext';

export function W3MentorsResetPasswordPage() {
  const { lbl } = useSite();
  const navigate = useNavigate();
  const { userId, token } = useParams<{ userId: string; token: string }>();
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [validating, setValidating] = useState(true);
  const [linkValid, setLinkValid] = useState(false);

  const parsedUserId = Number(userId);
  const resetToken = token ?? '';
  const showPwd = lbl('LBL_SHOW_PASSWORD', 'Show password');
  const hidePwd = lbl('LBL_HIDE_PASSWORD', 'Hide password');

  useEffect(() => {
    if (!parsedUserId || !resetToken) {
      setError(lbl('LBL_INVALID_OR_EXPIRED_LINK', 'Invalid or expired link.'));
      setValidating(false);
      return;
    }

    let cancelled = false;

    authApi
      .validateResetPassword(parsedUserId, resetToken)
      .then(() => {
        if (!cancelled) {
          setLinkValid(true);
        }
      })
      .catch((err) => {
        if (!cancelled) {
          const message = axios.isAxiosError(err)
            ? (err.response?.data?.message as string | undefined)
            : undefined;
          setError(message ?? lbl('LBL_INVALID_OR_EXPIRED_LINK', 'Invalid or expired link.'));
        }
      })
      .finally(() => {
        if (!cancelled) {
          setValidating(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [parsedUserId, resetToken, lbl]);

  useEffect(() => {
    if (!success) {
      return;
    }

    const timer = window.setTimeout(() => {
      navigate('/login', { replace: true });
    }, 3000);

    return () => window.clearTimeout(timer);
  }, [success, navigate]);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    if (!parsedUserId || !resetToken) {
      return;
    }

    setError('');
    setSuccess('');
    setSubmitting(true);

    try {
      const res = await authApi.resetPassword({
        user_id: parsedUserId,
        token: resetToken,
        new_password: newPassword,
        confirm_password: confirmPassword,
      });
      setSuccess(
        res.data.message ??
          lbl('MSG_PASSWORD_CHANGED_SUCCESSFULLY', 'Password changed successfully.')
      );
      setNewPassword('');
      setConfirmPassword('');
    } catch (err) {
      if (axios.isAxiosError(err)) {
        const message = err.response?.data?.message as string | undefined;
        setError(message ?? lbl('LBL_Something_went_wrong', 'Something went wrong. Please try again.'));
      } else {
        setError(lbl('LBL_Something_went_wrong', 'Something went wrong. Please try again.'));
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <section className="section bg-gradiant">
      <div className="container container--fixed">
        <div className="site-form-wrapper">
          <div className="site-form">
            <div className="site-form__header text-center mb-5">
              <h4>{lbl('LBL_RESET_PASSWORD?', 'Reset password?')}</h4>
              <p>{lbl('LBL_CHANGE_OR_RESET_YOUR_PASSWORD.', 'Change or reset your password.')}</p>
            </div>
            <div className="site-form__body">
              {validating ? (
                <p className="text-center">{lbl('LBL_Loading', 'Loading...')}</p>
              ) : success ? (
                <p className="text-center color-primary">{success}</p>
              ) : !linkValid ? (
                <p className="text-center color-red">{error}</p>
              ) : (
                <form
                  className="form"
                  id="frmResetPwd"
                  name="frmResetPwd"
                  autoComplete="off"
                  onSubmit={onSubmit}
                >
                  <div className="row">
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_NEW_PASSWORD', 'New password')}
                          </label>
                          <a
                            href="javascript:void(0)"
                            className="link show-hide-btn"
                            data-show-caption={showPwd}
                            data-hide-caption={hidePwd}
                            onClick={(e) => togglePasswordField(e, 'new_password')}
                          >
                            {showPwd}
                          </a>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              type="password"
                              name="new_password"
                              className="form-control"
                              value={newPassword}
                              onChange={(e) => setNewPassword(e.target.value)}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_CONFIRM_NEW_PASSWORD', 'Confirm new password')}
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              type="password"
                              name="confirm_password"
                              className="form-control"
                              value={confirmPassword}
                              onChange={(e) => setConfirmPassword(e.target.value)}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    {error && (
                      <div className="col-md-12">
                        <p className="color-red">{error}</p>
                      </div>
                    )}
                    <div className="col-md-12">
                      <input
                        type="submit"
                        name="btn_submit"
                        className="btn btn--primary btn--block"
                        value={
                          submitting
                            ? lbl('LBL_Loading', 'Loading...')
                            : lbl('LBL_RESET_PASSWORD', 'Reset password')
                        }
                        disabled={submitting}
                      />
                    </div>
                  </div>
                </form>
              )}
            </div>
            <div className="site-form__foot text-center pt-2">
              <p>
                <Link to="/login" className="link">
                  {lbl('LBL_BACK_TO_LOGIN', 'Back to login')}
                </Link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
