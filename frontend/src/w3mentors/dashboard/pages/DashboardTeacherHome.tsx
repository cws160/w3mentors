import { useEffect, useState } from 'react';
import { teacherDashboardApi, type TeacherDashboardData } from '../../../api/client';
import { apiErrorMessage } from '../../../utils/apiError';
import { useAuth } from '../../../context/AuthContext';
import { useSite } from '../../context/SiteContext';
import { SpriteIcon } from '../../components/SpriteIcon';
import { formatMoney } from '../../utils/assets';
import { TeacherDashboardCalendarSlot } from '../components/TeacherDashboardCalendarSlot';
import { UpcomingLessonsSidebar } from '../components/UpcomingLessonsSidebar';
import { dashboardPath } from '../dashboardPaths';

function statColumnClass(data: TeacherDashboardData | null): string {
  const showCourses = data?.modules.courses !== false;
  const showClasses = data?.modules.group_classes !== false;
  if (showCourses && showClasses) return 'col-lg-4 col-md-6 col-sm-6';
  if (showCourses || showClasses) return 'col-lg-6 col-md-6 col-sm-6';
  return 'col-lg-12 col-md-12 col-sm-12';
}

export function DashboardTeacherHome() {
  const { user } = useAuth();
  const { lbl, site } = useSite();
  const [data, setData] = useState<TeacherDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const moneySymbol = site?.currency_code ? `${site.currency_code} ` : '$';

  useEffect(() => {
    teacherDashboardApi
      .get()
      .then((res) => setData(res.data.data))
      .catch((err) =>
        setError(apiErrorMessage(err, lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')))
      )
      .finally(() => setLoading(false));
  }, [lbl]);

  const colClass = statColumnClass(data);

  return (
    <div className="container container--fixed">
    <div className="dashboard">
      <div className="dashboard__primary">
        <div className="page__head">
          <h1>{lbl('LBL_DASHBOARD', 'Dashboard')}</h1>
        </div>
        <div className="page__body">
          {error && <p className="alert alert--danger">{error}</p>}
          {loading && <p className="muted">{lbl('LBL_LOADING', 'Loading...')}</p>}
          {!loading && data && (
            <div className="stats-row align-item-stretch">
              <div className="row align-items-center g-4 mb-4">
                <div className={colClass}>
                  <div className="stat">
                    <div className="stat__amount">
                      <span>{lbl('LBL_SCHEDULED_LESSONS', 'Scheduled Lessons')}</span>
                      <h5>{data.scheduled_lessons}</h5>
                    </div>
                    <div className="stat__media bg-secondary">
                      <SpriteIcon id="lessons" className="icon icon--money icon--40 color-white" width={40} height={40} />
                    </div>
                    <a href={`${dashboardPath('teacher', 'lessons')}?status=2`} className="stat__action" />
                  </div>
                </div>
                {data.modules.group_classes && (
                  <div className={colClass}>
                    <div className="stat">
                      <div className="stat__amount">
                        <span>{lbl('LBL_SCHEDULED_CLASSES', 'Scheduled Classes')}</span>
                        <h5>{data.scheduled_classes}</h5>
                      </div>
                      <div className="stat__media bg-secondary">
                        <SpriteIcon id="group-classes" className="icon icon--money icon--40 color-white" width={40} height={40} />
                      </div>
                      <a href={dashboardPath('teacher', 'classes')} className="stat__action" />
                    </div>
                  </div>
                )}
                {data.modules.courses && (
                  <div className={colClass}>
                    <div className="stat">
                      <div className="stat__amount">
                        <span>{lbl('LBL_COURSES_SOLD', 'Courses Sold')}</span>
                        <h5>{data.courses_sold}</h5>
                      </div>
                      <div className="stat__media bg-secondary">
                        <SpriteIcon id="all-courses" className="icon icon--money icon--40 color-white" width={40} height={40} />
                      </div>
                      <a href={dashboardPath('teacher', 'courses')} className="stat__action" />
                    </div>
                  </div>
                )}
                <div className="col-lg-6 col-md-6 col-sm-6">
                  <div className="stat">
                    <div className="stat__amount">
                      <span>{lbl('LBL_TOTAL_EARNINGS', 'Total Earnings')}</span>
                      <h5>{formatMoney(data.total_earnings, moneySymbol)}</h5>
                    </div>
                    <div className="stat__media bg-yellow">
                      <SpriteIcon id="dashboard" className="icon icon--money icon--40 color-white" width={40} height={40} />
                    </div>
                  </div>
                </div>
                <div className="col-lg-6 col-md-6 col-sm-6">
                  <div className="stat">
                    <div className="stat__amount">
                      <span>{lbl('LBL_WALLET_BALANCE', 'Wallet Balance')}</span>
                      <h5>{formatMoney(data.wallet_balance, moneySymbol)}</h5>
                    </div>
                    <div className="stat__media bg-primary">
                      <SpriteIcon id="settings" className="icon icon--money icon--40 color-white" width={40} height={40} />
                    </div>
                    <a href={dashboardPath('teacher', 'wallet')} className="stat__action" />
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
      <div className="dashboard__secondary">
        <div className="status-bar">
          <div className="status-bar__head">
            <div className="status-title">
              <h5>{lbl('LBL_UPCOMING_LESSONS', 'Upcoming Lessons')}</h5>
              <a
                href={`${dashboardPath('teacher', 'lessons')}?status=2`}
                className="color-secondary underline pt-3 pb-3"
              >
                {lbl('LBL_View_All', 'View All')}
              </a>
            </div>
            <div className="calendar">
              <TeacherDashboardCalendarSlot teacherId={user?.id ?? 0} />
            </div>
          </div>
          <div className="status-bar__body">
            <div className="listing-window" id="listItemsLessons">
              {!loading && data && (
                <UpcomingLessonsSidebar groups={data.upcoming_lesson_groups ?? []} />
              )}
            </div>
          </div>
        </div>
        {user?.first_name && (
          <p className="p-3 small color-secondary">
            {lbl('LBL_TEACHER_DASHBOARD_HEADING_{user-first-name}', 'Welcome back, {user-first-name}!').replace(
              '{user-first-name}',
              user.first_name
            )}
          </p>
        )}
      </div>
    </div>
    </div>
  );
}
