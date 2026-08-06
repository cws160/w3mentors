import { type FormEvent, type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  faqId: number;
  onClose: () => void;
  onSaved: () => void;
};

type LanguageOption = { id: number; name: string };
type CategoryOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;

export function AdminFaqModal({ open, faqId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [categoryId, setCategoryId] = useState(0);
  const [active, setActive] = useState(1);
  const [langId, setLangId] = useState(0);
  const [faqTitle, setFaqTitle] = useState('');
  const [faqDescription, setFaqDescription] = useState('');
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
    setCategories([]);
    setIdentifier('');
    setCategoryId(0);
    setActive(1);
    setLangId(0);
    setFaqTitle('');
    setFaqDescription('');
    setLayoutDirection('ltr');
    setShowAutoTranslate(false);
    setAutoTranslate(false);
    setError('');
  }, [contextLanguages]);

  const applyGeneralData = (data: Record<string, unknown>) => {
    setRecordId(Number(data.faq_id ?? faqId));
    setIdentifier(String(data.faq_identifier ?? ''));
    setCategoryId(Number(data.faq_category ?? 0));
    setActive(Number(data.faq_active ?? 1));
    setCategories((data.categories as CategoryOption[] | undefined) ?? []);
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? contextLanguages);
    setActiveTab('general');
    setLangId(0);
    setShowAutoTranslate(false);
    setAutoTranslate(false);
  };

  const applyLangData = (data: Record<string, unknown>, nextLangId: number) => {
    setRecordId(Number(data.faq_id ?? faqId));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? siteLanguages);
    setLangId(Number(data.lang_id ?? nextLangId));
    setFaqTitle(String(data.faq_title ?? ''));
    setFaqDescription(String(data.faq_description ?? ''));
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
      .faqShow(faqId)
      .then((res) => applyGeneralData((res.data.data ?? {}) as Record<string, unknown>))
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [contextLanguages, faqId, lbl, open, reset]);

  const loadLang = useCallback(
    async (nextFaqId: number, nextLangId: number) => {
      if (nextFaqId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.faqLangForm(nextFaqId, nextLangId);
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
    [faqId, lbl, siteLanguages],
  );

  const submitGeneral = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.faqUpdate(recordId, {
        faq_identifier: identifier.trim(),
        faq_category: categoryId,
        faq_active: active,
      });
      const savedId = Number(res.data.data?.faq_id ?? recordId);
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
      const res = await adminApi.faqLangUpdate(recordId, langId, {
        faq_title: faqTitle.trim(),
        faq_description: faqDescription.trim(),
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
    <AdminModal open={open} title={lbl('LBL_FAQ_SETUP', 'FAQ Setup')} size="lg" onClose={onClose}>
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
                    faq_id: recordId,
                    faq_identifier: identifier,
                    faq_category: categoryId,
                    faq_active: active,
                    categories,
                    site_languages: siteLanguages,
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
              <LegacyField label={lbl('LBL_FAQ_IDENTIFIER', 'FAQ Identifier')} required>
                <input className="form-control" value={identifier} onChange={(event) => setIdentifier(event.target.value)} required />
              </LegacyField>
              <LegacyField label={lbl('LBL_FAQ_CATEGORY', 'FAQ Category')} required>
                <select className="form-control" value={categoryId} onChange={(event) => setCategoryId(Number(event.target.value))} required>
                  <option value={0}>{lbl('LBL_SELECT', 'Select')}</option>
                  {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                      {category.name}
                    </option>
                  ))}
                </select>
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
              <LegacyField label={lbl('LBL_FAQ_TITLE', 'FAQ Title')} required>
                <input className="form-control" value={faqTitle} onChange={(event) => setFaqTitle(event.target.value)} required />
              </LegacyField>
              <LegacyField label={lbl('LBL_FAQ_TEXT', 'FAQ Text')}>
                <textarea className="form-control" value={faqDescription} onChange={(event) => setFaqDescription(event.target.value)} rows={8} />
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
