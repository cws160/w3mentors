import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

type ConsentSettings = {
  necessary: number;
  preferences: number;
  statistics: number;
};

type ConsentPayload = {
  enabled: boolean;
  settings: ConsentSettings;
};

function syncConsentCookie(settings: ConsentSettings) {
  try {
    const value = encodeURIComponent(JSON.stringify(settings));
    document.cookie = `CONF_SITE_CONSENTS=${value}; path=/; max-age=604800; SameSite=Lax`;
  } catch {
    /* ignore */
  }
}

/** Legacy checkbox markup (application/views/cookie-consent/form.php). */
function LegacyConsentCheckbox({
  name,
  checked,
  disabled,
  onChange,
}: {
  name: string;
  checked: boolean;
  disabled?: boolean;
  onChange?: (checked: boolean) => void;
}) {
  return (
    <label className="field_label">
      <span className={`checkbox${disabled ? ' disabled' : ''}`}>
        <input
          type="checkbox"
          name={name}
          value="1"
          checked={checked}
          disabled={disabled}
          onChange={(e) => onChange?.(e.target.checked)}
        />
        <i className="input-helper" />
      </span>
    </label>
  );
}

export function AccountCookieConsentSection() {
  const { lbl } = useSite();
  const [activeTabId, setActiveTabId] = useState('tab_necessary');
  const [enabled, setEnabled] = useState(true);
  const [settings, setSettings] = useState<ConsentSettings>({
    necessary: 1,
    preferences: 0,
    statistics: 0,
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    api
      .get<{ data: ConsentPayload }>('/users/me/cookie-consent')
      .then((res) => {
        setEnabled(res.data.data.enabled);
        setSettings(res.data.data.settings);
      })
      .catch(() => {
        setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
      })
      .finally(() => setLoading(false));
  }, [lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const save = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    setMessage('');
    try {
      const res = await api.put<{ data: ConsentPayload; message?: string }>(
        '/users/me/cookie-consent',
        {
          preferences: settings.preferences === 1,
          statistics: settings.statistics === 1,
        }
      );
      setSettings(res.data.data.settings);
      syncConsentCookie(res.data.data.settings);
      setMessage(
        res.data.message ??
          lbl('LBL_COOKIE_SETTINGS_UPDATE_SUCCESSFULLY', 'Cookie settings updated successfully')
      );
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const acceptAll = async () => {
    setSaving(true);
    setError('');
    setMessage('');
    try {
      const res = await api.post<{ data: ConsentPayload; message?: string }>(
        '/users/me/cookie-consent/accept-all'
      );
      setSettings(res.data.data.settings);
      syncConsentCookie(res.data.data.settings);
      setMessage(
        res.data.message ??
          lbl('LBL_COOKIE_SETTINGS_UPDATE_SUCCESSFULLY', 'Cookie settings updated successfully')
      );
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const switchTab = (tabId: string) => {
    setActiveTabId(tabId);
  };

  if (loading) {
    return <p className="padding-6 color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  if (!enabled) {
    return (
      <>
        <div className="content-panel__head">
          <h5>{lbl('LBL_COOKIE_CONSENT', 'Cookie consent')}</h5>
        </div>
        <div className="content-panel__body">
          <p className="padding-6 color-secondary">
            {lbl('MSG_MODULE_NOT_ENABLED', 'Cookie consent is not enabled on this site.')}
          </p>
        </div>
      </>
    );
  }

  return (
    <>
      <div className="content-panel__head">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_COOKIE_CONSENT_HEADING', 'Manage cookie consent settings')}</h5>
          </div>
          <div>
            <a
              href="javascript:void(0);"
              className="btn btn--small btn--bordered color-secondary"
              onClick={(e) => {
                e.preventDefault();
                acceptAll();
              }}
            >
              {lbl('LBL_ACCEPT_COOKIES', 'Accept cookies')}
            </a>
          </div>
        </div>
      </div>
      <div className="modal-body p-0">
        <div className="form-edit-head">
          <nav className="tabs tabs--line border-bottom-0">
            <ul>
              <li className={activeTabId === 'tab_necessary' ? 'is-active' : ''}>
                <a
                  href="javascript:void(0);"
                  className="tab-a"
                  data-id="tab_necessary"
                  onClick={(e) => {
                    e.preventDefault();
                    switchTab('tab_necessary');
                  }}
                >
                  {lbl('LBL_NECESSARY', 'Necessary')}
                </a>
              </li>
              <li className={activeTabId === 'tab_preferences' ? 'is-active' : ''}>
                <a
                  href="javascript:void(0);"
                  className="tab-a"
                  data-id="tab_preferences"
                  onClick={(e) => {
                    e.preventDefault();
                    switchTab('tab_preferences');
                  }}
                >
                  {lbl('LBL_PREFERENCES', 'Preferences')}
                </a>
              </li>
              <li className={activeTabId === 'tab_statistics' ? 'is-active' : ''}>
                <a
                  href="javascript:void(0);"
                  className="tab-a"
                  data-id="tab_statistics"
                  onClick={(e) => {
                    e.preventDefault();
                    switchTab('tab_statistics');
                  }}
                >
                  {lbl('LBL_STATISTICS', 'Statistics')}
                </a>
              </li>
            </ul>
          </nav>
        </div>
        <div className="form-edit-body">
          <form
            id="cookieForm"
            className="form"
            autoComplete="off"
            onSubmit={save}
          >
            <div className="tabs-data">
              <div
                className={`tab-div${activeTabId === 'tab_necessary' ? '' : ' d-none'}`}
                data-id="tab_necessary"
              >
                <div className="tabs-data__box">
                  <div className="tab-heading d-flex align-items-center justify-content-between mb-3">
                    <h6>{lbl('LBL_NECESSARY', 'Necessary')}</h6>
                    <div className="field_cover">
                      <LegacyConsentCheckbox name="necessary" checked disabled />
                    </div>
                  </div>
                  <p>{lbl('LBL_NECESSARY_COOKIE_DESCRIPTION_TEXT', '')}</p>
                </div>
              </div>
              <div
                className={`tab-div${activeTabId === 'tab_preferences' ? '' : ' d-none'}`}
                data-id="tab_preferences"
              >
                <div className="tabs-data__box">
                  <div className="tab-heading d-flex align-items-center justify-content-between mb-3">
                    <h6>{lbl('LBL_PREFERENCES', 'Preferences')}</h6>
                    <div className="field_cover">
                      <LegacyConsentCheckbox
                        name="preferences"
                        checked={settings.preferences === 1}
                        onChange={(checked) =>
                          setSettings((s) => ({ ...s, preferences: checked ? 1 : 0 }))
                        }
                      />
                    </div>
                  </div>
                  <p>{lbl('LBL_PREFERENCES_COOKIE_DESCRIPTION_TEXT', '')}</p>
                </div>
              </div>
              <div
                className={`tab-div${activeTabId === 'tab_statistics' ? '' : ' d-none'}`}
                data-id="tab_statistics"
              >
                <div className="tabs-data__box">
                  <div className="tab-heading d-flex align-items-center justify-content-between mb-3">
                    <h6>{lbl('LBL_STATISTICS', 'Statistics')}</h6>
                    <div className="field_cover">
                      <LegacyConsentCheckbox
                        name="statistics"
                        checked={settings.statistics === 1}
                        onChange={(checked) =>
                          setSettings((s) => ({ ...s, statistics: checked ? 1 : 0 }))
                        }
                      />
                    </div>
                  </div>
                  <p>{lbl('LBL_STATISTICS_COOKIE_DESCRIPTION_TEXT', '')}</p>
                </div>
              </div>
            </div>
            {error ? <p className="color-primary padding-6 pt-0 mb-0">{error}</p> : null}
            {message ? <p className="color-secondary padding-6 pt-0 mb-0">{message}</p> : null}
            <div className="row">
              <div className="col-sm-12">
                <div className="field-set mb-0">
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="submit"
                        name="btn_submit"
                        className="btn btn--primary"
                        value={saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
                        disabled={saving}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
