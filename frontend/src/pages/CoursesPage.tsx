import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { coursesApi, formatDuration, type Course } from '../api/client';

const SORT_OPTIONS = [
  { value: 'newest', label: 'Newest' },
  { value: 'popular', label: 'Most popular' },
  { value: 'rating', label: 'Top rated' },
  { value: 'price_asc', label: 'Price: low to high' },
  { value: 'price_desc', label: 'Price: high to low' },
];

export function CoursesPage() {
  const [courses, setCourses] = useState<Course[]>([]);
  const [search, setSearch] = useState('');
  const [sort, setSort] = useState('newest');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    setLoading(true);
    coursesApi
      .list({ search: search || undefined, sort })
      .then((res) => setCourses(res.data.data))
      .catch(() => setError('Could not load courses. Is the API running?'))
      .finally(() => setLoading(false));
  }, [search, sort]);

  return (
    <section>
      <div className="page-header">
        <h1>Courses</h1>
        <div className="filters">
          <input
            className="search-input"
            placeholder="Search courses..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <select value={sort} onChange={(e) => setSort(e.target.value)}>
            {SORT_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>
      </div>
      {loading && <p className="muted">Loading courses...</p>}
      {error && <p className="error">{error}</p>}
      <div className="card-grid">
        {courses.map((course) => (
          <article key={course.id} className="card">
            <h2>{course.title || course.slug}</h2>
            {course.subtitle && <p className="muted clamp-2">{course.subtitle}</p>}
            <div className="card-meta">
              <span>{course.is_free ? 'Free' : `$${course.price.toFixed(0)}`}</span>
              <span>{course.lectures} lectures</span>
              <span>{formatDuration(course.duration)}</span>
              {course.ratings > 0 && <span>{course.ratings}★</span>}
            </div>
            {course.teacher && <p className="muted">By {course.teacher.full_name}</p>}
            <Link to={`/courses/${course.id}`} className="btn btn-secondary">
              View course
            </Link>
          </article>
        ))}
      </div>
    </section>
  );
}
