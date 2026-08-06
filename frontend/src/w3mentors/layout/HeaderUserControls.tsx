import { Link, useLocation } from 'react-router-dom';
import type { User } from '../../api/client';
import { DashboardSpriteIcon } from '../components/DashboardSpriteIcon';
import { ProfileAvatar } from '../components/ProfileAvatar';
import { dashboardUrl } from '../utils/dashboard';

type Props = {
  user: User;
  lbl: (key: string, fallback?: string) => string;
  onLogout: () => void;
  coursesEnabled?: boolean;
};

type MenuItem = {
  icon: string;
  label: string;
  href: string;
  onClick?: (e: React.MouseEvent<HTMLAnchorElement>) => void;
};

export function HeaderUserControls({ user, lbl, onLogout, coursesEnabled = true }: Props) {
  const isTeacher = user.is_teacher;
  const dashBase = isTeacher ? '/dashboard/teacher' : '/dashboard/learner';

  const menuItems: MenuItem[] = [
    ...(isTeacher
      ? [
          { icon: 'dashboard', label: lbl('LBL_Dashboard', 'Dashboard'), href: '/dashboard/teacher' },
          { icon: 'students', label: lbl('LBL_My_Students', 'My Students'), href: '/dashboard/teacher/students' },
        ]
      : [
          { icon: 'dashboard', label: lbl('LBL_Dashboard', 'Dashboard'), href: '/dashboard/learner' },
          { icon: 'students', label: lbl('LBL_My_Teachers', 'My Teachers'), href: '/dashboard/learner/teachers' },
        ]),
    {
      icon: 'lessons',
      label: lbl('LBL_Lessons', 'Lessons'),
      href: isTeacher ? '/dashboard/teacher/lessons' : '/dashboard/learner/lessons',
    },
    {
      icon: 'group-classes',
      label: lbl('LBL_Classes', 'Classes'),
      href: isTeacher ? '/dashboard/teacher/classes' : '/dashboard/learner/classes',
    },
    ...(coursesEnabled
      ? [
          {
            icon: 'all-courses',
            label: lbl('LBL_Courses', 'Courses'),
            href: isTeacher ? '/dashboard/teacher/courses' : '/dashboard/learner/courses',
          },
        ]
      : []),
    {
      icon: 'settings',
      label: lbl('LBL_Settings', 'Settings'),
      href: isTeacher ? '/dashboard/teacher/account' : '/dashboard/learner/account',
    },
    {
      icon: 'logout',
      label: lbl('LBL_Logout', 'Logout'),
      href: dashboardUrl('account/logout'),
      onClick: (e) => {
        e.preventDefault();
        onLogout();
      },
    },
  ];

  return (
    <>
      <div className="header-controls__item header--notification d-none d-md-block">
        <Link
          to={`${dashBase}/notifications`}
          className="header-controls__action btn btn--equal btn-round mobile-action"
          title={lbl('LBL_NOTIFICATIONS', 'Notifications')}
        >
          <span className="notification-count-js" />
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
            <path d="M20 17h2v2H2v-2h2v-7a8 8 0 1 1 16 0v7zm-2 0v-7a6 6 0 1 0-12 0v7h12zm-9 4h6v2H9v-2z" />
          </svg>
          <span className="mobile-action-label d-md-none d-block">
            {lbl('LBL_NOTIFICATIONS', 'Notifications')}
          </span>
        </Link>
      </div>
      <div className="header-controls__item header--message d-md-block">
        <Link
          to={`${dashBase}/chats`}
          className="header-controls__action btn btn--equal btn-round mobile-action"
          title={lbl('LBL_MESSAGES', 'Messages')}
        >
          <span className="message-count-js" />
          <svg
            xmlns="http://www.w3.org/2000/svg"
            xmlnsXlink="http://www.w3.org/1999/xlink"
            width="24.139"
            height="19.182"
            viewBox="0 0 24.139 19.182"
          >
            <g>
              <path d="M12.082,19.181q-4.232,0-8.464,0A3.382,3.382,0,0,1,.005,15.567q-.011-5.976,0-11.952A3.372,3.372,0,0,1,3.618,0Q12.11,0,20.6,0a3.359,3.359,0,0,1,3.525,3.469q.024,6.119,0,12.238A3.36,3.36,0,0,1,20.6,19.18q-4.26.007-8.521,0M22.239,3.309c-.235.033-.343.231-.484.373q-3.562,3.553-7.117,7.113a3.406,3.406,0,0,1-5.155-.013Q5.948,7.243,2.407,3.71c-.151-.151-.273-.355-.551-.414-.013.2-.034.367-.034.535q0,5.8,0,11.605c0,1.363.581,1.928,1.961,1.928q8.261,0,16.522,0c1.446,0,2-.55,2-2q0-5.688,0-11.377a1.3,1.3,0,0,0-.07-.676M3.169,1.847c.217.231.342.369.473.5q3.495,3.5,6.991,6.993c1.062,1.063,1.8,1.069,2.862.01q3.517-3.515,7.028-7.036c.122-.123.323-.208.311-.467Z" transform="translate(0 0)" />
            </g>
          </svg>
          <span className="mobile-action-label d-md-none d-block">
            {lbl('LBL_MESSAGES', 'Messages')}
          </span>
        </Link>
      </div>
      <div className="header-controls__item header-action">
        <div className="header__action">
          <a
            href="#HEADER-SEARCH"
            className="btn btn--equal btn-round btn--search search-trigger trigger-js"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="19.247" height="19.247" viewBox="0 0 19.247 19.247">
              <path d="M18.98,17.848l-4.78-4.78A8.022,8.022,0,1,0,13.067,14.2l4.78,4.78a.8.8,0,1,0,1.133-1.133ZM8,14.409A6.407,6.407,0,1,1,14.409,8,6.407,6.407,0,0,1,8,14.409Z" transform="translate(0.032 0.032)" />
            </svg>
            <span className="mobile-action-label d-md-none">
              {lbl('LBL_HEADER_SEARCH', 'Search')}
            </span>
          </a>
        </div>
      </div>
      <div className="header-dropdown header-dropwown--profile">
        <a className="header-dropdown__trigger trigger-js mobile-action" href="#profile-nav">
          <div className="teacher-profile">
            <div className="teacher__media">
              <ProfileAvatar userId={user.id} firstName={user.first_name} size="medium" />
            </div>
            <div className="mobile-action-label d-md-none d-block">{user.first_name}</div>
          </div>
        </a>
        <div id="profile-nav" className="header-dropdown__target">
          <div className="dropdown__cover">
            <div className="avtar-meta mb-3">
              <ProfileAvatar userId={user.id} firstName={user.first_name} size="small" />
              <div className="avtar-meta__name">{user.first_name}</div>
            </div>
            <nav className="menu--inline">
              <ul>
                {menuItems.map((item) => (
                  <ProfileMenuItem key={item.label} item={item} />
                ))}
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </>
  );
}

