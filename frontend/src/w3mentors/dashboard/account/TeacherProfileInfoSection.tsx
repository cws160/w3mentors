import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useAuth } from '../../../context/AuthContext';
import { useSite } from '../../context/SiteContext';
import { TeacherProfileLangSection } from './TeacherProfileLangSection';
import { TeacherProfilePhotosSection } from './TeacherProfilePhotosSection';
import type { ProfileGeneralForm, ProfileGeneralResponse } from './teacherProfileTypes';

type InnerTab = 'general' | 'photos' | `lang-${number}`;

function FieldLabel({
  children,
  required,
}: {
  children: React.ReactNode;
  required?: boolean;
}) {
  return (
    <label className="field_label">
      {children}
      {required && <span className="spn_must_field">*</span>}
    </label>
  );
}

function HorizontalRow({
  label,
  required,
  children,
  after,
  fieldCoverClass,
}: {
  label: React.ReactNode;
  required?: boolean;
  children: React.ReactNode;
  after?: React.ReactNode;
  fieldCoverClass?: string;
}) {
  return (
    <div className="row">
      <div className="col-md-12">
        <div className="field-set">
          <div className="caption-wraper">
            <FieldLabel required={required}>{label}</FieldLabel>
          </div>
          <div className="field-wraper">
            <div className={`field_cover${fieldCoverClass ? ` ${fieldCoverClass}` : ''}`}>
              {children}
            </div>
            {after}
          </div>
        </div>
      </div>
    </div>
  );
}

