import { useCallback, useEffect, useRef, useState } from 'react';
import { teachersApi, type TeacherAvailabilityMeta, type TeacherAvailabilitySlots } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import {
  formatApiDate,
  initAvailabilityCalendar,
} from '../../lib/booking-availability-calendar';

type Props = {
  slugOrId: string;
  defaultDuration?: number;
};

export function TeacherAvailabilityPanel({ slugOrId, defaultDuration = 15 }: Props) {
  const { lbl } = useSite();
  const calendarRef = useRef<HTMLDivElement>(null);
  const [meta, setMeta] = useState<TeacherAvailabilityMeta | null>(null);
  const [minDate, setMinDate] = useState('');
  const [bookDate, setBookDate] = useState('');
  const [slots, setSlots] = useState<TeacherAvailabilitySlots | null>(null);
  const [loadingSlots, setLoadingSlots] = useState(false);

  const loadSlots = useCallback(
    (date: string) => {
      if (!date) return;
      setLoadingSlots(true);
      teachersApi
        .availabilitySlots(slugOrId, { date, duration: defaultDuration })
        .then((res) => setSlots(res.data.data))
        .catch(() =>
          setSlots({
            date_heading: date,
            entries: [],
            empty: true,
          })
        )
        .finally(() => setLoadingSlots(false));
    },
    [slugOrId, defaultDuration]
  );

  useEffect(() => {
    teachersApi.availabilityMeta(slugOrId).then((res) => {
      const data = res.data.data;
      setMeta(data);
      setMinDate(data.min_date);
    });
  }, [slugOrId]);

  useEffect(() => {
    if (!calendarRef.current || !minDate) return;
    return initAvailabilityCalendar(
      calendarRef.current,
      minDate,
      '',
      (date) => {
        const formatted = formatApiDate(date);
        setBookDate(formatted);
        loadSlots(formatted);
      },
      '.viewAvCalendarJs'
    );
  }, [minDate, loadSlots]);

  useEffect(() => {
    if (bookDate) loadSlots(bookDate);
  }, [defaultDuration]);

  const timezoneLine = meta
    ? `${lbl('LBL_MY_TIMEZONE', 'My timezone')}: ${meta.timezone_label}`
    : null;

  return (
    <div className="teacher-availability" id="availbility">
      {timezoneLine && <div className="mb-4">{timezoneLine}</div>}
      <div className="calendar-availablity viewAvCalendarJs">
        <div className="calendar-availablity__col">
          <div className="calendar-wrapper">
            <div className="availability-calendar-js" ref={calendarRef} />
          </div>
        </div>
        <div className="calendar-availablity__col">
          <div className="timeslots-wrapper slots-available-js calendarPanelJs">
            {loadingSlots && (
              <p className="text-muted">{lbl('LBL_Loading', 'Loading...')}</p>
            )}
            {!loadingSlots && slots && (
              <>
                {slots.empty ? (
                  <div className="noslot-available">
                    <h4>{lbl('LBL_OOPS!', 'Oops!')}</h4>
                    <div>
                      {lbl(
                        'LBL_NO_SLOT_AVAILABLE_FOR_THIS_DATE',
                        'No slot available for this date.'
                      )}
                    </div>
                  </div>
                ) : (
                  <div className="timeslots">
                    {slots.entries.map((entry) => (
                      <div className="timeslot" key={entry.start}>
                        {entry.label}
                      </div>
                    ))}
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
