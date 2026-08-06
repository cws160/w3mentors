import axios from 'axios';
import { type CSSProperties, type FormEvent, useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { authApi, contentApi, type AffiliateSignupContent } from '../../api/client';
import { normalizeUser } from '../../utils/authUser';
import { useSite } from '../context/SiteContext';
import { useAuthModals } from '../hooks/useAuthModals';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { togglePasswordField } from '../modals/authFormUtils';
import { cmsPagePath } from '../utils/cms';
import { normalizeLegacyHtml } from '../utils/legacyHtml';

export function W3MentorsAffiliateSignupPage() {
  const { lbl, legalPages } = useSite();
  const { openLoginModal } = useAuthModals();
  const [data, setData] = useState<AffiliateSignupContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [agree, setAgree] = useState(false);
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    contentApi
      .affiliateSignup()
      .then((res) => setData(res.data))
      .catch(() =>
        setData({
          enabled: false,
          banner_html: '',
          banner_image: '',
          terms_page_id: 0,
          privacy_page_id: 0,
          default_timezone: 'UTC',
          timezones: [],
        })
      )
      .finally(() => setLoading(false));
  }, []);

  const termsUrl =
    legalPages?.terms_url ??
    (data?.terms_page_id ? cmsPagePath(data.terms_page_id, legalPages ?? undefined) : '/terms-and-conditions');
  const privacyUrl =
    legalPages?.privacy_url ??
    (data?.privacy_page_id ? cmsPagePath(data.privacy_page_id, legalPages ?? undefined) : '/privacy-policy');

  const showPwd = lbl('LBL_Show_Password', 'Show password');
  const hidePwd = lbl('LBL_Hide_Password', 'Hide password');
  const hasBannerContent = Boolean(data?.banner_html?.trim());

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    if (!agree) {
      setError(lbl('MSG_TERMS_AND_CONDITION_ARE_MANDATORY', 'Please accept the terms and conditions'));
      return;
    }
    setError('');
    setSubmitting(true);
    try {
      const res = await authApi.affiliateRegister({
        first_name: firstName,
        last_name: lastName,
        email,
        password,
        agree,
      });
      const normalized = normalizeUser(res.data.user);
      if (normalized && res.data.token) {
        localStorage.setItem('auth_token', res.data.token);
        localStorage.setItem('auth_user', JSON.stringify(normalized));
      }
      window.location.href = '/dashboard';
    } catch (err) {
      if (axios.isAxiosError(err)) {
        const errors = err.response?.data?.errors as Record<string, string[]> | undefined;
        const message = err.response?.data?.message as string | undefined;
        if (errors) {
          const first = Object.values(errors)[0]?.[0];
          setError(first ?? message ?? lbl('LBL_Something_went_wrong', 'Registration failed'));
        } else {
          setError(message ?? lbl('LBL_Something_went_wrong', 'Registration failed'));
        }
      } else {
        setError(lbl('LBL_Something_went_wrong', 'Registration failed'));
      }
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) {
    return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  }

  if (!data?.enabled) {
    return <Navigate to="/" replace />;
  }

  const sectionStyle: CSSProperties | undefined = data.banner_image
    ? {
        backgroundImage: `url(${data.banner_image})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
      }
    : undefined;

  return (
    <section className="section" style={sectionStyle}>
      <div className="container container--xxl">
        <div className="row gap-5 align-items-center justify-content-between">
          {hasBannerContent && (
            <div className="col-lg-5 col-xxl-5 order-2">
              <div className="cms-form">
                <div
                  className="editor-content"
                  dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(data.banner_html) }}
                />
              </div>
            </div>
          )}
          <div className={`col-lg-6 col-xxl-4 order-1${hasBannerContent ? '' : ' mx-auto'}`}>
            <div className="card-form">
              <div className="card-form__head">
                <h2>{lbl('LBL_REGISTER_AS_AFFILIATE', 'Register as affiliate')}</h2>
              </div>
              <div className="card-form__body">
                <form
                  className="form"
                  id="affiliateSignupFrm"
                  name="affiliateSignupFrm"
                  onSubmit={onSubmit}
                >
                  <div className="row">
                    <div className="col-sm-6">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">{lbl('LBL_FIRST_NAME', 'First name')}</label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              name="user_first_name"
                              className="form-control"
                              value={firstName}
                              onChange={(e) => setFirstName(e.target.value)}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-sm-6">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">{lbl('LBL_LAST_NAME', 'Last name')}</label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              name="user_last_name"
                              className="form-control"
                              value={lastName}
                              onChange={(e) => setLastName(e.target.value)}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-sm-12">
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
                              autoComplete="off"
                              value={email}
                              onChange={(e) => setEmail(e.target.value)}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-sm-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_Password', 'Password')}
                            <span className="spn_must_field">*</span>
                            <a
                              href="javascript:void(0)"
                              className="link"
                              data-show-caption={showPwd}
                              data-hide-caption={hidePwd}
                              onClick={(e) => togglePasswordField(e, 'user_password')}
                            >
                              {showPwd}
                            </a>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              type="password"
                              name="user_password"
                              className="form-control"
                              value={password}
                              onChange={(e) => setPassword(e.target.value)}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-sm-12">
                      <div className="field-set terms_wrap set-remember">
                        <div className="field-wraper" id="termLabelWrapper">
                          <div className="field_cover">
                            <label className="checkbox field_resp_block">
                              <input
                                type="checkbox"
                                name="agree"
                                value="1"
                                checked={agree}
                                onChange={(e) => setAgree(e.target.checked)}
                              />
                              <i className="input-helper" />
                              <span>
                                {lbl('LBL_I_ACCEPT_TO_THE', 'I accept to the')}{' '}
                                <a href={termsUrl} target="_blank" rel="noreferrer" className="link">
                                  {lbl('LBL_TERMS_AND_CONDITION', 'Terms & Conditions')}
                                </a>{' '}
                                {lbl('LBL_AND', 'And')}{' '}
                                <a href={privacyUrl} target="_blank" rel="noreferrer" className="link">
                                  {lbl('LBL_Privacy_Policy', 'Privacy Policy')}
                                </a>
                              </span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                    {error && (
                      <div className="col-sm-12">
                        <p className="color-red">{error}</p>
                      </div>
                    )}
                    <div className="col-sm-12">
                      <input
                        type="submit"
                        name="btn_submit"
                        className="btn btn--primary btn--block"
                        value={submitting ? lbl('LBL_Loading', 'Loading...') : lbl('LBL_Register', 'Register')}
                        disabled={submitting}
                      />
                    </div>
                  </div>
                </form>
              </div>
              <div className="card-form__footer text-center">
                <p>
                  {lbl('LBL_ALREADY_HAVE_AN_ACCOUNT?', 'Already have an account?')}{' '}
                  <a
                    href="javascript:void(0)"
                    className="link"
                    onClick={(e) => {
                      e.preventDefault();
                      openLoginModal();
                    }}
                  >
                    {lbl('LBL_Sign_In', 'Sign in')}
                  </a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
