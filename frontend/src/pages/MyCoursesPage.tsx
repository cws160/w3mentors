import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { formatDuration, myCoursesApi, type EnrolledCourse } from '../api/client';

export function MyCoursesPage() {
  const [courses, setCourses] = useState<EnrolledCourse[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    myCoursesApi
      .list()
      .then((res) => setCourses(res.data.data))
      .catch(() => setError('Could not load your courses.'))
      .finally(() => setLoading(false));
  }, []);

  return (
    <section>
      <h1>My courses</h1>
      <p className="lead">Courses you have purchased or been enrolled in.</p>

      {loading && <p className="muted">Loading...</p>}
      {error && <p className="error">{error}</p>}

      {!loading && courses.length === 0 && (
        <div className="card">
          <p className="muted">You have no enrolled courses yet.</p>
          <Link to="/courses" className="btn btn-primary">
            Browse courses
          </Link>
        </div>
      )}

      <div className="card-grid">
        {courses.map((item) => (
          <article key={item.enrollment_id} className="card">
            <h2>{item.course?.title || 'Course'}</h2>
            {item.course?.teacher && (
              <p className="muted">By {item.course.teacher.full_name}</p>
            )}
            <div className="progress-bar-wrap">
              <div
                className="progress-bar"
                style={{ width: `${Math.min(item.progress.percent, 100)}%` }}
              />
            </div>
            <p className="muted">{item.progress.percent.toFixed(0)}% complete</p>
            <div className="card-meta">
              <span>{item.course?.lectures ?? 0} lectures</span>
              {item.course?.duration ? (
                <span>{formatDuration(item.course.duration)}</span>
              ) : null}
            </div>
            <Link to={`/my/courses/${item.course?.id}`} className="btn btn-primary">
              {item.progress.percent > 0 ? 'Continue' : 'Start'}
            </Link>
          </article>
        ))}
      </div>
    </section>
  );
}
