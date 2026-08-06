export type AdminListColumn = {
  key: string;
  labelKey: string;
  labelFallback: string;
  align?: 'left' | 'right';
  render?: (row: Record<string, unknown>, index: number, page: number, perPage: number) => React.ReactNode;
};

export type AdminSearchField = {
  name: string;
  labelKey: string;
  labelFallback: string;
  type: 'text' | 'select' | 'date' | 'language';
  options?: { value: string; labelKey: string; labelFallback: string }[];
  col?: number;
  placeholderKey?: string;
  placeholderFallback?: string;
  /** Legacy input class (blog posts keyword uses `search-input`). */
  inputClass?: string;
};

export type AdminModuleConfig = {
  module: string;
  titleKey: string;
  titleFallback: string;
  columns: AdminListColumn[];
  searchFields?: AdminSearchField[];
  /** Legacy Fat form submit button column width (teacher-requests uses 6). */
  searchSubmitCol?: number;
  /** Legacy date input classes (users use small dateTimeFld field--calender). */
  searchDateInputClass?: string;
  tableClassName?: string;
  exportable?: boolean;
  importable?: boolean;
  creatable?: boolean;
  canEditPrivilege?: string;
  createLabelKey?: string;
  createLabelFallback?: string;
  pageLangKey?: string;
  useConfigTitle?: boolean;
  /** Applied on first load (e.g. preferences type from route). */
  defaultFilters?: Record<string, string>;
  hidePagination?: boolean;
  hideSearchPanel?: boolean;
  hidePageWarning?: boolean;
  showDragHandle?: boolean;
};
