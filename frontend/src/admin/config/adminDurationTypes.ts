import moment from 'moment';
import { ADMIN_DURATION_TYPE_ALL, ADMIN_DURATION_OPTIONS, type AdminDurationOption } from './adminDurationTypesCore';

export {
  ADMIN_DURATION_TYPE_TODAY,
  ADMIN_DURATION_TYPE_THIS_WEEK,
  ADMIN_DURATION_TYPE_LAST_WEEK,
  ADMIN_DURATION_TYPE_THIS_MONTH,
  ADMIN_DURATION_TYPE_LAST_MONTH,
  ADMIN_DURATION_TYPE_THIS_YEAR,
  ADMIN_DURATION_TYPE_LAST_YEAR,
  ADMIN_DURATION_TYPE_LAST_12_MONTH,
  ADMIN_DURATION_TYPE_ALL,
  ADMIN_DURATION_OPTIONS,
  type AdminDurationOption,
} from './adminDurationTypesCore';

/** Mirrors legacy MyDate::getStartEndDate display bounds (Y-m-d). */
export function getAdminDurationBounds(id: number): { startDate: string; endDate: string } {
  const dayNumber = moment().day();
  let start = moment();
  let end = moment();

  switch (id) {
    case 1:
      start = moment().startOf('day');
      end = moment().startOf('day').add(1, 'day');
      break;
    case 2:
      if (dayNumber === 0) {
        start = moment().startOf('week');
        end = moment().startOf('week').add(7, 'days');
      } else {
        start = moment().startOf('isoWeek').subtract(1, 'day');
        end = moment().startOf('isoWeek').add(6, 'days');
      }
      break;
    case 3:
      if (dayNumber === 0) {
        start = moment().startOf('week').subtract(7, 'days');
        end = moment().startOf('week');
      } else {
        start = moment().startOf('isoWeek').subtract(8, 'days');
        end = moment().startOf('isoWeek').subtract(1, 'day');
      }
      break;
    case 4:
      start = moment().startOf('month');
      end = moment().startOf('month').add(1, 'month');
      break;
    case 5:
      start = moment().startOf('month').subtract(1, 'month');
      end = moment().startOf('month');
      break;
    case 6:
      start = moment().startOf('year');
      end = moment().startOf('year').add(1, 'year');
      break;
    case 7:
      start = moment().startOf('year').subtract(1, 'year');
      end = moment().startOf('year');
      break;
    case 8:
      start = moment().startOf('month').subtract(11, 'months');
      end = moment().startOf('month').add(1, 'month');
      break;
    case ADMIN_DURATION_TYPE_ALL:
    default:
      start = moment('2018-01-01').startOf('day');
      end = moment().startOf('month').add(1, 'month');
      break;
  }

  return {
    startDate: start.format('YYYY-MM-DD'),
    endDate: end.format('YYYY-MM-DD'),
  };
}

export function formatAdminDurationRange(id: number): string {
  const { startDate, endDate } = getAdminDurationBounds(id);
  const days = id === ADMIN_DURATION_TYPE_ALL ? 0 : 1;
  const displayEnd = moment(endDate).subtract(days, 'day');
  const fmt = (value: moment.Moment) => value.format('MMM D, YYYY');
  return `${fmt(moment(startDate))} - ${fmt(displayEnd)}`;
}

export function getAdminDurationLabel(
  id: number,
  lbl: (key: string, fallback: string) => string
): string {
  const option = ADMIN_DURATION_OPTIONS.find((item) => item.id === id);
  return option ? lbl(option.labelKey, option.fallback) : lbl('LBL_ALL', 'All');
}
