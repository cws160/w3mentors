import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useDashboardMoney } from '../hooks/useDashboardMoney';
import { formatMoney } from '../../utils/assets';
import { groupClassStatusLabel } from './groupClassStatus';

export type GroupClassPlan = {
  plan_id: number;
  plan_title: string;
  plancls_id: number;
};

export type GroupClassCardItem = {
  id: number;
  grpcls_id?: number;
  title: string;
  start_time: string | null;
  end_time: string | null;
  offline: boolean;
  status: number;
  status_label?: string;
  entry_fee?: number;
  booked_seats?: number;
  total_seats?: number;
  is_package_class?: boolean;
  is_scheduled?: boolean;
  is_cancelled?: boolean;
  show_no_booking?: boolean;
  show_start_timer?: boolean;
  class_time_info?: '' | 'passed' | 'ongoing';
  plan?: GroupClassPlan;
  quiz_count?: number;
  can_edit?: boolean;
};

type Props = {
  item: GroupClassCardItem;
  lbl: (key: string, fallback: string) => string;
  isTeacher?: boolean;
  onAttachQuiz?: (grpclsId: number) => void;
};

function formatTime(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

function formatDate(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
}

function classTimeInfoLabel(
  info: GroupClassCardItem['class_time_info'],
  lbl: Props['lbl']
): string {
  if (info === 'ongoing') {
    return lbl('LBL_CLASS_IS_ONGOING', 'Class is ongoing');
  }
  if (info === 'passed') {
    return lbl('LBL_CLASS_TIME_HAS_PASSED', 'Class time has passed');
  }
  return '';
}

export function GroupClassCardLandscape({ item, lbl, isTeacher = true, onAttachQuiz }: Props) {
  const grpclsId = item.grpcls_id ?? item.id;
  const moneySymbol = useDashboardMoney();
  const offlineClass = item.offline ? 'bg-yellow' : 'bg-info';
  const offlineTooltip = item.offline
    ? lbl('LBL_IN-PERSON_SESSION', 'In-person session')
    : lbl('LBL_ONLINE_SESSION', 'Online session');

  const plan = item.plan ?? { plan_id: 0, plan_title: '', plancls_id: 0 };
  const quizCount = item.quiz_count ?? 0;
  const classScheduled = item.is_scheduled ?? item.status === 1;
  const showTimerBlock =
    classScheduled &&
    (item.show_start_timer || item.show_no_booking || Boolean(item.class_time_info));

  const timeInfo = classTimeInfoLabel(item.class_time_info, lbl);

  return (
    <div className="card-landscape">
      <div className="card-landscape__colum card-landscape__colum--first">
        {item.start_time && (
          <div className="card-landscape__head">
            <time className="card-landscape__time">
              {item.end_time
                ? `${formatTime(item.start_time)} - ${formatTime(item.end_time)}`
                : formatTime(item.start_time)}
            </time>
            <time className="card-landscape__date">{formatDate(item.start_time)}</time>
          </div>
        )}
        {showTimerBlock && (
          <div className="timer">
            {item.show_start_timer && (
              <>
                <div className="timer__media">
                  <span>
                    <DashboardSpriteIcon id="clock" className="icon icon--clock icon--small" />
                  </span>
                </div>
                <div className="timer__content">
                  <div className="timer__controls yocaoch-timer">00:00:00:00</div>
                </div>
              </>
            )}
            {item.show_no_booking && (
              <>
                <div className="timer__media">
                  <span>
                    <DashboardSpriteIcon id="clock" className="icon icon--clock icon--small" />
                  </span>
                </div>
                <div className="timer__content">
                  <span className="color-red">
                    {lbl('LBL_NO_ONE_HAS_BOOKED', 'No one has booked')}
                  </span>
                </div>
              </>
            )}
            {!item.show_no_booking && timeInfo && (
              <>
                <div className="timer__media">
                  <span>
                    <DashboardSpriteIcon id="clock" className="icon icon--clock icon--small" />
                  </span>
                </div>
                <div className="timer__content">
                  <span className="color-red">{timeInfo}</span>
                </div>
              </>
            )}
          </div>
        )}
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
            {item.title}
          </span>
          <span className="card-landscape__status badge color-secondary badge--curve badge--small ms-0">
            {item.status_label ?? groupClassStatusLabel(item.status, lbl)}
          </span>
          {isTeacher && (
            <>
              <span className="card-landscape__status badge color-primary badge--curve badge--small ms-0">
                {lbl('LBL_ENTRY_FEE', 'Entry fee')}: {formatMoney(item.entry_fee ?? 0, moneySymbol)}
              </span>
              <span className="card-landscape__status badge color-primary badge--curve badge--small ms-0">
                {lbl('LBL_BOOKED_SEATS', 'Booked seats')}: {item.booked_seats ?? 0}/
                {item.total_seats ?? 0}
              </span>
            </>
          )}
          {item.is_package_class && (
            <span className="card-landscape__status badge color-primary badge--curve badge--small ms-0">
              {lbl('LBL_PACKAGE_CLASS', 'Package class')}
            </span>
          )}
        </div>
        {!item.is_cancelled && isTeacher && (
          <div className="card-landscape__docs">
            {plan.plan_id > 0 ? (
              <div className="d-flex align-items-center">
                <span className="attachment-file color-black">
                  <DashboardSpriteIcon
                    id="attach"
                    className="icon icon--issue icon--attachement icon--xsmall color-black"
                  />
                  {plan.plan_title}
                </span>
                <button type="button" className="underline color-black btn btn--transparent btn--small" disabled>
                  {lbl('LBL_CHANGE', 'Change')}
                </button>
                <button type="button" className="underline color-black btn btn--transparent btn--small" disabled>
                  {lbl('LBL_REMOVE', 'Remove')}
                </button>
              </div>
            ) : (
              <button
                type="button"
                className="btn btn--transparent btn--addition color-black btn--small"
                disabled
              >
                {lbl('LBL_ATTACH_LESSON_PLAN', 'Attach lesson plan')}
              </button>
            )}
            {quizCount > 0 ? (
              <div className="d-flex align-items-center">
                <span className="attachment-file color-black">
                  <DashboardSpriteIcon
                    id="attach"
                    className="icon icon--issue icon--attachement icon--xsmall color-black"
                  />
                  {lbl('LBL_{quiz-count}_QUIZ(ZES)_ATTACHED', '{quiz-count} quiz(zes) attached').replace(
                    '{quiz-count}',
                    String(quizCount)
                  )}
                </span>
                <button
                  type="button"
                  className="underline color-black btn btn--transparent btn--small mx-1"
                  onClick={() => onAttachQuiz?.(grpclsId)}
                >
                  {lbl('LBL_ATTACH', 'Attach')}
                </button>
              </div>
            ) : (
              <button
                type="button"
                className="btn btn--transparent btn--addition color-black btn--small mx-1"
                onClick={() => onAttachQuiz?.(grpclsId)}
              >
                {lbl('LBL_ATTACH_QUIZ', 'Attach quiz')}
              </button>
            )}
          </div>
        )}
      </div>
      <div className="card-landscape__colum card-landscape__colum--third">
        {isTeacher && item.can_edit && (
          <div className="card-landscape__actions">
            <div className="actions-group">
              <button
                type="button"
                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                disabled
                title={lbl('LBL_EDIT', 'Edit')}
              >
                <DashboardSpriteIcon id="edit" className="icon icon--edit icon--small" />
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
