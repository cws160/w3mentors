import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useParams, useSearchParams } from 'react-router-dom';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { formatOrderId } from '../../w3mentors/dashboard/orders/orderFormat';
import { adminApi } from '../api/adminClient';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

type LogRow = {
  id: number;
  teacher_name: string;
  learner_name: string;
  order_id_formatted: string;
  lesson_id: number;
  prev_start_time: string;
  prev_end_time: string;
  prev_starttime_display: string;
  prev_endtime_display: string;
  has_prev_timings: boolean;
  show_prev_timings: boolean;
  prev_status_label: string;
  action_performed_label: string;
  action_by_name: string;
  added_on: string;
  added_on_display: string;
  reason: string;
};

const REPORT_RESCHEDULED = 1;

function isValidLogDatetime(value: string): boolean {
  const trimmed = value.trim();
  return trimmed !== '' && !trimmed.startsWith('0000-00-00');
}

function resolveDisplayName(name: string, deleted: boolean, deletedLabel: string): string {
  if (deleted) {
    return deletedLabel;
  }
  const trimmed = name.trim();
  return trimmed !== '' ? trimmed : deletedLabel;
}

function mapLogRow(row: Record<string, unknown>, deletedUserLabel: string): LogRow {
  const teacherDeleted = Boolean(row.teacher_deleted);
  const actorDeleted = Boolean(row.action_by_deleted);
  const orderId = Number(row.order_id ?? 0);
  const formattedOrderId = String(row.order_id_formatted ?? '').trim();
  const prevStart = String(
    row.prev_starttime ?? row.prev_start_time ?? row.sesslog_prev_starttime ?? '',
  );
  const prevEnd = String(row.prev_endtime ?? row.prev_end_time ?? row.sesslog_prev_endtime ?? '');

  return {
    id: Number(row.id ?? row.sesslog_id ?? 0),
    teacher_name: resolveDisplayName(String(row.teacher_name ?? ''), teacherDeleted, deletedUserLabel),
    learner_name: String(row.learner_name ?? '').trim(),
    order_id_formatted: formattedOrderId || (orderId > 0 ? formatOrderId(orderId) : ''),
    lesson_id: Number(row.ordles_id ?? row.lesson_id ?? 0),
    prev_start_time: prevStart,
    prev_end_time: prevEnd,
    prev_starttime_display: String(row.prev_starttime_display ?? ''),
    prev_endtime_display: String(row.prev_endtime_display ?? ''),
    has_prev_timings: isValidLogDatetime(prevStart) && isValidLogDatetime(prevEnd),
    show_prev_timings: Boolean(row.show_prev_timings ?? false),
    prev_status_label: String(row.sesslog_prev_status_label ?? row.prev_status_label ?? ''),
    action_performed_label: String(row.sesslog_changed_status_label ?? row.action_performed_label ?? ''),
    action_by_name: resolveDisplayName(String(row.action_by_name ?? ''), actorDeleted, deletedUserLabel),
    added_on: String(row.sesslog_created ?? row.added_on ?? ''),
    added_on_display: String(row.sesslog_created_display ?? row.added_on_display ?? ''),
    reason: String(row.sesslog_comment ?? row.reason ?? ''),
  };
}

function formatLogDateTime(value: string): string {
  if (!value) {
    return '';
  }
  const parsed = moment(value);
  if (!parsed.isValid()) {
    return value;
  }
  return parsed.format('MMM D, YYYY HH:mm');
}

function formatAddedOnHtml(value: string, displayValue = ''): string {
  const source = displayValue || value;
  if (!source) {
    return '';
  }
  const parsed = moment(source);
  if (!parsed.isValid()) {
    return source;
  }
  return `${parsed.format('MMM D, YYYY')}<br>${parsed.format('HH:mm')}`;
}

function formatPersonNameHtml(name: string): string {
  const trimmed = name.trim();
  if (!trimmed) {
    return '';
  }
  const parts = trimmed.split(/\s+/);
  if (parts.length <= 1) {
    return trimmed;
  }
  return `${parts[0]}<br>${parts.slice(1).join(' ')}`;
}

function escapeCsv(value: string | number | null | undefined): string {
  const text = String(value ?? '');
  if (text.includes(',') || text.includes('"') || text.includes('\n')) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
}

