import { FormEvent, ReactNode, useCallback, useEffect, useState } from 'react';
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

export type AdminCourseRefundRequestModalType = 'view' | 'change-status';

type RefundRequestView = {
  id: number;
  status: number;
  status_label: string;
  remark: string;
  comment: string;
  created_at: string;
  course_title: string;
  course_price: number;
  order_discount: number;
  order_reward_discount: number;
  order_amount: number;
  completed_progress: number;
  course_duration_label: string;
  course_status_label: string;
  learner_name: string;
  email_username: string;
  child: string | null;
};

type Props = {
  active: { type: AdminCourseRefundRequestModalType; requestId: number } | null;
  onClose: () => void;
  onUpdated: () => void;
};

function DetailRow({ label, children }: { label: string; children: ReactNode }) {
  return (
    <tr>
      <th width="40%">{label}</th>
      <td>{children}</td>
    </tr>
  );
}

function SectionTable({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="table-group mb-1">
      <div className="table-group-head">
        <h6 className="mb-0">{title}</h6>
      </div>
      <div className="table-group-body">
        <table className="table table-coloum">
          <tbody>{children}</tbody>
        </table>
      </div>
    </div>
  );
}

export function AdminCourseRefundRequestModals({ active, onClose, onUpdated }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [viewData, setViewData] = useState<RefundRequestView | null>(null);
  const [completedProgress, setCompletedProgress] = useState(0);
  const [statusValue, setStatusValue] = useState('');
  const [statusComment, setStatusComment] = useState('');
  const [saving, setSaving] = useState(false);

  const requestId = active?.requestId ?? 0;
  const modalType = active?.type ?? null;

  const formatDate = (value: string) => {
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY hh:mm A') : value;
  };

  const formatMoney = (value: number) => {
    const amount = Number(value) || 0;
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(amount);
  };

  const loadView = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.courseRefundRequestView(id);
      setViewData(res.data.data as unknown as RefundRequestView);
    } catch (e: unknown) {
      setViewData(null);
      setError(apiErrorMessage(e, 'Failed to load request'));
    } finally {
      setLoading(false);
    }
  }, []);

  const loadStatusForm = useCallback(async (id: number) => {
    setError('');
    setCompletedProgress(0);
    try {
      const res = await adminApi.courseRefundRequestStatusForm(id);
      const data = res.data.data as { completed_progress?: number };
      setCompletedProgress(Number(data.completed_progress ?? 0));
    } catch {
      setCompletedProgress(0);
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
    setCompletedProgress(0);
    setLoading(false);

    if (active.type === 'view') {
      void loadView(active.requestId);
      return;
    }

    if (active.type === 'change-status') {
      void loadStatusForm(active.requestId);
    }
  }, [active, loadView, loadStatusForm]);

  const submitStatus = async (e: FormEvent) => {
    e.preventDefault();
    if (!requestId || !statusValue) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      await adminApi.updateCourseRefundRequestStatus(requestId, {
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
      : lbl('LBL_COURSE_REFUND_REQUEST_DETAIL', 'Course refund request detail');

  const modalSize = modalType === 'change-status' ? 'md' : 'lg';

  const progressNote = lbl(
    'LBL_NOTE:_LEARNER_HAS_ALREADY_COMPLETED_{percent}%_OF_THE_COURSE',
    `Note: Learner has already completed ${completedProgress}% of the course`,
  ).replace('{percent}', String(completedProgress));

  const onStatusChange = (value: string) => {
    setStatusValue(value);
    if (Number(value) !== STATUS_DECLINED) {
      setStatusComment('');
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
          <SectionTable title={lbl('LBL_REQUEST_INFORMATION', 'Request information')}>
            <DetailRow label={lbl('LBL_REQUESTED_ON', 'Requested on')}>
              {formatDate(viewData.created_at)}
            </DetailRow>
            <DetailRow label={lbl('LBL_STATUS', 'Status')}>{viewData.status_label}</DetailRow>
            <DetailRow label={lbl('LBL_COMMENTS', 'Comments')}>
              <span style={{ whiteSpace: 'pre-wrap' }}>
                {viewData.remark || lbl('LBL_NA', 'NA')}
              </span>
            </DetailRow>
            <DetailRow label={lbl('LBL_DECLINE_REASON/COMMENTS', 'Decline reason/comments')}>
              <span style={{ whiteSpace: 'pre-wrap' }}>
                {viewData.comment || lbl('LBL_NA', 'NA')}
              </span>
            </DetailRow>
          </SectionTable>

          <SectionTable title={lbl('LBL_COURSE_INFORMATION', 'Course information')}>
            <DetailRow label={lbl('LBL_COURSE_TITLE', 'Course title')}>{viewData.course_title}</DetailRow>
            <DetailRow label={lbl('LBL_COURSE_PRICE', 'Course price')}>
              {formatMoney(viewData.course_price)}
            </DetailRow>
            <DetailRow label={lbl('LBL_DISCOUNT', 'Course discount')}>
              {formatMoney(viewData.order_discount)}
            </DetailRow>
            <DetailRow label={lbl('LBL_REWARD_DISCOUNT', 'Course reward discount')}>
              {formatMoney(viewData.order_reward_discount)}
            </DetailRow>
            <DetailRow label={lbl('LBL_COURSE_PURCHASED_PRICE', 'Course purchased price')}>
              {formatMoney(viewData.order_amount)}
            </DetailRow>
            <DetailRow label={lbl('LBL_COURSE_PROGRESS', 'Course progress')}>
              {viewData.completed_progress}%
            </DetailRow>
            <DetailRow label={lbl('LBL_COURSE_DURATION', 'Course duration')}>
              {viewData.course_duration_label}
            </DetailRow>
            <DetailRow label={lbl('LBL_STATUS', 'Status')}>{viewData.course_status_label}</DetailRow>
          </SectionTable>

          <SectionTable title={lbl('LBL_PROFILE_INFORMATION', 'Profile information')}>
            <DetailRow label={lbl('LBL_LEARNER_NAME', 'Learner name')}>{viewData.learner_name}</DetailRow>
            <DetailRow label={lbl('LBL_EMAIL/USERNAME', 'Email/Username')}>
              {viewData.email_username}
            </DetailRow>
            <DetailRow label={lbl('LBL_CHILD', 'Child')}>
              {viewData.child ? viewData.child : lbl('LBL_NA', 'NA')}
            </DetailRow>
          </SectionTable>
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
                        name="corere_status"
                        value={statusValue}
                        required
                        onChange={(e) => onStatusChange(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        <option value={String(STATUS_APPROVED)}>
                          {lbl('LBL_REFUND_APPROVED', 'Refund approved')}
                        </option>
                        <option value={String(STATUS_DECLINED)}>
                          {lbl('LBL_REFUND_DECLINED', 'Refund declined')}
                        </option>
                      </select>
                      <small>{progressNote}</small>
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
                        name="corere_comment"
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
