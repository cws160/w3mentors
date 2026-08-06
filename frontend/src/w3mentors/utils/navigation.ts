import type { NavLink } from '../../api/client';

/** Legacy NavigationLinks::NAVLINK_LOGIN_* visibility rules. */
export function filterNavLinksForUser(links: NavLink[], isLoggedIn: boolean): NavLink[] {
  return links.filter((link) => {
    const protectedMode = link.login_protected ?? 0;
    if (!isLoggedIn && protectedMode === 1) {
      return false;
    }
    if (isLoggedIn && protectedMode === 2) {
      return false;
    }
    return true;
  });
}
