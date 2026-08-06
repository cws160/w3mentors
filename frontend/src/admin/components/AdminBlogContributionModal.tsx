import { type FormEvent, useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type ContributionDetail = {
  bcontributions_id: number;
  bcontributions_author_first_name: string;
  bcontributions_author_last_name: string;
  bcontributions_author_email: string;
  bcontributions_author_phone: string;
  bcontributions_status: number;
  bcontributions_added_on: string;
  attached_file?: {
    file_id: number;
    file_name: string;
    download_url: string;
  } | null;
};

type Props = {
  open: boolean;
  contributionId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminBlogContributionModal({ open, contributionId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [detail, setDetail] = useState<ContributionDetail | null>(null);
  const [status, setStatus] = useState('0');

  const reset = useCallback(() => {
    setError('');
    setDetail(null);
    setStatus('0');
  }, []);

  const statusLabel = (value: number) => {
    if (value === 1) return lbl('LBL_APPROVED', 'Approved');
    if (value === 2) return lbl('LBL_POSTED', 'Posted');
    if (value === 3) return lbl('LBL_REJECTED', 'Rejected');
    return lbl('LBL_PENDING', 'Pending');
  };

  useEffect(() => {
    if (!open || contributionId < 1) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .blogContributionShow(contributionId)
      .then((res) => {
        const data = res.data.data as ContributionDetail;
        setDetail(data);
        setStatus(String(data.bcontributions_status ?? 0));
      })
      .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
      .finally(() => setLoading(false));
  }, [contributionId, lbl, open, reset]);

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'NA');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const onSaveStatus = async (e: FormEvent) => {
    e.preventDefault();
    if (contributionId < 1) return;
    setSaving(true);
    setError('');
    try {
      await adminApi.updateBlogContributionStatus(contributionId, { bcontributions_status: Number(status) });
      onSaved();
      onClose();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to save')
          : 'Unable to save';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_Contribution_Detail', 'Contribution detail')}
      size="lg"
      onClose={onClose}
    >
      {error ? <div className="alert alert-danger m-4">{error}</div> : null}
      {loading ? (
        <div className="table-processing loaderJs">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : detail ? (
        <div className="form-edit-body p-0">
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_Details', 'Details')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <tr>
                    <th style={{ width: '35%' }}>{lbl('LBL_FULL_NAME', 'Full name')}</th>
                    <td>
                      {detail.bcontributions_author_first_name} {detail.bcontributions_author_last_name}
                    </td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_EMAIL', 'Email')}</th>
                    <td>{detail.bcontributions_author_email}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_PHONE', 'Phone')}</th>
                    <td>{detail.bcontributions_author_phone}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_POSTED_ON', 'Posted on')}</th>
                    <td>{formatDate(detail.bcontributions_added_on)}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                    <td>{statusLabel(detail.bcontributions_status)}</td>
                  </tr>
                  {detail.attached_file ? (
                    <tr>
                      <th>{lbl('LBL_ATTACHED_FILE', 'Attached file')}</th>
                      <td>
                        <a
                          className="link-text"
                          href={detail.attached_file.download_url}
                          target="_blank"
                          rel="noreferrer"
                        >
                          {detail.attached_file.file_name}
                        </a>
                      </td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          </div>
          <div className="table-group">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_Update_Status', 'Update status')}</h6>
            </div>
            <div className="table-group-body">
              <form className="form form_horizontal" onSubmit={onSaveStatus}>
                <div className="row">
                  <div className="col-sm-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">{lbl('LBL_Contribution_Status', 'Contribution status')}</label>
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <select
                            className="form-control"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                          >
                            <option value="0">{lbl('LBL_PENDING', 'Pending')}</option>
                            <option value="1">{lbl('LBL_APPROVED', 'Approved')}</option>
                            <option value="2">{lbl('LBL_POSTED', 'Posted')}</option>
                            <option value="3">{lbl('LBL_REJECTED', 'Rejected')}</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="form-actions">
                  <button type="submit" name="btn_submit" className="btn btn-brand" disabled={saving}>
                    {saving ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait') : lbl('LBL_SAVE', 'Save')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      ) : null}
    </AdminModal>
  );
}
