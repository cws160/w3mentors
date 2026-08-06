import { Outlet } from 'react-router-dom';

/** Minimal wrapper for dashboard routes — no marketing header/CSS. */
export function DashboardRouteLayout() {
  return <Outlet />;
}
