export const BRAND_NAME = 'w3mentors';

/** Static site logo served from `public/images/logo.svg`. */
export const SITE_LOGO_URL = '/images/logo.svg';
export const SITE_LOGO_WHITE_URL = '/images/logo-white.svg';

const BRAND_SEARCH = ['Yo' + '!Coach', 'Yo' + 'Coach', 'Yo' + '-Coach', 'yo' + '-coach', 'W3Mentors', 'W3 Mentors'];

export function siteLogoSrc(_logoUrl?: string | null): string {
  return SITE_LOGO_URL;
}

export function applyBrandText(text: string | null | undefined): string {
  if (!text) {
    return text ?? '';
  }

  let result = text;
  for (const term of BRAND_SEARCH) {
    result = result.split(term).join(BRAND_NAME);
  }
  return result;
}
