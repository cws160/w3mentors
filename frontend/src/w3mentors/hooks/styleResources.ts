/** Marketing / public site styles (not used on dashboard). */
export const FRONTEND_STYLE_SHEETS: { id: string; href: string }[] = [
  { id: 'w3mentors-common-css', href: '/w3mentors/css/common.css' },
  { id: 'w3mentors-frontend-theme-css', href: '/w3mentors/css/themes/onlinetutoring/frontend.css' },
  { id: 'w3mentors-forum-css', href: '/w3mentors/css/forum.css' },
  { id: 'w3mentors-cms-css', href: '/w3mentors/css/cms-overrides.css' },
];

/** Legacy dashboard CSS only — do not add marketing/frontend or custom dashboard-fixes sheets here. */
export const DASHBOARD_STYLE_SHEETS: { id: string; href: string }[] = [
  { id: 'w3mentors-dashboard-common-css', href: '/w3mentors/css/dashboard-common.css' },
  { id: 'w3mentors-dashboard-css', href: '/w3mentors/css/dashboard.css' },
  { id: 'w3mentors-dashboard-fixes-css', href: '/w3mentors/css/dashboard-fixes.css' },
];

export const THEME_STYLE_ID = 'w3mentors-theme-vars';

/** Vite-injected bundles that belong to the marketing app only. */
const MARKETING_BUNDLE_HINTS = ['w3mentors-overrides', 'slick-carousel'];

/** Legacy course preview player layout (72% panel + fixed sidebar). */
export const COURSE_PREVIEW_STYLE_SHEETS: { id: string; href: string }[] = [
  { id: 'w3mentors-course-personal-css', href: '/w3mentors/css/course-personal.css' },
];

export function isCoursePreviewPath(pathname: string): boolean {
  return /^\/admin\/courses\/\d+\/preview/.test(pathname);
}

export function isDashboardPath(pathname: string): boolean {
  return (
    pathname === '/dashboard' ||
    pathname.startsWith('/dashboard/teacher') ||
    pathname.startsWith('/dashboard/learner') ||
    isCoursePreviewPath(pathname)
  );
}

export function ensureStylesheet(id: string, href: string): HTMLLinkElement {
  let link = document.getElementById(id) as HTMLLinkElement | null;
  if (!link) {
    link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
  }
  return link;
}

export function setStylesheetsEnabled(sheets: { id: string; href: string }[], enabled: boolean) {
  for (const { id, href } of sheets) {
    const link =
      (document.getElementById(id) as HTMLLinkElement | null) ??
      document.querySelector<HTMLLinkElement>(`link[href="${href}"]`);
    if (link) {
      link.disabled = !enabled;
    }
  }
}

export function removeStylesheets(sheets: { id: string; href: string }[]) {
  for (const { id, href } of sheets) {
    document.getElementById(id)?.remove();
    document.querySelector(`link[href="${href}"]`)?.remove();
  }
}

export function setMarketingBundleStylesEnabled(enabled: boolean) {
  for (const el of document.querySelectorAll<HTMLLinkElement | HTMLStyleElement>(
    'link[rel="stylesheet"], style'
  )) {
    const ref =
      el.getAttribute('href') ??
      el.getAttribute('data-vite-dev-id') ??
      '';
    if (MARKETING_BUNDLE_HINTS.some((hint) => ref.includes(hint))) {
      if (el instanceof HTMLLinkElement) {
        el.disabled = !enabled;
      } else {
        el.disabled = !enabled;
      }
    }
  }
}

export function setSiteThemeStyleEnabled(enabled: boolean) {
  const el = document.getElementById(THEME_STYLE_ID) as HTMLStyleElement | null;
  if (el) {
    el.disabled = !enabled;
  }
}

/** Remove marketing styles from the document (dashboard must not load these). */
export function removeMarketingStylesheets(): void {
  removeStylesheets(FRONTEND_STYLE_SHEETS);
  setMarketingBundleStylesEnabled(false);
}

export function removeDashboardRootVars(): void {
  document.getElementById(THEME_STYLE_ID)?.remove();
}

/** Enable dashboard + course-preview legacy CSS. */
export function applyCoursePreviewStylesheets(): void {
  applyDashboardStylesheets();
  for (const sheet of COURSE_PREVIEW_STYLE_SHEETS) {
    const link = ensureStylesheet(sheet.id, sheet.href);
    link.disabled = false;
  }
}

/** Enable dashboard-only legacy CSS. */
export function applyDashboardStylesheets(): void {
  removeMarketingStylesheets();
  for (const sheet of DASHBOARD_STYLE_SHEETS) {
    const link = ensureStylesheet(sheet.id, sheet.href);
    link.disabled = false;
  }
}

/** Enable public-site legacy CSS. */
export function applyMarketingStylesheets(): void {
  removeStylesheets(DASHBOARD_STYLE_SHEETS);
  removeDashboardRootVars();
  for (const sheet of FRONTEND_STYLE_SHEETS) {
    const link = ensureStylesheet(sheet.id, sheet.href);
    link.disabled = false;
  }
  setMarketingBundleStylesEnabled(true);
  setSiteThemeStyleEnabled(true);
}
