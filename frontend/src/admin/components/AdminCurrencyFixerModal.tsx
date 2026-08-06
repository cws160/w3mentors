import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  onClose: () => void;
  onSaved: (lastSynced: string) => void;
};

export function AdminCurrencyFixerModal({ open, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [apiKey, setApiKey] = useState('');
  const [status, setStatus] = useState('0');
  const [info, setInfo] = useState('');

  useEffect(() => {
    if (!open) {
      setError('');
      setApiKey('');
      setStatus('0');
      setInfo('');
      return;
    }

    setLoading(true);
    void adminApi
      .currencyFixerConfig()
      .then((res) => {
        const data = res.data.data ?? {};
        setApiKey(String(data.api_key ?? ''));
        setStatus(String(data.status ?? 0));
        setInfo(String(data.info ?? ''));
      })
      .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
      .finally(() => setLoading(false));
  }, [lbl, open]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.currencyFixerSetup({
        api_key: apiKey,
        status: Number(status),
      });
      onSaved(String(res.data.data?.last_synced ?? ''));
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
      title={lbl('LBL_FIXER_CURRENCY_CONVERSION_CONFIGURATION', 'Fixer currency conversion configuration')}
      size="md"
      onClose={onClose}
    >
      <div className="form-edit-body">
        {loading ? (
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          <form className="form form_horizontal" onSubmit={onSubmit}>
            {error ? <div className="alert alert-danger">{error}</div> : null}
            {info ? <p dangerouslySetInnerHTML={{ __html: info }} /> : null}
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_FIXER_API_KEY', 'Fixer API key')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={apiKey}
                        onChange={(e) => setApiKey(e.target.value)}
                        required={status === '1'}
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
        )}
      </div>
    </AdminModal>
  );
}
