import { lessonStatusLabel } from '../lessonStatus';

export const ISSUE_STATUS = {
  PROGRESS: 1,
  RESOLVED: 2,
  ESCALATED: 3,
  CLOSED: 4,
} as const;

export const ORDER_CLASS_STATUS = {
  SCHEDULED: 1,
  COMPLETED: 2,
  CANCELLED: 3,
} as const;

export function issueStatusLabel(status: number, lbl: (k: string, f: string) => string): string {
  switch (status) {
    case ISSUE_STATUS.PROGRESS:
      return lbl('STATUS_PROGRESS', 'In progress');
    case ISSUE_STATUS.RESOLVED:
      return lbl('STATUS_RESOLVED', 'Resolved');
    case ISSUE_STATUS.ESCALATED:
      return lbl('STATUS_ESCALATED', 'Escalated');
    case ISSUE_STATUS.CLOSED:
      return lbl('STATUS_CLOSED', 'Closed');
    default:
      return lbl('LBL_NA', 'N/A');
  }
}

export function orderClassStatusLabel(status: number, lbl: (k: string, f: string) => string): string {
  switch (status) {
    case ORDER_CLASS_STATUS.SCHEDULED:
      return lbl('LBL_SCHEDULED', 'Scheduled');
    case ORDER_CLASS_STATUS.COMPLETED:
      return lbl('LBL_COMPLETED', 'Completed');
    case ORDER_CLASS_STATUS.CANCELLED:
      return lbl('LBL_CANCELLED', 'Cancelled');
    default:
      return lbl('LBL_NA', 'N/A');
  }
}

export function sessionStatusLabel(
  recordType: number,
  status: number | null,
  lbl: (k: string, f: string) => string
): string {
  if (status === null) return lbl('LBL_NA', 'N/A');
  if (recordType === 2) {
    return orderClassStatusLabel(status, lbl);
  }
  return lessonStatusLabel(status, lbl);
}

function formatLegacyDateTime(value: string | null): string {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  const date = d.toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric',
  });
  const time = d.toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  });
  return `${date}, ${time}`;
}

export { formatLegacyDateTime };
