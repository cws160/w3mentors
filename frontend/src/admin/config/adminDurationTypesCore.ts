export const ADMIN_DURATION_TYPE_TODAY = 1;
export const ADMIN_DURATION_TYPE_THIS_WEEK = 2;
export const ADMIN_DURATION_TYPE_LAST_WEEK = 3;
export const ADMIN_DURATION_TYPE_THIS_MONTH = 4;
export const ADMIN_DURATION_TYPE_LAST_MONTH = 5;
export const ADMIN_DURATION_TYPE_THIS_YEAR = 6;
export const ADMIN_DURATION_TYPE_LAST_YEAR = 7;
export const ADMIN_DURATION_TYPE_LAST_12_MONTH = 8;
export const ADMIN_DURATION_TYPE_ALL = 9;

export type AdminDurationOption = {
  id: number;
  labelKey: string;
  fallback: string;
};

export const ADMIN_DURATION_OPTIONS: AdminDurationOption[] = [
  { id: ADMIN_DURATION_TYPE_TODAY, labelKey: 'LBL_TODAY', fallback: 'Today' },
  { id: ADMIN_DURATION_TYPE_THIS_WEEK, labelKey: 'LBL_THIS_WEEK', fallback: 'This week' },
  { id: ADMIN_DURATION_TYPE_LAST_WEEK, labelKey: 'LBL_LAST_WEEK', fallback: 'Last week' },
  { id: ADMIN_DURATION_TYPE_THIS_MONTH, labelKey: 'LBL_THIS_MONTH', fallback: 'This month' },
  { id: ADMIN_DURATION_TYPE_LAST_MONTH, labelKey: 'LBL_LAST_MONTH', fallback: 'Last month' },
  { id: ADMIN_DURATION_TYPE_THIS_YEAR, labelKey: 'LBL_THIS_YEAR', fallback: 'This year' },
  { id: ADMIN_DURATION_TYPE_LAST_YEAR, labelKey: 'LBL_LAST_YEAR', fallback: 'Last year' },
  { id: ADMIN_DURATION_TYPE_LAST_12_MONTH, labelKey: 'LBL_LAST_12_MONTH', fallback: 'Last 12 months' },
  { id: ADMIN_DURATION_TYPE_ALL, labelKey: 'LBL_ALL', fallback: 'All' },
];
