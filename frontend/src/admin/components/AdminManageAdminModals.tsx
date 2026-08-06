import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type TimezoneOption = { id: string; label: string };

type AdminDetail = {
  id: number;
  full_name: string;
  username: string;
  email: string;
  timezone: string;
  active: number;
};

const ADMIN_PASSWORD_REGEX = /^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%_-]{8,15}$/;

export type AdminManageAdminModalType = 'create' | 'edit' | 'change-password';

type Props = {
  active: { type: AdminManageAdminModalType; adminId: number } | null;
  onClose: () => void;
  onUpdated: () => void;
};

export function AdminManageAdminModals({ active, onClose, onUpdated }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [detail, setDetail] = useState<AdminDetail | null>(null);
  const [timezones, setTimezones] = useState<TimezoneOption[]>([]);
  const [defaultTimezone, setDefaultTimezone] = useState('UTC');
  const [fullName, setFullName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [timezone, setTimezone] = useState('UTC');
  const [activeStatus, setActiveStatus] = useState('1');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordErrors, setPasswordErrors] = useState<{ password?: string; confirm_password?: string }>({});

  const resetForm = () => {
    setDetail(null);
    setFullName('');
    setUsername('');
    setEmail('');
    setTimezone(defaultTimezone);
    setActiveStatus('1');
    setPassword('');
    setConfirmPassword('');
    setPasswordErrors({});
    setError('');
    setSuccess('');
  };

  const loadCreate = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.manageAdminCreateForm();
      const data = res.data;
      setTimezones(data.timezones ?? []);
      setDefaultTimezone(data.default_timezone ?? 'UTC');
      setTimezone(data.default_timezone ?? 'UTC');
    } catch (e: unknown) {
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load form',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  const loadEdit = useCallback(async (adminId: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.manageAdminShow(adminId);
      const data = res.data.data as unknown as AdminDetail;
      setDetail(data);
      setFullName(data.full_name ?? '');
      setUsername(data.username ?? '');
      setEmail(data.email ?? '');
      setTimezone(data.timezone ?? 'UTC');
      setActiveStatus(String(data.active ?? 1));
      const formRes = await adminApi.manageAdminCreateForm();
      setTimezones(formRes.data.timezones ?? []);
    } catch (e: unknown) {
      setDetail(null);
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load admin',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!active) {
      resetForm();
      return;
    }
    if (active.type === 'create') {
      resetForm();
      void loadCreate();
      return;
    }
    if (active.type === 'edit') {
      resetForm();
      void loadEdit(active.adminId);
      return;
    }
    if (active.type === 'change-password') {
      resetForm();
      void loadEdit(active.adminId);
      return;
    }
  }, [active, loadCreate, loadEdit]);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    if (!active) return;
    setError('');
    setSuccess('');
    setPasswordErrors({});
    if (active.type === 'create' || active.type === 'change-password') {
      const nextErrors: { password?: string; confirm_password?: string } = {};
      if (!password || !ADMIN_PASSWORD_REGEX.test(password)) {
        nextErrors.password = lbl(
          'LBL_PLEASE_ENTER_8_DIGIT_ALPHANUMERIC_PASSWORD',
          'Please enter a 8 digit alphanumeric password',
        );
      }
      if (!confirmPassword) {
        nextErrors.confirm_password = lbl(
          'LBL_CONFIRM_NEW_PASSWORD_IS_MANDATORY',
          'Confirm new password Is mandatory',
        );
      } else if (password !== confirmPassword) {
        nextErrors.confirm_password = lbl('LBL_CONFIRM_PASSWORD_MUST_MATCH', 'Confirm password must match');
      }
      if (Object.keys(nextErrors).length > 0) {
        setPasswordErrors(nextErrors);
        return;
      }
    }

    setSaving(true);
    try {
      if (active.type === 'create') {
        await adminApi.createManageAdmin({
          full_name: fullName,
          username,
          email,
          timezone,
          active: Number(activeStatus),
          password,
          confirm_password: confirmPassword,
        });
      } else if (active.type === 'edit') {
        await adminApi.updateManageAdmin(active.adminId, {
          full_name: fullName,
          timezone,
          active: Number(activeStatus),
        });
      } else {
        await adminApi.changeManageAdminPassword(active.adminId, {
          password,
          confirm_password: confirmPassword,
        });
      }
      setSuccess(lbl('LBL_UPDATED_SUCCESSFULLY', 'Updated successfully'));
      onUpdated();
      setTimeout(onClose, 600);
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Save failed';
      if (/confirm|match/i.test(message)) {
        setPasswordErrors({ confirm_password: message });
      } else if (/password/i.test(message)) {
        setPasswordErrors({ password: message });
      } else {
        setError(message);
      }
    } finally {
      setSaving(false);
    }
  };

  const title =
    active?.type === 'change-password'
      ? `${lbl('LBL_Admin_User_Change_Password', 'Admin user change password')}${
          detail?.full_name ? ` (${detail.full_name})` : ''
        }`
      : active?.type === 'edit'
        ? lbl('LBL_Admin_User_Setup', 'Admin user setup')
        : lbl('LBL_Admin_User_Setup', 'Admin user setup');

  return (
    <AdminModal open={active !== null} title={title} size="md" onClose={onClose}>
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {success ? <div className="alert alert-success m-3">{success}</div> : null}

      {active && !loading ? (
        <div className="form-edit-body">
          <form className="form form_horizontal" onSubmit={submit} noValidate>
            <div className="row">
              {active.type !== 'change-password' ? (
                <>
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">
                          {lbl('LBL_Full_Name', 'Full name')}
                          <span className="spn_must_field">*</span>
                        </label>
                      </div>
                      <div className="field-wraper">
                        <input
                          className="form-control"
                          value={fullName}
                          onChange={(e) => setFullName(e.target.value)}
                          required
                        />
                      </div>
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">
                          {lbl('LBL_Username', 'Username')}
                          {active.type === 'create' ? <span className="spn_must_field">*</span> : null}
                        </label>
                      </div>
                      <div className="field-wraper">
                        <input
                          className="form-control"
                          value={username}
                          onChange={(e) => setUsername(e.target.value)}
                          disabled={active.type === 'edit'}
                          required={active.type === 'create'}
                        />
                      </div>
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">
                          {lbl('LBL_Email', 'Email')}
                          {active.type === 'create' ? <span className="spn_must_field">*</span> : null}
                        </label>
                      </div>
                      <div className="field-wraper">
                        <input
                          type="email"
                          className="form-control"
                          value={email}
                          onChange={(e) => setEmail(e.target.value)}
                          disabled={active.type === 'edit'}
                          required={active.type === 'create'}
                        />
                      </div>
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">
                          {lbl('LBL_TIMEZONE', 'Timezone')}
                          <span className="spn_must_field">*</span>
                        </label>
                      </div>
                      <div className="field-wraper">
                        <select
                          className="form-control"
                          value={timezone}
                          onChange={(e) => setTimezone(e.target.value)}
                          required
                        >
                          <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                          {timezones.map((tz) => (
                            <option key={tz.id} value={tz.id}>
                              {tz.label}
                            </option>
                          ))}
                        </select>
                      </div>
                    </div>
                  </div>
                  {active.type === 'create' ? (
                    <>
                      <div className="col-md-12">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('LBL_Password', 'Password')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <input
                              type="password"
                              className={`form-control${passwordErrors.password ? ' error' : ''}`}
                              value={password}
                              onChange={(e) => {
                                setPassword(e.target.value);
                                setPasswordErrors((current) => ({ ...current, password: undefined }));
                              }}
                              required
                            />
                            {passwordErrors.password ? <div className="field-error">{passwordErrors.password}</div> : null}
                          </div>
                        </div>
                      </div>
                      <div className="col-md-12">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('LBL_Confirm_Password', 'Confirm password')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <input
                              type="password"
                              className={`form-control${passwordErrors.confirm_password ? ' error' : ''}`}
                              value={confirmPassword}
                              onChange={(e) => {
                                setConfirmPassword(e.target.value);
                                setPasswordErrors((current) => ({ ...current, confirm_password: undefined }));
                              }}
                              required
                            />
                            {passwordErrors.confirm_password ? (
                              <div className="field-error">{passwordErrors.confirm_password}</div>
                            ) : null}
                          </div>
                        </div>
                      </div>
                    </>
                  ) : null}
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">{lbl('LBL_Status', 'Status')}</label>
                      </div>
                      <div className="field-wraper">
                        <select
                          className="form-control"
                          value={activeStatus}
                          onChange={(e) => setActiveStatus(e.target.value)}
                        >
                          <option value="1">{lbl('LBL_Active', 'Active')}</option>
                          <option value="0">{lbl('LBL_Inactive', 'Inactive')}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </>
              ) : (
                <>
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">
                          {lbl('LBL_New_Password', 'New password')}
                          <span className="spn_must_field">*</span>
                        </label>
                      </div>
                      <div className="field-wraper">
                        <input
                          type="password"
                          className={`form-control${passwordErrors.password ? ' error' : ''}`}
                          value={password}
                          onChange={(e) => {
                            setPassword(e.target.value);
                            setPasswordErrors((current) => ({ ...current, password: undefined }));
                          }}
                          required
                        />
                        {passwordErrors.password ? <div className="field-error">{passwordErrors.password}</div> : null}
                      </div>
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">
                          {lbl('LBL_Confirm_Password', 'Confirm password')}
                          <span className="spn_must_field">*</span>
                        </label>
                      </div>
                      <div className="field-wraper">
                        <input
                          type="password"
                          className={`form-control${passwordErrors.confirm_password ? ' error' : ''}`}
                          value={confirmPassword}
                          onChange={(e) => {
                            setConfirmPassword(e.target.value);
                            setPasswordErrors((current) => ({ ...current, confirm_password: undefined }));
                          }}
                          required
                        />
                        {passwordErrors.confirm_password ? (
                          <div className="field-error">{passwordErrors.confirm_password}</div>
                        ) : null}
                      </div>
                    </div>
                  </div>
                </>
              )}
              <div className="col-md-12">
                <button type="submit" className="btn btn-brand" disabled={saving}>
                  {lbl('LBL_Save_Changes', 'Save changes')}
                </button>
              </div>
            </div>
          </form>
        </div>
      ) : null}
    </AdminModal>
  );
}
