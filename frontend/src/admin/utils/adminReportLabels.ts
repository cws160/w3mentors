/**
 * Legacy admin report captions. w3mentors maps LBL_LANGUAGE to "Subjects" on the
 * frontend, but lesson/class language reports use the "Language" column label.
 */
export const ADMIN_REPORT_LABEL_FALLBACKS: Record<string, string> = {
  LBL_LANGUAGE: 'Language',
  EXP_LESSON_LANGUAGES: 'Lesson languages',
  EXP_CLASS_LANGUAGES: 'Class languages',
};

export function adminReportLabel(
  lbl: (key: string, fallback?: string) => string,
  labelKey: string,
  labelFallback?: string,
): string {
  const reportFallback = ADMIN_REPORT_LABEL_FALLBACKS[labelKey];
  if (reportFallback) {
    return reportFallback;
  }

  return lbl(labelKey, labelFallback ?? labelKey);
}
