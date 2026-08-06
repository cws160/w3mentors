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

export function AdminEmailTemplateModal({ open, code, langId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [selectedLangId, setSelectedLangId] = useState(1);
  const [name, setName] = useState('');
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [vars, setVars] = useState('');
  const [active, setActive] = useState(1);
  const [layoutDirection, setLayoutDirection] = useState('ltr');
  const [showAutoTranslate, setShowAutoTranslate] = useState(false);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const reset = useCallback(() => {
    setSiteLanguages(contextLanguages);
    setSelectedLangId(langId || contextLanguages[0]?.id || 1);
    setName('');
    setSubject('');
    setBody('');
    setVars('');
    setActive(1);
    setLayoutDirection('ltr');
    setShowAutoTranslate(false);
    setAutoTranslate(false);
    setError('');
  }, [contextLanguages, langId]);

  const applyData = (data: Record<string, unknown>, nextLangId: number) => {
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? contextLanguages);
    setSelectedLangId(Number(data.etpl_lang_id ?? nextLangId));
    setName(String(data.etpl_name ?? ''));
    setSubject(String(data.etpl_subject ?? ''));
    setBody(String(data.etpl_body ?? ''));
    setVars(String(data.etpl_vars ?? ''));
    setActive(Number(data.etpl_status ?? 1));
    setLayoutDirection(String(data.layout_direction ?? 'ltr'));
    setShowAutoTranslate(Boolean(data.show_auto_translate));
    setAutoTranslate(false);
  };

  const loadLang = useCallback(
    async (nextLangId: number) => {
      if (!code) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.emailTemplateLangForm(code, nextLangId);
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

  const save = async () => {
    setSaving(true);
    setError('');
    try {
      await adminApi.emailTemplateLangUpdate(code, selectedLangId, {
        etpl_name: name.trim(),
        etpl_subject: subject.trim(),
        etpl_body: body,
        etpl_status: active,
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
      const res = await adminApi.previewEmailTemplate(code, selectedLangId);
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

  return (
    <AdminModal open={open} title={lbl('LBL_EMAIL_TEMPLATE_SETUP', 'Email template setup')} size="lg" onClose={onClose}>
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
                    <label className="field_label">{lbl('LBL_LANGUAGE', 'Language')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        value={selectedLangId}
                        onChange={(event) => void loadLang(Number(event.target.value))}
                      >
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
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_NAME', 'Name')}<span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input value={name} onChange={(event) => setName(event.target.value)} required />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_SUBJECT', 'Subject')}<span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input value={subject} onChange={(event) => setSubject(event.target.value)} required />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_BODY', 'Body')}<span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        dir={layoutDirection}
                        rows={12}
                        value={body}
                        onChange={(event) => setBody(event.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
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
                {code !== 'emails_header_footer_layout' ? (
                  <button
                    type="button"
                    className="btn btn-brand"
                    disabled={saving}
                    onClick={() => void saveAndPreview()}
                  >
                    {lbl('LBL_SAVE_&_PREVIEW', 'Save & Preview')}
                  </button>
                ) : null}
              </div>
            </div>
          )}
        </div>
      </form>
    </AdminModal>
  );
}
