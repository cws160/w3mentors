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

export type AdminCourseRequestModalType = 'view' | 'change-status';

type CourseRequestView = {
  id: number;
  course_id: number;
  status: number;
  status_label: string;
  remark: string;
  created_at: string;
  title: string;
  subtitle: string;
  details: string;
  price: number;
  duration: number;
  level: number;
  level_label: string;
  certificate: number;
  certificate_label: string;
  certificate_type: number;
  certificate_type_label: string;
  preview_video: string;
  category_name: string;
  subcategory_name: string;
  language_name: string;
  teacher_first_name: string;
  teacher_last_name: string;
  teacher_gender: string;
  teacher_email: string;
  learners: string[];
  learnings: string[];
  requirements: string[];
  search_tags: string[];
  quiz_title: string;
};

function formatDurationSeconds(seconds: number): string {
  if (seconds < 1) {
    return '00m';
  }
  const parts: string[] = [];
  const hrs = Math.floor(seconds / 3600);
  if (hrs > 0) {
    parts.push(`${hrs}h`);
  }
  const min = Math.floor((seconds % 3600) / 60);
  if (min > 0) {
    parts.push(`${min}m`);
  }
  return parts.length > 0 ? parts.join(' ') : '00m';
}

function DetailRow({ label, children }: { label: string; children: ReactNode }) {
  return (
    <tr>
      <th width="40%">{label}</th>
      <td>{children}</td>
    </tr>
  );
}

function ListItems({ items, emptyLabel }: { items: string[]; emptyLabel: string }) {
  if (items.length === 0) {
    return <>{emptyLabel}</>;
  }
  return (
    <ul className="mb-0">
      {items.map((item, index) => (
        <li key={`${item}-${index}`}>{item}</li>
      ))}
    </ul>
  );
}

type Props = {
  active: { type: AdminCourseRequestModalType; requestId: number } | null;
  onClose: () => void;
  onUpdated: () => void;
};