export function TeacherProfileInfoSection({ onNextTab }: { onNextTab?: () => void }) {
  const { lbl, langId } = useSite();
  const { reloadProfile } = useAuth();
  const [innerTab, setInnerTab] = useState<InnerTab>('general');
  const [form, setForm] = useState<ProfileGeneralForm | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get<ProfileGeneralResponse>('/users/me/profile/general', {
        params: { lang_id: langId },
      });
      setForm(res.data.data);
      setError('');
    } catch (err: unknown) {
      setForm(null);
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')
      );
    } finally {
      setLoading(false);
    }
  }, [langId, lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const setValue = <K extends keyof ProfileGeneralForm['values']>(
    key: K,
    value: ProfileGeneralForm['values'][K]
  ) => {
    setForm((prev) =>
      prev ? { ...prev, values: { ...prev.values, [key]: value } } : prev
    );
  };

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form) return;
    setSaving(true);
    setMessage('');
    setError('');
    try {
      const res = await api.put<{ message: string; data: ProfileGeneralForm }>(
        '/users/me/profile/general',
        {
          username: form.values.username,
          first_name: form.values.first_name,
          last_name: form.values.last_name,
          gender: form.values.gender,
          country_id: form.values.country_id,
          phone_code: form.values.phone_code,
          phone_number: form.values.phone_number,
          timezone: form.values.timezone,
          lang_id: form.values.lang_id,
          book_before: form.values.book_before,
          offline_sessions: form.values.offline_sessions,
          trial_enabled: form.values.trial_enabled,
        },
        { params: { lang_id: langId } }
      );
      setForm(res.data.data);
      setMessage(res.data.message);
      await reloadProfile();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const meta = form?.meta;
  const v = form?.values;
  const opts = form?.options;

  const profileLangTabs = meta?.profile_languages ?? [];
  const activeLangId =
    innerTab.startsWith('lang-') ? Number(innerTab.replace('lang-', '')) : null;
  const activeLangIndex =
    activeLangId != null
      ? profileLangTabs.findIndex((lang) => lang.id === activeLangId)
      : -1;

  const goToNextLangTab = () => {
    if (activeLangIndex < 0 || activeLangIndex >= profileLangTabs.length - 1) {
      return;
    }
    setInnerTab(`lang-${profileLangTabs[activeLangIndex + 1].id}`);
  };

  return (
    <>
      <div className="content-panel__head">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_MANAGE_PROFILE', 'Manage profile')}</h5>
          </div>
        </div>
      </div>
      <div className="content-panel__body">
        <div className="form" id="langForm">
          <div className="form__body p-0">
            <nav className="tabs tabs--line ps-4 pe-4">
              <ul className="tab-ul-js">
                <li className={innerTab === 'general' ? 'is-active' : ''}>
                  <a
                    href="javascript:void(0);"
                    onClick={(e) => {
                      e.preventDefault();
                      setInnerTab('general');
                    }}
                  >
                    {lbl('LBL_GENERAL', 'General')}
                  </a>
                </li>
                <li className={innerTab === 'photos' ? 'is-active' : ''}>
                  <a
                    href="javascript:void(0);"
                    className="profile-imag-li"
                    onClick={(e) => {
                      e.preventDefault();
                      setInnerTab('photos');
                    }}
                  >
                    {lbl('LBL_PHOTOS_&_VIDEOS', 'Photos & videos')}
                  </a>
                </li>
                {profileLangTabs.map((lang) => (
                  <li
                    key={lang.id}
                    className={`profile-lang-tab${innerTab === `lang-${lang.id}` ? ' is-active' : ''}`}
                  >
                    <a
                      href="javascript:void(0);"
                      className="profile-lang-li"
                      onClick={(e) => {
                        e.preventDefault();
                        setInnerTab(`lang-${lang.id}`);
                      }}
                    >
                      {lang.name}
                    </a>
                  </li>
                ))}
              </ul>
            </nav>
            <div className="tabs-data">
              <div id="profileInfoFrmBlock">
                {innerTab === 'general' && (
                  <>
                    {meta?.google_calendar_configured && (
                      <div
                        className={`action-bar border-top-0${
                          !meta.google_calendar_auth_ready ? ' selection-disabled' : ''
                        }`}
                      >
                        <div className="row justify-content-between align-items-center">
                          <div className="col-sm-6">
                            <div className="d-flex align-items-center">
                              <div className="action-bar__media me-4">
                                <div className="g-circle">
                                  <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="31"
                                    height="31"
                                    viewBox="0 0 31 31"
                                    aria-hidden
                                  >
                                    <path
                                      fill="#fbbb00"
                                      d="M6.87,148.63l-1.079,4.028-3.944.083a15.527,15.527,0,0,1-.114-14.474h0l3.511.644,1.538,3.49a9.25,9.25,0,0,0,.087,6.228Z"
                                      transform="translate(0 -129.896)"
                                    />
                                    <path
                                      fill="#518ef8"
                                      d="M276.516,208.176a15.494,15.494,0,0,1-5.525,14.983h0l-4.423-.226-.626-3.907a9.238,9.238,0,0,0,3.975-4.717h-8.288v-6.132h14.888Z"
                                      transform="translate(-245.787 -195.572)"
                                    />
                                    <path
                                      fill="#28b446"
                                      d="M53.865,318.262h0a15.5,15.5,0,0,1-23.356-4.742l5.023-4.112a9.219,9.219,0,0,0,13.284,4.72Z"
                                      transform="translate(-28.662 -290.675)"
                                    />
                                    <path
                                      fill="#f14336"
                                      d="M52.285,3.568,47.263,7.679a9.217,9.217,0,0,0-13.589,4.826L28.625,8.372h0a15.5,15.5,0,0,1,23.661-4.8Z"
                                      transform="translate(-26.891)"
                                    />
                                  </svg>
                                </div>
                              </div>
                              <div className="action-bar__content">
                                <p className="mb-0">
                                  {lbl('LBL_TO_SYNC_WITH_GOOGLE_CALENDAR', 'To sync with google calendar')}
                                </p>
                                <p className="mb-0 color-secondary">
                                  {meta.google_calendar_synced
                                    ? lbl(
                                        'LBL_YOUR_GOOGLE_CALENDAR_ALREADY_SYNCED',
                                        'Your google calendar is already synced'
                                      )
                                    : lbl(
                                        'LBL_GOOGLE_CALENDAR_NOT_ACTIVE_YET',
                                        'Your google calendar is not synced yet'
                                      )}
                                </p>
                              </div>
                            </div>
                          </div>
                          <div className="col-sm-auto">
                            <a
                              href="javascript:void(0);"
                              className="social-button social-button--google"
                              onClick={(e) => {
                                e.preventDefault();
                                if (meta.google_calendar_authorize_url) {
                                  window.location.href = meta.google_calendar_authorize_url;
                                }
                              }}
                            >
                              <span className="social-button__media">
                                <svg
                                  xmlns="http://www.w3.org/2000/svg"
                                  width="48"
                                  height="48"
                                  viewBox="0 0 48 48"
                                  aria-hidden
                                >
                                  <rect width="22" height="22" x="13" y="13" fill="#fff" />
                                  <polygon
                                    fill="#1e88e5"
                                    points="25.68,20.92 26.688,22.36 28.272,21.208 28.272,29.56 30,29.56 30,18.616 28.56,18.616"
                                  />
                                  <path
                                    fill="#1e88e5"
                                    d="M22.943,23.745c0.625-0.574,1.013-1.37,1.013-2.249c0-1.747-1.533-3.168-3.417-3.168 c-1.602,0-2.972,1.009-3.33,2.453l1.657,0.421c0.165-0.664,0.868-1.146,1.673-1.146c0.942,0,1.709,0.646,1.709,1.44 c0,0.794-0.767,1.44-1.709,1.44h-0.997v1.728h0.997c1.081,0,1.993,0.751,1.993,1.64c0,0.904-0.866,1.64-1.931,1.64 c-0.962,0-1.784-0.61-1.914-1.418L17,26.802c0.262,1.636,1.81,2.87,3.6,2.87c2.007,0,3.64-1.511,3.64-3.368 C24.24,25.281,23.736,24.363,22.943,23.745z"
                                  />
                                  <polygon
                                    fill="#fbc02d"
                                    points="34,42 14,42 13,38 14,34 34,34 35,38"
                                  />
                                  <polygon
                                    fill="#4caf50"
                                    points="38,35 42,34 42,14 38,13 34,14 34,34"
                                  />
                                  <path
                                    fill="#1e88e5"
                                    d="M34,14l1-4l-1-4H9C7.343,6,6,7.343,6,9v25l4,1l4-1V14H34z"
                                  />
                                  <polygon fill="#e53935" points="34,34 34,42 42,34" />
                                  <path
                                    fill="#1565c0"
                                    d="M39,6h-5v8h8V9C42,7.343,40.657,6,39,6z"
                                  />
                                  <path
                                    fill="#1565c0"
                                    d="M9,42h5v-8H6v5C6,40.657,7.343,42,9,42z"
                                  />
                                </svg>
                              </span>
                              <span className="social-button__label">
                                {lbl('LBL_CONNECT_GOOGLE_CALENDAR', 'Sync Google Calendar')}
                              </span>
                            </a>
                          </div>
                        </div>
                      </div>
                    )}
                    <div className="padding-6">
                      <div className="max-width-80">
                        {loading ? (
                          <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
                        ) : !form || !v || !opts ? (
                          <p className="color-danger">
                            {error || lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')}
                          </p>
                        ) : (
                          <form
                            id="profileInfoFrm"
                            className="form form--horizontal"
                            onSubmit={onSubmit}
                            autoComplete="off"
                          >
                            {meta?.is_teacher && (
                              <HorizontalRow label={lbl('LBL_USERNAME', 'Username')} required>
                                <>
                                  <input
                                    type="text"
                                    className="form-control"
                                    value={v.username}
                                    onChange={(e) =>
                                      setValue('username', e.target.value.replace(/\s/g, ''))
                                    }
                                    required
                                  />
                                  {meta.teacher_profile_url && (
                                    <small className="user_url_string mb-0">
                                      <a href={meta.teacher_profile_url} target="_blank" rel="noreferrer">
                                        {meta.teacher_profile_url}
                                      </a>
                                    </small>
                                  )}
                                </>
                              </HorizontalRow>
                            )}
                            <HorizontalRow label={lbl('LBL_Name', 'Name')} required>
                              <div className="custom-cols custom-cols--onehal">
                                <ul>
                                  <li>
                                    <input
                                      type="text"
                                      className="form-control"
                                      placeholder={lbl('LBL_First_Name', 'First name')}
                                      value={v.first_name}
                                      onChange={(e) => setValue('first_name', e.target.value)}
                                      required
                                    />
                                  </li>
                                  <li>
                                    <input
                                      type="text"
                                      className="form-control"
                                      placeholder={lbl('LBL_Last_Name', 'Last name')}
                                      value={v.last_name}
                                      onChange={(e) => setValue('last_name', e.target.value)}
                                    />
                                  </li>
                                </ul>
                              </div>
                            </HorizontalRow>
                            <HorizontalRow label={lbl('LBL_GENDER', 'Gender')} required>
                              <div className="custom-cols custom-cols--onehal">
                                <select
                                  className="form-control"
                                  value={v.gender || ''}
                                  onChange={(e) => setValue('gender', Number(e.target.value))}
                                  required
                                >
                                  <option value="">{lbl('LBL_SELECT_GENDER', 'Select gender')}</option>
                                  {opts.genders.map((g) => (
                                    <option key={g.id} value={g.id}>
                                      {lbl(
                                        g.label_key,
                                        g.id === 1
                                          ? 'Male'
                                          : g.id === 2
                                            ? 'Female'
                                            : g.id === 3
                                              ? 'Non-binary'
                                              : 'Prefer not to say'
                                      )}
                                    </option>
                                  ))}
                                </select>
                              </div>
                            </HorizontalRow>
                            <HorizontalRow
                              label={lbl('LBL_COUNTRY', 'Country')}
                              required
                              fieldCoverClass="custom-select-search"
                            >
                              <select
                                className="form-control"
                                value={v.country_id || ''}
                                onChange={(e) => {
                                  const id = Number(e.target.value);
                                  setValue('country_id', id);
                                  if (!v.phone_code) {
                                    setValue('phone_code', id);
                                  }
                                }}
                                required
                              >
                                <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                                {opts.countries.map((c) => (
                                  <option key={c.id} value={c.id}>
                                    {c.name}
                                  </option>
                                ))}
                              </select>
                            </HorizontalRow>
                            <HorizontalRow label={lbl('LBL_PHONE', 'Phone')} required>
                              <div className="custom-cols custom-cols--onehal">
                                <ul>
                                  <li className="custom-select-search">
                                    <select
                                      className="form-control"
                                      value={v.phone_code || ''}
                                      onChange={(e) => setValue('phone_code', Number(e.target.value))}
                                      required
                                    >
                                      <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                                      {opts.countries.map((c) => (
                                        <option key={c.id} value={c.id}>
                                          {c.phone_label ?? c.name}
                                        </option>
                                      ))}
                                    </select>
                                  </li>
                                  <li>
                                    <input
                                      type="text"
                                      className="form-control"
                                      value={v.phone_number}
                                      onChange={(e) => setValue('phone_number', e.target.value)}
                                      required
                                    />
                                  </li>
                                </ul>
                              </div>
                            </HorizontalRow>
                            <HorizontalRow
                              label={lbl('LBL_TIMEZONE', 'Timezone')}
                              required
                              after={
                                meta?.is_teacher ? (
                                  <>
                                    <br />
                                    <small className="color-secondary">
                                      {lbl(
                                        'htmlAfterField_TIMEZONE_TEXT',
                                        'If you change your timezone, your availability will be reset.'
                                      )}
                                      .
                                    </small>
                                  </>
                                ) : undefined
                              }
                            >
                              <select
                                className="form-control"
                                value={v.timezone}
                                onChange={(e) => setValue('timezone', e.target.value)}
                                required
                              >
                                <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                                {opts.timezones.map((tz) => (
                                  <option key={tz.id} value={tz.id}>
                                    {tz.label}
                                  </option>
                                ))}
                              </select>
                            </HorizontalRow>
                            {meta?.offline_sessions_enabled && (
                              <HorizontalRow label={lbl('LBL_OFFLINE_SESSIONS', 'Offline sessions')}>
                                <label className="switch-group d-flex align-items-center justify-content-between">
                                  <span className="switch-group__label offline-status-js">
                                    {v.offline_sessions
                                      ? lbl('LBL_Active', 'Active')
                                      : lbl('LBL_In-active', 'In-active')}
                                  </span>
                                  <span className="switch switch--small">
                                    <input
                                      className="switch__label"
                                      type="checkbox"
                                      checked={v.offline_sessions}
                                      onChange={(e) => setValue('offline_sessions', e.target.checked)}
                                    />
                                    <i className="switch__handle bg-green" />
                                  </span>
                                </label>
                              </HorizontalRow>
                            )}
                            {meta?.is_teacher && (
                              <HorizontalRow
                                label={lbl('LBL_BOOKING_BEFORE', 'Lesson booking buffer time')}
                                required
                                after={
                                  <>
                                    <br />
                                    <small>
                                      {lbl(
                                        'htmlAfterField_booking_before_text',
                                        'Students can book lessons based on this buffer.'
                                      )}
                                      .
                                    </small>
                                  </>
                                }
                              >
                                <select
                                  className="form-control"
                                  value={v.book_before}
                                  onChange={(e) => setValue('book_before', Number(e.target.value))}
                                  required
                                >
                                  {opts.book_before.map((opt) => (
                                    <option key={opt.id} value={opt.id}>
                                      {lbl(
                                        opt.label_key,
                                        opt.id === 0
                                          ? 'Immediate'
                                          : opt.id === 12
                                            ? '12 hours'
                                            : '24 hours'
                                      )}
                                    </option>
                                  ))}
                                </select>
                              </HorizontalRow>
                            )}
                            <HorizontalRow
                              label={lbl('LBL_NOTIFICATION_LANGUAGE', 'Notification language')}
                              required
                            >
                              <select
                                className="form-control"
                                value={v.lang_id || ''}
                                onChange={(e) => setValue('lang_id', Number(e.target.value))}
                                required
                              >
                                <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                                {opts.notification_languages.map((lang) => (
                                  <option key={lang.id} value={lang.id}>
                                    {lang.name}
                                  </option>
                                ))}
                              </select>
                            </HorizontalRow>
                            {meta?.free_trial_enabled && meta.is_teacher && (
                              <HorizontalRow label={lbl('LBL_ENABLE_TRIAL_LESSON', 'Free trial lesson')}>
                                <label className="switch-group d-flex align-items-center justify-content-between">
                                  <span className="switch-group__label free-trial-status-js">
                                    {v.trial_enabled
                                      ? lbl('LBL_Active', 'Active')
                                      : lbl('LBL_In-active', 'In-active')}
                                  </span>
                                  <span className="switch switch--small">
                                    <input
                                      className="switch__label"
                                      type="checkbox"
                                      checked={v.trial_enabled}
                                      onChange={(e) => setValue('trial_enabled', e.target.checked)}
                                    />
                                    <i className="switch__handle bg-green" />
                                  </span>
                                </label>
                              </HorizontalRow>
                            )}
                            {error ? <p className="color-danger mb-3">{error}</p> : null}
                            {message ? <p className="color-primary mb-3">{message}</p> : null}
                            <div className="row submit-row">
                              <div className="col-sm-12">
                                <div className="field-set">
                                  <div className="field-wraper">
                                    <div className="field_cover">
                                      <input
                                        type="submit"
                                        className="btn btn--primary"
                                        value={
                                          saving
                                            ? lbl('LBL_SAVING', 'Saving...')
                                            : lbl('LBL_SAVE', 'Save')
                                        }
                                        disabled={saving}
                                      />
                                      {meta?.is_teacher && onNextTab && (
                                        <input
                                          type="button"
                                          className="btn btn--secondary"
                                          value={lbl('LBL_Next', 'Next')}
                                          disabled={saving}
                                          onClick={onNextTab}
                                        />
                                      )}
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </form>
                        )}
                      </div>
                    </div>
                  </>
                )}
                {innerTab === 'photos' && (
                  <TeacherProfilePhotosSection onNextTab={onNextTab} />
                )}
                {activeLangId != null && activeLangId > 0 && (
                  <TeacherProfileLangSection
                    langId={activeLangId}
                    onNextLang={goToNextLangTab}
                    onGoToTeachLang={onNextTab}
                  />
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
