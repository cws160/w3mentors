import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminApi } from '../api/adminClient';
import { AdminDashboardAnalyticsCard } from '../components/AdminDashboardAnalyticsCard';
import { AdminDashboardRankCard } from '../components/AdminDashboardRankCard';
import { ADMIN_DURATION_TYPE_ALL } from '../config/adminDurationTypes';
import { renderAdminBarChart } from '../hooks/useAdminChartist';
import { renderAnalyticsPieChart } from '../hooks/useGoogleCharts';
import { useSite } from '../../w3mentors/context/SiteContext';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

type DashboardPayload = {
  stats: Record<string, number | string>;
  features: {
    courses_enabled: boolean;
    group_classes_enabled: boolean;
    subscription_plan_enabled: boolean;
    affiliate_enabled: boolean;
  };
  page_text?: {
    plang_id?: number;
    title?: string;
    summary?: string;
    warning?: string;
    recommendations?: string;
  };
};

type ChartPayload = {
  userData: Record<string, number>;
  lessonData: Record<string, number>;
  classData: Record<string, number>;
  courseData: Record<string, number>;
};

function formatMoney(value: number | string | undefined) {
  const n = Number(value ?? 0) || 0;
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n);
}

function titleCase(value: string) {
  return value.replace(/\b\w/g, (char) => char.toUpperCase());
}

function TrendArrowIcon() {
  return (
    <svg className="dashboard-trend-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M5 3V19H21V21H3V3H5ZM20.2929 6.29289L21.7071 7.70711L16 13.4142L13 10.415L8.70711 14.7071L7.29289 13.2929L13 7.58579L16 10.585L20.2929 6.29289Z" />
    </svg>
  );
}

function TotalOrdersIcon() {
  return (
    <svg className="dashboard-total-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M7 3H17C18.1046 3 19 3.89543 19 5V21L16 19L13 21L10 19L7 21L5 19.6667V5C5 3.89543 5.89543 3 7 3Z" />
      <path d="M9 8H15" />
      <path d="M9 12H15" />
      <path d="M9 16H13" />
    </svg>
  );
}

function TotalUsersIcon() {
  return (
    <svg className="dashboard-total-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M16 11C17.6569 11 19 9.65685 19 8C19 6.34315 17.6569 5 16 5" />
      <path d="M8 11C6.34315 11 5 9.65685 5 8C5 6.34315 6.34315 5 8 5" />
      <path d="M12 13C14.2091 13 16 11.2091 16 9C16 6.79086 14.2091 5 12 5C9.79086 5 8 6.79086 8 9C8 11.2091 9.79086 13 12 13Z" />
      <path d="M4 20C4 17.7909 5.79086 16 8 16" />
      <path d="M20 20C20 17.7909 18.2091 16 16 16" />
      <path d="M7 20C7 17.2386 9.23858 15 12 15C14.7614 15 17 17.2386 17 20" />
    </svg>
  );
}

function TotalAffiliatesIcon() {
  return (
    <svg className="dashboard-total-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 12C14.2091 12 16 10.2091 16 8C16 5.79086 14.2091 4 12 4C9.79086 4 8 5.79086 8 8C8 10.2091 9.79086 12 12 12Z" />
      <path d="M4.5 20C5.33566 17.6701 7.56468 16 10.184 16H13.816C16.4353 16 18.6643 17.6701 19.5 20" />
      <path d="M6 9H4C2.89543 9 2 9.89543 2 11V13" />
      <path d="M18 9H20C21.1046 9 22 9.89543 22 11V13" />
      <path d="M3.5 14.5L2 13L3.5 11.5" />
      <path d="M20.5 14.5L22 13L20.5 11.5" />
    </svg>
  );
}

function StatRow({
  label,
  allValue,
  monthValue,
  link,
  variant,
}: {
  label: string;
  allValue: number;
  monthValue: number;
  link?: string;
  variant: 'primary' | 'secondary' | 'third';
}) {
  const { lbl } = useSite();
  return (
    <div className={`stats-overview stats-overview--${variant}`}>
      <h6>{label}</h6>
      <h3 className="counter">{allValue}</h3>
      <p>
        <TrendArrowIcon />
        {lbl('LBL_THIS_MONTH', 'This month')} <strong>{monthValue}</strong>
      </p>
      {link ? <Link to={link} className="stats__link" /> : null}
    </div>
  );
}

