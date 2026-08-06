import { type FormEvent, type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  navigationId: number;
  linkId: number;
  onClose: () => void;
  onSaved: () => void;
};

type Option = { id: number | string; name: string };
type LanguageOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;

export function AdminNavigationLinkModal({ open, navigationId, linkId, onClose, onSaved }: Props) {
  const { lbl, langId: siteLangId, languages: contextLanguages } = useSite();
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [contentPages, setContentPages] = useState<Option[]>([]);
  const [typeOptions, setTypeOptions] = useState<Option[]>([]);
  const [targetOptions, setTargetOptions] = useState<Option[]>([]);
  const [loginOptions, setLoginOptions] = useState<Option[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [type, setType] = useState(0);
  const [target, setTarget] = useState('_self');
  const [loginProtected, setLoginProtected] = useState(0);
  const [cmsPageId, setCmsPageId] = useState(0);
  const [url, setUrl] = useState('');
  const [active, setActive] = useState(1);
  const [hasActiveColumn, setHasActiveColumn] = useState(false);
  const [langId, setLangId] = useState(0);
  const [caption, setCaption] = useState('');
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
    setContentPages([]);
    setTypeOptions([]);
    setTargetOptions([]);
    setLoginOptions([]);
    setIdentifier('');
    setType(0);
    setTarget('_self');
    setLoginProtected(0);
    setCmsPageId(0);
    setUrl('');
    setActive(1);
    setHasActiveColumn(false);
    setLangId(0);
    setCaption('');
    setShowAutoTranslate(false);
    setAutoTranslate(false);
    setError('');
  }, [contextLanguages]);

  const applyGeneralData = (data: Record<string, unknown>) => {
    setRecordId(Number(data.nlink_id ?? 0));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? contextLanguages);
    setContentPages((data.content_pages as Option[] | undefined) ?? []);
    setTypeOptions((data.type_options as Option[] | undefined) ?? []);
    setTargetOptions((data.target_options as Option[] | undefined) ?? []);
    setLoginOptions((data.login_options as Option[] | undefined) ?? []);
    setIdentifier(String(data.nlink_identifier ?? ''));
    setType(Number(data.nlink_type ?? 0));
    setTarget(String(data.nlink_target ?? '_self'));
    setLoginProtected(Number(data.nlink_login_protected ?? 0));
    setCmsPageId(Number(data.nlink_cpage_id ?? 0));
    setUrl(String(data.nlink_url ?? ''));
    setActive(Number(data.nlink_active ?? 1));
    setHasActiveColumn(Boolean(data.has_active_column));
    setActiveTab('general');
    setLangId(0);
    setShowAutoTranslate(false);
    setAutoTranslate(false);
  };

  const applyLangData = (data: Record<string, unknown>, nextLangId: number) => {
    setRecordId(Number(data.nlink_id ?? recordId));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? siteLanguages);
    setLangId(Number(data.lang_id ?? nextLangId));
    setCaption(String(data.nlink_caption ?? ''));
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
      .navigationLinkForm(navigationId, linkId, siteLangId || 1)
      .then((res) => applyGeneralData((res.data.data ?? {}) as Record<string, unknown>))
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [contextLanguages, lbl, linkId, navigationId, open, reset, siteLangId]);

  const loadLang = useCallback(
    async (nextLinkId: number, nextLangId: number) => {
      if (nextLinkId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.navigationLinkLangForm(navigationId, nextLinkId, nextLangId);
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
    [lbl, navigationId, recordId, siteLanguages],
  );

  const submitGeneral = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.navigationLinkSave(navigationId, recordId, {
        nlink_identifier: identifier.trim(),
        nlink_type: type,
        nlink_target: target,
        nlink_login_protected: loginProtected,
        nlink_cpage_id: cmsPageId,
        nlink_url: url.trim(),
        nlink_active: active,
      });
      const savedId = Number(res.data.data?.nlink_id ?? res.data.data?.id ?? recordId);
      setRecordId(savedId);
      onSaved();
      const nextLangId = Number(res.data.data?.next_lang_id ?? siteLanguages[0]?.id ?? 0);
      if (nextLangId > 0) {
        await loadLang(savedId, nextLangId);
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
      const res = await adminApi.navigationLinkLangSave(navigationId, recordId, langId, {
        nlink_caption: caption.trim(),
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
    <AdminModal
      open={open}
      title={lbl('LBL_NAVIGATION_LINK_SETUP', 'Navigation Link Setup')}
      size="lg"
      onClose={onClose}
    >
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul className="tabs-nav">
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={(event) => {
                  event.preventDefault();
                  void adminApi.navigationLinkForm(navigationId, recordId, siteLangId || 1).then((res) => {
                    applyGeneralData((res.data.data ?? {}) as Record<string, unknown>);
                  });
                }}
              >
                {lbl('LBL_GENERAL', 'General')}
              </a>
            </li>
            {siteLanguages.map((lang) => (
              <li key={lang.id} className={recordId < 1 ? 'is-inactive' : ''}>
                <a
                  href="javascript:void(0)"
                  data-id={lang.id}
                  className={activeTab === `lang-${lang.id}` ? 'active' : ''}
                  onClick={(event) => {
                    event.preventDefault();
                    if (recordId > 0) {
                      void loadLang(recordId, lang.id);
                    }
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
              <LegacyField label={lbl('LBL_CAPTION_IDENTIFIER', 'Caption Identifier')} required>
                <input className="form-control" value={identifier} onChange={(event) => setIdentifier(event.target.value)} required />
              </LegacyField>
              <LegacyField label={lbl('LBL_TYPE', 'Type')} required>
                <select className="form-control" value={type} onChange={(event) => setType(Number(event.target.value))}>
                  {typeOptions.map((option) => (
                    <option key={String(option.id)} value={Number(option.id)}>
                      {option.name}
                    </option>
                  ))}
                </select>
              </LegacyField>
              <LegacyField label={lbl('LBL_LINK_TARGET', 'Link Target')} required>
                <select className="form-control" value={target} onChange={(event) => setTarget(event.target.value)}>
                  {targetOptions.map((option) => (
                    <option key={String(option.id)} value={String(option.id)}>
                      {option.name}
                    </option>
                  ))}
                </select>
              </LegacyField>
              <LegacyField label={lbl('LBL_LOGIN_PROTECTED', 'Login Protected')} required>
                <select className="form-control" value={loginProtected} onChange={(event) => setLoginProtected(Number(event.target.value))}>
                  {loginOptions.map((option) => (
                    <option key={String(option.id)} value={Number(option.id)}>
                      {option.name}
                    </option>
                  ))}
                </select>
              </LegacyField>
              {type === 0 ? (
                <LegacyField label={lbl('LBL_LINK_TO_CMS_PAGE', 'Link to CMS Page')}>
                  <select className="form-control" value={cmsPageId} onChange={(event) => setCmsPageId(Number(event.target.value))}>
                    <option value={0}>{lbl('LBL_SELECT', 'Select')}</option>
                    {contentPages.map((page) => (
                      <option key={String(page.id)} value={Number(page.id)}>
                        {page.name}
                      </option>
                    ))}
                  </select>
                </LegacyField>
              ) : null}
              {type === 2 ? (
                <LegacyField label={lbl('LBL_EXTERNAL_PAGE', 'External Page')}>
                  <input className="form-control" value={url} onChange={(event) => setUrl(event.target.value)} />
                  <small>{lbl('LBL_TO_LINK_WITH_EXISTING_URL_USE_DOMAIN_PREFIX', 'To link with an existing URL, use prefix {domain} followed by the original URL. For eg. {domain}teachers Or {domain}aboutus')}</small>
                </LegacyField>
              ) : null}
              {hasActiveColumn ? (
                <LegacyField label={lbl('LBL_STATUS', 'Status')}>
                  <select className="form-control" value={active} onChange={(event) => setActive(Number(event.target.value))}>
                    <option value={1}>{lbl('LBL_ACTIVE', 'Active')}</option>
                    <option value={0}>{lbl('LBL_INACTIVE', 'Inactive')}</option>
                  </select>
                </LegacyField>
              ) : null}
              <FormButton saving={saving} label={lbl('LBL_SAVE_CHANGES', 'Save changes')} />
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" onSubmit={submitLang}>
            <div className="row">
              <LegacyField label={lbl('LBL_CAPTION', 'Caption')} required>
                <input className="form-control" value={caption} onChange={(event) => setCaption(event.target.value)} required />
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