export function AdminCourseRequestModals({ active, onClose, onUpdated }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [viewData, setViewData] = useState<CourseRequestView | null>(null);
  const [statusValue, setStatusValue] = useState('');
  const [statusRemark, setStatusRemark] = useState('');
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
      const res = await adminApi.courseRequestView(id);
      setViewData(res.data.data as CourseRequestView);
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
    setStatusRemark('');
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
      await adminApi.updateCourseRequestStatus(requestId, {
        status: Number(statusValue),
        remark: statusRemark,
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
      : lbl('LBL_COURSE_APPROVAL_REQUEST_DETAIL', 'Course approval request detail');

  const modalSize = modalType === 'change-status' ? 'md' : 'lg';

  const onStatusChange = (value: string) => {
    setStatusValue(value);
    if (Number(value) !== STATUS_DECLINED) {
      setStatusRemark('');
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
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_REQUEST_INFORMATION', 'Request information')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <DetailRow label={lbl('LBL_REQUESTED_ON', 'Requested on')}>
                    {formatDate(viewData.created_at)}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_STATUS', 'Status')}>{viewData.status_label}</DetailRow>
                  {viewData.remark ? (
                    <DetailRow label={lbl('LBL_COMMENTS', 'Comments')}>
                      <span style={{ whiteSpace: 'pre-wrap' }}>{viewData.remark}</span>
                    </DetailRow>
                  ) : null}
                </tbody>
              </table>
            </div>
          </div>
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_COURSE_INFORMATION', 'Course information')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <DetailRow label={lbl('LBL_COURSE_TITLE', 'Course title')}>{viewData.title}</DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_SUB_TITLE', 'Course sub title')}>
                    {viewData.subtitle}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_CATEGORY', 'Course category')}>
                    {viewData.category_name || lbl('LBL_NA', 'N/A')}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_SUBCATEGORY', 'Course subcategory')}>
                    {viewData.subcategory_name || lbl('LBL_NA', 'N/A')}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_DETAIL', 'Course detail')}>
                    <div
                      className="editor-content"
                      dangerouslySetInnerHTML={{ __html: viewData.details }}
                    />
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_PRICE', 'Course price')}>
                    {viewData.price.toFixed(2)}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_DURATION', 'Course duration')}>
                    {formatDurationSeconds(viewData.duration)}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_LEVEL', 'Course level')}>
                    {viewData.level_label || lbl('LBL_NA', 'N/A')}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_LANGUAGE', 'Course language')}>
                    {viewData.language_name || lbl('LBL_NA', 'N/A')}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_CERTIFICATE', 'Course certificate')}>
                    {viewData.certificate_label}
                  </DetailRow>
                  {viewData.certificate === 1 && viewData.certificate_type > 0 ? (
                    <DetailRow label={lbl('LBL_COURSE_CERTIFICATE_TYPE', 'Course certificate type')}>
                      {viewData.certificate_type_label || lbl('LBL_NA', 'N/A')}
                    </DetailRow>
                  ) : null}
                  {viewData.quiz_title ? (
                    <DetailRow label={lbl('LBL_COURSE_QUIZ', 'Course quiz')}>{viewData.quiz_title}</DetailRow>
                  ) : null}
                  <DetailRow label={lbl('LBL_COURSE_TAGS', 'Course tags')}>
                    {viewData.search_tags.length > 0 ? (
                      <div className="course-tags">
                        {viewData.search_tags.map((tag) => (
                          <span key={tag} className="badge bg-fill-dark">
                            {tag}
                          </span>
                        ))}
                      </div>
                    ) : (
                      lbl('LBL_NA', 'N/A')
                    )}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_PREVIEW_VIDEO', 'Course preview video')}>
                    {viewData.preview_video ? (
                      <a
                        className="link-text link-underline"
                        href={`/manager/courses/video/${encodeURIComponent(viewData.preview_video)}`}
                        target="_blank"
                        rel="noreferrer"
                      >
                        {lbl('LBL_VIEW', 'View')}
                      </a>
                    ) : (
                      lbl('LBL_NA', 'N/A')
                    )}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_CONTENT', 'Course content')}>
                    <ListItems items={viewData.learnings} emptyLabel={lbl('LBL_NA', 'N/A')} />
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_LEARNERS', 'Course learners')}>
                    <ListItems items={viewData.learners} emptyLabel={lbl('LBL_NA', 'N/A')} />
                  </DetailRow>
                  <DetailRow label={lbl('LBL_COURSE_REQUIREMENTS', 'Course requirements')}>
                    <ListItems items={viewData.requirements} emptyLabel={lbl('LBL_NA', 'N/A')} />
                  </DetailRow>
                </tbody>
              </table>
            </div>
          </div>
          <div className="table-group">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_PROFILE_INFORMATION', 'Profile information')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <DetailRow label={lbl('LBL_FIRST_NAME', 'First name')}>
                    {viewData.teacher_first_name}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_LAST_NAME', 'Last name')}>
                    {viewData.teacher_last_name || '-'}
                  </DetailRow>
                  <DetailRow label={lbl('LBL_GENDER', 'Gender')}>{viewData.teacher_gender}</DetailRow>
                  <DetailRow label={lbl('LBL_EMAIL', 'Email')}>{viewData.teacher_email}</DetailRow>
                </tbody>
              </table>
            </div>
          </div>
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
                        name="coapre_status"
                        value={statusValue}
                        required
                        onChange={(e) => onStatusChange(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        <option value={String(STATUS_APPROVED)}>{lbl('LBL_APPROVED', 'Approved')}</option>
                        <option value={String(STATUS_DECLINED)}>{lbl('LBL_DECLINED', 'Declined')}</option>
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
                        name="coapre_remark"
                        rows={4}
                        value={statusRemark}
                        required={Number(statusValue) === STATUS_DECLINED}
                        onChange={(e) => setStatusRemark(e.target.value)}
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
