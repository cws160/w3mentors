import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';
import { lessonStatusLabel } from '../lessonStatus';

export type LessonCardItem = {
  id: number;
  status: number;
  duration: number;
  amount: number;
  offline: boolean;
  start_time: string | null;
  end_time: string | null;
  address?: string;
  lesson_title: string;
  counterparty: {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    country_name: string;
  };
  can_schedule: boolean;
};

type Props = {
  lesson: LessonCardItem;
  lbl: (key: string, fallback: string) => string;
};

function formatLegacyTime(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

function formatLegacyDate(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleDateString(undefined, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

export function LessonCardLandscape({ lesson, lbl }: Props) {
  const role = useDashboardRole();
  const offlineClass = lesson.offline ? 'bg-yellow' : 'bg-info';
  const offlineTooltip = lesson.offline
    ? lbl('LBL_IN-PERSON_SESSION', 'In-person session')
    : lbl('LBL_ONLINE_SESSION', 'Online session');

  return (
    <div className="card-landscape">
      <div className="card-landscape__colum card-landscape__colum--first">
        {lesson.start_time || lesson.can_schedule ? (
          <>
            <div className="card-landscape__head">
              {lesson.start_time && lesson.end_time ? (
                <>
                  <time className="card-landscape__time">
                    {formatLegacyTime(lesson.start_time)} - {formatLegacyTime(lesson.end_time)}
                  </time>
                  <time className="card-landscape__date">
                    {formatLegacyDate(lesson.start_time)}
                  </time>
                </>
              ) : lesson.can_schedule ? (
                <span className="card-landscape__time">
                  {lbl('LBL_SCHEDULE_NOW', 'Schedule now')}
                </span>
              ) : null}
            </div>
          </>
        ) : null}
      </div>
      <div className="card-landscape__colum card-landscape__colum--second">
        <div className="card-landscape__head">
          <span className="card-landscape__title">
            <span
              className={`badge--round box-hint list-inline-item m-0 -no-border ${offlineClass}`}
              title={offlineTooltip}
            >
              &nbsp;
            </span>
            {lesson.lesson_title}
          </span>
          <span className="card-landscape__status badge color-secondary badge--curve badge--small ms-0">
            {lessonStatusLabel(lesson.status, lbl)}
          </span>
        </div>
      </div>
      <div className="card-landscape__colum card-landscape__colum--third">
        <div className="card-landscape__actions">
          <div className="profile-meta">
            <div className="profile-meta__media">
              <span
                className="avtar avtar--medium avtar--round"
                data-title={firstChar(lesson.counterparty.first_name)}
              >
                <img
                  src={imageUrl(AFILE.USER_PROFILE, lesson.counterparty.id, 'SMALL')}
                  alt=""
                />
              </span>
            </div>
            <div className="profile-meta__details">
              <p className="bold-600 color-black">{lesson.counterparty.full_name}</p>
              <p className="small">{lesson.counterparty.country_name}</p>
            </div>
          </div>
          <div className="actions-group">
            {!lesson.offline && lesson.status !== 4 && (
              <a
                href={dashboardPath(role, `lessons/${lesson.id}`)}
                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
              >
                <DashboardSpriteIcon id="enter" className="icon icon--enter icon--18" />
                <div className="tooltip tooltip--top bg-black">
                  {lbl('LBL_Enter_Classroom', 'Enter classroom')}
                </div>
              </a>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
