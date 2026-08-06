import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import { AdminTabbedModalLayout, type AdminTabbedModalTab } from './AdminTabbedModalLayout';

type LanguageOption = { id: number; name: string };
type TabKey = AdminTabbedModalTab;

type LangFormState = {
  meta_title: string;
  meta_keywords: string;
  meta_description: string;
  meta_other_meta_tags: string;
  meta_og_title: string;
  meta_og_url: string;
  meta_og_description: string;
  show_og_image: boolean;
  layout_direction: string;
};

const emptyLangForm = (): LangFormState => ({
  meta_title: '',
  meta_keywords: '',
  meta_description: '',
  meta_other_meta_tags: '',
  meta_og_title: '',
  meta_og_url: '',
  meta_og_description: '',
  show_og_image: true,
  layout_direction: 'ltr',
});

export type MetaTagEditTarget = {
  metaId: number;
  metaType: number;
  recordId: string;
};

type Props = {
  target: MetaTagEditTarget | null;
  onClose: () => void;
  onSaved: () => void;
};

const META_OTHER = 0;

export function AdminMetaTagModal({ target, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [metaType, setMetaType] = useState(-1);
  const [entityRecordId, setEntityRecordId] = useState('');
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [slug, setSlug] = useState('');
  const [langForm, setLangForm] = useState<LangFormState>(emptyLangForm);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [ogImageFileName, setOgImageFileName] = useState('');

  const isOpen = target !== null;
  const isOtherType = metaType === META_OTHER;
  const activeLangId = activeTab.startsWith('lang-') ? Number(activeTab.replace('lang-', '')) : 0;
  const activeLanguage = siteLanguages.find((lang) => lang.id === activeLangId);
  const isEnglishLang = activeLanguage?.name.toLowerCase() === 'english';
  const isArabicLang = activeLanguage?.name.toLowerCase() === 'arabic' || activeLangId === 2;
  const isSpanishLang = activeLanguage?.name.toLowerCase() === 'spanish';
  const isDefaultLang = activeLangId > 0 && activeLangId === siteLanguages[0]?.id;
  const showAutoTranslate = siteLanguages.length > 1 && (isDefaultLang || isEnglishLang);

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setMetaType(-1);
    setEntityRecordId('');
    setIdentifier('');
    setSlug('');
    setLangForm(emptyLangForm());
    setSiteLanguages([]);
    setAutoTranslate(false);
    setOgImageFileName('');
  }, []);

  const loadGeneralForm = useCallback(
    (metaId: number, type: number, recId: string) => {
      setLoading(true);
      setError('');
      void adminApi
        .metaTagForm(metaId, type, recId)
        .then((res) => {
          const data = res.data.data ?? {};
          setRecordId(Number(data.meta_id ?? metaId));
          setMetaType(Number(data.meta_type ?? type));
          setEntityRecordId(String(data.meta_record_id ?? recId));
          setIdentifier(String(data.meta_identifier ?? ''));
          setSlug(String(data.meta_slug ?? ''));
          setSiteLanguages((data.site_languages as LanguageOption[]) ?? []);
        })
        .catch((err: unknown) => {
          setError(
            (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
              'Failed to load meta tag',
          );
        })
        .finally(() => setLoading(false));
    },
    [],
  );

  const loadLangTab = useCallback((metaId: number, langId: number) => {
    setLoading(true);
    setError('');
    void adminApi
      .metaTagLangForm(metaId, langId)
      .then((res) => {
        const data = res.data.data ?? {};
        setLangForm({
          meta_title: String(data.meta_title ?? ''),
          meta_keywords: String(data.meta_keywords ?? ''),
          meta_description: String(data.meta_description ?? ''),
          meta_other_meta_tags: String(data.meta_other_meta_tags ?? ''),
          meta_og_title: String(data.meta_og_title ?? ''),
          meta_og_url: String(data.meta_og_url ?? ''),
          meta_og_description: String(data.meta_og_description ?? ''),
          show_og_image: Boolean(data.show_og_image ?? true),
          layout_direction: String(data.layout_direction ?? 'ltr'),
        });
        setAutoTranslate(false);
        setOgImageFileName('');
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load language data',
        );
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!target) {
      reset();
      return;
    }

    setActiveTab('general');
    loadGeneralForm(target.metaId, target.metaType, target.recordId);
  }, [target, reset, loadGeneralForm]);

  const onSelectLangTab = (langId: number) => {
    if (recordId < 1) {
      return;
    }
    setActiveTab(`lang-${langId}`);
    setAutoTranslate(false);
    setOgImageFileName('');
    loadLangTab(recordId, langId);
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.saveMetaTag({
        meta_id: recordId,
        meta_type: metaType,
        meta_record_id: entityRecordId,
        meta_identifier: identifier.trim(),
        meta_slug: slug.trim(),
      });
      const savedId = Number(res.data?.data?.meta_id ?? recordId);
      const nextLangId = Number(res.data?.data?.lang_id ?? 0);
      setRecordId(savedId);
      onSaved();
      if (nextLangId > 0) {
        setActiveTab(`lang-${nextLangId}`);
        loadLangTab(savedId, nextLangId);
        return;
      }
      const firstLang = siteLanguages[0];
      if (firstLang) {
        setActiveTab(`lang-${firstLang.id}`);
        loadLangTab(savedId, firstLang.id);
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save meta tag',
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
    setSaving(true);
    setError('');
    try {
      const submitter = (e.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
      const shouldUpdateLangs = autoTranslate || submitter?.name === 'update_langs_data';
      const res = await adminApi.saveMetaTagLang(recordId, langId, {
        ...langForm,
        update_langs_data: shouldUpdateLangs ? '1' : '',
      });
      onSaved();
      const nextLangId = Number(res.data?.data?.lang_id ?? 0);
      if (nextLangId > 0) {
        setActiveTab(`lang-${nextLangId}`);
        loadLangTab(recordId, nextLangId);
        return;
      }
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save language data',
      );
    } finally {
      setSaving(false);
    }
  };

  const updateLangField = (field: keyof LangFormState, value: string) => {
    setLangForm((prev) => ({ ...prev, [field]: value }));
  };

  return (
    <AdminModal
      open={isOpen}
      title={lbl('LBL_Meta_Tag_Setup', 'Meta tag setup')}
      size="lg"
      onClose={onClose}
    >
      <AdminTabbedModalLayout
        activeTab={activeTab}
        recordId={recordId}
        siteLanguages={siteLanguages}
        loading={loading}
        error={error}
        lbl={lbl}
        onSelectGeneral={() => {
          if (target) {
            setActiveTab('general');
            loadGeneralForm(recordId || target.metaId, metaType, entityRecordId || target.recordId);
          }
        }}
        onSelectLang={onSelectLangTab}
      >
        {activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={onSaveGeneral}>
            <div className="row">
              {isOtherType ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_SLUG', 'Slug')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          value={slug}
                          onChange={(e) => setSlug(e.target.value)}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_IDENTIFIER', 'Identifier')}
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
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : (
          <form
            className={`form form_horizontal layout--${langForm.layout_direction}`}
            dir={isArabicLang ? 'rtl' : undefined}
            onSubmit={onSaveLang}
          >
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Meta_Title', 'Meta title')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={langForm.meta_title}
                        onChange={(e) => updateLangField('meta_title', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Meta_Keywords', 'Meta keywords')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        className="form-control"
                        rows={3}
                        value={langForm.meta_keywords}
                        onChange={(e) => updateLangField('meta_keywords', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Meta_Description', 'Meta description')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        className="form-control"
                        rows={3}
                        value={langForm.meta_description}
                        onChange={(e) => updateLangField('meta_description', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Other_Meta_Tags', 'Other meta tags')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        className="form-control"
                        rows={3}
                        value={langForm.meta_other_meta_tags}
                        onChange={(e) => updateLangField('meta_other_meta_tags', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Open_Graph_Title', 'Open graph title')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={langForm.meta_og_title}
                        onChange={(e) => updateLangField('meta_og_title', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Open_Graph_Url', 'Open graph URL')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={langForm.meta_og_url}
                        onChange={(e) => updateLangField('meta_og_url', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Open_Graph_Description', 'Open graph description')}
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        className="form-control"
                        rows={3}
                        value={langForm.meta_og_description}
                        onChange={(e) => updateLangField('meta_og_description', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              {langForm.show_og_image ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_Open_Graph_Image', 'Open graph image')}
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <label className="btn btn-secondary mb-0">
                          {lbl('LBL_UPLOAD_FILE', 'Upload file')}
                          <input
                            type="file"
                            accept="image/png,image/jpeg,image/jpg"
                            hidden
                            onChange={(event) => {
                              setOgImageFileName(event.target.files?.[0]?.name ?? '');
                            }}
                          />
                        </label>
                        {ogImageFileName ? <span className="ms-2">{ogImageFileName}</span> : null}
                        <p className="mt-2 mb-0">
                          {lbl('LBL_Preferred_Dimensions_1200_X_627', 'Preferred dimensions 1200 x 627')}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
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
                          <span className="input-helper" />
                          <span>
                            {lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
            </div>
            <div className="form-actions form-buttons-group">
              {isArabicLang ? (
                <>
                  <button type="submit" name="update_langs_data" value="1" className="btn btn-secondary" disabled={saving}>
                    Ø§Ù„Ù…Ù„Ø¡ Ø§Ù„ØªÙ„Ù‚Ø§Ø¦ÙŠ Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ù„ØºØ©
                  </button>
                  <button type="submit" className="btn btn-brand" disabled={saving}>
                    Ø­ÙØ¸ Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª
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
