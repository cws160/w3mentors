import { useEffect, useRef, useState, type RefObject } from 'react';
import $ from 'jquery';
import moment from 'moment';
import { api } from '../../../api/client';
import { ensureLegacyCalendarScripts } from './legacyCalendarGlobals';

type CalendarInstance = { destroy?: () => void };

function installDashboardFcomBridge() {
  const previous = window.fcom;
  window.fcom = {
    makeUrl(controller: string, action: string) {
      return `${controller}-${action}`;
    },
    updateWithAjax(url, data, callback) {
      if (url === 'Teachers-getTeacherScheduledSessions') {
        const params = new URLSearchParams(data);
        const token = localStorage.getItem('auth_token');
        const headers: Record<string, string> = { Accept: 'application/json' };
        if (token) {
          headers.Authorization = `Bearer ${token}`;
        }
        const base = import.meta.env.VITE_API_URL || '/api/v1';
        fetch(`${base}/teacher/dashboard/scheduled-sessions?${params.toString()}`, { headers })
          .then(async (res) => {
            const json = await res.json();
            if (!res.ok) {
              callback({ status: 0, msg: json.message || 'Request failed' });
              return;
            }
            callback({ status: 1, data: json.data });
          })
          .catch(() => callback({ status: 0, msg: 'Network error' }));
        return;
      }
      previous?.updateWithAjax(url, data, callback);
    },
  };
}

/** Legacy dashboard/views/teacher/index.php — FatEventCalendar.TeacherDashboardCalendar */
export function useTeacherDashboardCalendar(
  containerRef: RefObject<HTMLDivElement | null>,
  teacherId: number
) {
  const [ready, setReady] = useState(false);
  const [currentTime, setCurrentTime] = useState('');
  const [timezoneOffset, setTimezoneOffset] = useState('+00:00');
  const calendarRef = useRef<CalendarInstance | null>(null);
  const scriptsBootstrapped = useRef(false);

  useEffect(() => {
    api
      .get<{ data: { current_time: string; timezone_offset: string } }>(
        '/account/teacher/availability/context'
      )
      .then((res) => {
        setCurrentTime(res.data.data.current_time);
        setTimezoneOffset(res.data.data.timezone_offset);
      })
      .catch(() => {
        setCurrentTime(moment().format('YYYY-MM-DD HH:mm:ss'));
        setTimezoneOffset(moment().format('Z'));
      });
  }, []);

  useEffect(() => {
    if (scriptsBootstrapped.current) {
      return;
    }
    let cancelled = false;
    scriptsBootstrapped.current = true;
    (async () => {
      try {
        window.langLbl = { today: 'Today' };
        window.tFmtJs = 'h:mm A';
        window.userType = 2;
        window.moreLinkTextLabel = 'View more';
        window.confFrontEndUrl = '';
        installDashboardFcomBridge();
        await ensureLegacyCalendarScripts($, moment);
        if (!cancelled) {
          setReady(true);
        }
      } catch {
        scriptsBootstrapped.current = false;
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (!ready || !teacherId || !currentTime) {
      return;
    }
    const calendarEl = containerRef.current;
    if (!calendarEl) {
      return;
    }
    const FatEventCalendar = window.FatEventCalendar;
    if (!FatEventCalendar) {
      return;
    }

    calendarRef.current?.destroy?.();
    calendarRef.current = null;

    const fecal = new FatEventCalendar(0, timezoneOffset);
    const instance = fecal.TeacherDashboardCalendar?.(currentTime, teacherId);
    if (instance && typeof instance === 'object') {
      calendarRef.current = instance as CalendarInstance;
    }

    return () => {
      calendarRef.current?.destroy?.();
      calendarRef.current = null;
    };
  }, [ready, teacherId, currentTime, timezoneOffset, containerRef]);
}
