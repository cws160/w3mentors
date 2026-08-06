import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  formatDuration,
  myCoursesApi,
  type CurriculumSection,
  type EnrolledCourse,
} from '../api/client';

export function MyCourseDetailPage() {
  const { id } = useParams();
  const [enrollment, setEnrollment] = useState<EnrolledCourse | null>(null);
  const [curriculum, setCurriculum] = useState<CurriculumSection[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [starting, setStarting] = useState(false);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    myCoursesApi
      .get(Number(id))
      .then((res) => {
        setEnrollment(res.data.data);
        setCurriculum(res.data.curriculum);
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Could not load course.');
      })
      .finally(() => setLoading(false));
  }, [id]);

  async function handleStart() {
    if (!id) return;
    setStarting(true);
    try {
      await myCoursesApi.start(Number(id));
      const res = await myCoursesApi.get(Number(id));
      setEnrollment(res.data.data);
      setCurriculum(res.data.curriculum);
    } finally {
      setStarting(false);
    }
  }

  if (loading) return <p className="muted">Loading...</p>;
  if (error || !enrollment?.course) return <p className="error">{error || 'Not found'}</p>;

  const course = enrollment.course;
  const nextLecture = curriculum
    .flatMap((s) => s.lectures)
    .find((l) => l.is_accessible && !l.is_completed);

  return (
    <section>
      <Link to="/my/courses" className="back-link">
        ← My courses
      </Link>
      <h1>{course.title}</h1>
      <div className="progress-bar-wrap large">
        <div
          className="progress-bar"
          style={{ width: `${Math.min(enrollment.progress.percent, 100)}%` }}
        />
      </div>
      <p className="muted">{enrollment.progress.percent.toFixed(0)}% complete</p>

      <div className="hero-actions">
        {nextLecture ? (
          <Link
            to={`/courses/${course.id}/learn/${nextLecture.id}`}
            className="btn btn-primary"
          >
            {enrollment.progress.percent > 0 ? 'Continue' : 'Start'} next lecture
          </Link>
        ) : (
          <span className="badge badge-success">Course completed</span>
        )}
        {enrollment.progress.percent === 0 && (
          <button
            type="button"
            className="btn btn-secondary"
            disabled={starting}
            onClick={handleStart}
          >
            {starting ? 'Starting...' : 'Initialize progress'}
          </button>
        )}
        <Link to={`/courses/${course.id}`} className="btn btn-ghost">
          Course overview
        </Link>
      </div>

      <h2 className="section-title">Curriculum</h2>
      <div className="curriculum">
        {curriculum.map((section) => (
          <details key={section.id} className="curriculum-section">
            <summary>
              <span className="curriculum-title">{section.title}</span>
              <span className="muted">{formatDuration(section.duration)}</span>
            </summary>
            <ul className="lecture-list">
              {section.lectures.map((lecture) => (
                <li key={lecture.id} className={lecture.is_completed ? 'completed' : ''}>
                  <Link to={`/courses/${course.id}/learn/${lecture.id}`}>{lecture.title}</Link>
                  <span className="muted">{formatDuration(lecture.duration)}</span>
                </li>
              ))}
            </ul>
          </details>
        ))}
      </div>
    </section>
  );
}
