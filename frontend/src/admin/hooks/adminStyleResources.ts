import { FRONTEND_BRAND } from '../utils/adminBranding';

/** Admin panel legacy CSS (manager/views) + Poppins (legacy common-header-html.php). */
type AdminStyleLink = {
  id: string;
  href: string;
  rel?: 'stylesheet' | 'preconnect';
  crossOrigin?: string;
};

export const ADMIN_STYLE_SHEETS: AdminStyleLink[] = [
  { id: 'admin-fonts-preconnect', href: 'https://fonts.googleapis.com', rel: 'preconnect' },
  {
    id: 'admin-fonts-preconnect-gstatic',
    href: 'https://fonts.gstatic.com',
    rel: 'preconnect',
    crossOrigin: 'anonymous',
  },
  {
    id: 'admin-poppins-font',
    href: 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
    rel: 'stylesheet',
  },
  { id: 'manager-ionicons-css', href: '/manager/views/common-css/ionicons.css', rel: 'stylesheet' },
  { id: 'manager-css', href: '/manager/views/css/manager.css', rel: 'stylesheet' },
];

export const ADMIN_BODY_CLASS = 'is-admin';
const ADMIN_ROOT_VARS_ID = 'admin-root-vars';

const ADMIN_ROOT_VARS_CSS = `:root,
body.is-admin {
  --brand-color: ${FRONTEND_BRAND.primary};
  --brand-color-alpha: ${FRONTEND_BRAND.primaryRgb};
  --brand-color-inverse: #ffffff;
  --primary-color: ${FRONTEND_BRAND.primary};
  --primary-color-alpha: ${FRONTEND_BRAND.primaryRgb};
  --primary-color-inverse: #ffffff;
  --secondary-color: ${FRONTEND_BRAND.secondary};
  --secondary-color-alpha: ${FRONTEND_BRAND.secondaryRgb};
  --secondary-color-inverse: #ffffff;
  --third-color: ${FRONTEND_BRAND.third};
  --color-primary: ${FRONTEND_BRAND.primary};
  --color-secondary: ${FRONTEND_BRAND.secondary};
  --bs-primary: ${FRONTEND_BRAND.primary};
  --bs-primary-rgb: ${FRONTEND_BRAND.primaryRgb};
  --bs-secondary: ${FRONTEND_BRAND.secondary};
  --bs-secondary-rgb: ${FRONTEND_BRAND.secondaryRgb};
}`;

export function isAdminPath(pathname: string): boolean {
  return pathname === '/admin' || pathname.startsWith('/admin/');
}

function ensureAdminRootVars(): void {
  if (document.getElementById(ADMIN_ROOT_VARS_ID)) {
    return;
  }
  const style = document.createElement('style');
  style.id = ADMIN_ROOT_VARS_ID;
  style.textContent = ADMIN_ROOT_VARS_CSS;
  document.head.appendChild(style);
}

export function applyAdminStylesheets(): void {
  for (const sheet of ADMIN_STYLE_SHEETS) {
    let link = document.getElementById(sheet.id) as HTMLLinkElement | null;
    if (!link) {
      link = document.createElement('link');
      link.id = sheet.id;
      link.rel = sheet.rel ?? 'stylesheet';
      link.href = sheet.href;
      if (sheet.crossOrigin) {
        link.crossOrigin = sheet.crossOrigin;
      }
      document.head.appendChild(link);
    }
    if (link.rel === 'stylesheet') {
      link.disabled = false;
    }
  }
  ensureAdminRootVars();
}

export function removeAdminStylesheets(): void {
  for (const sheet of ADMIN_STYLE_SHEETS) {
    document.getElementById(sheet.id)?.remove();
    document.querySelector(`link[href="${sheet.href}"]`)?.remove();
  }
  document.getElementById(ADMIN_ROOT_VARS_ID)?.remove();
}
