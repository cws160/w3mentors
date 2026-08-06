/** Legacy Afile type constants (application/models/Afile.php) */
export const AFILE = {
  USER_PROFILE: 4,
  TEACHING_LANGUAGE: 42,
  BLOG_POST: 23,
  TESTIMONIAL: 26,
  HOME_BANNER_BG: 34,
  HOME_BANNER_DESKTOP: 49,
  GROUP_CLASS_BANNER: 55,
  COURSE_IMAGE: 57,
  COURSE_PREVIEW_VIDEO: 65,
  APPLY_TO_TEACH_BANNER: 52,
  AFFILIATE_REGISTRATION_BANNER: 67,
  CPAGE_BACKGROUND_IMAGE: 27,
  CATEGORY_IMAGE: 64,
} as const;

export function spriteUrl(id: string): string {
  return `/images/sprite.svg#${id}`;
}

export function imageUrl(
  fileType: number,
  recordId: number,
  size: string = 'MEDIUM',
  langId: number = 1
): string {
  return `/image/show/${fileType}/${recordId}/${size}/${langId}`;
}

export function formatMoney(amount: number | string, symbol = '$'): string {
  const n = Number(amount);
  if (!Number.isFinite(n) || n <= 0) return 'Free';
  return `${symbol}${n.toFixed(2).replace(/\.00$/, '')}`;
}

export function truncate(text: string, max = 30): string {
  if (text.length <= max) return text;
  return `${text.slice(0, max)}...`;
}

export function firstChar(name: string): string {
  return (name.trim()[0] ?? '?').toUpperCase();
}
