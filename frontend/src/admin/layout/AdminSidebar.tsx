import { useEffect } from 'react';
import { NavLink, useLocation, useNavigate } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import type { AdminNavChild, AdminNavItem } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { AdminLogo } from '../components/AdminLogo';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';
import { useSitemapGenerate } from '../hooks/useSitemapGenerate';
import { adminNavLabel } from '../utils/adminNavLabels';

const MENU_ION_ICONS: Record<string, string> = {
  'icon-dashboard': 'ion-ios-speedometer-outline',
  'manage-user': 'ion-ios-people-outline',
  'group-classes': 'ion-ios-monitor-outline',
  'manage-courses': 'ion-ios-book-outline',
  'manage-quiz': 'ion-ios-checkmark-outline',
  'manage-orders': 'ion-ios-list-outline',
  'issue-reported': 'ion-ios-information-outline',
  'teacher-preferences': 'ion-ios-settings',
  'manage-cms': 'ion-ios-browsers-outline',
  'manage-setting': 'ion-ios-gear-outline',
  'manage-blogs': 'ion-ios-compose-outline',
  'manage-seo': 'ion-arrow-graph-up-right',
  'view-reports': 'ion-ios-paper-outline',
  'discussion-forum': 'ion-ios-chatboxes-outline',
};

function NavIcon({ icon, menuId }: { icon: string; menuId?: string }) {
  const ionIcon = (menuId && MENU_ION_ICONS[menuId]) || MENU_ION_ICONS[icon];

  return (
    <span className="menu-icon">
      {ionIcon ? <i className={`ion ${ionIcon}`} aria-hidden="true" /> : <AdminSpriteIcon icon={icon} width={24} height={24} />}
    </span>
  );
}

function SidebarLink({ item }: { item: Extract<AdminNavItem, { type: 'link' }> }) {
  const { lbl } = useSite();
  return (
    <li className="menu-item">
      <NavLink className="menu-section navLinkJs" to={item.path} end={item.path === '/admin'}>
        <NavIcon icon={item.icon} />
        <span className="menu-title menuTitleJs">{adminNavLabel(lbl, item.labelKey, item.labelFallback)}</span>
      </NavLink>
    </li>
  );
}

function SidebarDropdownChild({
  child,
  lbl,
  onGenerateSitemap,
}: {
  child: AdminNavChild;
  lbl: (key: string, fallback?: string) => string;
  onGenerateSitemap: () => void;
}) {
  const navigate = useNavigate();
  const location = useLocation();
  const label = adminNavLabel(lbl, child.labelKey, child.labelFallback);

  if (child.action === 'generate-sitemap') {
    return (
      <li className="nav_item navItemJs">
        <a href="javascript:void(0)" className="nav_link navLinkJs" onClick={onGenerateSitemap}>
          <span className="nav_text navTextJs">{label}</span>
        </a>
      </li>
    );
  }

  if (child.external && child.path) {
    return (
      <li className="nav_item navItemJs">
        <a href={child.path} className="nav_link navLinkJs" target="_blank" rel="noreferrer">
          <span className="nav_text navTextJs">{label}</span>
        </a>
      </li>
    );
  }

  if (!child.path) {
    return null;
  }

  const targetPath = child.path;
  const path = targetPath.split('?')[0];
  const isActive = location.pathname === path;

  return (
    <li className="nav_item navItemJs">
      <a
        href={targetPath}
        className={`nav_link navLinkJs${isActive ? ' active' : ''}`}
        onClick={(e) => {
          e.preventDefault();
          navigate(targetPath);
        }}
      >
        <span className="nav_text navTextJs">{label}</span>
      </a>
    </li>
  );
}

function SidebarDropdown({
  item,
  onGenerateSitemap,
}: {
  item: Extract<AdminNavItem, { type: 'dropdown' }>;
  onGenerateSitemap: () => void;
}) {
  const { lbl } = useSite();
  const location = useLocation();
  const isOpen = item.children.some(
    (child) => child.path && !child.external && location.pathname.startsWith(child.path.split('?')[0]),
  );

  return (
    <li className="menu-item dropdownJs">
      <button
        className={`menu-section dropdown-toggle-custom menuLinkJs ${isOpen ? '' : 'collapsed'}`}
        type="button"
        data-bs-toggle="collapse"
        data-bs-target={`#${item.id}`}
        aria-expanded={isOpen}
        aria-controls={item.id}
      >
        <NavIcon icon={item.icon} menuId={item.id} />
        <span className="menu-title menuTitleJs">{adminNavLabel(lbl, item.labelKey, item.labelFallback)}</span>
        <i className="menu_arrow dropdown-toggle-custom-arrow" />
      </button>
      <div className={`sidebar-dropdown-menu collapse ${isOpen ? 'show' : ''}`} id={item.id} data-bs-parent="#sidebarNavLinks">
        <ul className="nav nav-level">
          {item.children.map((child) => (
            <SidebarDropdownChild
              key={child.path ?? child.action ?? child.labelKey}
              child={child}
              lbl={lbl}
              onGenerateSitemap={onGenerateSitemap}
            />
          ))}
        </ul>
      </div>
    </li>
  );
}

export function AdminSidebar() {
  const { navigation } = useAdminAuth();
  const { successMessage, onGenerateSitemap, setSuccessMessage } = useSitemapGenerate();

  useEffect(() => {
    if (!successMessage) {
      return;
    }
    const timer = window.setTimeout(() => setSuccessMessage(''), 4000);
    return () => window.clearTimeout(timer);
  }, [setSuccessMessage, successMessage]);

  return (
    <>
      {successMessage ? (
        <div className="page-alert" style={{ position: 'fixed', top: 16, right: 16, zIndex: 1050, maxWidth: 360 }}>
          <div className="alert alert-success">
            <span>{successMessage}</span>
          </div>
        </div>
      ) : null}
      <div className="sidebar sidebar-hoverable" id="sidebar">
        <div className="sidebar-logo">
          <NavLink className="logo" to="/admin">
            <AdminLogo variant="white" />
          </NavLink>
        </div>
        <div className="sidebar-menu sidebarMenuJs" id="sidebar-menu">
          <ul className="menu" id="sidebarNavLinks">
            {navigation.map((item) =>
              item.type === 'link' ? (
                <SidebarLink key={item.path} item={item} />
              ) : (
                <SidebarDropdown key={item.id} item={item} onGenerateSitemap={onGenerateSitemap} />
              ),
            )}
          </ul>
        </div>
      </div>
    </>
  );
}
