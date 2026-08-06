import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import { AdminTabbedModalLayout, type AdminTabbedModalTab } from './AdminTabbedModalLayout';

type LanguageOption = { id: number; name: string };
type TabKey = AdminTabbedModalTab;

type OptionDetail = {
  tissueopt_id: number;
  tissueopt_identifier: string;
  tissueoptlang_title: string;
  tissueopt_active: number;
};

type Props = {
  open: boolean;
  optId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminIssueReportOptionModal({ open, optId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [title, setTitle] = useState('');
  const [status, setStatus] = useState('1');
  const [autoTranslate, setAutoTranslate] = useState(false);

  const activeLangId = String(activeTab).startsWith('lang-')
    ? Number(String(activeTab).replace('lang-', ''))
    : 0;
  const activeLanguage = siteLanguages.find((lang) => lang.id === activeLangId);
  const activeLanguageName = activeLanguage?.name.toLowerCase() ?? '';
  const isArabicLang = activeLanguageName === 'arabic' || activeLangId === 2;
  const isSpanishLang = activeLanguageName === 'spanish';
  const layoutDirection = isArabicLang ? 'rtl' : undefined;
  const showAutoTranslate = siteLanguages.length > 1 && activeLangId === siteLanguages[0]?.id;
  const showAutoTranslateButton = isArabicLang || isSpanishLang;

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIdentifier('');
    setTitle('');
    setStatus('1');
    setAutoTranslate(false);
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

    if (optId > 0) {
      setRecordId(optId);
      void adminApi.issueReportOptionShow(optId).then((res) => {
        const data = res.data.data as OptionDetail;
        setIdentifier(data.tissueopt_identifier ?? '');
        setStatus(String(data.tissueopt_active ?? 1));
      });
    } else {
      reset();
    }
  }, [open, reset, optId]);

  const loadLangTab = useCallback((id: number, langId: number) => {
    setLoading(true);
    setError('');
    void adminApi
      .issueReportOptionShow(id, langId)
      .then((res) => {
        const data = res.data.data as OptionDetail;
        setTitle(data.tissueoptlang_title ?? '');
        setAutoTranslate(false);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load issue report option',
        );
      })
      .finally(() => setLoading(false));
  }, []);

  const onSelectLangTab = (langId: number) => {
    if (recordId < 1) {
      return;
    }
    setActiveTab(`lang-${langId}`);
    loadLangTab(recordId, langId);
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload = {
        tissueopt_identifier: identifier.trim(),
        tissueopt_active: Number(status),
        lang_id: 1,
      };
      const res =
        recordId > 0
          ? await adminApi.updateIssueReportOption(recordId, payload)
          : await adminApi.createIssueReportOption(payload);
      const savedId = Number(res.data?.data?.tissueopt_id ?? res.data?.data?.optId ?? recordId);
      setRecordId(savedId);
      onSaved();
      const firstLang = siteLanguages[0];
      if (firstLang) {
        setActiveTab(`lang-${firstLang.id}`);
        loadLangTab(savedId, firstLang.id);
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save issue report option',
      );
    } finally {
      setSaving(false);
    }
  };

  const onSaveLang = async (e: FormEvent) => {
    e.preventDefault();
    if (recordId < 1) {
      return;
    }
    const langId = Number(String(activeTab).replace('lang-', ''));
    const submitter = (e.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
    const shouldAutoTranslate = submitter?.name === 'update_langs_data' || autoTranslate;
    setSaving(true);
    setError('');
    try {
      await adminApi.updateIssueReportOption(recordId, {
        tissueopt_identifier: identifier.trim(),
        tissueoptlang_title: title.trim(),
        tissueopt_active: Number(status),
        lang_id: langId,
        update_langs_data: shouldAutoTranslate ? 1 : 0,
      });
      onSaved();
      const currentIndex = siteLanguages.findIndex((lang) => lang.id === langId);
      const nextLang = siteLanguages[currentIndex + 1];
      if (nextLang) {
        setActiveTab(`lang-${nextLang.id}`);
        loadLangTab(recordId, nextLang.id);
        return;
      }
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save issue report option',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_ISSUE_REPORT_OPTIONS_SETUP', 'Issue report options setup')}
      size="md"
      onClose={onClose}
    >
      <AdminTabbedModalLayout
        activeTab={activeTab}
        recordId={recordId}
        siteLanguages={siteLanguages}
        generalBodyClass="form-edit-body"
        langBodyClass="card-body"
        loading={loading}
        error={error}
        lbl={lbl}
        onSelectGeneral={() => setActiveTab('general')}
        onSelectLang={onSelectLangTab}
      >
        {activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={onSaveGeneral}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_OPTION_IDENTIFIER', 'Option identifier')}
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
                      <select
                        className="form-control"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                      >
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
                      {isArabicLang ? 'العنوان' : isSpanishLang ? 'Título' : lbl('LBL_TITLE', 'Title')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {showAutoTranslate ? (
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
            <div className="form-actions form-buttons-group">
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
                      ? 'الملء التلقائي لبيانات اللغة'
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
      </AdminTabbedModalLayout>
    </AdminModal>
  );
}