export function AdminDashboardPage() {
  const { lbl } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [data, setData] = useState<DashboardPayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('tabs_1');
  const [chartData, setChartData] = useState<ChartPayload | null>(null);
  const [chartsLoading, setChartsLoading] = useState(true);
  const [eventInterval, setEventInterval] = useState(ADMIN_DURATION_TYPE_ALL);
  const [trafficInterval, setTrafficInterval] = useState(ADMIN_DURATION_TYPE_ALL);
  const [eventAnalytics, setEventAnalytics] = useState<{
    loading: boolean;
    error: string | null;
    data: Record<string, number> | null;
  }>({ loading: true, error: null, data: null });
  const [trafficAnalytics, setTrafficAnalytics] = useState<{
    loading: boolean;
    error: string | null;
    data: Record<string, number> | null;
  }>({ loading: true, error: null, data: null });

  const canView = privileges.canViewAdminDashboard;

  useEffect(() => {
    let cancelled = false;

    adminApi
      .dashboard()
      .then((res) => {
        if (cancelled) return;
        const payload = res.data as DashboardPayload;
        setData(payload);
        if (payload.page_text) {
          setMeta({
            title: payload.page_text.title,
            summary: payload.page_text.summary,
            warning: payload.page_text.warning,
            recommendations: payload.page_text.recommendations,
            plangId: payload.page_text.plang_id,
          });
        }
      })
      .catch(() => {
        if (!cancelled) setData(null);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
      clearMeta();
    };
  }, [setMeta, clearMeta]);

  useEffect(() => {
    if (!canView) return;
    let cancelled = false;
    setChartsLoading(true);
    adminApi
      .dashboardCharts()
      .then((res) => {
        if (!cancelled) setChartData(res.data);
      })
      .catch(() => {
        if (!cancelled) setChartData(null);
      })
      .finally(() => {
        if (!cancelled) setChartsLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [canView]);

  useEffect(() => {
    if (!canView) return;
    let cancelled = false;
    setEventAnalytics((prev) => ({ ...prev, loading: true }));

    adminApi
      .dashboardAnalyticsEvents(eventInterval)
      .then((events) => {
        if (cancelled) return;
        setEventAnalytics({
          loading: false,
          error: events.data.error ? events.data.message ?? 'Analytics unavailable' : null,
          data: events.data.error ? null : (events.data.data ?? {}),
        });
      })
      .catch(() => {
        if (!cancelled) {
          setEventAnalytics({ loading: false, error: 'Analytics unavailable', data: null });
        }
      });

    return () => {
      cancelled = true;
    };
  }, [canView, eventInterval]);

  useEffect(() => {
    if (!canView) return;
    let cancelled = false;
    setTrafficAnalytics((prev) => ({ ...prev, loading: true }));

    adminApi
      .dashboardAnalyticsTraffic(trafficInterval)
      .then((traffic) => {
        if (cancelled) return;
        setTrafficAnalytics({
          loading: false,
          error: traffic.data.error ? traffic.data.message ?? 'Analytics unavailable' : null,
          data: traffic.data.error ? null : (traffic.data.data ?? {}),
        });
      })
      .catch(() => {
        if (!cancelled) {
          setTrafficAnalytics({ loading: false, error: 'Analytics unavailable', data: null });
        }
      });

    return () => {
      cancelled = true;
    };
  }, [canView, trafficInterval]);

  useEffect(() => {
    if (eventAnalytics.loading || eventAnalytics.error || !eventAnalytics.data) return;
    void renderAnalyticsPieChart('analytic-event-chart', eventAnalytics.data);
  }, [eventAnalytics]);

  useEffect(() => {
    if (trafficAnalytics.loading || trafficAnalytics.error || !trafficAnalytics.data) return;
    void renderAnalyticsPieChart('analytic-traffic-chart', trafficAnalytics.data);
  }, [trafficAnalytics]);

  const chartSeries = useMemo(() => {
    if (!chartData) return null;
    switch (activeTab) {
      case 'tabs_2':
        return chartData.classData;
      case 'tabs_3':
        return chartData.courseData;
      case 'tabs_4':
        return chartData.userData;
      default:
        return chartData.lessonData;
    }
  }, [activeTab, chartData]);

  const chartElementId = useMemo(() => {
    switch (activeTab) {
      case 'tabs_2':
        return 'classEarning--js';
      case 'tabs_3':
        return 'courseEarning--js';
      case 'tabs_4':
        return 'userSignups--js';
      default:
        return 'lessonEarning--js';
    }
  }, [activeTab]);

  useEffect(() => {
    if (!chartSeries || chartsLoading) return;
    void renderAdminBarChart(chartElementId, chartSeries);
  }, [chartSeries, chartElementId, chartsLoading]);

  const stats = useMemo(() => {
    const raw = data?.stats ?? {};
    const normalized: Record<string, number> = {};
    for (const [key, value] of Object.entries(raw)) {
      normalized[key] = Number(value) || 0;
    }
    return normalized;
  }, [data?.stats]);
  const features = data?.features;
  const fetchLessonLanguages = useCallback((interval: number) => adminApi.dashboardTopLessonLanguages(interval), []);
  const fetchClassLanguages = useCallback((interval: number) => adminApi.dashboardTopClassLanguages(interval), []);
  const fetchCourseCategories = useCallback((interval: number) => adminApi.dashboardTopCourseCategories(interval), []);
  const viewIndex = useMemo(() => {
    let count = 1;
    if (features?.courses_enabled) count += 1;
    if (features?.group_classes_enabled) count += 1;
    return count;
  }, [features]);

  if (!privileges.canViewAdminDashboard) {
    return (
      <main className="main is-dashboard">
        <div className="container container-fluid">
          <div className="card">
            <div className="card-body">{lbl('MSG_UNAUTHORIZED_ACCESS!', 'Unauthorized access')}</div>
          </div>
        </div>
      </main>
    );
  }

  if (loading) {
    return (
      <main className="main is-dashboard">
        <div className="container container-fluid">
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        </div>
      </main>
    );
  }

  return (
    <main className="main is-dashboard">
      <div className="container container-fluid">
        <div className="grid-panel">
          <div className="grid-panel__item">
            <div className="stats-grid" data-view="3">
              {/* Lessons */}
              <div className="stats-grid__item">
                <div className="stats dashboard-revenue-card dashboard-revenue-card--lessons stats-bg-1">
                  <span className="stats__icon">
                    <img src="/manager/views/images/lesson-revenue-green.svg" alt="" />
                  </span>
                  <div className="stats__content">
                    <h6>{titleCase(lbl('LBL_LESSONS_REVENUE', 'Lessons revenue'))}</h6>
                    <h3 className="counter">{formatMoney(stats.ALL_LESSONS_REVENUE)}</h3>
                    <p>
                      <TrendArrowIcon />
                      {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                      <strong>{formatMoney(stats.TM_LESSONS_REVENUE)}</strong>
                    </p>
                    {privileges.canViewLessonsOrders ? (
                      <Link to="/admin/lessons" className="stats__link" />
                    ) : null}
                  </div>
                </div>
                <div className="card dashboard-overview-card dashboard-overview-card--lessons">
                  <div className="card-head">
                    <span className="card-head-icon">
                      <img src="/manager/views/images/lesson-revenue-green.svg" alt="" />
                    </span>
                  </div>
                  <div className="card-body card-body--stats-overview pt-0">
                    <StatRow
                      variant="primary"
                      label={lbl('LBL_TOTAL_LESSONS', 'Total lessons')}
                      allValue={stats.ALL_LESSONS_TOTAL ?? 0}
                      monthValue={stats.TM_LESSONS_TOTAL ?? 0}
                      link={privileges.canViewLessonsOrders ? '/admin/lessons' : undefined}
                    />
                    <StatRow
                      variant="primary"
                      label={lbl('LBL_COMPLETED_LESSONS', 'Completed lessons')}
                      allValue={stats.ALL_COMPLETED_LESSONS ?? 0}
                      monthValue={stats.TM_COMPLETED_LESSONS ?? 0}
                      link={privileges.canViewLessonsOrders ? '/admin/lessons' : undefined}
                    />
                  </div>
                </div>
              </div>

              {/* Classes */}
              {features?.group_classes_enabled ? (
                <div className="stats-grid__item">
                  <div className="stats dashboard-revenue-card dashboard-revenue-card--classes stats-bg-2">
                    <span className="stats__icon">
                      <img src="/manager/views/images/class-revenue-orange.svg" alt="" />
                    </span>
                    <div className="stats__content">
                      <h6>{titleCase(lbl('LBL_CLASSES_REVENUE', 'Classes revenue'))}</h6>
                      <h3 className="counter">{formatMoney(stats.ALL_CLASSES_REVENUE)}</h3>
                      <p>
                        <TrendArrowIcon />
                        {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                        <strong>{formatMoney(stats.TM_CLASSES_REVENUE)}</strong>
                      </p>
                      {privileges.canViewClassesOrders ? (
                        <Link to="/admin/classes" className="stats__link" />
                      ) : null}
                    </div>
                  </div>
                  <div className="card dashboard-overview-card dashboard-overview-card--classes">
                    <div className="card-head">
                      <span className="card-head-icon">
                        <img src="/manager/views/images/class-revenue-orange.svg" alt="" />
                      </span>
                    </div>
                    <div className="card-body card-body--stats-overview pt-0">
                      <StatRow
                        variant="secondary"
                        label={lbl('LBL_TOTAL_CLASSES', 'Total classes')}
                        allValue={stats.ALL_CLASSES_TOTAL ?? 0}
                        monthValue={stats.TM_CLASSES_TOTAL ?? 0}
                        link={privileges.canViewGroupClasses ? '/admin/group-classes' : undefined}
                      />
                      <StatRow
                        variant="secondary"
                        label={lbl('LBL_PURCHASED_CLASSES', 'Purchased classes')}
                        allValue={stats.ALL_COMPLETED_CLASSES ?? 0}
                        monthValue={stats.TM_COMPLETED_CLASSES ?? 0}
                        link={privileges.canViewClassesOrders ? '/admin/classes' : undefined}
                      />
                    </div>
                  </div>
                </div>
              ) : null}

              {/* Subscriptions */}
              {features?.subscription_plan_enabled ? (
                <div className="stats-grid__item">
                  <div className="stats dashboard-revenue-card dashboard-revenue-card--classes stats-bg-3">
                    <span className="stats__icon">
                      <img src="/manager/views/images/class-revenue-orange.svg" alt="" />
                    </span>
                    <div className="stats__content">
                      <h6>{titleCase(lbl('LBL_SUBSCRIPTION_REVENUE', 'Subscription revenue'))}</h6>
                      <h3 className="counter">{formatMoney(stats.ALL_SUBSCRIPTION_REVENUE)}</h3>
                      <p>
                        <TrendArrowIcon />
                        {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                        <strong>{formatMoney(stats.TM_SUBSCRIPTION_REVENUE)}</strong>
                      </p>
                    </div>
                  </div>
                  <div className="card dashboard-overview-card dashboard-overview-card--classes">
                    <div className="card-head">
                      <span className="card-head-icon">
                        <img src="/manager/views/images/class-revenue-orange.svg" alt="" />
                      </span>
                    </div>
                    <div className="card-body card-body--stats-overview pt-0">
                      <StatRow
                        variant="secondary"
                        label={lbl('LBL_PURCHASED_SUBSCRIPTIONS', 'Purchased subscriptions')}
                        allValue={stats.ALL_SUBSCRIPTIONS_TOTAL ?? 0}
                        monthValue={stats.TM_SUBSCRIPTIONS_TOTAL ?? 0}
                      />
                      <StatRow
                        variant="secondary"
                        label={lbl('LBL_COMPLETED_SUBSCRIPTIONS', 'Completed subscriptions')}
                        allValue={stats.ALL_COMPLETED_SUBSCRIPTIONS ?? 0}
                        monthValue={stats.TM_COMPLETED_SUBSCRIPTIONS ?? 0}
                      />
                    </div>
                  </div>
                </div>
              ) : null}

              {/* Courses */}
              {features?.courses_enabled ? (
                <div className="stats-grid__item">
                  <div className="stats dashboard-revenue-card dashboard-revenue-card--courses stats-bg-1">
                    <span className="stats__icon">
                      <img src="/manager/views/images/courses-revenue-dark.svg" alt="" />
                    </span>
                    <div className="stats__content">
                      <h6>{titleCase(lbl('LBL_COURSES_REVENUE', 'Courses revenue'))}</h6>
                      <h3 className="counter">{formatMoney(stats.ALL_COURSES_REVENUE)}</h3>
                      <p>
                        <TrendArrowIcon />
                        {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                        <strong>{formatMoney(stats.TM_COURSES_REVENUE)}</strong>
                      </p>
                      {privileges.canViewCoursesOrders ? (
                        <Link to="/admin/course-orders" className="stats__link" />
                      ) : null}
                    </div>
                  </div>
                  <div className="card dashboard-overview-card dashboard-overview-card--courses">
                    <div className="card-head">
                      <span className="card-head-icon">
                        <img src="/manager/views/images/courses-revenue-dark.svg" alt="" />
                      </span>
                    </div>
                    <div className="card-body card-body--stats-overview pt-0">
                      <StatRow
                        variant="third"
                        label={lbl('LBL_TOTAL_COURSES', 'Total courses')}
                        allValue={stats.ALL_COURSES_TOTAL ?? 0}
                        monthValue={stats.TM_COURSES_TOTAL ?? 0}
                        link={privileges.canViewCourses ? '/admin/courses' : undefined}
                      />
                      <StatRow
                        variant="third"
                        label={lbl('LBL_REFUNDED_COURSES', 'Refunded courses')}
                        allValue={stats.ALL_REFUNDED_COURSES ?? 0}
                        monthValue={stats.TM_REFUNDED_COURSES ?? 0}
                        link={privileges.canViewCourseRefundRequests ? '/admin/course-refund-requests' : undefined}
                      />
                    </div>
                  </div>
                </div>
              ) : null}
            </div>
          </div>

          <div className="grid-panel__item">
            <div className="stats stats--total-earning">
              <div className="stats__content">
                <h6>{lbl('LBL_ADMIN_EARNINGS', 'Admin earnings')}</h6>
                <h3 className="counter">{formatMoney(stats.ALL_ADMIN_EARNINGS)}</h3>
                <p className="mb-2">
                  <TrendArrowIcon />
                  {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                  <strong>{formatMoney(stats.TM_ADMIN_EARNINGS)}</strong>
                </p>
                {privileges.canViewAdminEarningsReport ? (
                  <Link to="/admin/admin-earnings" className="btn btn-orange">
                    {lbl('LBL_VIEW_EARNING_REPORTS', 'View reports')}
                  </Link>
                ) : null}
              </div>
            </div>
            <div className="total-stats">
              <div className="stats stats-bg-2">
                <span className="stats__icon">
                  <TotalOrdersIcon />
                </span>
                <div className="stats__content">
                  <h6>{lbl('LBL_TOTAL_ORDERS', 'Total orders')}</h6>
                  <h3 className="counter">{stats.ALL_ORDERS_TOTAL ?? 0}</h3>
                  <p>
                    <TrendArrowIcon />
                    {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                    <strong>{stats.TM_ORDERS_TOTAL ?? 0}</strong>
                  </p>
                  {privileges.canViewOrders ? <Link to="/admin/orders" className="stats__link" /> : null}
                </div>
              </div>
              <div className="stats stats-bg-1">
                <span className="stats__icon">
                  <TotalUsersIcon />
                </span>
                <div className="stats__content">
                  <h6>{lbl('LBL_TOTAL_USERS', 'Total users')}</h6>
                  <h3 className="counter">{stats.ALL_USERS_TOTAL ?? 0}</h3>
                  <p>
                    <TrendArrowIcon />
                    {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                    <strong>{stats.TM_USERS_TOTAL ?? 0}</strong>
                  </p>
                  {privileges.canViewUsers ? <Link to="/admin/users" className="stats__link" /> : null}
                </div>
              </div>
              {features?.affiliate_enabled ? (
                <div className="stats stats-bg-3">
                  <span className="stats__icon">
                    <TotalAffiliatesIcon />
                  </span>
                  <div className="stats__content">
                    <h6>{lbl('LBL_TOTAL_AFFILIATES', 'Total affiliates')}</h6>
                    <h3 className="counter">{stats.ALL_AFFILIATES_TOTAL ?? 0}</h3>
                    <p>
                      <TrendArrowIcon />
                      {lbl('LBL_THIS_MONTH', 'This month')}{' '}
                      <strong>{stats.TM_AFFILIATES_TOTAL ?? 0}</strong>
                    </p>
                  </div>
                </div>
              ) : null}
            </div>
          </div>

          <div className="grid-panel__item">
            <div className="card height-100">
              <div className="card-head d-md-flex">
                <div className="card-head-label">
                  <h3 className="card-head-caption">{lbl('LBL_STATISTICS', 'Statistics')}</h3>
                </div>
                <div className="card-head-toolbar">
                  <ul className="nav nav--button statistics-nav-js">
                    <li>
                      <a
                        className={activeTab === 'tabs_1' ? 'active' : ''}
                        href="#tabs_1"
                        onClick={(e) => {
                          e.preventDefault();
                          setActiveTab('tabs_1');
                        }}
                      >
                        {lbl('LBL_LESSONS_COMMISSION', 'Lessons commission')}
                      </a>
                    </li>
                    {features?.group_classes_enabled ? (
                      <li>
                        <a
                          className={activeTab === 'tabs_2' ? 'active' : ''}
                          href="#tabs_2"
                          onClick={(e) => {
                            e.preventDefault();
                            setActiveTab('tabs_2');
                          }}
                        >
                          {lbl('LBL_CLASSES_COMMISSION', 'Classes commission')}
                        </a>
                      </li>
                    ) : null}
                    {features?.courses_enabled ? (
                      <li>
                        <a
                          className={activeTab === 'tabs_3' ? 'active' : ''}
                          href="#tabs_3"
                          onClick={(e) => {
                            e.preventDefault();
                            setActiveTab('tabs_3');
                          }}
                        >
                          {lbl('LBL_COURSES_COMMISSION', 'Courses commission')}
                        </a>
                      </li>
                    ) : null}
                    <li>
                      <a
                        className={activeTab === 'tabs_4' ? 'active' : ''}
                        href="#tabs_4"
                        onClick={(e) => {
                          e.preventDefault();
                          setActiveTab('tabs_4');
                        }}
                      >
                        {lbl('LBL_SIGN_UPS', 'Sign ups')}
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
              <div className="card-body">
                <div className="tabs-wrap statistics-tab-js height-100">
                  {chartsLoading ? (
                    <div className="table-processing loaderJs" style={{ minHeight: 280 }}>
                      <div className="spinner spinner--sm spinner--brand" />
                    </div>
                  ) : (
                    <>
                      <div
                        id="tabs_1"
                        className="tabs_panel"
                        style={{
                          width: '100%',
                          height: '100%',
                          minHeight: 280,
                          display: activeTab === 'tabs_1' ? 'block' : 'none',
                        }}
                      >
                        <div id="lessonEarning--js" className="ct-chart ct-perfect-fourth graph--sales" />
                      </div>
                      {features?.group_classes_enabled ? (
                        <div
                          id="tabs_2"
                          className="tabs_panel"
                          style={{
                            width: '100%',
                            height: '100%',
                            minHeight: 280,
                            display: activeTab === 'tabs_2' ? 'block' : 'none',
                          }}
                        >
                          <div id="classEarning--js" className="ct-chart ct-perfect-fourth graph--sales" />
                        </div>
                      ) : null}
                      {features?.courses_enabled ? (
                        <div
                          id="tabs_3"
                          className="tabs_panel"
                          style={{
                            width: '100%',
                            height: '100%',
                            minHeight: 280,
                            display: activeTab === 'tabs_3' ? 'block' : 'none',
                          }}
                        >
                          <div id="courseEarning--js" className="ct-chart ct-perfect-fourth graph--sales" />
                        </div>
                      ) : null}
                      <div
                        id="tabs_4"
                        className="tabs_panel"
                        style={{
                          width: '100%',
                          height: '100%',
                          minHeight: 280,
                          display: activeTab === 'tabs_4' ? 'block' : 'none',
                        }}
                      >
                        <div id="userSignups--js" className="ct-chart ct-perfect-fourth graph--sales" />
                      </div>
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="d-grid mt-4 pt-1 pt-lg-0 mt-lg-0" data-view={viewIndex}>
          <AdminDashboardRankCard
            className="topLessonLanguage"
            title={lbl('LBL_TOP_LESSON_LANGUAGES', 'Top lesson languages')}
            labelKey="language"
            fetchRows={fetchLessonLanguages}
          />
          <AdminDashboardRankCard
            className="topClassLanguage"
            title={lbl('LBL_TOP_CLASS_LANGUAGES', 'Top class languages')}
            titleTag="h2"
            enabled={features?.group_classes_enabled}
            labelKey="language"
            fetchRows={fetchClassLanguages}
          />
          <AdminDashboardRankCard
            className="topCourseCategories"
            title={lbl('LBL_TOP_COURSE_CATEGORIES', 'Top course categories')}
            enabled={features?.courses_enabled}
            labelKey="category"
            fetchRows={fetchCourseCategories}
          />
        </div>

        <div className="gap" />

        <div className="d-grid" data-view="2">
          <AdminDashboardAnalyticsCard
            title={lbl('LBL_ANALYTICS_EVENT_MEASUREMENTS', 'Analytics event measurements')}
            excludeToday
            loading={eventAnalytics.loading}
            error={eventAnalytics.error}
            interval={eventInterval}
            onIntervalChange={setEventInterval}
            settingsLabel={lbl('MSG_THIRD_PARTY_APIS', 'Configure Third Party APIs')}
          >
            {eventAnalytics.data && Object.keys(eventAnalytics.data).length > 0 ? (
              <div className="analytics-box">
                <div id="analytic-event-chart" className="analytic-event-chart w-100" style={{ minHeight: 360 }} />
              </div>
            ) : (
              <div className="analytics-box">
                <p className="text-center w-100 mb-0">{lbl('LBL_NO_RECORD_FOUND', 'No record found')}</p>
              </div>
            )}
          </AdminDashboardAnalyticsCard>
          <AdminDashboardAnalyticsCard
            title={lbl('LBL_ANALYTICS_TRAFFIC_ACQUITIONS', 'Analytics traffic acquisitions')}
            excludeToday
            loading={trafficAnalytics.loading}
            error={trafficAnalytics.error}
            interval={trafficInterval}
            onIntervalChange={setTrafficInterval}
            settingsLabel={lbl('MSG_THIRD_PARTY_APIS', 'Configure Third Party APIs')}
          >
            {trafficAnalytics.data && Object.keys(trafficAnalytics.data).length > 0 ? (
              <div className="analytics-box">
                <div id="analytic-traffic-chart" className="analytic-traffic-chart w-100" style={{ minHeight: 360 }} />
              </div>
            ) : (
              <div className="analytics-box">
                <p className="text-center w-100 mb-0">{lbl('LBL_NO_RECORD_FOUND', 'No record found')}</p>
              </div>
            )}
          </AdminDashboardAnalyticsCard>
        </div>
      </div>
    </main>
  );
}
