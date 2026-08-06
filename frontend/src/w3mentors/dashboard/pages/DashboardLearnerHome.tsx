import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { learnerDashboardApi, type LearnerDashboardData } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { SpriteIcon } from '../../components/SpriteIcon';
import { formatMoney } from '../../utils/assets';
import { dashboardPath } from '../dashboardPaths';

export function DashboardLearnerHome() {
  const { lbl, site } = useSite();
  const [data, setData] = useState<LearnerDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const moneySymbol = site?.currency_code ? `${site.currency_code} ` : '$';

  useEffect(() => {
    learnerDashboardApi
      .get()
      .then((res) => setData(res.data.data))
      .finally(() => setLoading(false));
  }, []);

  const colClass =
    data?.modules.group_classes && data?.modules.courses
      ? 'col-lg-4 col-md-6 col-sm-6'
      : data?.modules.group_classes || data?.modules.courses
        ? 'col-lg-6 col-md-6 col-sm-6'
        : 'col-lg-6 col-md-6 col-sm-6';

  return (
    <div className="container container--fixed">
    <div className="dashboard">
      <div className="dashboard__primary">
        <div className="page__head">
          <h1>{lbl('LBL_DASHBOARD', 'Dashboard')}</h1>
        </div>
        <div className="page__body">
          {loading && <p className="muted">{lbl('LBL_LOADING', 'Loading...')}</p>}
          {!loading && data && (
            <div className="stats-row mb-4">
              <div className="row align-items-center g-4">
                <div className={colClass}>
                  <div className="stat">
                    <div className="stat__amount">
                      <span>{lbl('LBL_LESSONS_SCHEDULED', 'Lessons Scheduled')}</span>
                      <h5>{data.scheduled_lessons}</h5>
                    </div>
                    <div className="stat__media bg-yellow">
                      <SpriteIcon id="lessons" className="icon icon--money icon--40 color-white" width={40} height={40} />
                    </div>
                    <a href={`${dashboardPath('learner', 'lessons')}?status=2`} className="stat__action" />
                  </div>
                </div>
                <div className={colClass}>
                  <div className="stat">
                    <div className="stat__amount">
                      <span>{lbl('LBL_TOTAL_LESSONS', 'Total Lessons')}</span>
                      <h5>{data.total_lessons}</h5>
                    </div>
                    <div className="stat__media bg-secondary">
                      <SpriteIcon id="lessons" className="icon icon--money icon--40 color-white" width={40} height={40} />
                    </div>
                    <a href={dashboardPath('learner', 'lessons')} className="stat__action" />
                  </div>
                </div>
                {data.modules.group_classes && (
                  <div className={colClass}>
                    <div className="stat">
                      <div className="stat__amount">
                        <span>{lbl('LBL_TOTAL_CLASSES', 'Total Classes')}</span>
                        <h5>{data.total_classes}</h5>
                      </div>
                      <div className="stat__media bg-secondary">
                        <SpriteIcon id="group-classes" className="icon icon--money icon--40 color-white" width={40} height={40} />
                      </div>
                      <a href={dashboardPath('learner', 'classes')} className="stat__action" />
                    </div>
                  </div>
                )}
                {data.modules.courses && (
                  <div className="col-lg-6 col-md-6 col-sm-6">
                    <div className="stat">
                      <div className="stat__amount">
                        <span>{lbl('LBL_TOTAL_COURSES', 'Total Courses')}</span>
                        <h5>{data.total_courses}</h5>
                      </div>
                      <div className="stat__media bg-secondary">
                        <SpriteIcon id="all-courses" className="icon icon--money icon--40 color-white" width={40} height={40} />
                      </div>
                      <Link to="/my/courses" className="stat__action" />
                    </div>
                  </div>
                )}
                <div className="col-lg-6 col-md-6 col-sm-6">
                  <div className="stat">
                    <div className="stat__amount">
                      <span>{lbl('LBL_WALLET_BALANCE', 'Wallet Balance')}</span>
                      <h5>{formatMoney(data.wallet_balance, moneySymbol)}</h5>
                    </div>
                    <div className="stat__media bg-primary">
                      <SpriteIcon id="settings" className="icon icon--money icon--40 color-white" width={40} height={40} />
                    </div>
                    <a href={dashboardPath('learner', 'wallet')} className="stat__action" />
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
    </div>
  );
}
