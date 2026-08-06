export const CONFIG_FORM_GENERAL_SETTINGS = 1;
export const CONFIG_FORM_MEDIA_AND_LOGOS = 2;
export const CONFIG_FORM_THIRD_PARTY_APIS = 3;
export const CONFIG_FORM_COMMON_SETTINGS = 4;
export const CONFIG_FORM_EMAIL_AND_SMTPS = 5;
export const CONFIG_FORM_DASHBOARD_LESSONS = 6;
export const CONFIG_FORM_DASHBOARD_CLASSES = 7;
export const CONFIG_FORM_DISCUSSION_FORUM = 8;
export const CONFIG_FORM_SEO_AND_GOOGLE_TAGS = 9;
export const CONFIG_FORM_MAINTAINANCE_AND_SSL = 10;
export const CONFIG_FORM_REMEMBER_ME_SECURITY = 11;
export const CONFIG_FORM_PWA_SETTINGS = 12;
export const CONFIG_FORM_DASHBOARD_COURSES = 13;
export const CONFIG_FORM_REFERRAL_SETTINGS = 14;
export const CONFIG_FORM_OFFLINE_SESSIONS_SETTINGS = 15;
export const CONFIG_FORM_AFFILIATE_SETTINGS = 16;

export type ConfigurationTab = {
  id: number;
  labelKey: string;
  labelFallback: string;
  requiresCourses?: boolean;
  requiresGroupClasses?: boolean;
  implemented: boolean;
};

export const CONFIGURATION_TABS: ConfigurationTab[] = [
  { id: CONFIG_FORM_GENERAL_SETTINGS, labelKey: 'MSG_GENERAL_SETTINGS', labelFallback: 'General', implemented: true },
  { id: CONFIG_FORM_MEDIA_AND_LOGOS, labelKey: 'MSG_MEDIA_&_LOGOS', labelFallback: 'Media', implemented: true },
  { id: CONFIG_FORM_THIRD_PARTY_APIS, labelKey: 'MSG_THIRD_PARTY_APIS', labelFallback: 'Third-Party APIs', implemented: true },
  { id: CONFIG_FORM_COMMON_SETTINGS, labelKey: 'MSG_COMMON_SETTINGS', labelFallback: 'System', implemented: true },
  { id: CONFIG_FORM_EMAIL_AND_SMTPS, labelKey: 'MSG_EMAIL_AND_SMTP', labelFallback: 'Email', implemented: true },
  { id: CONFIG_FORM_DASHBOARD_LESSONS, labelKey: 'MSG_DASHBOARD_LESSONS', labelFallback: 'Lessons', implemented: true },
  {
    id: CONFIG_FORM_DASHBOARD_CLASSES,
    labelKey: 'MSG_DASHBOARD_CLASSES',
    labelFallback: 'Classes',
    requiresGroupClasses: true,
    implemented: true,
  },
  {
    id: CONFIG_FORM_DASHBOARD_COURSES,
    labelKey: 'MSG_DASHBOARD_COURSES',
    labelFallback: 'Courses',
    requiresCourses: true,
    implemented: true,
  },
  { id: CONFIG_FORM_DISCUSSION_FORUM, labelKey: 'MSG_DISCUSSION_FORUM', labelFallback: 'Forum', implemented: true },
  { id: CONFIG_FORM_SEO_AND_GOOGLE_TAGS, labelKey: 'MSG_SEO_&_TAG_MANAGER', labelFallback: 'SEO', implemented: true },
  { id: CONFIG_FORM_MAINTAINANCE_AND_SSL, labelKey: 'MSG_MAINTAINANCE_&_SSL', labelFallback: 'Server', implemented: true },
  { id: CONFIG_FORM_REMEMBER_ME_SECURITY, labelKey: 'MSG_REMEMBER_ME', labelFallback: 'Security', implemented: true },
  { id: CONFIG_FORM_PWA_SETTINGS, labelKey: 'MSG_PWA_SETTINGS', labelFallback: 'PWA', implemented: true },
  { id: CONFIG_FORM_REFERRAL_SETTINGS, labelKey: 'MSG_REFERRAL_SETTINGS', labelFallback: 'Referral settings', implemented: true },
  { id: CONFIG_FORM_OFFLINE_SESSIONS_SETTINGS, labelKey: 'MSG_OFFLINE_SESSIONS', labelFallback: 'Offline sessions', implemented: true },
  { id: CONFIG_FORM_AFFILIATE_SETTINGS, labelKey: 'MSG_AFFILIATE_SETTINGS', labelFallback: 'Affiliate settings', implemented: true },
];

const DYNAMIC_FORM_TABS = new Set([
  CONFIG_FORM_COMMON_SETTINGS,
  CONFIG_FORM_EMAIL_AND_SMTPS,
  CONFIG_FORM_DASHBOARD_LESSONS,
  CONFIG_FORM_DASHBOARD_CLASSES,
  CONFIG_FORM_DISCUSSION_FORUM,
  CONFIG_FORM_SEO_AND_GOOGLE_TAGS,
  CONFIG_FORM_MAINTAINANCE_AND_SSL,
  CONFIG_FORM_REMEMBER_ME_SECURITY,
  CONFIG_FORM_PWA_SETTINGS,
  CONFIG_FORM_DASHBOARD_COURSES,
  CONFIG_FORM_REFERRAL_SETTINGS,
  CONFIG_FORM_OFFLINE_SESSIONS_SETTINGS,
  CONFIG_FORM_AFFILIATE_SETTINGS,
]);

export function usesDynamicConfigurationForm(tabId: number): boolean {
  return DYNAMIC_FORM_TABS.has(tabId);
}

export function configurationTabHasLangTabs(tabId: number): boolean {
  return (
    tabId === CONFIG_FORM_GENERAL_SETTINGS ||
    tabId === CONFIG_FORM_MEDIA_AND_LOGOS ||
    tabId === CONFIG_FORM_MAINTAINANCE_AND_SSL
  );
}

export function configurationTabLangOnly(tabId: number): boolean {
  return tabId === CONFIG_FORM_MEDIA_AND_LOGOS;
}

export function resolveConfigurationTab(
  tabParam: string | null,
  pathname: string,
  modules: { courses?: boolean; group_classes?: boolean },
): number {
  const parsed = Number(tabParam);
  if (Number.isFinite(parsed) && parsed > 0) {
    return parsed;
  }

  if (pathname.endsWith('/third-party-apis')) {
    return CONFIG_FORM_THIRD_PARTY_APIS;
  }

  return CONFIG_FORM_GENERAL_SETTINGS;
}

export function visibleConfigurationTabs(modules: { courses?: boolean; group_classes?: boolean }): ConfigurationTab[] {
  return CONFIGURATION_TABS.filter((tab) => {
    if (tab.requiresCourses && !modules.courses) {
      return false;
    }
    if (tab.requiresGroupClasses && !modules.group_classes) {
      return false;
    }
    return true;
  });
}

export function configurationTabMeta(tabId: number): ConfigurationTab {
  return CONFIGURATION_TABS.find((tab) => tab.id === tabId) ?? CONFIGURATION_TABS[0];
}
