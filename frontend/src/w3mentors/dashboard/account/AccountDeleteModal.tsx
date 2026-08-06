import { useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

type Props = {
  onClose: () => void;
  onSubmitted: () => void;
};

/** Legacy: application/views/cookie-consent/../account/delete-account-form.php */
export function AccountDeleteModal({ onClose, onSubmitted }: Props) {
  const { lbl } = useSite();
  const [reason, setReason] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const setUpGdprDelAcc = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!reason.trim()) return;
    setSaving(true);
    setError('');
    try {
      await api.post('/users/me/delete-account', { reason: reason.trim() });
      onSubmitted();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <div className="modal-header">
        <h5>{lbl('LBL_DELETE_ACCOUNT_FORM', '')}</h5>
        <button type="button" className="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label="" onClick={onClose} />
      </div>
      <div className="modal-body">
        <form id="delFrm" className="form" autoComplete="off" onSubmit={setUpGdprDelAcc}>
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label" htmlFor="gdpreq_reason">
                    {lbl('LBL_REASON_FOR_ERASURE', 'Reason for erasure')}
                    <span className="spn_must_field">*</span>
                  </label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <textarea
                      id="gdpreq_reason"
                      name="gdpreq_reason"
                      className="form-control"
                      value={reason}
                      onChange={(e) => setReason(e.target.value)}
                      required
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
          {error ? <p className="color-primary">{error}</p> : null}
          <div className="row">
            <div className="col-sm-12">
              <div className="field-set mb-0">
                <div className="field-wraper form-buttons-group">
                  <div className="field_cover">
                    <input
                      type="submit"
                      name="btn_submit"
                      form="delFrm"
                      className="btn btn--primary block-on-mobile"
                      value={saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SEND', 'Send')}
                      disabled={saving}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </>
  );
}
