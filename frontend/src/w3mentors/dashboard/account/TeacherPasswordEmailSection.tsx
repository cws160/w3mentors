import { useState } from 'react';
import { api } from '../../../api/client';
import { useAuth } from '../../../context/AuthContext';
import { useSite } from '../../context/SiteContext';

type SubTab = 'password' | 'email';

function PasswordField({
  id,
  label,
  value,
  onChange,
  required,
  showToggle,
  captionFlex,
}: {
  id: string;
  label: string;
  value: string;
  onChange: (v: string) => void;
  required?: boolean;
  showToggle?: boolean;
  captionFlex?: boolean;
}) {
  const { lbl } = useSite();
  const [visible, setVisible] = useState(false);

  const captionClass = captionFlex
    ? 'caption-wraper d-flex align-items-center'
    : 'caption-wraper';

  return (
    <div className="field-set">
      <div className={captionClass}>
        <label className="field_label" htmlFor={id}>
          {label}
          {required && <span className="spn_must_field">*</span>}
          {showToggle && (
            <a
              href="javascript:void(0)"
              className="-link-underline -float-right link-color"
              onClick={(e) => {
                e.preventDefault();
                setVisible((v) => !v);
              }}
            >
              {visible
                ? lbl('LBL_Hide_Password', 'Hide password')
                : lbl('LBL_Show_Password', 'Show password')}
            </a>
          )}
        </label>
      </div>
      <div className="field-wraper">
        <div className="field_cover">
          <input
            id={id}
            type={visible ? 'text' : 'password'}
            className="form-control"
            value={value}
            autoComplete="off"
            onChange={(e) => onChange(e.target.value)}
            required={required}
          />
        </div>
      </div>
    </div>
  );
}

function FormActions({
  formId,
  saving,
  error,
  message,
  alignCenter,
}: {
  formId: string;
  saving: boolean;
  error: string;
  message: string;
  alignCenter?: boolean;
}) {
  const { lbl } = useSite();
  const flexClass = alignCenter ? 'd-flex align-items-center' : 'd-flex';

  return (
    <div className="form__actions">
      <div className={flexClass}>
        {error && <p className="color-danger m-0 me-3">{error}</p>}
        {message && <p className="color-primary m-0 me-3">{message}</p>}
        <button
          type="submit"
          form={formId}
          className="btn btn--primary"
          disabled={saving}
        >
          {saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
        </button>
      </div>
    </div>
  );
}

export function TeacherPasswordEmailSection() {
  const { lbl } = useSite();
  const { user } = useAuth();
  const [subTab, setSubTab] = useState<SubTab>('password');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confPassword, setConfPassword] = useState('');

  const currentEmail = user?.email ?? '';
  const [newEmail, setNewEmail] = useState('');
  const [emailPassword, setEmailPassword] = useState('');

  const onSubmitPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage('');
    setError('');
    try {
      const res = await api.put<{ message: string }>('/users/me/password', {
        current_password: currentPassword,
        new_password: newPassword,
        conf_new_password: confPassword,
      });
      setMessage(res.data.message);
      setCurrentPassword('');
      setNewPassword('');
      setConfPassword('');
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const onSubmitEmail = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage('');
    setError('');
    try {
      const res = await api.put<{ message: string }>('/users/me/email', {
        new_email: newEmail,
        current_password: emailPassword,
      });
      setMessage(res.data.message);
      setEmailPassword('');
      setNewEmail('');
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const switchTab = (tab: SubTab) => {
    setSubTab(tab);
    setError('');
    setMessage('');
  };

  return (
    <>
      <div className="content-panel__head">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_CHANGE_PASSWORD_OR_EMAIL', 'Change password or email')}</h5>
          </div>
          <div />
        </div>
      </div>
      <div className="content-panel__body">
        <div className="form">
          <div className="form__body p-0">
            <nav className="tabs tabs--line ps-4 pe-4">
              <ul>
                <li className={subTab === 'password' ? 'is-active' : ''}>
                  <a
                    href="javascript:void(0);"
                    onClick={(e) => {
                      e.preventDefault();
                      switchTab('password');
                    }}
                  >
                    {lbl('LBL_Password', 'Password')}
                  </a>
                </li>
                <li className={subTab === 'email' ? 'is-active' : ''}>
                  <a
                    href="javascript:void(0);"
                    onClick={(e) => {
                      e.preventDefault();
                      switchTab('email');
                    }}
                  >
                    {lbl('LBL_Email', 'Email')}
                  </a>
                </li>
              </ul>
            </nav>
            <div className="tabs-data">
              <div className="padding-6 pb-0">
                {subTab === 'password' ? (
                  <form
                    id="pwdFrm"
                    className="form"
                    onSubmit={onSubmitPassword}
                    autoComplete="off"
                  >
                    <div className="row">
                      <div className="col-md-6">
                        <PasswordField
                          id="current_password"
                          label={lbl('LBL_CURRENT_PASSWORD', 'Current password')}
                          value={currentPassword}
                          onChange={setCurrentPassword}
                          required
                          showToggle
                          captionFlex
                        />
                      </div>
                    </div>
                    <div className="row">
                      <div className="col-md-6">
                        <PasswordField
                          id="new_password"
                          label={lbl('LBL_NEW_PASSWORD', 'New password')}
                          value={newPassword}
                          onChange={setNewPassword}
                          required
                          showToggle
                        />
                      </div>
                    </div>
                    <div className="row">
                      <div className="col-md-6">
                        <PasswordField
                          id="conf_new_password"
                          label={lbl('LBL_CONFIRM_NEW_PASSWORD', 'Confirm new password')}
                          value={confPassword}
                          onChange={setConfPassword}
                          required
                        />
                      </div>
                    </div>
                  </form>
                ) : (
                  <form
                    id="EmailFrm"
                    className="form"
                    onSubmit={onSubmitEmail}
                    autoComplete="off"
                  >
                    <div className="row">
                      <div className="col-md-6">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('LBL_CURRENT_EMAIL', 'Current email')}
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="email"
                                className="form-control"
                                value={currentEmail}
                                disabled
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="row">
                      <div className="col-md-6">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('LBL_NEW_EMAIL', 'New email')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="email"
                                className="form-control"
                                value={newEmail}
                                onChange={(e) => setNewEmail(e.target.value)}
                                required
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="row">
                      <div className="col-md-6">
                        <PasswordField
                          id="current_password"
                          label={lbl('LBL_CURRENT_PASSWORD', 'Current password')}
                          value={emailPassword}
                          onChange={setEmailPassword}
                          required
                        />
                      </div>
                    </div>
                  </form>
                )}
              </div>
            </div>
          </div>
          {subTab === 'password' ? (
            <FormActions
              formId="pwdFrm"
              saving={saving}
              error={error}
              message={message}
              alignCenter
            />
          ) : (
            <FormActions formId="EmailFrm" saving={saving} error={error} message={message} />
          )}
        </div>
      </div>
    </>
  );
}
