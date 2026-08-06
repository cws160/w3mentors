import moment from 'moment';
import type { TeacherDashboardUpcomingLessonGroup } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';
import { dashboardPath } from '../dashboardPaths';

type Props = {
  groups: TeacherDashboardUpcomingLessonGroup[];
};

function formatLessonTime(startTime: string | null): string {
  if (!startTime) return '';
  return moment(startTime).format('HH:mm');
}

/** Legacy dashboard/views/lessons/short-detail-listing.php */
export function UpcomingLessonsSidebar({ groups }: Props) {
  const { lbl } = useSite();

  if (groups.length === 0) {
    return (
      <div className="message-display">
        <h5>{lbl('LBL_NO_UPCOMING_LESSON', 'No upcoming lesson')}</h5>
        <a href={dashboardPath('teacher', 'lessons')} className="btn btn--primary">
          {lbl('LBL_VIEW_ALL_LESSONS', 'View all lessons')}
        </a>
      </div>
    );
  }

  return (
    <>
      {groups.map((group) => (
        <div className="lesson-list-container" key={group.key}>
          <div className="lesson-list_head">
            <div className="date">
              <p>{group.key}</p>
            </div>
            {group.lessons.map((lesson) => {
              const offline = !!lesson.offline;
              const offlineClass = offline ? 'bg-yellow' : 'bg-info';
              const offlineTooltip = offline
                ? lbl('LBL_IN-PERSON_SESSION', 'In-person session')
                : lbl('LBL_ONLINE_SESSION', 'Online session');

              return (
                <div
                  key={lesson.id}
                  className={`lesson-list${offline ? ' noafter' : ''} short-details`}
                >
                  <div className="lesson-list__left">
                    <div
                      className="avtar avtar--small avtar--round avtar--centered"
                      data-title={firstChar(lesson.counterparty.first_name)}
                    >
                      <img
                        src={imageUrl(AFILE.USER_PROFILE, lesson.counterparty.id, 'SMALL')}
                        alt=""
                      />
                    </div>
                  </div>
                  <div className="lesson-list__right">
                    <p>
                      <span
                        className={`badge--round box-hint list-inline-item m-0 -no-border ${offlineClass}`}
                        title={offlineTooltip}
                      >
                        &nbsp;
                      </span>
                      {lesson.counterparty.full_name}
                    </p>
                    <p className="lesson-time">
                      <span>{formatLessonTime(lesson.start_time)}</span>
                      {lesson.lesson_title}
                    </p>
                  </div>
                  {!offline && (
                    <a
                      href={dashboardPath('teacher', `lessons/${lesson.id}`)}
                      className="lesson-list__action"
                    />
                  )}
                </div>
              );
            })}
          </div>
        </div>
      ))}
    </>
  );
}
