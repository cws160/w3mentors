import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export function HomePage() {
  const { user } = useAuth();

  return (
    <section className="hero">
      <p className="eyebrow">Open learning platform</p>
      <h1>Learn with expert tutors and structured courses</h1>
      <p className="lead">
        W3Mentors runs on a modern Laravel API and React frontend — no IonCube or license
        files required.
      </p>
      <div className="hero-actions">
        <Link to="/courses" className="btn btn-primary">
          Browse courses
        </Link>
        <Link to="/teachers" className="btn btn-secondary">
          Find teachers
        </Link>
        {!user && (
          <Link to="/register" className="btn btn-ghost">
            Create account
          </Link>
        )}
      </div>
    </section>
  );
}
