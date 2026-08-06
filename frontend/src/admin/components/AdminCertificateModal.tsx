import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  code: string;
  langId: number;
  onClose: () => void;
  onSaved: () => void;
};

type LanguageOption = { id: number; name: string };

const emptyContent = {
  heading: '',
  content_part_1: '',
  learner: '',
  content_part_2: '',
  trainer: '',
  certificate_number: '',
};

export function AdminCertificateModal({ open, code, langId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [selectedLangId, setSelectedLangId] = useState(1);
  const [name, setName] = useState('');
  const [status, setStatus] = useState(1);
  const [vars, setVars] = useState('');
  const [content, setContent] = useState(emptyContent);
  const [backgroundUrl, setBackgroundUrl] = useState('');
  const [showAutoTranslate, setShowAutoTranslate] = useState(false);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const reset = useCallback(() => {
    setSiteLanguages(contextLanguages);
    setSelectedLangId(langId || contextLanguages[0]?.id || 1);
    setName('');
    setStatus(1);
    setVars('');
    setContent(emptyContent);
    setBackgroundUrl('');
    setShowAutoTranslate(false);
    setAutoTranslate(false);
    setError('');
  }, [contextLanguages, langId]);

  const applyData = (data: Record<string, unknown>, nextLangId: number) => {
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? contextLanguages);
    setSelectedLangId(Number(data.certpl_lang_id ?? nextLangId));
    setName(String(data.certpl_name ?? ''));
    setStatus(Number(data.certpl_status ?? 1));
    setVars(String(data.certpl_vars ?? ''));
    setContent({
      heading: String(data.heading ?? ''),
      content_part_1: String(data.content_part_1 ?? ''),
      learner: String(data.learner ?? ''),
      content_part_2: String(data.content_part_2 ?? ''),
      trainer: String(data.trainer ?? ''),
      certificate_number: String(data.certificate_number ?? ''),
    });
    setBackgroundUrl(String(data.background_url ?? ''));
    setShowAutoTranslate(Boolean(data.show_auto_translate));
    setAutoTranslate(false);
  };

  const loadLang = useCallback(
    async (nextLangId: number) => {
      if (!code) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.certificateLangForm(code, nextLangId);
        applyData((res.data.data ?? {}) as Record<string, unknown>, nextLangId);
      } catch (err: unknown) {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      } finally {
        setLoading(false);
      }
    },
    [code, contextLanguages, lbl],
  );

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }
    void loadLang(langId || contextLanguages[0]?.id || 1);
  }, [contextLanguages, langId, loadLang, open, reset]);

  const setContentField = (field: keyof typeof emptyContent, value: string) => {
    setContent((prev) => ({ ...prev, [field]: value }));
  };

  const save = async () => {
    setSaving(true);
    setError('');
    try {
      await adminApi.certificateLangUpdate(code, selectedLangId, {
        certpl_name: name.trim(),
        certpl_status: status,
        ...content,
        update_langs_data: autoTranslate ? 1 : 0,
      });
      onSaved();
      return true;
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
      return false;
    } finally {
      setSaving(false);
    }
  };

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    const saved = await save();
    if (saved) {
      onClose();
    }
  };

  const saveAndPreview = async () => {
    const popup = window.open('', '_blank', 'noopener,noreferrer');
    const saved = await save();
    if (!saved) {
      popup?.close();
      return;
    }
    try {
      const res = await adminApi.previewCertificate(code, selectedLangId);
      const blob = new Blob([res.data], { type: 'text/html;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      if (popup) {
        popup.location.href = url;
      } else {
        window.open(url, '_blank', 'noopener,noreferrer');
      }
      setTimeout(() => URL.revokeObjectURL(url), 30000);
    } catch (err: unknown) {
      popup?.close();
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_INVALID_REQUEST', 'Invalid request'),
      );
    }
  };

  const uploadMedia = async (file: File | undefined) => {
    if (!file || !code) return;
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.uploadCertificateMedia(code, selectedLangId, file);
      setBackgroundUrl(String(res.data.data?.background_url ?? ''));
      onSaved();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_INVALID_FILE', 'Invalid file'),
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal open={open} title={lbl('LBL_CERTIFICATE_SETUP', 'Certificate setup')} size="lg" onClose={onClose}>
      <form className="form" onSubmit={submit}>
        {error ? <div className="alert alert--danger">{error}</div> : null}
        <div className="p-4">
          {loading ? (
            <div className="table-processing loaderJs">
              <div className="spinner spinner--sm spinner--brand" />
            </div>
          ) : (
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_BACKGROUND_IMAGE', 'Background image')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="file"
                        accept=".png,.jpg,.jpeg,.gif,.webp,.bmp"
                        onChange={(event) => void uploadMedia(event.target.files?.[0])}
                      />
                      <small>{lbl('LBL_PREFERRED_DIMENSIONS_2070X1680', 'Preferred dimensions 2070 x 1680')}</small>
                      {backgroundUrl ? (
                        <div className="uploaded-media">
                          <img src={backgroundUrl} alt="" />
                        </div>
                      ) : null}
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_LANGUAGE', 'Language')}<span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select value={selectedLangId} onChange={(event) => void loadLang(Number(event.target.value))}>
                        {siteLanguages.map((language) => (
                          <option key={language.id} value={language.id}>
                            {language.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <TextField label={lbl('LBL_NAME', 'Name')} value={name} required onChange={setName} />
              <TextField label={lbl('LBL_HEADING', 'Heading')} value={content.heading} required onChange={(value) => setContentField('heading', value)} />
              <TextField label={lbl('LBL_CONTENT_PART_1', 'Content part 1')} value={content.content_part_1} required onChange={(value) => setContentField('content_part_1', value)} />
              <TextField label={lbl('LBL_LEARNER', 'Learner')} value={content.learner} required onChange={(value) => setContentField('learner', value)} />
              <TextArea label={lbl('LBL_CONTENT_PART_2', 'Content part 2')} value={content.content_part_2} required onChange={(value) => setContentField('content_part_2', value)} />
              <TextField label={lbl('LBL_TRAINER', 'Trainer')} value={content.trainer} required onChange={(value) => setContentField('trainer', value)} />
              <TextField label={lbl('LBL_CERTIFICATE_NUMBER', 'Certificate number')} value={content.certificate_number} required onChange={(value) => setContentField('certificate_number', value)} />
              {vars ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">{lbl('LBL_REPLACEMENT_VARIABLES', 'Replacement variables')}</label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <div dangerouslySetInnerHTML={{ __html: vars }} />
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_STATUS', 'Status')}<span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select value={status} onChange={(event) => setStatus(Number(event.target.value))}>
                        <option value={1}>{lbl('LBL_ACTIVE', 'Active')}</option>
                        <option value={0}>{lbl('LBL_INACTIVE', 'Inactive')}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              {showAutoTranslate ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <label className="checkbox d-flex">
                      <input
                        type="checkbox"
                        checked={autoTranslate}
                        onChange={(event) => setAutoTranslate(event.target.checked)}
                      />
                      <i className="input-helper" />
                      {lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                    </label>
                  </div>
                </div>
              ) : null}
              <div className="col-md-12">
                <button type="submit" className="btn btn-brand" disabled={saving}>
                  {lbl('LBL_SAVE_CHANGES', 'Save changes')}
                </button>
                <button type="button" className="btn btn-brand" disabled={saving} onClick={() => void saveAndPreview()}>
                  {lbl('LBL_SAVE_&_PREVIEW', 'Save & Preview')}
                </button>
              </div>
            </div>
          )}
        </div>
      </form>
    </AdminModal>
  );
}

function TextField({
  label,
  value,
  required,
  onChange,
}: {
  label: string;
  value: string;
  required?: boolean;
  onChange: (value: string) => void;
}) {
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
          <div className="field_cover">
            <input value={value} required={required} onChange={(event) => onChange(event.target.value)} />
          </div>
        </div>
      </div>
    </div>
  );
}

function TextArea({
  label,
  value,
  required,
  onChange,
}: {
  label: string;
  value: string;
  required?: boolean;
  onChange: (value: string) => void;
}) {
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
          <div className="field_cover">
            <textarea rows={4} value={value} required={required} onChange={(event) => onChange(event.target.value)} />
          </div>
        </div>
      </div>
    </div>
  );
}
