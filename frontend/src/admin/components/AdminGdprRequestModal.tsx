import { FormEvent, useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

const STATUS_PENDING = 1;
const STATUS_DECLINED = 2;
const STATUS_APPROVED = 3;

type GdprRequestDetail = {
  id: number;
  user_id: number;
  full_name: string;
  reason: string;
  status: number;
  status_label: string;
  created_at: string;
  updated_at: string | null;
};

type Props = {
  requestId: number | null;
  onClose: () => void;
  onUpdated: () => void;
};

export function AdminGdprRequestModal({ requestId, onClose, onUpdated }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [data, setData] = useState<GdprRequestDetail | null>(null);
  const [statusValue, setStatusValue] = useState('');
  const [comment, setComment] = useState('');

  const formatDate = (value: string | null) => {
    if (!value) return lbl('LBL_NA', 'N/A');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const load = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    setSuccess('');
    try {
      const res = await adminApi.gdprRequestShow(id);
      const detail = res.data.data as unknown as GdprRequestDetail;
      setData(detail);
      setStatusValue('');
      setComment('');
    } catch (e: unknown) {
      setData(null);
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load request',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (requestId) {
      void load(requestId);
    } else {
      setData(null);
      setError('');
      setSuccess('');
    }
  }, [load, requestId]);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    if (!requestId || !statusValue) return;
    setSaving(true);
    setError('');
    setSuccess('');
    try {
      await adminApi.updateGdprRequestStatus(requestId, Number(statusValue), comment);
      setSuccess(lbl('LBL_UPDATED_SUCCESSFULLY', 'Updated successfully'));
      onUpdated();
      setTimeout(onClose, 600);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Update failed',
      );
    } finally {
      setSaving(false);
    }
  };

  const showComment = Number(statusValue) === STATUS_DECLINED;

  return (
    <AdminModal open={requestId !== null} title={lbl('LBL_REQUEST_DETAIL', 'Request detail')} size="md" onClose={onClose}>
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {success ? <div className="alert alert-success m-3">{success}</div> : null}

      {data && !loading ? (
        <div className="form-edit-body p-0">
          <table className="table table-coloum">
            <tbody>
              <tr>
                <th>{lbl('LBL_Username', 'Username')}:</th>
                <td>{data.full_name}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_Request_Added', 'Request added')}:</th>
                <td>{formatDate(data.created_at)}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_REQUEST_MODIFIED', 'Request modified')}:</th>
                <td>
                  {data.status === STATUS_PENDING
                    ? lbl('LBL_NA', 'N/A')
                    : formatDate(data.updated_at)}
                </td>
              </tr>
              <tr>
                <th>{lbl('LBL_Erasure_Request_Reason', 'Erasure request reason')}:</th>
                <td style={{ whiteSpace: 'pre-wrap' }}>{data.reason}</td>
              </tr>
            </tbody>
          </table>

          {data.status === STATUS_PENDING ? (
            <div className="table-group">
              <div className="table-group-head">
                <h6 className="mb-0">{lbl('LBL_Change_Status', 'Change status')}</h6>
              </div>
              <div className="table-group-body">
                <form className="form form_horizontal" onSubmit={submit}>
                  <div className="row">
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_REQUEST_STATUS', 'Request status')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <select
                            className="form-control"
                            value={statusValue}
                            onChange={(e) => setStatusValue(e.target.value)}
                            required
                          >
                            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                            <option value={STATUS_APPROVED}>{lbl('LBL_GDPR_APPROVED', 'Approved')}</option>
                            <option value={STATUS_DECLINED}>{lbl('LBL_GDPR_DECLINED', 'Declined')}</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    {showComment ? (
                      <div className="col-md-12" id="remarkField">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('LBL_COMMENT', 'Comment')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <textarea
                              className="form-control"
                              value={comment}
                              onChange={(e) => setComment(e.target.value)}
                              rows={4}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    ) : null}
                    <div className="col-md-12">
                      <button type="submit" className="btn btn-brand" disabled={saving}>
                        {lbl('LBL_UPDATE', 'Update')}
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          ) : null}
        </div>
      ) : null}
    </AdminModal>
  );
}
