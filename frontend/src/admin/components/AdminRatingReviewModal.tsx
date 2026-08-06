import { FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

const TYPE_COURSE = 3;

type RatingReviewDetail = {
  id: number;
  type: number;
  learner_name: string;
  teacher_name: string;
  teacher_deleted: boolean;
  rating: number;
  title: string;
  detail: string;
  status: number;
  status_label: string;
  course_name?: string | null;
};

type Props = {
  reviewId: number | null;
  isCourseReviews?: boolean;
  fallbackCourseName?: string;
  onClose: () => void;
  onUpdated: () => void;
};

function RatingStars({ rating }: { rating: number }) {
  const overall = Math.round(Number(rating) || 0);

  return (
    <ul className="rating list-inline">
      {[1, 2, 3, 4, 5].map((star) => (
        <li key={star} className={star <= overall ? 'active' : 'in-active'} style={{ padding: 0 }}>
          <svg
            xmlSpace="preserve"
            enableBackground="new 0 0 70 70"
            viewBox="0 0 70 70"
            height="18px"
            width="18px"
            y="0px"
            x="0px"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
          >
            <g>
              <path
                d="M51,42l5.6,24.6L35,53.6l-21.6,13L19,42L0,25.4l25.1-2.2L35,0l9.9,23.2L70,25.4L51,42z M51,42"
                fill={star <= overall ? '#ff3a59' : '#474747'}
              />
            </g>
          </svg>
        </li>
      ))}
    </ul>
  );
}

export function AdminRatingReviewModal({
  reviewId,
  isCourseReviews = false,
  fallbackCourseName = '',
  onClose,
  onUpdated,
}: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [data, setData] = useState<RatingReviewDetail | null>(null);
  const [statusValue, setStatusValue] = useState('');

  const load = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    setSuccess('');
    try {
      const res = await adminApi.ratingReviewShow(id);
      const review = res.data.data as unknown as RatingReviewDetail;
      setData(review);
      setStatusValue(String(review.status));
    } catch (e: unknown) {
      setData(null);
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load review',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (reviewId) {
      void load(reviewId);
    } else {
      setData(null);
      setError('');
      setSuccess('');
    }
  }, [load, reviewId]);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    if (!reviewId) return;
    setSaving(true);
    setError('');
    setSuccess('');
    try {
      await adminApi.updateRatingReviewStatus(reviewId, Number(statusValue));
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

  const isCourseReview =
    isCourseReviews ||
    Number(data?.type) === TYPE_COURSE ||
    Boolean(data?.course_name) ||
    Boolean(fallbackCourseName);
  const courseName = data?.course_name || fallbackCourseName || '';
  const modalTitle = isCourseReview
    ? lbl('LBL_COURSE_RATING_INFORMATION', 'Course rating information')
    : lbl('LBL_TEACHER_RATING_INFORMATION', 'Teacher rating information');

  return (
    <AdminModal open={reviewId !== null} title={modalTitle} size="md" onClose={onClose}>
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {success ? <div className="alert alert-success m-3">{success}</div> : null}

      {data && !loading ? (
        <div className="form-edit-body p-0">
          <div className="table-group mb-1">
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  {isCourseReview && courseName ? (
                    <tr>
                      <th width="40%">{lbl('LBL_COURSE_NAME', 'Course name')}</th>
                      <td>{courseName}</td>
                    </tr>
                  ) : null}
                  <tr>
                    <th width="40%">{lbl('LBL_REVIEWED_BY', 'Reviewed by')}</th>
                    <td>{data.learner_name}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_RATING', 'Rating')}</th>
                    <td>
                      <RatingStars rating={data.rating} />
                    </td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_REVIEW_TITLE', 'Review title')}</th>
                    <td style={{ whiteSpace: 'pre-wrap' }}>{data.title}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_REVIEW_COMMENTS', 'Review comments')}</th>
                    <td style={{ whiteSpace: 'pre-wrap' }}>{data.detail}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          {!data.teacher_deleted ? (
            <div className="table-group">
              <div className="table-group-head">
                <h6 className="mb-0">{lbl('LBL_CHANGE_STATUS', 'Change status')}</h6>
              </div>
              <div className="table-group-body">
                <form className="form form_horizontal" onSubmit={submit}>
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
                            value={statusValue}
                            onChange={(e) => setStatusValue(e.target.value)}
                            required
                          >
                            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                            <option value="0">{lbl('STATUS_PENDING', 'Pending')}</option>
                            <option value="1">{lbl('STATUS_APPROVED', 'Approved')}</option>
                            <option value="2">{lbl('STATUS_DECLINED', 'Declined')}</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-12">
                      <button type="submit" className="btn btn-brand" disabled={saving}>
                        {lbl('LBL_SAVE_CHANGES', 'Save changes')}
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
