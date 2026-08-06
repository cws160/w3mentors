import type { JQueryStatic } from 'jquery';
import type moment from 'moment';

export const LEGACY_CALENDAR_SCRIPT_PATHS = [
  '/w3mentors/dashboard/js/fullcalendar-luxon.min.js',
  '/w3mentors/dashboard/js/fullcalendar.min.js',
  '/w3mentors/dashboard/js/fullcalendar-luxon-global.min.js',
  '/w3mentors/dashboard/js/fateventcalendar.js',
] as const;

declare global {
  interface Window {
    userType?: number;
    moreLinkTextLabel?: string;
    confFrontEndUrl?: string;
    layoutDirection?: string;
    weekDayNames?: { shortName: string[]; longName: string[] };
    langLbl?: Record<string, string>;
    tFmtJs?: string;
    decodeHtmlCharCodes?: (str: string) => string;
    FullCalendar?: { Calendar: new (el: HTMLElement, config: object) => unknown };
    FatEventCalendar?: new (teacherId: number, offset: string) => {
      generalAvailaibility?: (currentTime: string) => unknown;
      weeklyAvailaibility?: (currentTime: string, initialDate?: string) => unknown;
      TeacherDashboardCalendar?: (currentTime: string, userId: number) => { destroy?: () => void } | void;
    };
    calendar?: {
      getEvents: () => Array<{ start: Date | null; end: Date | null }>;
      view: { activeStart: Date; activeEnd: Date };
    };
    fcom?: {
      makeUrl: (controller: string, action: string, args?: unknown[], base?: string) => string;
      updateWithAjax: (
        url: string,
        data: string,
        callback: (res: { status?: number; msg?: string; data?: unknown }) => void,
        options?: { process?: boolean }
      ) => void;
    };
  }
}

let scriptsReady: Promise<void> | null = null;

export function decodeHtmlCharCodes(str: string): string {
  return str.replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)));
}

function loadScript(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) {
      resolve();
      return;
    }
    const script = document.createElement('script');
    script.src = src;
    script.async = false;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed to load ${src}`));
    document.body.appendChild(script);
  });
}

export function ensureLegacyCalendarScripts(
  $: JQueryStatic,
  momentLib: typeof moment
): Promise<void> {
  if (scriptsReady) {
    return scriptsReady;
  }

  scriptsReady = (async () => {
    window.$ = $;
    window.jQuery = $;
    window.moment = momentLib;
    window.decodeHtmlCharCodes = decodeHtmlCharCodes;
    window.layoutDirection = 'ltr';
    window.weekDayNames = {
      shortName: momentLib.weekdaysShort(),
      longName: momentLib.weekdays(),
    };
    for (const src of LEGACY_CALENDAR_SCRIPT_PATHS) {
      await loadScript(src);
    }
  })();

  return scriptsReady;
}
