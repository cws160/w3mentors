import { useEffect, useState, type FormEvent } from 'react';
import { adminApi } from '../api/adminClient';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { useSite } from '../../w3mentors/context/SiteContext';

type PasswordForm = {
  current_password: string;
  new_password: string;
  conf_new_password: string;
};

type PasswordErrors = Partial<Record<keyof PasswordForm, string>>;

export function AdminChangePasswordPage() {
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [form, setForm] = useState<PasswordForm>({
    current_password: '',
    new_password: '',
    conf_new_password: '',
  });
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [errors, setErrors] = useState<PasswordErrors>({});

  useEffect(() => {
    setMeta({
      title: lbl('LBL_PROFILE', 'Profile'),
      summary: lbl('LBL_UPDATE_YOUR_PROFILE_INFORMATION_UNDER_THIS_SECTION', 'Update your profile information under this section.'),
    });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  const updateField = (name: keyof PasswordForm, value: string) => {
    setForm((current) => ({ ...current, [name]: value }));
    setErrors((current) => ({ ...current, [name]: undefined }));
  };

  const validate = (): PasswordErrors => {
    const nextErrors: PasswordErrors = {};
    if (!form.current_password.trim()) {
      nextErrors.current_password = lbl('LBL_CURRENT_PASSWORD_IS_MANDATORY', 'Current password Is mandatory');
    }
    if (!/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%_-]{8,15}$/.test(form.new_password)) {
      nextErrors.new_password = lbl('LBL_PLEASE_ENTER_8_DIGIT_ALPHANUMERIC_PASSWORD', 'Please enter a 8 digit alphanumeric password');
    }
    if (!form.conf_new_password.trim()) {
      nextErrors.conf_new_password = lbl('LBL_CONFIRM_NEW_PASSWORD_IS_MANDATORY', 'Confirm new password Is mandatory');
    } else if (form.conf_new_password !== form.new_password) {
      nextErrors.conf_new_password = lbl('LBL_CONFIRM_PASSWORD_MUST_MATCH', 'Confirm password must match');
    }
    return nextErrors;
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const nextErrors = validate();
    if (Object.keys(nextErrors).length > 0) {
      setErrors(nextErrors);
      setMessage('');
      return;
    }
    setSaving(true);
    setMessage('');
    setErrors({});
    try {
      await adminApi.updatePassword(form);
      setForm({ current_password: '', new_password: '', conf_new_password: '' });
      setMessage(lbl('LBL_PASSWORD_UPDATED_SUCCESSFULLY', 'Password updated successfully'));
    } catch (err) {
      const apiError = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      const errors = apiError.response?.data?.errors;
      if (errors) {
        setErrors({
          current_password: errors.current_password?.[0],
          new_password: errors.new_password?.[0],
          conf_new_password: errors.conf_new_password?.[0],
        });
      } else {
        setErrors({ current_password: apiError.response?.data?.message || lbl('LBL_UNABLE_TO_UPDATE_PASSWORD', 'Unable to update password') });
      }
    } finally {
      setSaving(false);
    }
  };

  return (
    <main className="main">
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-6">
            <div className="card">
              <div className="card-head">
                <div className="card-head-label">
                  <h3 className="card-head-title">{lbl('LBL_CHANGE_PASSWORD', 'Change Password')}</h3>
                </div>
              </div>
              <div className="card-body">
                {message ? <div className="alert alert-success">{message}</div> : null}
                <form id="getPwdFrm" className="form" autoComplete="off" onSubmit={handleSubmit} noValidate>
                  <div className="row">
                    <div className="col-md-6">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label" htmlFor="current_password">
                            {lbl('LBL_CURRENT_PASSWORD', 'Current Password')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              id="current_password"
                              className={`form-control${errors.current_password ? ' error' : ''}`}
                              type="password"
                              name="current_password"
                              autoComplete="off"
                              value={form.current_password}
                              onChange={(event) => updateField('current_password', event.target.value)}
                            />
                            {errors.current_password ? <div className="field-error">{errors.current_password}</div> : null}
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label" htmlFor="new_password">
                            {lbl('LBL_NEW_PASSWORD', 'New Password')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              id="new_password"
                              className={`form-control${errors.new_password ? ' error' : ''}`}
                              type="password"
                              name="new_password"
                              value={form.new_password}
                              onChange={(event) => updateField('new_password', event.target.value)}
                            />
                            {errors.new_password ? <div className="field-error">{errors.new_password}</div> : null}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="row">
                    <div className="col-md-6">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label" htmlFor="conf_new_password">
                            {lbl('LBL_CONFIRM_NEW_PASSWORD', 'Confirm New Password')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              id="conf_new_password"
                              className={`form-control${errors.conf_new_password ? ' error' : ''}`}
                              type="password"
                              name="conf_new_password"
                              value={form.conf_new_password}
                              onChange={(event) => updateField('conf_new_password', event.target.value)}
                            />
                            {errors.conf_new_password ? <div className="field-error">{errors.conf_new_password}</div> : null}
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label" htmlFor="btn_submit" />
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <button id="btn_submit" className="btn btn-primary" type="submit" disabled={saving}>
                              {saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_CHANGE', 'Change')}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  );
}
