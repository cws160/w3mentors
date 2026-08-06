import { useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

type LanguageOption = { value: number; label: string };

type FormData = {
  id: number;
  name: string;
  language_id: number;
};

type Props = {
  requestId: number;
  onClose: () => void;
  onSaved: () => void;
};

/** Legacy dashboard/views/forum/tag-requests/form.php */
export function ForumTagRequestModal({ requestId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [languages, setLanguages] = useState<LanguageOption[]>([]);
  const [form, setForm] = useState<FormData>({ id: 0, name: '', language_id: 0 });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    setLoading(true);
    setError('');
    const path =
      requestId > 0
        ? `/dashboard/forum-tag-requests/${requestId}/form`
        : '/dashboard/forum-tag-requests/form';

    api
      .get<{ data: FormData; meta: { languages: LanguageOption[]; default_language_id?: number } }>(path)
      .then((res) => {
        setForm(res.data.data);
        setLanguages(res.data.meta.languages ?? []);
      })
      .catch((err: unknown) => {
        const msg =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_Something_went_wrong', 'Something went wrong.');
        setError(msg);
      })
      .finally(() => setLoading(false));
  }, [requestId, lbl]);

  const save = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      await api.post('/dashboard/forum-tag-requests', {
        ftagreq_id: form.id,
        ftagreq_name: form.name.trim(),
        ftagreq_language_id: form.language_id,
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_Something_went_wrong', 'Something went wrong.');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <div className="modal-header">
        <h4>{lbl('LBL_REQUEST_NEW_TAG', 'Request new tag')}</h4>
        <button
          type="button"
          className="btn-close w3mentorsmodalJs"
          data-bs-dismiss="modal"
          aria-label=""
          onClick={onClose}
        />
      </div>
      <div className="modal-body">
        {loading ? (
          <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : error && !form.name && form.id === 0 ? (
          <p className="text-danger">{error}</p>
        ) : (
          <form className="form" id="flashcardFrm" onSubmit={save}>
            <input type="hidden" name="ftagreq_id" value={form.id} />
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Tag', 'Tag')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="text"
                        className="form-control"
                        name="ftagreq_name"
                        id="ftagreq_name"
                        autoComplete="off"
                        value={form.name}
                        onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                        required
                        minLength={2}
                        maxLength={50}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Language', 'Language')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        name="ftagreq_language_id"
                        value={form.language_id || ''}
                        onChange={(e) =>
                          setForm((f) => ({ ...f, language_id: Number(e.target.value) }))
                        }
                        required
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {languages.map((opt) => (
                          <option key={opt.value} value={opt.value}>
                            {opt.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              {error && form.name && <p className="text-danger small col-md-12">{error}</p>}
              <div className="col-md-12">
                <div className="field-set form-buttons-group">
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="submit"
                        className="btn btn--primary"
                        name="btn_submit"
                        disabled={saving}
                        value={
                          saving
                            ? lbl('LBL_LOADING', 'Loading...')
                            : lbl('LBL_Request_Tag', 'Request tag')
                        }
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        )}
      </div>
    </>
  );
}
