import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  coursesApi,
  formatDuration,
  type Course,
  type CurriculumSection,
  type EnrollmentInfo,
  type IntendedLearners,
} from '../api/client';
import { useAuth } from '../context/AuthContext';

export function CourseDetailPage() {
  const { id } = useParams();
  const { user } = useAuth();
  const [course, setCourse] = useState<Course | null>(null);
  const [enrollment, setEnrollment] = useState<EnrollmentInfo | null>(null);
  const [curriculum, setCurriculum] = useState<CurriculumSection[]>([]);
  const [intended, setIntended] = useState<IntendedLearners | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!id) return;
    const courseId = Number(id);
    setLoading(true);
    setError('');

    Promise.all([
      coursesApi.get(courseId),
      coursesApi.curriculum(courseId),
      coursesApi.intendedLearners(courseId),
    ])
      .then(([courseRes, curriculumRes, intendedRes]) => {
        setCourse(courseRes.data.data);
        setEnrollment(courseRes.data.enrollment);
        setCurriculum(curriculumRes.data.data);
        setIntended(intendedRes.data.data);
      })
      .catch(() => setError('Course not found or API unavailable.'))
      .finally(() => setLoading(false));
  }, [id, user?.id]);

  if (loading) return <p className="muted">Loading course...</p>;
  if (error || !course) return <p className="error">{error || 'Not found'}</p>;

  return (
    <article className="detail-page">
      <Link to="/courses" className="back-link">
        ← Back to courses
      </Link>

      <div className="course-hero">
        <div>
          <h1>{course.title}</h1>
          {course.subtitle && <p className="lead">{course.subtitle}</p>}
          <div className="detail-stats">
            <span>{course.is_free ? 'Free' : `$${course.price.toFixed(0)}`}</span>
            <span>{course.sections} sections</span>
            <span>{course.lectures} lectures</span>
            <span>{formatDuration(course.duration)}</span>
            <span>{course.students} students</span>
            {course.ratings > 0 && <span>{course.ratings}★ rating</span>}
          </div>
          {course.teacher && <p className="muted">Instructor: {course.teacher.full_name}</p>}
        </div>
        <div className="course-actions card">
          {enrollment?.is_enrolled ? (
            <>
              <p className="badge badge-success">Enrolled</p>
              <p className="muted">{enrollment.progress_percent?.toFixed(0) ?? 0}% complete</p>
              <Link to={`/my/courses/${course.id}`} className="btn btn-primary">
                Continue learning
              </Link>
            </>
          ) : (
            <>
              <p className="muted">Purchase flow coming in the next phase.</p>
              {!user && (
                <Link to="/login" className="btn btn-primary">
                  Sign in to enroll
                </Link>
              )}
            </>
          )}
        </div>
      </div>

      {intended && (
        <section className="course-section">
          <h2>What you'll learn</h2>
          <ul className="check-list">
            {intended.learning_outcomes.map((item) => (
              <li key={item.id}>{item.text}</li>
            ))}
          </ul>
          {intended.requirements.length > 0 && (
            <>
              <h3>Requirements</h3>
              <ul>
                {intended.requirements.map((item) => (
                  <li key={item.id}>{item.text}</li>
                ))}
              </ul>
            </>
          )}
          {intended.target_audience.length > 0 && (
            <>
              <h3>Who this course is for</h3>
              <ul>
                {intended.target_audience.map((item) => (
                  <li key={item.id}>{item.text}</li>
                ))}
              </ul>
            </>
          )}
        </section>
      )}

      <section className="course-section">
        <h2>Course content</h2>
        <p className="muted">
          {curriculum.length} sections · {course.lectures} lectures ·{' '}
          {formatDuration(course.duration)}
        </p>
        <div className="curriculum">
          {curriculum.map((section) => (
            <details key={section.id} className="curriculum-section" open={section.order === 1}>
              <summary>
                <span className="curriculum-title">{section.title}</span>
                <span className="muted">
                  {section.lectures.length} lectures · {formatDuration(section.duration)}
                </span>
              </summary>
              <ul className="lecture-list">
                {section.lectures.map((lecture) => (
                  <li key={lecture.id} className={lecture.is_completed ? 'completed' : ''}>
                    <div className="lecture-row">
                      <span>{lecture.title}</span>
                      <span className="lecture-meta">
                        {lecture.is_trial && <span className="badge">Preview</span>}
                        {!lecture.is_accessible && <span className="badge badge-lock">Locked</span>}
                        {lecture.is_completed && <span className="badge badge-success">Done</span>}
                        <span className="muted">{formatDuration(lecture.duration)}</span>
                      </span>
                    </div>
                    {lecture.is_accessible && (
                      <Link
                        to={`/courses/${course.id}/learn/${lecture.id}`}
                        className="btn btn-ghost btn-sm"
                      >
                        {lecture.is_trial ? 'Preview' : 'Watch'}
                      </Link>
                    )}
                  </li>
                ))}
              </ul>
            </details>
          ))}
        </div>
      </section>

      {course.description && (
        <section className="course-section">
          <h2>Description</h2>
          <div className="prose">{course.description.slice(0, 2000)}</div>
        </section>
      )}
    </article>
  );
}
