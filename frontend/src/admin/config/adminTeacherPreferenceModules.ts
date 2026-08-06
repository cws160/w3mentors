import type { AdminModuleConfig } from './adminModuleTypes';

const keywordSearch = [
  { name: 'keyword', labelKey: 'LBL_KEYWORD', labelFallback: 'Keyword', type: 'text' as const },
];

const identifierTitleColumns = [
  { key: 'serial', labelKey: 'LBL_SRNO', labelFallback: 'Sr. No' },
  { key: 'identifier', labelKey: 'LBL_PREFERENCE_IDENTIFIER', labelFallback: 'Identifier' },
  { key: 'title', labelKey: 'LBL_PREFERENCE_TITLE', labelFallback: 'Title' },
];

const identifierNameStatusColumns = (identifierKey: string, nameKey: string) => [
  { key: 'serial', labelKey: 'LBL_SRNO', labelFallback: 'Sr. No' },
  { key: 'identifier', labelKey: identifierKey, labelFallback: 'Identifier' },
  { key: 'title', labelKey: nameKey, labelFallback: 'Name' },
  { key: 'active', labelKey: 'LBL_STATUS', labelFallback: 'Status' },
];

const PREFERENCE_TYPES: Record<
  string,
  { titleKey: string; titleFallback: string; identifierLabel: string; titleLabel: string }
> = {
  '1': {
    titleKey: 'LBL_ACCENTS',
    titleFallback: 'Accents',
    identifierLabel: 'LBL_PREFERENCE_IDENTIFIER',
    titleLabel: 'LBL_PREFERENCE_TITLE',
  },
  '2': {
    titleKey: 'LBL_TEACHES_LEVEL',
    titleFallback: 'Teaches level',
    identifierLabel: 'LBL_PREFERENCE_IDENTIFIER',
    titleLabel: 'LBL_PREFERENCE_TITLE',
  },
  '3': {
    titleKey: 'LBL_LEARNERS_AGES',
    titleFallback: 'Learners ages',
    identifierLabel: 'LBL_PREFERENCE_IDENTIFIER',
    titleLabel: 'LBL_PREFERENCE_TITLE',
  },
  '4': {
    titleKey: 'LBL_LESSONS_INCLUDE',
    titleFallback: 'Lessons include',
    identifierLabel: 'LBL_PREFERENCE_IDENTIFIER',
    titleLabel: 'LBL_PREFERENCE_TITLE',
  },
  '6': {
    titleKey: 'LBL_TEST_PREPARATION',
    titleFallback: 'Test preparation',
    identifierLabel: 'LBL_PREFERENCE_IDENTIFIER',
    titleLabel: 'LBL_PREFERENCE_TITLE',
  },
};

export function getPreferenceTypeMeta(typeId: string) {
  return (
    PREFERENCE_TYPES[typeId] ?? {
      titleKey: 'LBL_PREFERENCES',
      titleFallback: 'Preferences',
      identifierLabel: 'LBL_PREFERENCE_IDENTIFIER',
      titleLabel: 'LBL_PREFERENCE_TITLE',
    }
  );
}

export function getPreferencesModuleConfig(typeId: string): AdminModuleConfig {
  const meta = getPreferenceTypeMeta(typeId);

  return {
    module: 'preferences',
    pageLangKey: 'preferences',
    titleKey: meta.titleKey,
    titleFallback: meta.titleFallback,
    exportable: true,
    creatable: true,
    canEditPrivilege: 'canEditPreferences',
    createLabelKey: 'LBL_ADD_NEW',
    createLabelFallback: 'Add new',
    tableClassName: 'table--hovered table-dragable',
    hidePagination: true,
    hideSearchPanel: true,
    showDragHandle: true,
    defaultFilters: { type: typeId },
    columns: [
      { key: 'serial', labelKey: 'LBL_SRNO', labelFallback: 'Sr. No' },
      { key: 'identifier', labelKey: meta.identifierLabel, labelFallback: 'Preference identifier' },
      { key: 'title', labelKey: meta.titleLabel, labelFallback: 'Preference title' },
      { key: 'action', labelKey: 'LBL_ACTION', labelFallback: 'Action', align: 'right' },
    ],
  };
}

