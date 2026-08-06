import { type FormEvent, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type LanguageOption = { id: number; name: string };
type HttpCodeOption = { value: number; label: string };

type FormData = {
  seourl_id: number;
  seourl_original: string;
  seourl_httpcode: number;
  seourl_custom: Record<string, string>;
  http_codes: HttpCodeOption[];
  languages: LanguageOption[];
};

type Props = {
  open: boolean;
  seoUrlId: number;
  onClose: () => void;
  onSaved: () => void;
};

function httpCodeLabel(value: number, lbl: (key: string, fallback: string) => string): string {
  switch (value) {
    case 301:
      return lbl('LBL_301_REDIRECT_PERMANENTLY', '301 Redirect Permanently');
    case 302:
      return lbl('LBL_302_REDIRECT_TEMPRARY', '302 Redirect Temporary');
    default:
      return String(value);
  }
}

export function AdminUrlRewritingModal({ open, seoUrlId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [form, setForm] = useState<FormData | null>(null);

  useEffect(() => {
    if (!open) {
      setError('');
      setForm(null);
      return;
    }

    setLoading(true);
    void adminApi
      .urlRewritingForm(seoUrlId)
      .then((res) => {
        const data = res.data.data as FormData;
        setForm({
          ...data,
          seourl_httpcode: Number(data.seourl_httpcode) || 301,
        });
      })
      .catch(() => setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')))
      .finally(() => setLoading(false));
  }, [lbl, open, seoUrlId]);

  const setCustomUrl = (langId: number, value: string) => {
    if (!form) {
      return;
    }
    setForm({
      ...form,
      seourl_custom: {
        ...form.seourl_custom,
        [String(langId)]: value,
      },
    });
  };

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!form) {
      return;
    }

    setSaving(true);
    setError('');
    try {
      await adminApi.saveUrlRewriting({
        seourl_id: form.seourl_id,
        seourl_original: form.seourl_original.trim(),
        seourl_httpcode: form.seourl_httpcode,
        seourl_custom: form.seourl_custom,
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  const modalTitle =
    seoUrlId > 0
      ? lbl('LBL_EDIT', 'Edit')
      : lbl('LBL_ADD_NEW', 'Add new');

  return (
    <AdminModal open={open} onClose={onClose} title={modalTitle} size="md">
      {loading ? (
        <div className="table-processing loaderJs">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : form ? (
        <div className="form-edit-body">
          {error ? <div className="alert alert-danger">{error}</div> : null}
          <form className="form form_horizontal" id="frmSeoUrl" onSubmit={onSubmit}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_ORIGINAL_URL', 'Original URL')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        type="text"
                        name="seourl_original"
                        value={form.seourl_original}
                        onChange={(e) => setForm({ ...form, seourl_original: e.target.value })}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>

              {form.languages.map((lang) => (
                <div className="col-md-12" key={lang.id}>
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_CUSTOM_URL', 'Custom URL')} [{lang.name}]
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          type="text"
                          name={`seourl_custom[${lang.id}]`}
                          value={form.seourl_custom[String(lang.id)] ?? ''}
                          onChange={(e) => setCustomUrl(lang.id, e.target.value)}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
              ))}

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_HTTP_CODE', 'HTTP code')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        name="seourl_httpcode"
                        value={String(form.seourl_httpcode || '')}
                        onChange={(e) =>
                          setForm({ ...form, seourl_httpcode: Number(e.target.value) })
                        }
                        required
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {form.http_codes.map((code) => (
                          <option key={code.value} value={String(code.value)}>
                            {httpCodeLabel(code.value, lbl)}
                          </option>
                        ))}
                      </select>
                      <small className="text--small">
                        {lbl('LBL_Example_Custom_URL_Example', 'Example: my-custom-url')}
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="form-actions">
              <button type="submit" name="btn_submit" className="btn btn-primary" disabled={saving}>
                {lbl('LBL_Save_Changes', 'Save changes')}
              </button>
            </div>
          </form>
        </div>
      ) : null}
    </AdminModal>
  );
}
