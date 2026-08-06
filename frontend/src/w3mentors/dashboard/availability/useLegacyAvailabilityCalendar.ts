import { useEffect, useState } from 'react';
import $ from 'jquery';
import moment from 'moment';
import { api } from '../../../api/client';
import { ensureLegacyCalendarScripts } from '../hooks/legacyCalendarGlobals';

function installFcomBridge(getToken: () => string | null) {
  const endpoints: Record<string, { method: 'get' | 'put'; path: string }> = {
    'Teacher-generalAvblJson': { method: 'get', path: '/account/teacher/availability/general' },
    'Teacher-avalabilityJson': { method: 'get', path: '/account/teacher/availability/weekly' },
    'Teacher-setupGeneralAvailability': { method: 'put', path: '/account/teacher/availability/general' },
    'Teacher-setupAvailability': { method: 'put', path: '/account/teacher/availability/weekly' },
    'Teacher-profileProgress': { method: 'get', path: '/account/teacher/availability/progress' },
  };

  window.fcom = {
    makeUrl(controller: string, action: string) {
      return `${controller}-${action}`;
    },
    updateWithAjax(url, data, callback) {
      const route = endpoints[url];
      if (!route) {
        callback({ status: 0, msg: 'Unknown action' });
        return;
      }
      const params = new URLSearchParams(data);
      const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      };
      const token = getToken();
      if (token) {
        headers.Authorization = `Bearer ${token}`;
      }

      const base = import.meta.env.VITE_API_URL || '/api/v1';
      let body: Record<string, unknown> | undefined;
      let query = '';

      if (url === 'Teacher-setupGeneralAvailability') {
        body = { data: JSON.parse(params.get('data') || '[]') };
      } else if (url === 'Teacher-setupAvailability') {
        body = {
          start: params.get('start'),
          end: params.get('end'),
          availability: JSON.parse(params.get('availability') || '[]'),
        };
      } else if (route.method === 'get') {
        query = `?${params.toString()}`;
      }

      fetch(`${base}${route.path}${query}`, {
        method: route.method.toUpperCase(),
        headers,
        body: body ? JSON.stringify(body) : undefined,
      })
        .then(async (res) => {
          const json = await res.json();
          if (!res.ok) {
            callback({ status: 0, msg: json.message || 'Request failed' });
            return;
          }
          callback({ status: 1, data: json.data, msg: json.message });
        })
        .catch(() => callback({ status: 0, msg: 'Network error' }));
    },
  };
}

export type AvailabilityTab = 'general' | 'weekly';

type CalendarLabels = {
  today: string;
  confirmRemove: string;
  myTimeZoneLabel: string;
  timezoneString: string;
};

export function useLegacyAvailabilityCalendar(
  teacherId: number,
  calendarLabels: CalendarLabels,
  _timezone: string,
  timezoneOffset: string,
  currentTime: string,
  activeTab: AvailabilityTab,
  calendarKey: number,
  onProgress?: (progress: Record<string, unknown>) => void
) {
  const [ready, setReady] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        window.langLbl = {
          today: calendarLabels.today,
          confirmRemove: calendarLabels.confirmRemove,
          myTimeZoneLabel: calendarLabels.myTimeZoneLabel,
          timezoneString: calendarLabels.timezoneString,
        };
        window.tFmtJs = 'h:mm A';
        installFcomBridge(() => localStorage.getItem('auth_token'));
        await ensureLegacyCalendarScripts($, moment);
        if (!cancelled) {
          setReady(true);
        }
      } catch {
        if (!cancelled) {
          setError('Could not load calendar scripts');
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [
    calendarLabels.today,
    calendarLabels.confirmRemove,
    calendarLabels.myTimeZoneLabel,
    calendarLabels.timezoneString,
  ]);

  useEffect(() => {
    if (!ready || !teacherId || !currentTime) {
      return;
    }

    const calendarId = activeTab === 'general' ? 'ga_calendar' : 'w_calendar';
    if (!document.getElementById(calendarId)) {
      return;
    }

    const FatEventCalendar = window.FatEventCalendar;
    if (!FatEventCalendar) {
      setError('Calendar library unavailable');
      return;
    }

    const fecal = new FatEventCalendar(teacherId, timezoneOffset);
    if (activeTab === 'general' && fecal.generalAvailaibility) {
      window.calendar = fecal.generalAvailaibility(currentTime) as typeof window.calendar;
    } else if (fecal.weeklyAvailaibility) {
      window.calendar = fecal.weeklyAvailaibility(currentTime) as typeof window.calendar;
    } else {
      setError('Calendar mode unavailable');
    }
    return () => {
      window.calendar = undefined;
    };
  }, [ready, teacherId, activeTab, currentTime, timezoneOffset, calendarKey]);

  const save = async () => {
    const cal = window.calendar;
    if (!cal) {
      return;
    }
    if (activeTab === 'general') {
      const data = cal.getEvents().map((e) => ({
        start: moment(e.start).format('YYYY-MM-DD HH:mm:ss'),
        end: moment(e.end).format('YYYY-MM-DD HH:mm:ss'),
      }));
      await api.put('/account/teacher/availability/general', { data });
      const progressRes = await api.get<{ data: Record<string, unknown> }>(
        '/account/teacher/availability/progress'
      );
      onProgress?.(progressRes.data.data);
      return;
    }

    const start = moment(cal.view.activeStart).format('YYYY-MM-DD HH:mm:ss');
    const end = moment(cal.view.activeEnd).format('YYYY-MM-DD HH:mm:ss');
    const allevents = cal.getEvents().map((e) => ({
      start: moment(e.start).format('YYYY-MM-DD HH:mm:ss'),
      end: moment(e.end).format('YYYY-MM-DD HH:mm:ss'),
    }));
    const merged = mergeEvents(allevents);
    await api.put('/account/teacher/availability/weekly', {
      start,
      end,
      availability: merged,
    });
  };

  return { ready, error, save };
}

function mergeEvents(
  allevents: Array<{ start: string; end: string } | null>
): Array<{ start: string; end: string }> {
  const events = [...allevents];
  for (let index = 0; index < events.length; index++) {
    const element = events[index];
    if (element == null) {
      continue;
    }
    let { start, end } = element;
    for (let i = 0; i < events.length; i++) {
      const event = events[i];
      if (index === i || event == null) {
        continue;
      }
      if (moment(end).isSameOrAfter(moment(event.start)) && moment(start).isSameOrBefore(moment(event.end))) {
        if (moment(start).isAfter(moment(event.start))) {
          start = event.start;
          events[index] = { ...element, start: event.start };
        }
        if (moment(end).isBefore(moment(event.end))) {
          end = event.end;
          events[index] = { ...events[index]!, end: event.end };
        }
        events[i] = null;
      }
    }
  }
  return events.filter((el): el is { start: string; end: string } => el != null);
}