export const TEACHER_PREFERENCE_MODULE_CONFIGS: Record<string, AdminModuleConfig> = {
  'speak-language': {
    module: 'speak-language',
    pageLangKey: 'spoken-languages',
    titleKey: 'LBL_SPOKEN_LANGUAGE',
    titleFallback: 'Spoken language',
    exportable: true,
    creatable: true,
    canEditPrivilege: 'canEditSpeakLanguage',
    createLabelKey: 'LBL_ADD_NEW',
    createLabelFallback: 'Add new',
    tableClassName: 'table--hovered table-dragable',
    hidePagination: true,
    hideSearchPanel: true,
    searchFields: keywordSearch,
    columns: [
      ...identifierNameStatusColumns('LBL_SPEAK_LANGUAGE_IDENTIFIER', 'LBL_SPEAK_LANGUAGE_NAME'),
      { key: 'action', labelKey: 'LBL_ACTION', labelFallback: 'Action', align: 'right' },
    ],
  },
  'speak-language-levels': {
    module: 'speak-language-levels',
    pageLangKey: 'spoken-language-levels',
    titleKey: 'LBL_SPOKEN_LANGUAGE_LEVELS',
    titleFallback: 'Spoken language levels',
    exportable: true,
    creatable: true,
    canEditPrivilege: 'canEditSpeakLanguageLevels',
    createLabelKey: 'LBL_ADD_NEW',
    createLabelFallback: 'Add new',
    tableClassName: 'table--hovered table-dragable',
    hidePagination: true,
    hideSearchPanel: true,
    searchFields: keywordSearch,
    columns: [
      ...identifierNameStatusColumns('LBL_LANGUAGE_LEVEL_IDENTIFIER', 'LBL_LANGUAGE_LEVEL_NAME'),
      { key: 'action', labelKey: 'LBL_ACTION', labelFallback: 'Action', align: 'right' },
    ],
  },
  'teach-language': {
    module: 'teach-language',
    pageLangKey: 'teach-language',
    titleKey: 'LBL_TEACHING_LANGUAGE',
    titleFallback: 'Teaching subjects',
    exportable: true,
    creatable: true,
    canEditPrivilege: 'canEditTeachLanguage',
    createLabelKey: 'LBL_ADD_NEW',
    createLabelFallback: 'Add new',
    tableClassName: 'table--hovered table-dragable',
    hidePagination: true,
    hideSearchPanel: true,
    defaultFilters: { parent_id: '0' },
    searchFields: keywordSearch,
    columns: [
      { key: 'serial', labelKey: 'LBL_SRNO', labelFallback: 'Sr. No' },
      { key: 'identifier', labelKey: 'LBL_LANGUAGE_IDENTIFIER', labelFallback: 'Identifier' },
      { key: 'title', labelKey: 'LBL_Teach_Language_name', labelFallback: 'Name' },
      { key: 'subcategories_label', labelKey: 'LBL_SUB_LANGUAGES', labelFallback: 'Sub languages' },
      { key: 'min_price_label', labelKey: 'LBL_MIN_PRICE/HOUR', labelFallback: 'Min price/hour' },
      { key: 'max_price_label', labelKey: 'LBL_MAX_PRICE/HOUR', labelFallback: 'Max price/hour' },
      { key: 'featured_label', labelKey: 'LBL_FEATURED_TLANG', labelFallback: 'Featured' },
      { key: 'active', labelKey: 'LBL_STATUS', labelFallback: 'Status' },
      { key: 'action', labelKey: 'LBL_ACTION', labelFallback: 'Action', align: 'right' },
    ],
  },
  'issue-report-options': {
    module: 'issue-report-options',
    pageLangKey: 'issue-report-options',
    titleKey: 'LBL_ISSUE_REPORT_OPTIONS',
    titleFallback: 'Issue report options',
    exportable: true,
    creatable: true,
    canEditPrivilege: 'canEditIssueReportOptions',
    createLabelKey: 'LBL_ADD_NEW',
    createLabelFallback: 'Add new',
    tableClassName: 'table--hovered table-dragable',
    hidePagination: true,
    hideSearchPanel: true,
    searchFields: keywordSearch,
    columns: [
      { key: 'serial', labelKey: 'LBL_SRNO', labelFallback: 'Sr. No' },
      { key: 'identifier', labelKey: 'LBL_IDENTIFIER', labelFallback: 'Identifier' },
      { key: 'title', labelKey: 'LBL_TITLE', labelFallback: 'Title' },
      { key: 'active', labelKey: 'LBL_STATUS', labelFallback: 'Status' },
      { key: 'action', labelKey: 'LBL_ACTION', labelFallback: 'Action', align: 'right' },
    ],
  },
};
