import { useCallback, useEffect, useMemo, useState } from 'react';
import { api } from '../../../api/client';
import { useAuth } from '../../../context/AuthContext';
import { useSite } from '../../context/SiteContext';
import {
  useLegacyAvailabilityCalendar,
  type AvailabilityTab,
} from '../availability/useLegacyAvailabilityCalendar';
import { ProfileProgressBar } from '../components/ProfileProgressBar';

type ProfileProgress = {
  percentage: number;
  total_fields: number;
  total_filled: number;
  is_completed: boolean;
};

export function DashboardAvailabilityPage() {
  const { lbl } = useSite();
  const { user } = useAuth();
  const teacherId = user?.id ?? 0;
  const [activeTab, setActiveTab] = useState<AvailabilityTab>('general');
  const [timezone, setTimezone] = useState('UTC');
  const [timezoneOffset, setTimezoneOffset] = useState('+00:00');
  const [currentTime, setCurrentTime] = useState('');
  const [progress, setProgress] = useState<ProfileProgress | null>(null);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [calendarKey, setCalendarKey] = useState(0);

  const loadContext = useCallback(() => {
    api
      .get<{
        data: { current_time: string; timezone: string; timezone_offset: string };
      }>('/account/teacher/availability/context')
      .then((res) => {
        setCurrentTime(res.data.data.current_time);
        setTimezone(res.data.data.timezone);
        setTimezoneOffset(res.data.data.timezone_offset);
      })
      .catch(() => undefined);

    api
      .get<{ data: ProfileProgress }>('/account/teacher/availability/progress')
      .then((res) => setProgress(res.data.data))
      .catch(() => undefined);
  }, []);

  useEffect(() => {
    loadContext();
  }, [loadContext]);

  const calendarLabels = useMemo(
    () => ({
      today: lbl('LBL_TODAY', 'Today'),
      confirmRemove: lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'),
      myTimeZoneLabel: lbl('LBL_MY_CURRENT_TIME', 'My current time'),
      timezoneString: lbl('LBL_TIMEZONE_STRING', 'Timezone'),
    }),
    [lbl]
  );

  const { ready, error, save } = useLegacyAvailabilityCalendar(
    teacherId,
    calendarLabels,
    timezone,
    timezoneOffset,
    currentTime,
    activeTab,
    calendarKey,
    (p) => setProgress(p as ProfileProgress)
  );

  const switchTab = (tab: AvailabilityTab) => {
    setActiveTab(tab);
    setCalendarKey((k) => k + 1);
  };

  const onSave = async () => {
    setSaving(true);
    setMessage('');
    try {
      await save();
      setMessage(lbl('LBL_AVAILABILITY_UPDATED_SUCCESSFULLY', 'Availability updated successfully'));
      loadContext();
    } catch {
      setMessage(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  const calendarId = activeTab === 'general' ? 'ga_calendar' : 'w_calendar';

  return (
    <div className="container container--fixed">
      <div className="page__head">
        <h1>{lbl('LBL_MANAGE_CALENDAR', 'Manage calendar')}</h1>
      </div>
      <div className="page__body">
        <div className="infobar">
          <div className="row justify-content-between align-items-start">
            <div className="col-lg-8 col-sm-8">
              <div className="d-flex">
                <div className="infobar__media me-4">
                  <div
                    className={`infobar__media-icon${
                      progress?.is_completed
                        ? ' infobar__media-icon--tick'
                        : ' infobar__media-icon--alert is-profile-complete-js'
                    }`}
                  >
                    {!progress?.is_completed && '!'}
                  </div>
                </div>
                <div className="infobar__content">
                  <h6 className="mb-1">{lbl('LBL_COMPLETE_YOUR_PROFILE', 'Complete your profile')}</h6>
                  <p className="m-0">
                    {lbl('LBL_PROFILE_INFO_HEADING', 'Finish your profile to start getting bookings.')}
                  </p>
                </div>
              </div>
            </div>
            {progress && (
              <div className="col-lg-3 col-sm-4">
                <ProfileProgressBar
                  totalFilled={progress.total_filled}
                  totalFields={progress.total_fields}
                />
              </div>
            )}
          </div>
        </div>

        <div className="page-panel" style={{ minHeight: 400 }}>
          <div className="page-panel__head">
            <div className="row align-items-center justify-content-between">
              <div className="col-6">
                <div className="tab-switch">
                  <a
                    href="javascript:void(0);"
                    className={`tab-switch__item${activeTab === 'general' ? ' is-active' : ''}`}
                    onClick={(e) => {
                      e.preventDefault();
                      switchTab('general');
                    }}
                  >
                    {lbl('LBL_GENERAL', 'General')}
                  </a>
                  <a
                    href="javascript:void(0);"
                    className={`tab-switch__item${activeTab === 'weekly' ? ' is-active' : ''}`}
                    onClick={(e) => {
                      e.preventDefault();
                      switchTab('weekly');
                    }}
                  >
                    {lbl('LBL_WEEKLY', 'Weekly')}
                  </a>
                </div>
              </div>
              <div className="col-lg-auto col-auto">
                <input
                  type="button"
                  value={lbl('LBL_SAVE', 'Save')}
                  className="btn btn--primary"
                  disabled={!ready || saving}
                  onClick={onSave}
                />
              </div>
            </div>
          </div>
          <div className="page-panel__body availability-setting-calendar" id="calendar-container">
            {message && <p className="color-secondary mb-3">{message}</p>}
            {error && <p className="color-danger mb-3">{error}</p>}
            {!ready && !error && (
              <p className="color-secondary">{lbl('LBL_PROCESSING_PLEASE_WAIT', 'Loading calendar…')}</p>
            )}
            <div
              key={`${activeTab}-${calendarKey}`}
              id={calendarId}
              className={`calendar-view availability-calendar ${
                activeTab === 'general' ? 'general-calendar' : 'weekly-calendar'
              }`}
            />
          </div>
        </div>
      </div>
    </div>
  );
}
