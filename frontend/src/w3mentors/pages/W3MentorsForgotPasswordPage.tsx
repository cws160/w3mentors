import axios from 'axios';
import { type FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { authApi } from '../../api/client';
import { useSite } from '../context/SiteContext';

export function W3MentorsForgotPasswordPage() {
  const { lbl } = useSite();
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError('');
    setSuccess('');
    setSubmitting(true);
    try {
      const res = await authApi.forgotPassword(email.trim());
      setSuccess(
        res.data.message ??
          lbl(
            'MSG_SENT_RESET_PASSWORD_INSTRUCTIONS_ON_YOUR_EMAIL',
            'Password reset instructions have been sent to your email.'
          )
      );
      setEmail('');
    } catch (err) {
      if (axios.isAxiosError(err)) {
        const message = err.response?.data?.message as string | undefined;
        const errors = err.response?.data?.errors as Record<string, string[]> | undefined;
        if (errors?.email?.[0]) {
          setError(errors.email[0]);
        } else {
          setError(message ?? lbl('LBL_Something_went_wrong', 'Something went wrong. Please try again.'));
        }
      } else {
        setError(lbl('LBL_Something_went_wrong', 'Something went wrong. Please try again.'));
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <section className="section bg-gradiant h-100">
      <div className="container container--fixed">
        <div className="site-form-wrapper">
          <div className="site-form">
            <div className="site-form__header text-center mb-4">
              <h4>{lbl('LBL_FORGOT_PASSWORD', 'Forgot password')}</h4>
            </div>
            <div className="site-form__body">
              {success ? (
                <p className="text-center color-primary">{success}</p>
              ) : (
                <form
                  className="form"
                  id="forgotPasswordFrma"
                  name="forgotPasswordFrm"
                  autoComplete="off"
                  onSubmit={onSubmit}
                >
                  <div className="row">
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">{lbl('LBL_EMAIL_ID', 'Email ID')}</label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              type="email"
                              name="user_email"
                              className="form-control"
                              value={email}
                              onChange={(e) => setEmail(e.target.value)}
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
                            : lbl('BTN_SUBMIT', 'Submit')
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
