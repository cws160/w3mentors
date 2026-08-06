import { useEffect, useState } from 'react';
import { teachersApi } from '../api/client';

type Teacher = {
  id: number;
  full_name: string;
  is_featured: boolean;
  last_seen?: string;
};

export function TeachersPage() {
  const [teachers, setTeachers] = useState<Teacher[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    setLoading(true);
    teachersApi
      .list({ search: search || undefined })
      .then((res) => setTeachers(res.data.data))
      .catch(() => setError('Could not load teachers.'))
      .finally(() => setLoading(false));
  }, [search]);

  return (
    <section>
      <div className="page-header">
        <h1>Teachers</h1>
        <input
          className="search-input"
          placeholder="Search teachers..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>
      {loading && <p className="muted">Loading teachers...</p>}
      {error && <p className="error">{error}</p>}
      <div className="card-grid">
        {teachers.map((teacher) => (
          <article key={teacher.id} className="card">
            <h2>{teacher.full_name}</h2>
            {teacher.is_featured && <span className="badge">Featured</span>}
          </article>
        ))}
      </div>
    </section>
  );
}
