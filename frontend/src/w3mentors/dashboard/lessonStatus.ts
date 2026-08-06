export const LESSON_STATUS = {
  ALL: -1,
  UNSCHEDULED: 1,
  SCHEDULED: 2,
  COMPLETED: 3,
  CANCELLED: 4,
} as const;

export function lessonStatusLabel(status: number, lbl: (k: string, f: string) => string): string {
  switch (status) {
    case LESSON_STATUS.UNSCHEDULED:
      return lbl('LBL_UNSCHEDULED', 'Unscheduled');
    case LESSON_STATUS.SCHEDULED:
      return lbl('LBL_SCHEDULED', 'Scheduled');
    case LESSON_STATUS.COMPLETED:
      return lbl('LBL_COMPLETED', 'Completed');
    case LESSON_STATUS.CANCELLED:
      return lbl('LBL_CANCELLED', 'Cancelled');
    default:
      return lbl('LBL_NA', 'N/A');
  }
}
