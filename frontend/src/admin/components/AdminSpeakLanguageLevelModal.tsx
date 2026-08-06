import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import { AdminTabbedModalLayout, type AdminTabbedModalTab } from './AdminTabbedModalLayout';

type LanguageOption = { id: number; name: string };
type TabKey = AdminTabbedModalTab;

type LevelDetail = {
  slanglvl_id: number;
  slanglvl_identifier: string;
  slanglvl_name: string;
  slanglvl_active: number;
};

type Props = {
  open: boolean;
  slanglvlId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminSpeakLanguageLevelModal({ open, slanglvlId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [name, setName] = useState('');
  const [status, setStatus] = useState('1');
  const [autoTranslate, setAutoTranslate] = useState(false);
  const activeLangId = activeTab.startsWith('lang-') ? Number(activeTab.replace('lang-', '')) : 0;
  const activeLanguage = siteLanguages.find((lang) => lang.id === activeLangId);
  const isEnglishLang = activeLanguage?.name.toLowerCase() === 'english';
  const isArabicLang = activeLanguage?.name.toLowerCase() === 'arabic' || activeLangId === 2;
  const isSpanishLang = activeLanguage?.name.toLowerCase() === 'spanish';
  const showAutoTranslate =
    siteLanguages.length > 1 && activeLangId > 0 && (activeLangId === siteLanguages[0]?.id || isEnglishLang);

  const reset = useCallback(() => {
    setLoading(false);
    setSaving(false);
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIdentifier('');
    setName('');
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

    if (slanglvlId > 0) {
      setRecordId(slanglvlId);
      void adminApi.speakLanguageLevelShow(slanglvlId).then((res) => {
        const data = res.data.data as LevelDetail;
        setIdentifier(data.slanglvl_identifier ?? '');
        setStatus(String(data.slanglvl_active ?? 1));
      });
    } else {
      reset();
    }
  }, [open, reset, slanglvlId]);

  const loadLangTab = useCallback((id: number, langId: number) => {
    setLoading(true);
    setError('');
    void adminApi
      .speakLanguageLevelShow(id, langId)
      .then((res) => {
        const data = res.data.data as LevelDetail;
        setName(data.slanglvl_name ?? '');
        setAutoTranslate(false);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load language level',
        );
      })
      .finally(() => setLoading(false));
  }, []);

  const onSelectLangTab = (langId: number) => {
    if (recordId < 1) {
      return;
    }
    setActiveTab(`lang-${langId}`);
    setAutoTranslate(false);
    loadLangTab(recordId, langId);
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload = {
        slanglvl_identifier: identifier.trim(),
        slanglvl_active: Number(status),
        lang_id: 1,
      };
      const res =
        recordId > 0
          ? await adminApi.updateSpeakLanguageLevel(recordId, payload)
          : await adminApi.createSpeakLanguageLevel(payload);
      const savedId = Number(res.data?.data?.slanglvl_id ?? recordId);
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
          'Failed to save language level',
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
      await adminApi.updateSpeakLanguageLevel(recordId, {
        slanglvl_identifier: identifier.trim(),
        slanglvl_name: name.trim(),
        slanglvl_active: Number(status),
        lang_id: langId,
        update_langs_data: (showAutoTranslate || isArabicLang || isSpanishLang) && shouldAutoTranslate ? 1 : 0,
      });
      setLoading(false);
      setSaving(false);
      onClose();
      window.setTimeout(onSaved, 300);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save language level',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_SPOKEN_LANGUAGE_LEVEL_SETUP', 'Spoken language level setup')}
      size="md"
      onClose={onClose}
    >
      <AdminTabbedModalLayout
        activeTab={activeTab}
        recordId={recordId}
        siteLanguages={siteLanguages}
        generalBodyClass="card-body"
        langBodyClass="form-edit-body"
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
                      {lbl('LBL_LANGUAGE_LEVEL_IDENTIFIER', 'Identifier')}
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
          <form className="form form_horizontal" dir={isArabicLang ? 'rtl' : undefined} onSubmit={onSaveLang}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {isArabicLang ? 'اسم' : lbl('LBL_LANGUAGE_LEVEL_NAME', 'Name')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
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
                            {lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}
            <div className="form-actions form-buttons-group">
              {isArabicLang ? (
                <>
                  <button type="submit" name="update_langs_data" value="1" className="btn btn-secondary" disabled={saving}>
                    الملء التلقائي لبيانات اللغة
                  </button>
                  <button type="submit" className="btn btn-brand" disabled={saving}>
                    حفظ التغييرات
                  </button>
                </>
              ) : isSpanishLang ? (
                <>
                  <button type="submit" className="btn btn-brand" disabled={saving}>
                    Guardar cambios
                  </button>
                  <button type="submit" name="update_langs_data" value="1" className="btn btn-secondary" disabled={saving}>
                    Autocompletar datos de idioma
                  </button>
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
