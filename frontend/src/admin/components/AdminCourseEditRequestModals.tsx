import { FormEvent, useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

const STATUS_APPROVED = 1;
const STATUS_DECLINED = 2;

function apiErrorMessage(e: unknown, fallback: string) {
  const message = (e as { response?: { data?: { message?: string } } })?.response?.data?.message;
  if (!message || message.includes('SQLSTATE')) {
    return fallback;
  }
  return message;
}

export type AdminCourseEditRequestModalType = 'view' | 'change-status';

type CourseEditRequestView = {
  id: number;
  status: number;
  status_label: string;
  reason: string;
  created_at: string;
};

type Props = {
  active: { type: AdminCourseEditRequestModalType; requestId: number } | null;
  onClose: () => void;
  onUpdated: () => void;
};

export function AdminCourseEditRequestModals({ active, onClose, onUpdated }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [viewData, setViewData] = useState<CourseEditRequestView | null>(null);
  const [statusValue, setStatusValue] = useState('');
  const [statusComment, setStatusComment] = useState('');
  const [saving, setSaving] = useState(false);

  const requestId = active?.requestId ?? 0;
  const modalType = active?.type ?? null;

  const formatDate = (value: string) => {
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY hh:mm A') : value;
  };

  const loadView = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.courseEditRequestView(id);
      setViewData(res.data.data as CourseEditRequestView);
    } catch (e: unknown) {
      setError(apiErrorMessage(e, 'Failed to load request'));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!active) {
      return;
    }
    setViewData(null);
    setError('');
    setStatusValue('');
    setStatusComment('');
    setLoading(false);
    if (active.type === 'view') {
      void loadView(active.requestId);
    }
  }, [active, loadView]);

  const submitStatus = async (e: FormEvent) => {
    e.preventDefault();
    if (!requestId || !statusValue) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      await adminApi.updateCourseEditRequestStatus(requestId, {
        status: Number(statusValue),
        comment: statusComment,
      });
      onUpdated();
      onClose();
    } catch (err: unknown) {
      setError(apiErrorMessage(err, 'Failed to update status'));
    } finally {
      setSaving(false);
    }
  };

  if (!active) {
    return null;
  }

  const title =
    modalType === 'change-status'
      ? lbl('LBL_UPDATE_STATUS', 'Update status')
      : lbl('LBL_COURSE_EDIT_REQUEST_DETAIL', 'Course edit request detail');

  const modalSize = modalType === 'change-status' ? 'md' : 'lg';

  const onStatusChange = (value: string) => {
    setStatusValue(value);
    if (Number(value) !== STATUS_DECLINED) {
      setStatusComment('');
    }
  };

  const statusLabel = (status: number) => {
    switch (status) {
      case 0:
        return lbl('LBL_EDIT_REQUEST_PENDING', 'Edit request pending');
      case 1:
        return lbl('LBL_EDIT_REQUEST_APPROVED', 'Edit request approved');
      case 2:
        return lbl('LBL_EDIT_REQUEST_DECLINED', 'Edit request declined');
      default:
        return lbl('LBL_NA', 'N/A');
    }
  };

  return (
    <AdminModal open={Boolean(active)} title={title} onClose={onClose} size={modalSize}>
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}

      {modalType === 'view' && viewData ? (
        <div className="form-edit-body p-0">
          <table className="table table-coloum">
            <tbody>
              <tr>
                <th width="40%">{lbl('LBL_REQUESTED_ON', 'Requested on')}</th>
                <td>{formatDate(viewData.created_at)}</td>
              </tr>
              <tr>
                <th width="40%">{lbl('LBL_STATUS', 'Status')}</th>
                <td>{statusLabel(viewData.status)}</td>
              </tr>
              <tr>
                <th width="40%">{lbl('LBL_REQUEST_REASON', 'Request reason')}</th>
                <td style={{ whiteSpace: 'pre-wrap' }}>{viewData.reason}</td>
              </tr>
            </tbody>
          </table>
        </div>
      ) : null}

      {modalType === 'change-status' ? (
        <div className="card">
          <div className="card-body">
            <form className="form form_horizontal" onSubmit={submitStatus}>
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_STATUS', 'Status')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <select
                        className="form-control"
                        name="coedre_status"
                        value={statusValue}
                        required
                        onChange={(e) => onStatusChange(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        <option value={String(STATUS_APPROVED)}>
                          {lbl('LBL_EDIT_REQUEST_APPROVED', 'Edit request approved')}
                        </option>
                        <option value={String(STATUS_DECLINED)}>
                          {lbl('LBL_EDIT_REQUEST_DECLINED', 'Edit request declined')}
                        </option>
                      </select>
                    </div>
                  </div>
                </div>
                <div
                  className="col-md-12"
                  id="remarkField"
                  style={{ display: Number(statusValue) === STATUS_DECLINED ? 'block' : 'none' }}
                >
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
                        name="coedre_comment"
                        rows={4}
                        value={statusComment}
                        required={Number(statusValue) === STATUS_DECLINED}
                        onChange={(e) => setStatusComment(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
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
    </AdminModal>
  );
}
