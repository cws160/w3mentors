/** Legacy dashboard base (`CONF_WEBROOT_DASHBOARD`). */
const DASHBOARD_BASE = (import.meta.env.VITE_DASHBOARD_URL ?? '/dashboard/').replace(/\/?$/, '/');

export function dashboardUrl(path = ''): string {
  const segment = path.replace(/^\//, '');
  return segment ? `${DASHBOARD_BASE}${segment}` : DASHBOARD_BASE.replace(/\/$/, '');
}
