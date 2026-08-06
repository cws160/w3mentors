import { type ChangeEvent, type FormEvent, type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  testimonialId: number;
  onClose: () => void;
  onSaved: () => void;
};

type LanguageOption = { id: number; name: string };
type TabKey = 'general' | 'media' | `lang-${number}`;

export function AdminTestimonialModal({ open, testimonialId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [userName, setUserName] = useState('');
  const [active, setActive] = useState(1);
  const [langId, setLangId] = useState(0);
  const [testimonialText, setTestimonialText] = useState('');
  const [layoutDirection, setLayoutDirection] = useState('ltr');
  const [showAutoTranslate, setShowAutoTranslate] = useState(false);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [mediaImage, setMediaImage] = useState<{ url: string } | null>(null);
  const [preferredDimensions, setPreferredDimensions] = useState('275 x 275');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const activeLangIndex = useMemo(
    () => (langId > 0 ? siteLanguages.findIndex((lang) => lang.id === langId) : -1),
    [langId, siteLanguages],
  );

  const reset = useCallback(() => {
    setActiveTab('general');
    setRecordId(0);
    setSiteLanguages(contextLanguages);
    setIdentifier('');
    setUserName('');
    setActive(1);
    setLangId(0);
    setTestimonialText('');
    setLayoutDirection('ltr');
    setShowAutoTranslate(false);
    setAutoTranslate(false);
    setMediaImage(null);
    setPreferredDimensions('275 x 275');
    setError('');
  }, [contextLanguages]);

  const applyGeneralData = (data: Record<string, unknown>) => {
    setRecordId(Number(data.testimonial_id ?? testimonialId));
    setIdentifier(String(data.testimonial_identifier ?? ''));
    setUserName(String(data.testimonial_user_name ?? ''));
    setActive(Number(data.testimonial_active ?? 1));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? contextLanguages);
    setActiveTab('general');
    setLangId(0);
    setShowAutoTranslate(false);
    setAutoTranslate(false);
  };

  const applyLangData = (data: Record<string, unknown>, nextLangId: number) => {
    setRecordId(Number(data.testimonial_id ?? testimonialId));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? siteLanguages);
    setLangId(Number(data.lang_id ?? nextLangId));
    setTestimonialText(String(data.testimonial_text ?? ''));
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
      .testimonialShow(testimonialId)
      .then((res) => applyGeneralData((res.data.data ?? {}) as Record<string, unknown>))
      .catch((err: unknown) => {
        setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_INVALID_REQUEST', 'Invalid request'));
      })
      .finally(() => setLoading(false));
  }, [testimonialId, contextLanguages, lbl, open, reset]);

  const loadLang = useCallback(
    async (nextTestimonialId: number, nextLangId: number) => {
      if (nextTestimonialId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.testimonialLangForm(nextTestimonialId, nextLangId);
        applyLangData((res.data.data ?? {}) as Record<string, unknown>, nextLangId);
      } catch (err: unknown) {
        setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_INVALID_REQUEST', 'Invalid request'));
      } finally {
        setLoading(false);
      }
    },
    [lbl, siteLanguages],
  );

  const loadMedia = useCallback(
    async (nextTestimonialId: number) => {
      if (nextTestimonialId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.testimonialMedia(nextTestimonialId);
        const data = (res.data.data ?? {}) as Record<string, unknown>;
        setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? siteLanguages);
        setPreferredDimensions(String(data.preferred_dimensions ?? '275 x 275'));
        setMediaImage((data.image as { url: string } | null) ?? null);
        setActiveTab('media');
      } catch (err: unknown) {
        setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_INVALID_REQUEST', 'Invalid request'));
      } finally {
        setLoading(false);
      }
    },
    [lbl, siteLanguages],
  );

  const submitGeneral = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.testimonialUpdate(recordId, {
        testimonial_identifier: identifier.trim(),
        testimonial_user_name: userName.trim(),
        testimonial_active: active,
      });
      const savedId = Number(res.data.data?.testimonial_id ?? recordId);
      setRecordId(savedId);
      onSaved();
      const nextLangId = Number(res.data.data?.next_lang_id ?? siteLanguages[0]?.id ?? 0);
      if (nextLangId > 0) {
        await loadLang(savedId, nextLangId);
      } else {
        await loadMedia(savedId);
      }
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
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
      const res = await adminApi.testimonialLangUpdate(recordId, langId, {
        testimonial_text: testimonialText.trim(),
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
        await loadMedia(recordId);
      }
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  const uploadMedia = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file || recordId < 1) return;
    setSaving(true);
    setError('');
    try {
      await adminApi.uploadTestimonialMedia(recordId, file);
      await loadMedia(recordId);
      onSaved();
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
      event.target.value = '';
    }
  };

  const removeMedia = async () => {
    if (recordId < 1 || !window.confirm(lbl('LBL_CONFIRM_DELETE_IMAGE', 'Are you sure you want to delete this image?'))) return;
    setSaving(true);
    setError('');
    try {
      await adminApi.removeTestimonialMedia(recordId);
      await loadMedia(recordId);
      onSaved();
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl(
        activeTab === 'media' ? 'LBL_Testimonial_Media_setup' : 'LBL_Testimonial_Setup',
        activeTab === 'media' ? 'Testimonial media setup' : 'Testimonial Setup',
      )}
      size="md"
      onClose={onClose}
    >
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul className="tabs-nav">
            <li>
              <a href="javascript:void(0)" className={activeTab === 'general' ? 'active' : ''} onClick={(event) => { event.preventDefault(); applyGeneralData({ testimonial_id: recordId, testimonial_identifier: identifier, testimonial_user_name: userName, testimonial_active: active, site_languages: siteLanguages }); }}>
                {lbl('LBL_GENERAL', 'General')}
              </a>
            </li>
            {siteLanguages.map((lang) => (
              <li key={lang.id} className={recordId < 1 ? 'is-inactive' : ''}>
                <a href="javascript:void(0)" data-id={lang.id} className={activeTab === `lang-${lang.id}` ? 'active' : ''} onClick={(event) => { event.preventDefault(); if (recordId > 0) void loadLang(recordId, lang.id); }}>
                  {lang.name}
                </a>
              </li>
            ))}
            <li className={recordId < 1 ? 'is-inactive' : ''}>
              <a href="javascript:void(0)" data-id="media" className={activeTab === 'media' ? 'active' : ''} onClick={(event) => { event.preventDefault(); if (recordId > 0) void loadMedia(recordId); }}>
                {lbl('LBL_MEDIA', 'Media')}
              </a>
            </li>
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
              <LegacyField label={lbl('LBL_Testimonial_Identifier', 'Testimonial Identifier')} required>
                <input className="form-control" value={identifier} onChange={(event) => setIdentifier(event.target.value)} required />
              </LegacyField>
              <LegacyField label={lbl('LBL_Testimonial_User_Name', 'Testimonial User Name')} required>
                <input className="form-control" value={userName} onChange={(event) => setUserName(event.target.value)} required />
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
        ) : activeTab === 'media' ? (
          <div className="form form_horizontal admin-testimonial-media-form">
            <div className="row">
              <LegacyField label={lbl('LBL_IMAGE', 'Image')} required>
                <label className="btn btn-primary btn--sm m-0">
                  {saving ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait') : lbl('LBL_UPLOAD_IMAGE', 'Upload Image')}
                  <input type="file" accept="image/*" hidden disabled={saving} onChange={uploadMedia} />
                </label>
                <small className="text--small">{lbl('LBL_Preferred_Dimensions', 'Preferred dimensions')} {preferredDimensions}</small>
                {mediaImage ? (
                  <div className="image-listing row g-4">
                    <div className="col-md-4">
                      <div className="ratio ratio-1x1 uploaded-media">
                        <img src={`${mediaImage.url}?${Date.now()}`} alt="" />
                        <button type="button" className="remove--img" onClick={removeMedia} disabled={saving}>x</button>
                      </div>
                    </div>
                  </div>
                ) : null}
              </LegacyField>
            </div>
          </div>
        ) : (
          <form className={`form form_horizontal layout--${layoutDirection}`} dir={layoutDirection} onSubmit={submitLang}>
            <div className="row">
              <LegacyField label={lbl('LBL_Testimonial_Text', 'Testimonial Text')} required>
                <textarea className="form-control" rows={5} minLength={10} maxLength={300} value={testimonialText} onChange={(event) => setTestimonialText(event.target.value)} required />
              </LegacyField>
              {showAutoTranslate ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="field-wraper">
                      <div className="field_cover">
                        <label className="checkbox d-flex">
                          <input type="checkbox" name="update_langs_data" value="1" checked={autoTranslate} onChange={(event) => setAutoTranslate(event.target.checked)} />
                          <span>{lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
              <FormButton saving={saving} label={activeLangIndex === siteLanguages.length - 1 ? lbl('LBL_SAVE_CHANGES', 'Save changes') : lbl('LBL_SAVE_&_NEXT', 'Save & next')} />
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
