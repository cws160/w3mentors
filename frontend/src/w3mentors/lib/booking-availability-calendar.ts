/// <reference types="jqueryui" />
import moment from 'moment';
import { $ } from './setup-jquery';
import 'jquery-ui/ui/widgets/datepicker';

declare global {
  interface Window {
    monthNames?: { longName: string[]; shortName: string[] };
    dayShortNames?: string[];
  }
}

function ensureDatepickerLocale(): void {
  window.monthNames = {
    longName: moment.months(),
    shortName: moment.monthsShort(),
  };
  window.dayShortNames = moment.weekdaysMin();
}

export type AvailabilityCalendarRoot = '.viewCalendarJs' | '.viewAvCalendarJs';

export function adjustAvailabilityCalendarHeight(
  root: AvailabilityCalendarRoot = '.viewCalendarJs'
): void {
  const calendarWrapper = $(`${root} .calendar-wrapper`);
  const calendarPanel = $(`${root} .calendarPanelJs`);
  if (calendarWrapper.is(':visible') && calendarPanel.length) {
    calendarPanel.height(`${calendarWrapper.outerHeight()}px`);
  }
}

/** @deprecated Use adjustAvailabilityCalendarHeight */
export function adjustBookingCalendarHeight(): void {
  adjustAvailabilityCalendarHeight('.viewCalendarJs');
}

export function initAvailabilityCalendar(
  calendarEl: HTMLElement,
  minDate: string,
  maxDate: string,
  onSelect: (date: Date) => void,
  root: AvailabilityCalendarRoot = '.viewCalendarJs'
): () => void {
  ensureDatepickerLocale();
  const $cal = $(calendarEl);
  const minAvailDate = new Date(minDate);
  const maxAvailDate = maxDate ? new Date(maxDate) : null;
  const syncHeight = () => adjustAvailabilityCalendarHeight(root);

  $cal.datepicker({
    minDate: minAvailDate,
    maxDate: maxAvailDate,
    monthNames: window.monthNames!.longName,
    monthNamesShort: window.monthNames!.shortName,
    dayNamesMin: window.dayShortNames,
    dayNamesShort: window.dayShortNames,
    firstDay: 1,
    dateFormat: 'DD, MM d',
    onSelect() {
      const selected = $cal.datepicker('getDate');
      if (selected) {
        onSelect(selected);
      }
      setTimeout(syncHeight, 100);
    },
    onChangeMonthYear() {
      setTimeout(syncHeight, 100);
    },
  });

  const initial = $cal.datepicker('getDate') ?? minAvailDate;
  onSelect(initial);

  const onResize = () => syncHeight();
  window.addEventListener('resize', onResize);
  setTimeout(syncHeight, 200);

  return () => {
    window.removeEventListener('resize', onResize);
    try {
      $cal.datepicker('destroy');
    } catch {
      // not initialized
    }
  };
}

export function initBookingAvailabilityCalendar(
  calendarEl: HTMLElement,
  minDate: string,
  maxDate: string,
  onSelect: (date: Date) => void
): () => void {
  return initAvailabilityCalendar(calendarEl, minDate, maxDate, onSelect, '.viewCalendarJs');
}

export function formatApiDate(date: Date): string {
  return $.datepicker.formatDate('yy-mm-dd', date);
}
