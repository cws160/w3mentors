/** Frontend site branding (matches w3mentors-overrides.css / dashboard-fixes.css). */
import { SITE_LOGO_URL, SITE_LOGO_WHITE_URL } from '../../utils/branding';

export const FRONTEND_BRAND = {
  primary: '#0c9331',
  primaryRgb: '12, 147, 49',
  secondary: '#0c9331',
  secondaryRgb: '12, 147, 49',
  third: '#10a83a',
} as const;

export const DEFAULT_ADMIN_LOGO = SITE_LOGO_URL;
export const DEFAULT_ADMIN_WHITE_LOGO = SITE_LOGO_WHITE_URL;

export function adminLogoSrc(_logoUrl?: string | null, variant: 'default' | 'white' = 'default'): string {
  return variant === 'white' ? SITE_LOGO_WHITE_URL : SITE_LOGO_URL;
}

export function adminSiteTitle(name?: string | null): string {
  return name || 'w3mentors';
}
