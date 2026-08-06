import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type LanguageOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;

type Props = {
  open: boolean;
  clangId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminCourseLanguageModal({ open, clangId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [status, setStatus] = useState('1');
  const [langName, setLangName] = useState('');
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [layoutDirection, setLayoutDirection] = useState('ltr');

  const activeLangId = activeTab.startsWith('lang-') ? Number(activeTab.replace('lang-', '')) : 0;
  const activeLanguage = siteLanguages.find((lang) => lang.id === activeLangId);
  const isArabicLang =
    activeTab.startsWith('lang-') &&
    (activeLanguage?.name.toLowerCase() === 'arabic' || activeLanguage?.id === 2);
  const isSpanishLang = activeTab.startsWith('lang-') && activeLanguage?.name.toLowerCase() === 'spanish';
  const isDefaultLang = activeLangId > 0 && activeLangId === siteLanguages[0]?.id;
  const showAutoTranslate = siteLanguages.length > 1 && activeLangId > 0;
  const showAutoTranslateCheckbox = showAutoTranslate && isDefaultLang;
  const showAutoTranslateButton = showAutoTranslate && !isDefaultLang;

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIdentifier('');
    setStatus('1');
    setLangName('');
    setAutoTranslate(false);
    setLayoutDirection('ltr');
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .courseLanguageCreateForm()
      .then((res) => {
        setSiteLanguages(res.data.data?.site_languages ?? []);
      })
      .finally(() => setLoading(false));

    if (clangId > 0) {
      void adminApi.courseLanguageShow(clangId).then((res) => {
        const data = res.data.data ?? {};
        setRecordId(clangId);
        setIdentifier(String(data.clang_identifier ?? ''));
        setStatus(String(data.clang_active ?? 1));
      });
    }
  }, [clangId, open, reset]);

  const loadLangTab = useCallback(
    (langId: number) => {
      if (recordId < 1) return;
      setLoading(true);
      void adminApi
        .courseLanguageLangForm(recordId, langId)
        .then((res) => {
          const data = res.data.data ?? {};
          setLangName(String(data.clang_name ?? ''));
          setAutoTranslate(false);
          setLayoutDirection(String(data.layout_direction ?? 'ltr'));
        })
        .finally(() => setLoading(false));
    },
    [recordId],
  );

  const onSelectLangTab = (langId: number) => {
    if (recordId < 1) return;
    setActiveTab(`lang-${langId}`);
    loadLangTab(langId);
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload = {
        clang_id: recordId,
        clang_identifier: identifier.trim(),
        clang_active: Number(status),
      };
      const res =
        recordId > 0
          ? await adminApi.updateCourseLanguage(recordId, payload)
          : await adminApi.createCourseLanguage(payload);
      const savedId = Number(res.data.id ?? recordId);
      setRecordId(savedId);
      const firstLang = siteLanguages[0];
      if (firstLang) {
        setActiveTab(`lang-${firstLang.id}`);
        loadLangTab(firstLang.id);
      }
      onSaved();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to save')
          : 'Unable to save';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  const onSaveLang = async (e: FormEvent) => {
    e.preventDefault();
    if (recordId < 1) return;
    const langId = Number(String(activeTab).replace('lang-', ''));
    const submitter = (e.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
    const shouldAutoTranslate = submitter?.name === 'update_langs_data' || autoTranslate;
    setSaving(true);
    setError('');
    try {
      await adminApi.storeCourseLanguageLang(recordId, langId, {
        clang_name: langName.trim(),
        update_langs_data: shouldAutoTranslate ? 1 : 0,
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to save')
          : 'Unable to save';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_COURSE_LANGUAGE_SETUP', 'Course Language Setup')}
      size="md"
      onClose={onClose}
    >
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul className="tabs-nav">
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={(e) => {
                  e.preventDefault();
                  setActiveTab('general');
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
                  onClick={(e) => {
                    e.preventDefault();
                    onSelectLangTab(lang.id);
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
        {error ? <div className="alert alert-danger">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={onSaveGeneral}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_COURSE_LANGUAGE_IDENTIFIER', 'Identifier')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={identifier}
                        onChange={(e) => setIdentifier(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_STATUS', 'Status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                        <option value="1">{lbl('LBL_ACTIVE', 'Active')}</option>
                        <option value="0">{lbl('LBL_INACTIVE', 'Inactive')}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" dir={layoutDirection} onSubmit={onSaveLang}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {isArabicLang
                        ? 'اسم'
                        : isSpanishLang
                          ? 'Nombre'
                          : lbl('LBL_COURSE_LANGUAGE_NAME', 'Name')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={langName}
                        onChange={(e) => setLangName(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {showAutoTranslateCheckbox ? (
              <div className="row">
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
                          <span className="input-helper" />
                          <span>
                            {lbl(
                              'LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES',
                              'Auto translate for other languages',
                            )}
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}
            <div className="form-actions">
              {showAutoTranslateButton ? (
                <>
                  {!isArabicLang ? (
                    <button type="submit" className="btn btn-brand" disabled={saving}>
                      {isSpanishLang ? 'Guardar cambios' : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                    </button>
                  ) : null}
                  <button
                    type="submit"
                    name="update_langs_data"
                    value="1"
                    className="btn btn-secondary"
                    disabled={saving}
                  >
                    {isArabicLang
                      ? 'الملء التلقائي للبيانات الباقية'
                      : isSpanishLang
                        ? 'Autocompletar datos de idioma'
                        : lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                  </button>
                  {isArabicLang ? (
                    <button type="submit" className="btn btn-brand" disabled={saving}>
                      حفظ التغييرات
                    </button>
                  ) : null}
                </>
              ) : (
                <button type="submit" className="btn btn-primary" disabled={saving}>
                  {lbl('LBL_SAVE_CHANGES', 'Save changes')}
                </button>
              )}
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}
