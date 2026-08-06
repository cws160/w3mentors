import { useEffect } from 'react';
import { Link, Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { BRAND_NAME, siteLogoSrc } from '../../utils/branding';
import { useSite } from '../context/SiteContext';
import { ProfileAvatar } from '../components/ProfileAvatar';
import { DashboardSpriteIcon } from '../components/DashboardSpriteIcon';
import { bindDashboardUiHandlers } from '../lib/w3mentors-ui';
import { useDashboardStyles } from '../hooks/useDashboardStyles';
import { dashboardPath, dashboardRoleFromPath, type DashboardRole } from './dashboardPaths';
import { DashboardSidebar } from './DashboardSidebar';

export function DashboardShell() {
  const location = useLocation();
  const role = dashboardRoleFromPath(location.pathname);
  const { user, loading, logout } = useAuth();
  const { lbl, site } = useSite();

  useDashboardStyles(true);

  useEffect(() => {
    if (!role) return;
    document.body.className = `dashboard-${role} ${role} main-dashboard`;
    const cleanupDashboardUi = bindDashboardUiHandlers();
    return () => {
      document.body.className = '';
      cleanupDashboardUi?.();
    };
  }, [role]);

  if (!role) {
    return <Navigate to="/dashboard" replace />;
  }

  if (loading) {
    return <div className="page-loading">Loading...</div>;
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  if (role === 'teacher' && !user.is_teacher) {
    return <Navigate to={dashboardPath('learner')} replace />;
  }

  const loggedAsLabel = role === 'teacher'
    ? lbl('LBL_LOGGED_IN_AS_A_TEACHER', 'Logged in as a teacher')
    : lbl('LBL_LOGGED_IN_AS_A_LEARNER', 'Logged in as a learner');

  return (
    <div className="site">
      <aside className="sidebar">
        <div className="sidebar__secondary">
          <nav className="menu menu--secondary">
            <ul>
              <li className="menu__item menu__item-toggle">
                <a
                  href="#sidebar__primary"
                  className="menu__item-trigger trigger-js for-responsive"
                  title={lbl('LBL_MENU', 'Menu')}
                >
                  <span className="icon icon--menu">
                    <span className="toggle">
                      <span />
                    </span>
                  </span>
                  <span className="sr-only">{lbl('LBL_MENU', 'Menu')}</span>
                </a>
                <a
                  href="#sidebar__primary"
                  className="menu__item-trigger fullview-js for-desktop"
                  title={lbl('LBL_MENU', 'Menu')}
                >
                  <span className="icon icon--menu">
                    <span className="toggle">
                      <span />
                    </span>
                  </span>
                  <span className="sr-only">{lbl('LBL_MENU', 'Menu')}</span>
                </a>
              </li>
              <li className="menu__item menu__item-home">
                <Link to="/" className="menu__item-trigger" title={lbl('LBL_HOME', 'Home')}>
                  <DashboardSpriteIcon id="home" className="icon icon--home" />
                  <span className="sr-only">{lbl('LBL_HOME', 'Home')}</span>
                </Link>
              </li>
              <li className="menu__item menu__item-messaging">
                <Link
                  to={dashboardPath(role, 'chats')}
                  className="menu__item-trigger"
                  title={lbl('LBL_MESSAGING', 'Messaging')}
                >
                  <DashboardSpriteIcon id="message" className="icon icon--messaging" />
                  <span className="sr-only">{lbl('LBL_MESSAGING', 'Messaging')}</span>
                </Link>
              </li>
              <li className="menu__item menu__item-notifications">
                <Link
                  to={dashboardPath(role, 'notifications')}
                  className="menu__item-trigger"
                  title={lbl('LBL_NOTIFICATIONS', 'Notifications')}
                >
                  <DashboardSpriteIcon id="notification" className="icon icon--notificatons" />
                  <span className="sr-only">{lbl('LBL_NOTIFICATIONS', 'Notifications')}</span>
                </Link>
              </li>
              <li className="menu__item menu__item-logout">
                <a
                  href="#logout"
                  className="menu__item-trigger"
                  title={lbl('LBL_LOGOUT', 'Logout')}
                  onClick={(e) => {
                    e.preventDefault();
                    logout();
                  }}
                >
                  <DashboardSpriteIcon id="logout" className="icon icon--logout" />
                  <span className="sr-only">{lbl('LBL_LOGOUT', 'Logout')}</span>
                </a>
              </li>
            </ul>
          </nav>
        </div>
        <div id="sidebar__primary" className="sidebar__primary">
          <div className="sidebar__head">
            <figure className="logo">
              <Link to="/">
                <img src={siteLogoSrc()} alt={site?.name ?? BRAND_NAME} />
              </Link>
            </figure>
            <div className="profile">
              <a href="#profile-target" className="trigger-js profile__trigger">
                <div className="profile__meta d-flex align-items-center">
                  <div className="profile__media me-3">
                    <ProfileAvatar userId={user.id} firstName={user.first_name} size="medium" />
                  </div>
                  <div className="profile__details d-none d-md-block">
                    <h6 className="profile__title">{user.full_name || user.first_name}</h6>
                    <small className="color-black">{loggedAsLabel}</small>
                  </div>
                </div>
              </a>
              <div id="profile-target" className="profile__target">
                <div className="profile__target-details">
                  <div className="d-md-none">
                    <h6 className="profile__title">{user.full_name || user.first_name}</h6>
                    <small className="color-black">{loggedAsLabel}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="sidebar__body">
            <div className="sidebar__scroll">
              <div id="primary-nav" className="menu-offset">
                <DashboardSidebar role={role} />
              </div>
            </div>
          </div>
        </div>
      </aside>
      <main className="page">
        <Outlet context={{ role }} />
      </main>
    </div>
  );
}

export function useDashboardRole(): DashboardRole {
  const { pathname } = useLocation();
  return dashboardRoleFromPath(pathname) ?? 'learner';
}