function ProfileMenuItem({ item }: { item: MenuItem }) {
  const location = useLocation();
  const isInternal = item.href.startsWith('/') && !item.onClick;
  const isActive = isInternal && location.pathname === item.href;

  const icon =
    item.icon === 'logout' ? (
      <svg className="icon" width="24" height="24" viewBox="0 0 24 24">
        <path
          d="M7.68421 19C7.30633 19 7 18.6866 7 18.3V5.7C7 5.3134 7.30633 5 7.68421 5H17.2632C17.641 5 17.9474 5.3134 17.9474 5.7V7.8H16.5789V6.4H8.36842V17.6H16.5789V16.2H17.9474V18.3C17.9474 18.6866 17.641 19 17.2632 19H7.68421ZM16.5789 14.8V12.7H11.7895V11.3H16.5789V9.2L20 12L16.5789 14.8Z"
          fill="black"
        />
      </svg>
    ) : (
      <DashboardSpriteIcon id={item.icon} className="icon" />
    );

  return (
    <li className={`menu__item${isActive ? ' is--active' : ''}`}>
      {isInternal ? (
        <Link to={item.href}>
          {icon}
          {item.label}
        </Link>
      ) : (
        <a href={item.href} onClick={item.onClick}>
          {icon}
          {item.label}
        </a>
      )}
    </li>
  );
}

export function HeaderGuestControls({
  lbl,
  openLoginModal,
  openSignupModal,
}: {
  lbl: (key: string, fallback?: string) => string;
  openLoginModal: () => void;
  openSignupModal: () => void;
}) {
  return (
    <div className="header-controls__item header-action">
      <div className="header__action">
        <button
          type="button"
          onClick={openLoginModal}
          className="header-controls__action btn btn--transparent user-click mobile-action"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
            <path d="M10 11V8l5 4-5 4v-3H1v-2h9zm-7.542 4h2.124A8.003 8.003 0 0 0 20 12 8 8 0 0 0 4.582 9H2.458C3.732 4.943 7.522 2 12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10c-4.478 0-8.268-2.943-9.542-7z" />
          </svg>
          <span className="mobile-action-label">{lbl('LBL_Login', 'Login')}</span>
        </button>
        <button type="button" onClick={openSignupModal} className="btn btn--primary user-click mobile-action">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
            <path d="M14 14.252v2.09A6 6 0 0 0 6 22l-2-.001a8 8 0 0 1 10-7.748zM12 13c-3.315 0-6-2.685-6-6s2.685-6 6-6 6 2.685 6 6-2.685 6-6 6zm0-2c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm6 6v-3h2v3h3v2h-3v3h-2v-3h-3v-2h3z" />
          </svg>
          <span className="mobile-action-label">{lbl('LBL_SIGN_UP', 'Sign up')}</span>
        </button>
        <a
          href="#HEADER-SEARCH"
          title={lbl('LBL_HEADER_SEARCH', 'Search')}
          className="btn btn--equal btn-round btn--search search-trigger trigger-js"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="19.247" height="19.247" viewBox="0 0 19.247 19.247">
            <path d="M18.98,17.848l-4.78-4.78A8.022,8.022,0,1,0,13.067,14.2l4.78,4.78a.8.8,0,1,0,1.133-1.133ZM8,14.409A6.407,6.407,0,1,1,14.409,8,6.407,6.407,0,0,1,8,14.409Z" transform="translate(0.032 0.032)" />
          </svg>
          <span className="mobile-action-label d-md-none">{lbl('LBL_HEADER_SEARCH', 'Search')}</span>
        </a>
      </div>
    </div>
  );
}
