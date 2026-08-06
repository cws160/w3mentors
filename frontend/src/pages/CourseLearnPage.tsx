import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { coursesApi, formatDuration, myCoursesApi, type LectureDetail } from '../api/client';
import { useAuth } from '../context/AuthContext';

export function CourseLearnPage() {
  const { courseId, lectureId } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [lecture, setLecture] = useState<LectureDetail | null>(null);
  const [progress, setProgress] = useState<{
    percent: number;
    is_completed: boolean;
  } | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [marking, setMarking] = useState(false);

  useEffect(() => {
    if (!courseId || !lectureId) return;
    setLoading(true);
    coursesApi
      .getLecture(Number(courseId), Number(lectureId))
      .then((res) => {
        setLecture(res.data.data);
        setProgress(res.data.progress);
      })
      .catch((err) => {
        const msg = err.response?.data?.message;
        setError(msg || 'Unable to load lecture.');
      })
      .finally(() => setLoading(false));
  }, [courseId, lectureId]);

  async function handleMarkComplete() {
    if (!courseId || !lectureId || !user) return;
    setMarking(true);
    try {
      const res = await myCoursesApi.markComplete(Number(courseId), Number(lectureId));
      setProgress({
        percent: res.data.progress.percent,
        is_completed: true,
      });
    } catch {
      setError('Could not save progress. Make sure you are enrolled.');
    } finally {
      setMarking(false);
    }
  }

  if (loading) return <p className="muted">Loading lecture...</p>;
  if (error || !lecture) return <p className="error">{error || 'Not found'}</p>;

  const primaryResource = lecture.resources?.[0];

  return (
    <section className="learn-page">
      <Link to={`/courses/${courseId}`} className="back-link">
        ← Back to course
      </Link>
      {lecture.section && <p className="muted">{lecture.section.title}</p>}
      <h1>{lecture.title}</h1>
      {lecture.details && <p className="lead">{lecture.details}</p>}

      <div className="player-card card">
        {primaryResource ? (
          <div className="video-placeholder">
            <p className="muted">Video resource: {primaryResource.name}</p>
            <code>{primaryResource.link}</code>
            <p className="muted small">
              MUX/YouTube embed integration will be added in a later phase.
            </p>
          </div>
        ) : (
          <p className="muted">No video attached to this lecture.</p>
        )}
        <div className="learn-meta">
          <span>{formatDuration(lecture.duration)}</span>
          {lecture.is_trial && <span className="badge">Free preview</span>}
          {progress && <span>{progress.percent.toFixed(0)}% course progress</span>}
        </div>
      </div>

      <div className="learn-actions">
        {user && !lecture.is_trial && !progress?.is_completed && (
          <button
            type="button"
            className="btn btn-primary"
            disabled={marking}
            onClick={handleMarkComplete}
          >
            {marking ? 'Saving...' : 'Mark as complete'}
          </button>
        )}
        {progress?.is_completed && <span className="badge badge-success">Completed</span>}
        {!user && (
          <button type="button" className="btn btn-secondary" onClick={() => navigate('/login')}>
            Sign in to track progress
          </button>
        )}
      </div>

      {lecture.resources && lecture.resources.length > 1 && (
        <div className="card">
          <h2>Resources</h2>
          <ul>
            {lecture.resources.map((r) => (
              <li key={r.id}>
                {r.name} ({formatDuration(r.duration)})
              </li>
            ))}
          </ul>
        </div>
      )}
    </section>
  );
}
