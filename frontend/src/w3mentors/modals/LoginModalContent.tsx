import { type FormEvent, useEffect, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useModal } from '../context/ModalContext';
import { Link } from 'react-router-dom';
import { useSite } from '../context/SiteContext';
import { AuthSocialLogin } from './AuthSocialLogin';
import { togglePasswordField } from './authFormUtils';

type Props = {
  onRegisterClick?: () => void;
};

export function LoginModalContent({ onRegisterClick }: Props) {
  const { login } = useAuth();
  const { closeModal } = useModal();
  const { lbl, demoLogin } = useSite();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!demoLogin?.default) {
      return;
    }
    setEmail(demoLogin.default.email);
    setPassword(demoLogin.default.password);
  }, [demoLogin]);

  const showPwd = lbl('LBL_Show_Password', 'Show password');
  const hidePwd = lbl('LBL_Hide_Password', 'Hide password');

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await login(email, password);
      closeModal();
    } catch {
      setError(lbl('ERR_INVALID_CERDENTIALS', 'Invalid credentials'));
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="site-form-wrapper">
      <div className="site-form">
        <div className="site-form__header text-center mb-4">
          <h4>{lbl('LBL_LOGIN', 'Login')}</h4>
          <button
            type="button"
            className="btn-close w3mentorsmodalJs"
            data-bs-dismiss="modal"
            aria-label=""
          />
        </div>
        <div className="site-form__body">
          <AuthSocialLogin />
          <form className="form" id="signinFrmPopUp" name="signinFrmPopUp" onSubmit={onSubmit}>
            <div className="row">
              <div className="col-sm-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Email', 'Email')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="email"
                        name="username_email"
                        className="form-control"
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
                      <a
                        href="javascript:void(0)"
                        className="link"
                        data-show-caption={showPwd}
                        data-hide-caption={hidePwd}
                        onClick={(e) => togglePasswordField(e, 'password')}
                      >
                        {showPwd}
                      </a>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="password"
                        name="password"
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
                <div className="field-set set-remember">
                  <div className="field-wraper">
                    <div className="field_cover">
                      <label className="checkbox">
                        <input
                          type="checkbox"
                          name="remember_me"
                          value="1"
                          checked={rememberMe}
                          onChange={(e) => setRememberMe(e.target.checked)}
                        />
                        <i className="input-helper" />
                        <span>{lbl('LBL_Remember_Me', 'Remember me')}</span>
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
                  className="btn btn--primary btn--block"
                  value={loading ? '...' : lbl('LBL_LOGIN', 'Login')}
                  disabled={loading}
                />
              </div>
            </div>
          </form>
        </div>
        <div className="site-form__foot">
          <div className="text-center">
            <p>
              {lbl('LBL_DO_NOT_HAVE_AN_ACCOUNT?', "Don't have an account?")}{' '}
              <a
                href="javascript:void(0)"
                className="link"
                onClick={(e) => {
                  e.preventDefault();
                  if (onRegisterClick) onRegisterClick();
                }}
              >
                {lbl('LBL_REGISTER', 'Register')}
              </a>
            </p>
            <p>
              <Link to="/guest-user/forgot-password" className="link">
                {lbl('LBL_Forgot_Password?', 'Forgot password?')}
              </Link>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
