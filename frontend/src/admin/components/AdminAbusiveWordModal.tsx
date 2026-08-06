import { type FormEvent, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  wordId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminAbusiveWordModal({ open, wordId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [keyword, setKeyword] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!open) {
      setKeyword('');
      setError('');
      return;
    }
    if (wordId < 1) {
      setKeyword('');
      setError('');
      return;
    }
    setLoading(true);
    setError('');
    void adminApi
      .abusiveWordShow(wordId)
      .then((res) => setKeyword(String(res.data.data?.abusive_keyword ?? '')))
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [lbl, open, wordId]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      await adminApi.abusiveWordSave(wordId, { abusive_keyword: keyword.trim() });
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

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_ABUSIVE_WORD_SETUP', 'Abusive word setup')}
      size="md"
      onClose={onClose}
    >
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
                    <label className="field_label">
                      {lbl('LBL_ABUSIVE_KEYWORD', 'Abusive keyword')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        name="abusive_keyword"
                        value={keyword}
                        onChange={(event) => setKeyword(event.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <button type="submit" className="btn btn-brand" disabled={saving}>
                  {lbl('LBL_SAVE', 'Save')}
                </button>
              </div>
            </div>
          )}
        </div>
      </form>
    </AdminModal>
  );
}
