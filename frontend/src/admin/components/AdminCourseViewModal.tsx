import { useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type CourseDetails = {
  id: number;
  title: string;
  subtitle: string;
  teacher_name: string;
  duration: number;
  category_name: string;
  subcategory_name: string;
  level: number;
  course_language: string;
  status: number;
  price: number;
  published_at: string;
  sections: number;
  lectures: number;
  reviews: number;
  students: number;
  certificate: number;
  certificate_type: number;
  quiz_id: number;
  quiz_title: string;
  ratings: number;
  preview_video: string;
  details: string;
};

type Props = {
  courseId: number | null;
  onClose: () => void;
};

function DetailRow({ label, value }: { label: string; value: string }) {
  return (
    <tr>
      <th width="40%">{label}</th>
      <td>{value || '—'}</td>
    </tr>
  );
}

function formatDuration(minutes: number): string {
  if (minutes < 1) return '—';
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  if (hours > 0 && mins > 0) return `${hours}h ${mins}m`;
  if (hours > 0) return `${hours}h`;
  return `${mins}m`;
}

export function AdminCourseViewModal({ courseId, onClose }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [data, setData] = useState<CourseDetails | null>(null);

  const load = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.courseShow(id);
      setData(res.data.data as CourseDetails);
    } catch (e: unknown) {
      setData(null);
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load course details',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!courseId) {
      setData(null);
      setError('');
      return;
    }
    void load(courseId);
  }, [courseId, load]);

  const publishedAt = data?.published_at
    ? moment(data.published_at).isValid()
      ? moment(data.published_at).format('MMM DD, YYYY HH:mm')
      : data.published_at
    : lbl('LBL_NA', 'N/A');

  return (
    <AdminModal
      open={courseId !== null}
      title={lbl('LBL_COURSE_DETAIL', 'Course detail')}
      size="lg"
      onClose={onClose}
    >
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {data && !loading ? (
        <div className="form-edit-body p-0">
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_BASIC_DETAILS', 'Basic details')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <DetailRow label={lbl('LBL_TITLE', 'Title')} value={data.title} />
                  <DetailRow label={lbl('LBL_SUB_TITLE', 'Sub title')} value={data.subtitle} />
                  <DetailRow label={lbl('LBL_TEACHER_NAME', 'Teacher name')} value={data.teacher_name} />
                  <DetailRow label={lbl('LBL_DURATION', 'Duration')} value={formatDuration(data.duration)} />
                  <DetailRow label={lbl('LBL_CATEGORY', 'Category')} value={data.category_name} />
                  <DetailRow
                    label={lbl('LBL_SUB_CATEGORY', 'Sub category')}
                    value={data.subcategory_name || lbl('LBL_NA', 'N/A')}
                  />
                  <DetailRow
                    label={lbl('LBL_LEVEL', 'Level')}
                    value={data.level > 0 ? String(data.level) : lbl('LBL_NA', 'N/A')}
                  />
                  <DetailRow label={lbl('LBL_COURSE_LANGUAGE', 'Course language')} value={data.course_language} />
                  <DetailRow
                    label={lbl('LBL_STATUS', 'Status')}
                    value={data.status === 3 ? lbl('LBL_PUBLISHED', 'Published') : String(data.status)}
                  />
                  <DetailRow label={lbl('LBL_PRICE', 'Price')} value={data.price.toFixed(2)} />
                  <DetailRow label={lbl('LBL_PUBLISHED_ON', 'Published on')} value={publishedAt} />
                  <DetailRow label={lbl('LBL_SECTIONS', 'Sections')} value={String(data.sections)} />
                  <DetailRow label={lbl('LBL_LECTURES', 'Lectures')} value={String(data.lectures)} />
                  <DetailRow label={lbl('LBL_REVIEWS', 'Reviews')} value={String(data.reviews)} />
                  <DetailRow label={lbl('LBL_STUDENTS', 'Students')} value={String(data.students)} />
                  <DetailRow
                    label={lbl('LBL_CERTIFICATE', 'Certificate')}
                    value={data.certificate === 1 ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No')}
                  />
                  <DetailRow label={lbl('LBL_RATINGS', 'Ratings')} value={String(data.ratings)} />
                  {data.quiz_id > 0 ? (
                    <DetailRow label={lbl('LBL_COURSE_QUIZ', 'Course quiz')} value={data.quiz_title} />
                  ) : null}
                </tbody>
              </table>
            </div>
          </div>
          <div className="table-group">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_OTHER_DETAILS', 'Other details')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  {data.preview_video ? (
                    <tr>
                      <th width="40%">{lbl('LBL_PREVIEW_VIDEO', 'Preview video')}</th>
                      <td>
                        <a
                          className="link-text link-underline"
                          href={`/manager/courses/video/${encodeURIComponent(data.preview_video)}`}
                          target="_blank"
                          rel="noreferrer"
                        >
                          {lbl('LBL_VIEW', 'View')}
                        </a>
                      </td>
                    </tr>
                  ) : null}
                  <tr>
                    <th width="40%">{lbl('LBL_DESCRIPTION', 'Description')}</th>
                    <td>
                      <div
                        className="editor-content"
                        dangerouslySetInnerHTML={{ __html: data.details }}
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      ) : null}
    </AdminModal>
  );
}
