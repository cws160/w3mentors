/**
 * Legacy public-site body classes from application/views/header.php:
 * strtolower(controller) + ' ' + strtolower(controller) + '-' + strtolower(action)
 */
function controllerActionClass(controller: string, action: string): string {
  const c = controller.toLowerCase();
  const a = action.toLowerCase();
  return `${c} ${c}-${a}`;
}

const CMS_ROUTES = new Set(['about', 'privacy-policy', 'terms-and-conditions']);

/** Map React pathname to legacy FatController body class string. */
export function legacyBodyClass(pathname: string): string {
  const path = pathname.replace(/\/$/, '') || '/';
  const segments = path.split('/').filter(Boolean);

  if (segments.length === 0) {
    return controllerActionClass('Home', 'index');
  }

  if (segments[0] === 'cms' && segments[1]) {
    return controllerActionClass('Cms', 'view');
  }

  if (CMS_ROUTES.has(segments[0])) {
    return controllerActionClass('Cms', 'view');
  }

  switch (segments[0]) {
    case 'blog':
      return segments.length > 1
        ? controllerActionClass('Blog', 'postDetail')
        : controllerActionClass('Blog', 'index');
    case 'contact':
      return controllerActionClass('Contact', 'index');
    case 'courses':
      return segments.length > 1
        ? controllerActionClass('Courses', 'view')
        : controllerActionClass('Courses', 'index');
    case 'teachers':
      return segments.length > 1
        ? controllerActionClass('Teachers', 'view')
        : controllerActionClass('Teachers', 'index');
    case 'group-classes':
      return segments.length > 1
        ? controllerActionClass('GroupClasses', 'view')
        : controllerActionClass('GroupClasses', 'index');
    case 'forum':
      return segments.length > 1
        ? controllerActionClass('Forum', 'view')
        : controllerActionClass('Forum', 'index');
    case 'faq':
      return controllerActionClass('Faq', 'index');
    case 'video-content':
    case 'videos':
      return controllerActionClass('Videos', 'index');
    case 'teacher-request':
      return segments[1] === 'form'
        ? controllerActionClass('TeacherRequest', 'form')
        : controllerActionClass('TeacherRequest', 'index');
    case 'subscription-plans':
      return controllerActionClass('SubscriptionPlans', 'index');
    case 'guest-user':
      if (segments[1] === 'affiliate-signup-form') {
        return controllerActionClass('GuestUser', 'affiliateSignupForm');
      }
      if (segments[1] === 'forgot-password') {
        return controllerActionClass('GuestUser', 'forgotPassword');
      }
      if (segments[1] === 'reset-password') {
        return controllerActionClass('GuestUser', 'resetPassword');
      }
      return controllerActionClass('GuestUser', segments[1] ?? 'index');
    case 'login':
      return controllerActionClass('GuestUser', 'loginForm');
    case 'register':
      return controllerActionClass('GuestUser', 'registrationForm');
    default:
      return segments.join(' ');
  }
}
