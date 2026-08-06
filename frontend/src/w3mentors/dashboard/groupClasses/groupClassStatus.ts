export const GRPCLS_STATUS = {
  ALL: -1,
  SCHEDULED: 1,
  COMPLETED: 2,
  CANCELLED: 3,
} as const;

export function groupClassStatusLabel(
  status: number,
  lbl: (key: string, fallback: string) => string
): string {
  switch (status) {
    case GRPCLS_STATUS.SCHEDULED:
      return lbl('LBL_SCHEDULED', 'Scheduled');
    case GRPCLS_STATUS.COMPLETED:
      return lbl('LBL_COMPLETED', 'Completed');
    case GRPCLS_STATUS.CANCELLED:
      return lbl('LBL_CANCELLED', 'Canceled');
    default:
      return String(status);
  }
}
