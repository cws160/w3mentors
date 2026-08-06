import { type FormEvent, useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useModal } from '../context/ModalContext';
import { useSite } from '../context/SiteContext';
import { AuthSocialLogin } from './AuthSocialLogin';
import { togglePasswordField } from './authFormUtils';

type Props = {
  onSignInClick?: () => void;
};

export function RegisterModalContent({ onSignInClick }: Props) {
  const { register } = useAuth();
  const { closeModal } = useModal();
  const { lbl, legalPages } = useSite();
  const termsUrl = legalPages?.terms_url ?? '/terms-and-conditions';
  const privacyUrl = legalPages?.privacy_url ?? '/privacy-policy';
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [agree, setAgree] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const showPwd = lbl('LBL_Show_Password', 'Show password');
  const hidePwd = lbl('LBL_Hide_Password', 'Hide password');

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    if (!agree) {
      setError(lbl('LBL_Please_accept_terms', 'Please accept the terms and conditions'));
      return;
    }
    setError('');
    setLoading(true);
    try {
      await register({ first_name: firstName, last_name: lastName, email, password });
      closeModal();
      window.location.href = '/dashboard';
    } catch {
      setError(lbl('LBL_Something_went_wrong', 'Registration failed'));
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="site-form-wrapper">
      <div className="site-form">
        <div className="site-form__header text-center mb-4">
          <h4>{lbl('LBL_REGISTER', 'Register')}</h4>
          <button
            type="button"
            className="btn-close w3mentorsmodalJs"
            data-bs-dismiss="modal"
            aria-label=""
          />
        </div>
        <div className="site-form__body">
          <AuthSocialLogin />
          <form className="form" id="signupForm" name="signupForm" onSubmit={onSubmit}>
            <div className="row">
              <div className="col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_First_Name', 'First name')}</label>
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
                    <label className="field_label">{lbl('LBL_Last_Name', 'Last name')}</label>
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
                    <label className="field_label">{lbl('LBL_Email', 'Email')}</label>
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
                  <div className="field-wraper">
                    <div className="field_cover">
                      <label className="checkbox">
                        <input
                          type="checkbox"
                          name="agree"
                          value="1"
                          checked={agree}
                          onChange={(e) => setAgree(e.target.checked)}
                        />
                        <i className="input-helper" />
                        <span>
                          <a href={termsUrl} target="_blank" rel="noreferrer" className="link">
                            {lbl('LBL_TERMS_AND_CONDITION', 'Terms and conditions')}
                          </a>{' '}
                          {lbl('LBL_AND', 'and')}{' '}
                          <a href={privacyUrl} target="_blank" rel="noreferrer" className="link">
                            {lbl('LBL_Privacy_Policy', 'Privacy policy')}
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
                  className="btn btn--primary btn--block"
                  value={loading ? '...' : lbl('LBL_REGISTER', 'Register')}
                  disabled={loading}
                />
              </div>
            </div>
          </form>
        </div>
        <div className="site-form__foot text-center">
          <p>
            {lbl('LBL_ALREADY_HAVE_AN_ACCOUNT?', 'Already have an account?')}{' '}
            <a
              href="javascript:void(0)"
              className="link"
              onClick={(e) => {
                e.preventDefault();
                if (onSignInClick) onSignInClick();
              }}
            >
              {lbl('LBL_Sign_In', 'Sign in')}
            </a>
          </p>
        </div>
      </div>
    </div>
  );
}
