import { normalizeLegacyHtml } from '../../w3mentors/utils/legacyHtml';

/** Mirrors legacy `CommonHelper::renderHtml()` for admin read-only views. */
export function renderLegacyAdminHtml(content: string | null | undefined): string {
  if (!content) {
    return '';
  }

  const decoded = decodeHtmlEntities(content);
  const withoutScripts = decoded.replace(/<script[^>]*?>.*?<\/script>/gis, '');

  return normalizeLegacyHtml(withoutScripts);
}

function decodeHtmlEntities(content: string): string {
  if (typeof document === 'undefined') {
    return content
      .replace(/&quot;/g, '"')
      .replace(/&#039;/g, "'")
      .replace(/&apos;/g, "'")
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&amp;/g, '&');
  }

  const textarea = document.createElement('textarea');
  textarea.innerHTML = content;
  return textarea.value;
}