export function AdminLessonStatsLogsPage() {
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const { userId, reportType: reportTypeParam } = useParams<{ userId: string; reportType: string }>();

  const reportType = Number(
    reportTypeParam
      ?? searchParams.get('report_type')
      ?? searchParams.get('reportType')
      ?? (location.state as { reportType?: number } | null)?.reportType
      ?? 0,
  );
  const userIdNum = Number(userId);
  const isRescheduled = reportType === REPORT_RESCHEDULED;
  const showExtendedColumns = true;

  const inheritedFilters = useMemo(() => {
    const state = location.state as { filters?: Record<string, string> } | null;
    const filters = state?.filters ?? {};
    return {
      fromDate: filters.fromDate ?? '',
      toDate: filters.toDate ?? '',
    };
  }, [location.state]);

  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [rows, setRows] = useState<LogRow[]>([]);
  const [userName, setUserName] = useState('');
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const deletedUserLabel = lbl('LBL_DELETED_USER', 'Deleted User');

  const pageText = useMemo(
    () => ({
      title: lbl('LBL_LESSON_STATS', 'Lesson stats'),
      viewLogs: lbl('LBL_View_Logs', 'View logs'),
      sr: lbl('LBL_SR', 'Sr'),
      teacherName: lbl('LBL_TEACHER_NAME', 'Teacher name'),
      learnerName: lbl('LBL_LEARNER_NAME', 'Learner name'),
      orderDetails: lbl('LBL_ORDER_DETAILS', 'Order details'),
      prevTimings: lbl('LBL_PREV_TIMINGS', 'Prev timings'),
      prevStatus: lbl('LBL_PREV_STATUS', 'Prev status'),
      actionPerformed: lbl('LBL_ACTION_PERFORMED', 'Action performed'),
      actionBy: lbl('LBL_ACTION_BY', 'Action by'),
      addedOn: lbl('LBL_ADDED_ON', 'Added on'),
      reason: lbl('LBL_REASON', 'Reason'),
      orderId: 'O-id',
      lessonId: lbl('LBL_LESSON_ID', 'Lesson ID'),
      st: lbl('LBL_ST', 'St'),
      et: lbl('LBL_ET', 'Et'),
      na: lbl('LBL_NA', 'N/A'),
      rescheduledLog: lbl('LBL_RESCHEDULED_LOG', 'Rescheduled log'),
      cancelledLog: lbl('LBL_CANCELLED_LOG', 'Canceled log'),
    }),
    [lbl]
  );

  const reportTypeLabel = isRescheduled ? pageText.rescheduledLog : pageText.cancelledLog;
  const colSpan = 10;

  const resolveActionByName = (row: LogRow): string => row.action_by_name || userName;

  const formatPrevTimingsHtml = (row: LogRow): string => {
    const startDisplay = row.prev_starttime_display || formatLogDateTime(row.prev_start_time);
    const endDisplay = row.prev_endtime_display || formatLogDateTime(row.prev_end_time);
    if (!startDisplay || !endDisplay) {
      return pageText.na;
    }
    return `${pageText.st}: ${startDisplay}<br> ${pageText.et}: ${endDisplay}`;
  };

  const loadLogs = useCallback(() => {
    if (!userIdNum || !reportType) {
      setLoading(false);
      setRows([]);
      return;
    }

    setLoading(true);
    const logFilters = Object.fromEntries(
      Object.entries(inheritedFilters).filter(([, value]) => value !== ''),
    );

    void adminApi
      .lessonStatsLogs(userIdNum, reportType, { page, ...logFilters })
      .then((res) => {
        const payload = res.data;
        const mapped = (payload.data ?? []).map((row) => mapLogRow(row, deletedUserLabel));
        setRows(mapped);
        setPagination(
          payload.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 },
        );
        const user = payload.user as { full_name?: string } | undefined;
        setUserName(String(user?.full_name ?? ''));
      })
      .catch(() => {
        setRows([]);
        setPagination({ current_page: 1, per_page: 10, total: 0, last_page: 1 });
        setUserName('');
      })
      .finally(() => setLoading(false));
  }, [userIdNum, reportType, page, inheritedFilters, deletedUserLabel]);

  useEffect(() => {
    setPage(1);
  }, [userIdNum, reportType]);

  useEffect(() => {
    loadLogs();
  }, [loadLogs]);

  useEffect(() => {
    void adminApi.pageText('lesson-stats').then((res) => {
      const text = res.data.data ?? {};
      const baseTitle = text.title || pageText.title;
      const title = userName ? `${reportTypeLabel} - ${userName}` : baseTitle;
      setMeta({
        title,
        summary: text.summary,
        warning: text.warning,
        recommendations: text.recommendations,
        helpingText: text.helping_text,
        plangId: text.plang_id,
      });
    });

    return () => clearMeta();
  }, [clearMeta, pageText.title, reportTypeLabel, setMeta, userName]);

  const exportCsv = () => {
    const headers = [
      pageText.teacherName,
      pageText.learnerName,
      pageText.orderId,
      pageText.lessonId,
      ...(showExtendedColumns ? [pageText.prevTimings] : []),
      pageText.prevStatus,
      pageText.actionPerformed,
      ...(showExtendedColumns ? [pageText.actionBy] : []),
      pageText.addedOn,
      pageText.reason,
    ];

    const lines = rows.map((row) => {
      const prevTimings = showExtendedColumns
        ? row.has_prev_timings
          ? `${formatLogDateTime(row.prev_start_time)} / ${formatLogDateTime(row.prev_end_time)}`
          : pageText.na
        : '';
      const cells = [
        row.teacher_name,
        row.learner_name,
        row.order_id_formatted,
        row.lesson_id,
        ...(showExtendedColumns ? [prevTimings] : []),
        row.prev_status_label,
        row.action_performed_label,
        ...(showExtendedColumns ? [resolveActionByName(row)] : []),
        formatLogDateTime(row.added_on),
        row.reason,
      ];
      return cells.map(escapeCsv).join(',');
    });

    const csv = [headers.map(escapeCsv).join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `STATS_${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/lesson-stats">{pageText.title}</Link>
            </li>
            <li className="breadcrumb-item">{pageText.viewLogs}</li>
          </ul>
          <div className="action-toolbar">
            <a href="javascript:void(0)" className="btn btn-primary" onClick={exportCsv}>
              {lbl('LBL_EXPORT', 'Export')}
            </a>
          </div>
        </div>

        <div className="card">
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <>
                <table width="100%" className="table table--hovered">
                    <thead>
                      <tr>
                        <th style={{ width: '2%' }}>{pageText.sr}</th>
                        <th style={{ width: '15%' }}>{pageText.teacherName}</th>
                        <th>{pageText.learnerName}</th>
                        <th style={{ width: '15%' }}>{pageText.orderDetails}</th>
                        <th style={{ width: '20%', minWidth: 180 }}>{pageText.prevTimings}</th>
                        <th>{pageText.prevStatus}</th>
                        <th>{pageText.actionPerformed}</th>
                        <th>{pageText.actionBy}</th>
                        <th>{pageText.addedOn}</th>
                        <th>{pageText.reason}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {rows.length === 0 ? (
                        <tr>
                          <td colSpan={colSpan}>{lbl('LBL_No_Records_Found', 'No records found')}</td>
                        </tr>
                      ) : (
                        rows.map((row, index) => (
                          <tr key={row.id}>
                            <td>{(page - 1) * pagination.per_page + index + 1}</td>
                            <td>
                              <span>{row.teacher_name}</span>
                            </td>
                            <td>
                              <span>{row.learner_name}</span>
                            </td>
                            <td>
                              <span>
                                {pageText.orderId}: {row.order_id_formatted}
                                <br /> {pageText.lessonId}: {row.lesson_id}
                              </span>
                            </td>
                            <td style={{ minWidth: 180 }}>
                              <span dangerouslySetInnerHTML={{ __html: formatPrevTimingsHtml(row) }} />
                            </td>
                            <td>
                              <span>{row.prev_status_label}</span>
                            </td>
                            <td>
                              <span>{row.action_performed_label}</span>
                            </td>
                            <td>
                              <span
                                dangerouslySetInnerHTML={{
                                  __html: formatPersonNameHtml(resolveActionByName(row)),
                                }}
                              />
                            </td>
                            <td>
                              <span dangerouslySetInnerHTML={{ __html: formatAddedOnHtml(row.added_on, row.added_on_display) }} />
                            </td>
                            <td>
                              <span style={{ whiteSpace: 'pre-line' }}>{row.reason}</span>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                  <AdminLegacyPagination
                    page={page}
                    lastPage={pagination.last_page}
                    perPage={pagination.per_page}
                    total={pagination.total}
                    onPageChange={setPage}
                    labels={{
                      showing: lbl('LBL_Showing', 'Showing'),
                      to: lbl('LBL_to', 'to'),
                      of: lbl('LBL_of', 'of'),
                      entries: lbl('LBL_Entries', 'Entries'),
                    }}
                  />
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </main>
  );
}
