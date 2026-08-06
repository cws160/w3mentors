import { type FormEvent, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';
import { AdminModal } from './AdminModal';

const TAG_REQ_APPROVED = 1;
const TAG_REQ_REJECTED = 2;

type Props = {
  open: boolean;
  requestId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminForumTagRequestStatusModal({ open, requestId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [status, setStatus] = useState('');
  const confirms = adminLegacyConfirms(lbl);

  useEffect(() => {
    if (!open) {
      setError('');
      setStatus('');
    }
  }, [open]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!status) {
      return;
    }
    if (!window.confirm(confirms.updateStatus)) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      await adminApi.updateForumTagRequestStatus(requestId, Number(status));
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to update status',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal open={open} onClose={onClose} title={lbl('LBL_TAG_STATUS', 'Tag status')} size="md">
      <div className="form-edit-body">
        {error ? <div className="alert alert-danger">{error}</div> : null}
        <form className="form form_horizontal" name="frmftagReqStatus" onSubmit={onSubmit}>
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">{lbl('LBL_REQUEST_STATUS', 'Request status')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <select
                      className="form-control"
                      name="ftagreq_status"
                      value={status}
                      onChange={(e) => setStatus(e.target.value)}
                      required
                    >
                      <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                      <option value={String(TAG_REQ_APPROVED)}>{lbl('LBL_Approved', 'Approved')}</option>
                      <option value={String(TAG_REQ_REJECTED)}>{lbl('LBL_Rejected', 'Rejected')}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="form-actions">
            <button type="submit" name="btn_submit" className="btn btn-primary" disabled={saving}>
              {lbl('LBL_UPDATE', 'Update')}
            </button>
          </div>
        </form>
      </div>
    </AdminModal>
  );
}
