/** Public URL for a CMS content page (matches NavigationService / legacy cms/view). */
export function cmsPagePath(
  pageId: number,
  legal?: { terms?: number; privacy?: number; about?: number }
): string {
  const aboutId = legal?.about ?? 1;
  const termsId = legal?.terms ?? 2;
  const privacyId = legal?.privacy ?? 3;

  if (pageId === aboutId) return '/about';
  if (pageId === termsId) return '/terms-and-conditions';
  if (pageId === privacyId) return '/privacy-policy';

  return `/cms/${pageId}`;
}
