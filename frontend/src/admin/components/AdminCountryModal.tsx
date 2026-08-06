import { type FormEvent, type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  countryId: number;
  onClose: () => void;
  onSaved: () => void;
};

type LanguageOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;

export function AdminCountryModal({ open, countryId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [countryCode, setCountryCode] = useState('');
  const [dialCode, setDialCode] = useState('');
  const [active, setActive] = useState(1);
  const [langId, setLangId] = useState(0);
  const [countryName, setCountryName] = useState('');
  const [layoutDirection, setLayoutDirection] = useState('ltr');
  const [showAutoTranslate, setShowAutoTranslate] = useState(false);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const activeLangIndex = useMemo(
    () => (langId > 0 ? siteLanguages.findIndex((lang) => lang.id === langId) : -1),
    [langId, siteLanguages],
  );
  const isLastLangTab = activeLangIndex >= 0 && activeLangIndex === siteLanguages.length - 1;

  const reset = useCallback(() => {
    setActiveTab('general');
    setRecordId(0);
    setSiteLanguages(contextLanguages);
    setIdentifier('');
    setCountryCode('');
    setDialCode('');
    setActive(1);
    setLangId(0);
    setCountryName('');
    setLayoutDirection('ltr');
    setShowAutoTranslate(false);
    setAutoTranslate(false);
    setError('');
  }, [contextLanguages]);

  const applyGeneralData = (data: Record<string, unknown>) => {
    setRecordId(Number(data.country_id ?? countryId));
    setIdentifier(String(data.country_identifier ?? ''));
    setCountryCode(String(data.country_code ?? ''));
    setDialCode(String(data.country_dial_code ?? ''));
    setActive(Number(data.country_active ?? 1));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? contextLanguages);
    setActiveTab('general');
    setLangId(0);
    setShowAutoTranslate(false);
    setAutoTranslate(false);
  };

  const applyLangData = (data: Record<string, unknown>, nextLangId: number) => {
    setRecordId(Number(data.country_id ?? countryId));
    setIdentifier(String(data.country_identifier ?? identifier));
    setActive(Number(data.country_active ?? active));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? siteLanguages);
    setLangId(Number(data.lang_id ?? nextLangId));
    setCountryName(String(data.country_name ?? ''));
    setLayoutDirection(String(data.layout_direction ?? 'ltr'));
    setShowAutoTranslate(Boolean(data.show_auto_translate));
    setAutoTranslate(false);
    setActiveTab(`lang-${nextLangId}`);
  };

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .countryShow(countryId)
      .then((res) => applyGeneralData((res.data.data ?? {}) as Record<string, unknown>))
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [contextLanguages, countryId, lbl, open, reset]);

  const loadLang = useCallback(
    async (nextCountryId: number, nextLangId: number) => {
      if (nextCountryId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.countryLangForm(nextCountryId, nextLangId);
        applyLangData((res.data.data ?? {}) as Record<string, unknown>, nextLangId);
      } catch (err: unknown) {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      } finally {
        setLoading(false);
      }
    },
    [active, countryId, identifier, lbl, siteLanguages],
  );

  const submitGeneral = async (event: FormEvent) => {
    event.preventDefault();
    if (recordId < 1) return;
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.countryUpdate(recordId, {
        country_identifier: identifier.trim(),
        country_active: active,
      });
      onSaved();
      const nextLangId = Number(res.data.data?.next_lang_id ?? siteLanguages[0]?.id ?? 0);
      if (nextLangId > 0) {
        await loadLang(recordId, nextLangId);
      } else {
        onClose();
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  const submitLang = async (event: FormEvent) => {
    event.preventDefault();
    if (recordId < 1 || langId < 1) return;
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.countryLangUpdate(recordId, langId, {
        country_name: countryName.trim(),
        ...(showAutoTranslate && autoTranslate ? { update_langs_data: 1 } : {}),
      });
      onSaved();
      const nextLang = siteLanguages[activeLangIndex + 1];
      const missingLangId = Number(res.data.data?.next_lang_id ?? 0);
      if (nextLang) {
        await loadLang(recordId, nextLang.id);
      } else if (missingLangId > 0) {
        await loadLang(recordId, missingLangId);
      } else {
        onClose();
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal open={open} title={lbl('LBL_COUNTRY_SETUP', 'Country Setup')} size="lg" onClose={onClose}>
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul className="tabs-nav">
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={(event) => {
                  event.preventDefault();
                  applyGeneralData({
                    country_id: recordId,
                    country_identifier: identifier,
                    country_code: countryCode,
                    country_dial_code: dialCode,
                    country_active: active,
                    site_languages: siteLanguages,
                  });
                }}
              >
                {lbl('LBL_GENERAL', 'General')}
              </a>
            </li>
            {siteLanguages.map((lang) => (
              <li key={lang.id}>
                <a
                  href="javascript:void(0)"
                  className={activeTab === `lang-${lang.id}` ? 'active' : ''}
                  onClick={(event) => {
                    event.preventDefault();
                    void loadLang(recordId, lang.id);
                  }}
                >
                  {lang.name}
                </a>
              </li>
            ))}
          </ul>
        </nav>
      </div>
      <div className="form-edit-body">
        {error ? <div className="alert alert-danger m-3">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={submitGeneral}>
            <div className="row">
              <LegacyField label={lbl('LBL_IDENTIFIER', 'Identifier')} required>
                <input className="form-control" value={identifier} onChange={(event) => setIdentifier(event.target.value)} required />
              </LegacyField>
              <LegacyField label={lbl('LBL_COUNTRY_CODE', 'Country Code')}>
                <input className="form-control" value={countryCode} disabled readOnly />
              </LegacyField>
              <LegacyField label={lbl('LBL_DIAL_CODE', 'Dial Code')}>
                <input className="form-control" value={dialCode} disabled readOnly />
              </LegacyField>
              <LegacyField label={lbl('LBL_STATUS', 'Status')}>
                <select className="form-control" value={active} onChange={(event) => setActive(Number(event.target.value))}>
                  <option value={1}>{lbl('LBL_ACTIVE', 'Active')}</option>
                  <option value={0}>{lbl('LBL_INACTIVE', 'Inactive')}</option>
                </select>
              </LegacyField>
              <FormButton saving={saving} label={lbl('LBL_SAVE_CHANGES', 'Save changes')} />
            </div>
          </form>
        ) : (
          <form className={`form form_horizontal layout--${layoutDirection}`} dir={layoutDirection} onSubmit={submitLang}>
            <div className="row">
              <LegacyField label={lbl('LBL_COUNTRY_NAME', 'Country Name')} required>
                <input className="form-control" value={countryName} onChange={(event) => setCountryName(event.target.value)} required />
              </LegacyField>
              {showAutoTranslate ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="field-wraper">
                      <div className="field_cover">
                        <label className="checkbox d-flex">
                          <input
                            type="checkbox"
                            name="update_langs_data"
                            value="1"
                            checked={autoTranslate}
                            onChange={(event) => setAutoTranslate(event.target.checked)}
                          />
                          <span>{lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
              <FormButton saving={saving} label={isLastLangTab ? lbl('LBL_SAVE_CHANGES', 'Save changes') : lbl('LBL_SAVE_&_NEXT', 'Save & next')} />
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}

function LegacyField({ label, required = false, children }: { label: string; required?: boolean; children: ReactNode }) {
  return (
    <div className="col-md-12">
      <div className="field-set">
        <div className="caption-wraper">
          <label className="field_label">
            {label}
            {required ? <span className="spn_must_field">*</span> : null}
          </label>
        </div>
        <div className="field-wraper">
          <div className="field_cover">{children}</div>
        </div>
      </div>
    </div>
  );
}

function FormButton({ saving, label }: { saving: boolean; label: string }) {
  return (
    <div className="col-md-12">
      <div className="field-set">
        <div className="field-wraper">
          <div className="field_cover">
            <button className="btn btn-primary" type="submit" disabled={saving}>
              {saving ? 'Processing please wait' : label}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
