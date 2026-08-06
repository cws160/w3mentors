/** Legacy admin sidebar label fallbacks when a key is missing from language labels. */
export const ADMIN_NAV_LABEL_FALLBACKS: Record<string, string> = {
  LBL_DASHBOARD: 'Dashboard',
  LBL_MANAGE_USERS: 'Manage users',
  LBL_USERS: 'Users',
  LBL_TEACHER_REQUESTS: 'Teacher requests',
  LBL_WITHDRAW_REQUESTS: 'Wallet withdrawal requests',
  LBL_TEACHER_REVIEWS: 'Teacher reviews & ratings',
  LBL_GDPR_REQUESTS: 'GDPR requests',
  LBL_MANAGE_ADMINS: 'Manage Admins',
  LBL_ADMIN_CLASSES: 'Classes',
  LBL_GROUP_CLASSES: 'Group classes',
  LBL_PACKAGE_CLASSES: 'Package classes',
  LBL_MANAGE_COURSES: 'Manage courses',
  LBL_COURSE_LANGUAGES: 'Course languages',
  LBL_CATEGORIES: 'Categories',
  LBL_COURSES: 'Courses',
  LBL_APPROVAL_REQUESTS: 'Approval requests',
  LBL_EDIT_REQUESTS: 'Edit requests',
  LBL_COURSE_REVIEWS: 'Course reviews',
  LBL_REFUND_REQUESTS: 'Refund requests',
  LBL_MANAGE_QUIZZES: 'Manage quizzes',
  LBL_QUESTIONS: 'Questions',
  LBL_QUIZZES: 'Quizzes',
  LBL_MANAGE_ORDERS: 'Manage orders',
  LBL_ALL_ORDERS: 'All orders',
  LBL_LESSONS_ORDERS: 'Lessons orders',
  LBL_RECURRING_LESSON_ORDERS: 'Recurring lesson orders',
  LBL_SUBSCRIPTION_PLAN_ORDERS: 'Subscription plan orders',
  LBL_CLASSES_ORDERS: 'Classes Orders',
  LBL_COURSE_ORDERS: 'Courses Orders',
  LBL_PACKAGES_ORDERS: 'Packages Orders',
  LBL_GIFTCARD_ORDERS: 'Gift Card Orders',
  LBL_GIFT_CARD_ORDERS: 'Gift Card Orders',
  LBL_WALLET_RECHARGE_ORDERS: 'Wallet recharge orders',
  LBL_ISSUES_REPORTED: 'Issues reported',
  LBL_ESCALATED_ISSUES: 'Escalated Issues',
  LBL_REPORTED_ISSUES: 'Reported issues',
  LBL_ESCALATED: 'Escalated',
  LBL_ALL_REPORTED_ISSUES: 'All reported issues',
  LBL_TEACHER_PREFERENCES: 'Teacher preferences',
  LBL_ACCENTS: 'Accents',
  LBL_TEACHES_LEVEL: 'Teaches level',
  LBL_LEARNERS_AGES: 'Learners ages',
  LBL_LESSONS_INCLUDE: 'Lessons include',
  LBL_TEST_PREPARATION: 'Test preparation',
  LBL_SPOKEN_LANGUAGE: 'Spoken language',
  LBL_SPOKEN_LANGUAGE_LEVELS: 'Spoken language levels',
  LBL_TEACHING_LANGUAGE: 'Teaching subjects',
  LBL_ISSUE_REPORT_OPTIONS: 'Issue report options',
  LBL_MANAGE_CMS: 'Manage CMS',
  LBL_HOMEPAGE_SLIDES: 'Homepage slides',
  LBL_CONTENT_PAGES: 'Content pages',
  LBL_CONTENT_BLOCKS: 'Content blocks',
  LBL_NAVIGATION: 'Navigation',
  LBL_COUNTRIES: 'Countries',
  LBL_STATES: 'States',
  LBL_VIDEO_CONTENT: 'Video content',
  LBL_TESTIMONIALS: 'Testimonials',
  LBL_LANGUAGE_LABEL: 'Language label',
  LBL_FAQ_CATEGORIES: 'FAQ categories',
  LBL_MANAGE_FAQS: 'Manage FAQs',
  LBL_EMAIL_TEMPLATES: 'Email templates',
  LBL_ABUSIVE_WORDS: 'Abusive words',
  LBL_CERTIFICATES: 'Certificates',
  LBL_MANAGE_SETTINGS: 'Manage settings',
  LBL_GENERAL_SETTINGS: 'General settings',
  LBL_MEETING_TOOLS: 'Meeting tools',
  LBL_PAYMENT_METHODS: 'Payment methods',
  LBL_SOCIAL_PLATFORMS: 'Social platforms',
  LBL_DISCOUNT_COUPONS: 'Discount coupons',
  LBL_COMMISSION_SETTINGS: 'Commission settings',
  LBL_CURRENCY_MANAGEMENT: 'Currency management',
  LBL_THEME_MANAGEMENT: 'Theme management',
  LBL_PAGE_LANGUAGE_DATA: 'Page language data',
  LBL_MANAGE_SUBSCRIPTION_PLANS: 'Manage subscription plans',
  LBL_AFFILIATE_COMMISSION: 'Affiliate commission',
  LBL_MANAGE_BLOGS: 'Manage blogs',
  LBL_BLOG_CATEGORIES: 'Blog categories',
  LBL_BLOG_POSTS: 'Blog posts',
  LBL_BLOG_COMMENTS: 'Blog comments',
  LBL_BLOG_CONTRIBUTIONS: 'Blog contributions',
  LBL_MANAGE_SEO: 'Manage SEO',
  LBL_META_TAGS: 'Meta tags',
  LBL_SEO_URLS: 'SEO URLs',
  'LBL_ROBOTS.TXT': 'Robots.txt',
  LBL_UPDATE_SITEMAP: 'Update sitemap',
  LBL_XML_SITEMAP: 'XML sitemap',
  LBL_HTML_SITEMAP: 'HTML sitemap',
  LBL_VIEW_REPORTS: 'View reports',
  LBL_LESSONS_TOP_LANGUAGES: 'Lessons top languages',
  LBL_CLASSES_TOP_LANGUAGES: 'Classes top languages',
  LBL_TEACHER_PERFORMANCE: 'Teacher performance',
  LBL_LESSON_STATS: 'Lesson stats',
  LBL_SALES_REPORT: 'Sales report',
  LBL_SETTLEMENTS: 'Settlements',
  LBL_ADMIN_EARNINGS: 'Admin earnings',
  LBL_AFFILIATE_REPORT: 'Affiliate report',
  LBL_WALLET_BALANCE_REPORT: 'Wallet balance report',
  LBL_TEACHER_PAYOUTS_REPORT: 'Teacher payouts report',
  LBL_HOURS_TAUGHT_REPORT: 'Hours taught report',
  LBL_DISCUSSION_FORUM: 'Forum',
  LBL_ALL_QUESTIONS: 'All questions',
  LBL_REPORTED_QUESTIONS: 'Reported questions',
  LBL_FORUM_TAGS: 'Forum tags',
  LBL_REQUESTED_TAGS: 'Requested tags',
  LBL_REPORT_REASONS: 'Report reasons',
};

/** Same caption derivation as legacy `Label::getLabel()` when DB caption is missing. */
function deriveLabelFromKey(labelKey: string): string {
  const words = labelKey
    .toLowerCase()
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1));
  if (words[0] === 'Lbl') {
    words.shift();
  }
  return words.join(' ');
}

export function adminNavLabel(
  lbl: (key: string, fallback?: string) => string,
  labelKey: string,
  labelFallback?: string,
): string {
  const normalizedKey = labelKey.toUpperCase();
  const fallback =
    labelFallback ??
    ADMIN_NAV_LABEL_FALLBACKS[labelKey] ??
    ADMIN_NAV_LABEL_FALLBACKS[normalizedKey] ??
    deriveLabelFromKey(normalizedKey);
  const resolved = lbl(normalizedKey, fallback);
  if (!resolved || resolved === normalizedKey || resolved === labelKey || resolved.startsWith('LBL_')) {
    return fallback;
  }
  return resolved;
}
