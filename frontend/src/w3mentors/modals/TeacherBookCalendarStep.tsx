import moment from 'moment';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  teachersApi,
  type TeacherAvailabilitySlotEntry,
} from '../../api/client';
import { useSite } from '../context/SiteContext';
import { useModal } from '../context/ModalContext';
import { formatApiDate, initBookingAvailabilityCalendar } from '../lib/booking-availability-calendar';
import { $ } from '../lib/setup-jquery';
import { formatMoney } from '../utils/assets';

const LESSON_TYPE_REGULAR = 2;
const LESSON_TYPE_SUBSCRIPTION = 3;

type SelectedSlot = {
  ordles_starttime: string;
  ordles_endtime: string;
};

type Props = {
  slugOrId: string;
  quantity: number;
  duration: number;
  ordlesType: number;
  ordlesOffline: number;
  ordlesAddressId: number;
  totalPrice: number;
  onBack: () => void;
};

export function TeacherBookCalendarStep({
  slugOrId,
  quantity,
  duration,
  ordlesType,
  ordlesOffline: _ordlesOffline,
  ordlesAddressId: _ordlesAddressId,
  totalPrice,
  onBack,
}: Props) {
  const { lbl } = useSite();
  const { closeModal } = useModal();
  const calendarRef = useRef<HTMLDivElement>(null);
  const [minDate, setMinDate] = useState('');
  const [bookDate, setBookDate] = useState('');
  const [entries, setEntries] = useState<TeacherAvailabilitySlotEntry[]>([]);
  const [dateHeading, setDateHeading] = useState('');
  const [slotsEmpty, setSlotsEmpty] = useState(false);
  const [slotsLoading, setSlotsLoading] = useState(false);
  const [selectedSlots, setSelectedSlots] = useState<Record<string, SelectedSlot>>({});

  const labelToSchedule = lbl('LBL_LESSON(S)_TO_SCHEDULE', 'Lesson(s) to schedule');
  const labelAllScheduled = lbl('LBL_ALL_SCHEDULED', 'All scheduled');

  const loadSlots = useCallback(
    (date: string) => {
      if (!date || !duration) return;
      setSlotsLoading(true);
      teachersApi
        .availabilitySlots(slugOrId, { date, duration })
        .then((res) => {
          const data = res.data.data;
          setEntries(data.entries ?? []);
          setDateHeading(data.date_heading);
          setSlotsEmpty(data.empty);
        })
        .catch(() => {
          setEntries([]);
          setDateHeading('');
          setSlotsEmpty(true);
        })
        .finally(() => setSlotsLoading(false));
    },
    [slugOrId, duration]
  );

  useEffect(() => {
    teachersApi.availabilityMeta(slugOrId).then((res) => {
      setMinDate(res.data.data.min_date);
    });
  }, [slugOrId]);

  useEffect(() => {
    if (!calendarRef.current || !minDate) return;
    return initBookingAvailabilityCalendar(calendarRef.current, minDate, '', (date) => {
      const formatted = formatApiDate(date);
      setBookDate(formatted);
      loadSlots(formatted);
    });
  }, [minDate, loadSlots]);

  useEffect(() => {
    if (bookDate) loadSlots(bookDate);
  }, [duration]);

  useEffect(() => {
    const onDropTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
      e.preventDefault();
      $('.drop-target-js').toggleClass('is-visible');
      $(this).toggleClass('is-open');
    };
    const onClosePopup = function (e: JQuery.TriggeredEvent) {
      e.preventDefault();
      $('.drop-trigger-js').trigger('click');
    };

    $(document).on('click.bookingDrop', '.viewCalendarJs .drop-trigger-js', onDropTrigger);
    $(document).on('click.bookingDropClose', '.viewCalendarJs .js--close-popup', onClosePopup);

    return () => {
      $(document).off('click.bookingDrop', '.viewCalendarJs .drop-trigger-js', onDropTrigger);
      $(document).off('click.bookingDropClose', '.viewCalendarJs .js--close-popup', onClosePopup);
    };
  }, []);

  const scheduledCount = Object.keys(selectedSlots).length;
  const unscheduledCount = Math.max(0, quantity - scheduledCount);

  const dropActionValue = useMemo(() => {
    if (unscheduledCount > 0) {
      return labelToSchedule;
    }
    if (quantity > 1) {
      return labelAllScheduled;
    }
    const first = Object.values(selectedSlots)[0];
    if (!first) return labelToSchedule;
    const start = moment(first.ordles_starttime);
    const end = moment(first.ordles_endtime);
    return `${start.format('dddd MMM D, YYYY h:mm A')} - ${end.format('h:mm A')}`;
  }, [unscheduledCount, quantity, selectedSlots, labelToSchedule, labelAllScheduled]);

  const lessonListItems = useMemo(() => {
    const keys = Object.keys(selectedSlots);
    return Array.from({ length: quantity }, (_, i) => {
      const key = keys[i];
      const slot = key ? selectedSlots[key] : null;
      if (slot) {
        const start = moment(slot.ordles_starttime);
        const end = moment(slot.ordles_endtime);
        return {
          text: `${start.format('dddd MMM D, YYYY h:mm A')} - ${end.format('h:mm A')}`,
          selected: true,
          deleteId: key,
        };
      }
      return { text: labelToSchedule, selected: false, deleteId: '' };
    });
  }, [quantity, selectedSlots, labelToSchedule]);

  const toggleSlot = (start: string) => {
    setSelectedSlots((prev) => {
      if (prev[start]) {
        const next = { ...prev };
        delete next[start];
        return next;
      }
      if (Object.keys(prev).length >= quantity) {
        return prev;
      }
      const startTime = moment(start).format('YYYY-MM-DD HH:mm:ss');
      const endTime = moment(start).add(duration, 'minutes').format('YYYY-MM-DD HH:mm:ss');
      return {
        ...prev,
        [start]: { ordles_starttime: startTime, ordles_endtime: endTime },
      };
    });
  };

  const deleteSlotById = (eventId: string) => {
    if (!eventId) return;
    setSelectedSlots((prev) => {
      const next = { ...prev };
      delete next[eventId];
      return next;
    });
    $("input[name='slots[]']").each(function () {
      const el = this as HTMLInputElement;
      if (el.value === eventId) {
        el.checked = false;
        el.classList.remove('is-checked');
      }
    });
  };

  const canProceedRegular =
    ordlesType === LESSON_TYPE_REGULAR && scheduledCount >= 1 && scheduledCount === quantity;
  const canProceedSubscription =
    ordlesType === LESSON_TYPE_SUBSCRIPTION && scheduledCount === quantity;

  return (
    <>
      <div className="modal-header modal-header--checkout">
        <button type="button" className="btn-back" onClick={onBack} />
        <h4 className="flex-1 text-center">
          {lbl('LBL_SCHEDULE_YOUR_LESSONS', 'Schedule your lessons')}
        </h4>
        <button
          type="button"
          className="btn-close w3mentorsmodalJs close-checkout-modal"
          data-bs-dismiss="modal"
          aria-label=""
          onClick={closeModal}
        />
      </div>
      <div className="modal-body p-0 viewCalendarJs">
        <div className="checkout-body">
          <div className="schedule-calendar">
            <div className="schedule-calendar__left">
              <div className="calendar-wrapper">
                <div
                  className="calendar availability-calendar-js"
                  id="booking-calendar"
                  ref={calendarRef}
                />
              </div>
            </div>
            {quantity > 0 && (
              <div className="schedule-calendar__right">
                <div className="calendar-panel calendarPanelJs" style={{ height: 260 }}>
                  <div className="calendar-panel-head">
                    <div className="drop-action">
                      <div className="drop-action__head" id="lesson-drop-action">
                        <div className="drop-action__label">
                          <span className="unscheduled-lessson-js">
                            {unscheduledCount > 0 ? unscheduledCount : ''}
                          </span>
                          <span className="drop-action__value">{dropActionValue}</span>
                        </div>
                        <span
                          className={`is-delete single-quantity-js${quantity === 1 && scheduledCount === 1 ? '' : ' d-none'}`}
                          style={{ cursor: 'pointer' }}
                          data-id={quantity === 1 ? Object.keys(selectedSlots)[0] ?? '' : ''}
                          onClick={(e) => {
                            const id = (e.currentTarget as HTMLElement).dataset.id ?? '';
                            deleteSlotById(id);
                          }}
                        />
                        {quantity > 1 && (
                          <a href="javascript:void(0);" className="drop-action__trigger drop-trigger-js link">
                            {lbl('LBL_Click_here', 'Click here')}
                          </a>
                        )}
                      </div>
                      <div className="drop-action__target drop-target-js">
                        <div className="schedule-lessions">
                          <div className="schedule-lessions__head">
                            <h6>{lbl('LBL_Scheduled_Lesson(s)', 'Scheduled lesson(s)')}</h6>
                            <button type="button" className="btn-close js--close-popup" />
                          </div>
                          <div className="schedule-lessions__body">
                            <div className="listing-wrapper">
                              <div className="numbers-list" id="cal-lesson-list">
                                {lessonListItems.map((item, i) => (
                                  <span
                                    key={i}
                                    className={`numbers-list__item${item.selected ? ' is-selected' : ''}`}
                                  >
                                    <span className="number-list__value">{item.text}</span>
                                    <span
                                      className={`is-delete${item.selected ? '' : ' d-none'}`}
                                      data-id={item.deleteId}
                                      style={{ cursor: 'pointer' }}
                                      onClick={() => deleteSlotById(item.deleteId)}
                                    />
                                  </span>
                                ))}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="calendar-panel-body">
                    <div className="timeslot-picker cslots-available-js">
                      {slotsLoading && (
                        <p className="text-center p-3">{lbl('LBL_Loading', 'Loading...')}</p>
                      )}
                      {!slotsLoading && slotsEmpty && (
                        <div className="noslot-available">
                          <h4>{lbl('LBL_OOPS!', 'Oops!')}</h4>
                          <div>
                            {lbl(
                              'LBL_NO_SLOT_AVAILABLE_FOR_THIS_DATE',
                              'No slot available for this date.'
                            )}
                          </div>
                        </div>
                      )}
                      {!slotsLoading && !slotsEmpty && entries.length > 0 && (
                        <>
                          <div className="timeslot-picker__head text-center">
                            <h6 className="selected-day">{dateHeading}</h6>
                          </div>
                          <div className="timeslot-picker__body">
                            <div className="calendar-timeslots" style={{ display: 'flex' }}>
                              {entries.map((slot) => (
                                <label className="radio-option" key={slot.start}>
                                  <input
                                    type="checkbox"
                                    name="slots[]"
                                    value={slot.start}
                                    className={selectedSlots[slot.start] ? 'is-checked' : ''}
                                    checked={!!selectedSlots[slot.start]}
                                    onChange={() => toggleSlot(slot.start)}
                                  />
                                  <span className="calendar-time">{slot.label}</span>
                                </label>
                              ))}
                            </div>
                          </div>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
      <div className="modal-footer">
        <div className="row justify-content-center align-items-center gap-md-5">
          <div className="col-auto">
            <div className="cart-price">
              <span className="cart-price__label">
                {lbl('LBL_TOTAL_PRICE', 'Total price')}:
              </span>
              <span className="cart-price__value" id="price-js">
                {formatMoney(totalPrice)}
              </span>
            </div>
          </div>
          <div className="col-auto">
            {ordlesType === LESSON_TYPE_REGULAR ? (
              <button
                type="button"
                className={`btn btn--primary color-white${canProceedRegular ? '' : ' btn--disabled'}`}
                id="lesson-checkout-btn-js"
                disabled={!canProceedRegular}
                onClick={closeModal}
              >
                {lbl('LBL_NEXT', 'Next')}
              </button>
            ) : (
              <button
                type="button"
                className={`btn btn--primary color-white${canProceedSubscription ? '' : ' btn--disabled'}`}
                id="subcrip-checkout-btn-js"
                disabled={!canProceedSubscription}
                onClick={closeModal}
              >
                {lbl('LBL_NEXT', 'Next')}
              </button>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
