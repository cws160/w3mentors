/** Rewrite legacy CMS HTML paths for the React app */
export function normalizeLegacyHtml(html: string): string {
  return html
    .replace(/href="(?!https?:|\/|#|mailto:|tel:)([^"]+)"/g, 'href="/$1"')
    .replace(/href="\/\//g, 'href="/')
    .replace(/xlink:href="(?!https?:|\/)([^"]+)"/g, 'xlink:href="/$1"')
    .replace(/src="(?!https?:|\/|data:)([^"]+)"/g, 'src="/$1"')
    .replace(/url\(\s*['"]?(?!https?:|\/|data:)([^'")]+)['"]?\s*\)/gi, 'url(/$1)')
    .replace(/src="\/images\//g, 'src="/images/')
    .replace(/url\(\/images\//g, 'url(/images/')
    .replace(/href="\/guest-user\/register[^"]*"/gi, 'href="/register"')
    .replace(/href="\/guest-user\/signin[^"]*"/gi, 'href="/login"')
    .replace(/href="\/guest-user\/login[^"]*"/gi, 'href="/login"');
}

/** CMS contact left block ships wrapped in a Bootstrap column div — unwrap for React row layout. */
export function unwrapBootstrapColumn(html: string): { columnClass: string; inner: string } {
  const trimmed = html.trim();
  if (!trimmed) {
    return { columnClass: 'col-md-5 col-lg-4', inner: '' };
  }

  const match = trimmed.match(/^<div\b([^>]*)>([\s\S]*)<\/div>\s*$/i);
  if (!match) {
    return { columnClass: 'col-md-5 col-lg-4', inner: trimmed };
  }

  const attrs = match[1];
  const inner = match[2].trim();
  const classMatch = attrs.match(/\bclass=(["'])(.*?)\1/i);
  const className = classMatch?.[2] ?? '';

  if (/\bcol-(?:sm|md|lg|xl|xxl)-\d+\b/.test(className)) {
    return { columnClass: className, inner };
  }

  return { columnClass: 'col-md-5 col-lg-4', inner: trimmed };
}
