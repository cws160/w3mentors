import { type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { AdminDashboardDurationSelect } from './AdminDashboardDurationSelect';

type Props = {
  title: string;
  excludeToday?: boolean;
  loading?: boolean;
  error?: string | null;
  interval: number;
  onIntervalChange: (interval: number) => void;
  settingsHref?: string;
  settingsLabel?: string;
  children: ReactNode;
};

export function AdminDashboardAnalyticsCard({
  title,
  excludeToday,
  loading,
  error,
  interval,
  onIntervalChange,
  settingsHref = '/admin/configurations',
  settingsLabel = 'Configure Google Analytics',
  children,
}: Props) {
  return (
    <div className="d-grid__item">
      <div className="card card-height">
        <div className="card-head">
          <div className="card-head-label">
            <h3 className="card-head-caption">{title}</h3>
          </div>
          <div className="card-head-toolbar">
            <AdminDashboardDurationSelect
              value={interval}
              excludeToday={excludeToday}
              onChange={(nextInterval) => onIntervalChange(nextInterval)}
            />
          </div>
        </div>
        <div className="card-body">
          {loading ? (
            <div className="analytics-box">
              <div className="table-processing loaderJs" style={{ minHeight: 360 }}>
                <div className="spinner spinner--sm spinner--brand" />
              </div>
            </div>
          ) : error ? (
            <div className="analytics-box">
              <p className="text-center w-100 mb-2">{error}</p>
              {settingsHref ? (
                <p className="text-center w-100 mb-0">
                  <Link to={settingsHref} className="btn btn-sm btn-brand">
                    {settingsLabel}
                  </Link>
                </p>
              ) : null}
            </div>
          ) : (
            children
          )}
        </div>
      </div>
    </div>
  );
}
