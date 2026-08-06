/** Match legacy GuestUser URLs and React auth routes. */
export function isLegacyLoginHref(href: string): boolean {
  const path = href.replace(/^\/+/, '').toLowerCase();
  return path === 'login' || path.includes('guest-user/signin') || path.includes('guest-user/login');
}

export function isLegacySignupHref(href: string): boolean {
  const path = href.replace(/^\/+/, '').toLowerCase();
  return (
    path === 'register' ||
    path.includes('guest-user/register') ||
    path.includes('guest-user/signup')
  );
}
