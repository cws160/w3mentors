export type DashboardRole = 'teacher' | 'learner';

/** Resolve role from React Router pathname (routes use `/dashboard/teacher`, not `:role`). */
export function dashboardRoleFromPath(pathname: string): DashboardRole | null {
  if (pathname.startsWith('/dashboard/teacher')) {
    return 'teacher';
  }
  if (pathname.startsWith('/dashboard/learner')) {
    return 'learner';
  }
  return null;
}

export function dashboardPath(role: DashboardRole, segment = ''): string {
  const base = `/dashboard/${role}`;
  if (!segment) {
    return base;
  }
  return `${base}/${segment.replace(/^\//, '')}`;
}
