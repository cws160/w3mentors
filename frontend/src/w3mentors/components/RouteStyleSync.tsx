import { useLayoutEffect } from 'react';
import { useLocation } from 'react-router-dom';
import {
  applyAdminStylesheets,
  isAdminPath,
  removeAdminStylesheets,
  ADMIN_BODY_CLASS,
} from '../../admin/hooks/adminStyleResources';
import {
  applyCoursePreviewStylesheets,
  applyDashboardStylesheets,
  applyMarketingStylesheets,
  isCoursePreviewPath,
  isDashboardPath,
  removeMarketingStylesheets,
} from '../hooks/styleResources';

/**
 * Keeps marketing CSS (common, frontend, etc.) off dashboard/admin routes.
 * useLayoutEffect runs before paint to avoid flash on refresh.
 */
export function RouteStyleSync() {
  const { pathname } = useLocation();

  useLayoutEffect(() => {
    if (isCoursePreviewPath(pathname)) {
      document.body.classList.remove(ADMIN_BODY_CLASS);
      document.body.classList.add('is-dashboard-route', 'course-leaner');
      removeAdminStylesheets();
      applyCoursePreviewStylesheets();
    } else if (isAdminPath(pathname)) {
      document.body.classList.remove('is-dashboard-route', 'course-leaner');
      document.body.classList.add(ADMIN_BODY_CLASS);
      removeMarketingStylesheets();
      applyAdminStylesheets();
    } else if (isDashboardPath(pathname)) {
      document.body.classList.remove(ADMIN_BODY_CLASS, 'course-leaner');
      document.body.classList.add('is-dashboard-route');
      removeAdminStylesheets();
      applyDashboardStylesheets();
    } else {
      document.body.classList.remove('is-dashboard-route', ADMIN_BODY_CLASS, 'course-leaner');
      removeAdminStylesheets();
      applyMarketingStylesheets();
    }
  }, [pathname]);

  return null;
}
