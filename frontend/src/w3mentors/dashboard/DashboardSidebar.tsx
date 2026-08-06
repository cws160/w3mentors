import { Link, useLocation } from 'react-router-dom';
import { DashboardSpriteIcon } from '../components/DashboardSpriteIcon';
import { useSite } from '../context/SiteContext';
import type { DashboardRole } from './dashboardPaths';
import { getDashboardNav, type DashboardNavItem } from './dashboardNavConfig';

type Props = {
  role: DashboardRole;
};

function SidebarNavItem({ item, lbl }: { item: DashboardNavItem; lbl: (k: string, f: string) => string }) {
  const location = useLocation();
  const isActive = item.end
    ? location.pathname === item.path
    : location.pathname === item.path || location.pathname.startsWith(`${item.path}/`);

  const label = lbl(item.labelKey, item.labelFallback);
  const icon = (
    <DashboardSpriteIcon
      id={item.spriteId}
      className={item.iconClass}
      spritePath={item.spritePath}
    />
  );

  if (item.external) {
    return (
      <li className="menu__item">
        <a href={item.external} target={item.target} rel={item.target === '_blank' ? 'noreferrer' : undefined}>
          {icon}
          <span>{label}</span>
        </a>
      </li>
    );
  }

  return (
    <li className={`menu__item${isActive ? ' is-active' : ''}`}>
      <Link to={item.path}>
        {icon}
        <span>{label}</span>
      </Link>
    </li>
  );
}

export function DashboardSidebar({ role }: Props) {
  const { lbl, modules } = useSite();
  const groups = getDashboardNav(role, {
    courses: modules?.courses !== false,
    groupClasses: modules?.group_classes !== false,
  });

  return (
    <>
      {groups.map((group) => (
        <div key={group.titleKey} className="menu-group">
          <h6 className="heading-6">{lbl(group.titleKey, group.titleFallback)}</h6>
          <nav className="menu menu--primary">
            <ul>
              {group.items.map((item) => (
                <SidebarNavItem key={item.path} item={item} lbl={lbl} />
              ))}
            </ul>
          </nav>
        </div>
      ))}
    </>
  );
}
