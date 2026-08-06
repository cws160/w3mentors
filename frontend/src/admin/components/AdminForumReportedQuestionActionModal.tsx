import { type FormEvent, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

const REPORT_ACCEPTED = 1;
const REPORT_CANCELLED = 2;

type Props = {
  open: boolean;
  reportId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminForumReportedQuestionActionModal({ open, reportId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [status, setStatus] = useState('');
  const [adminComments, setAdminComments] = useState('');

  useEffect(() => {
    if (!open) {
      setError('');
      setStatus('');
      setAdminComments('');
    }
  }, [open]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      await adminApi.forumReportedQuestionAction(reportId, {
        fquerep_status: Number(status),
        fquerep_admin_comments: adminComments.trim(),
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save action',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      onClose={onClose}
      title={lbl('LBL_ACTION_FORM', 'Action form')}
      size="md"
    >
      <div className="form-edit-body">
        {error ? <div className="alert alert-danger">{error}</div> : null}
        <form className="form form_horizontal" id="actionForm" onSubmit={onSubmit}>
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">
                    {lbl('LBL_TAKE_ACTION', 'Take action')}
                    <span className="spn_must_field">*</span>
                  </label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <select
                      className="form-control"
                      name="fquerep_status"
                      value={status}
                      onChange={(e) => setStatus(e.target.value)}
                      required
                    >
                      <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                      <option value={String(REPORT_ACCEPTED)}>{lbl('LBL_Accepted', 'Accepted')}</option>
                      <option value={String(REPORT_CANCELLED)}>{lbl('LBL_Cancelled', 'Cancelled')}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">
                    {lbl('LBL_ADMIN_COMMENT', 'Admin comment')}
                    <span className="spn_must_field">*</span>
                  </label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <textarea
                      className="form-control"
                      name="fquerep_admin_comments"
                      value={adminComments}
                      onChange={(e) => setAdminComments(e.target.value)}
                      required
                      minLength={10}
                      maxLength={2000}
                      rows={5}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="form-actions">
            <button type="submit" name="submit" className="btn btn-primary" disabled={saving}>
              {lbl('LBL_Save', 'Save')}
            </button>
          </div>
        </form>
      </div>
    </AdminModal>
  );
}
