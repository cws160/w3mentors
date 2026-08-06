import { Link, NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export function Layout() {
  const { user, logout } = useAuth();

  return (
    <div className="app-shell">
      <header className="topbar">
        <Link to="/" className="brand">
          W3Mentors
        </Link>
        <nav className="nav">
          <NavLink to="/courses">Courses</NavLink>
          <NavLink to="/teachers">Teachers</NavLink>
          {user && <NavLink to="/my/courses">My Courses</NavLink>}
          {user && <NavLink to="/dashboard">Dashboard</NavLink>}
        </nav>
        <div className="auth-actions">
          {user ? (
            <>
              <span className="user-chip">{user.full_name}</span>
              <button type="button" className="btn btn-ghost" onClick={() => logout()}>
                Logout
              </button>
            </>
          ) : (
            <>
              <Link to="/login" className="btn btn-ghost">
                Login
              </Link>
              <Link to="/register" className="btn btn-primary">
                Sign up
              </Link>
            </>
          )}
        </div>
      </header>
      <main className="main-content">
        <Outlet />
      </main>
    </div>
  );
}
