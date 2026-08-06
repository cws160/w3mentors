import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

const LOG_RESCHEDULED = 1;
const LOG_CANCELLED = 2;

type Row = {
  id: number;
  user_id: number;
  user_full_name: string;
  user_email: string;
  user_is_teacher: number;
  rescheduledCount: number;
  cancelledCount: number;
};

const searchConfig: AdminModuleConfig = {
  module: 'lesson-stats',
  pageLangKey: 'lesson-stats',
  titleKey: 'LBL_LESSON_STATS',
  titleFallback: 'Lesson stats',
  searchDateInputClass: 'small dateTimeFld field--calender',
  searchSubmitCol: 3,
  searchFields: [
    { name: 'user', labelKey: 'LBL_USER', labelFallback: 'User', type: 'text' },
    { name: 'fromDate', labelKey: 'LBL_DATE_FROM', labelFallback: 'Date from', type: 'date' },
    { name: 'toDate', labelKey: 'LBL_DATE_TO', labelFallback: 'Date to', type: 'date' },
  ],
  columns: [],
};

function formatUserType(isTeacher: number, lbl: (key: string, fallback?: string) => string): string {
  const learner = lbl('LBL_LEARNER', 'Learner');
  if (Number(isTeacher) === 1) {
    return `${learner} | ${lbl('LBL_TEACHER', 'Teacher')}`;
  }
  return learner;
}

export function AdminLessonStatsPage() {
  const navigate = useNavigate();
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('lesson-stats', { page, ...filters })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [filters, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    void adminApi.pageText('lesson-stats').then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_LESSON_STATS', 'Lesson stats'),
        summary: pageText.summary,
        warning: pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });

    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    setFilters({ ...draft });
    setPage(1);
  };

  const onClear = () => {
    setDraft({});
    setFilters({});
    setPage(1);
  };

  const onExport = () => {
    const headers = [
      lbl('LBL_USER_NAME', 'User name'),
      lbl('LBL_USER_EMAIL', 'User email'),
      lbl('LBL_USER_TYPE', 'User type'),
      lbl('LBL_RESCHEDULED', 'Rescheduled'),
      lbl('LBL_CANCELLED', 'Cancelled'),
    ];
    const lines = rows.map((row) =>
      [
        `"${String(row.user_full_name).replace(/"/g, '""')}"`,
        `"${String(row.user_email).replace(/"/g, '""')}"`,
        `"${formatUserType(row.user_is_teacher, lbl).replace(/"/g, '""')}"`,
        String(row.rescheduledCount),
        String(row.cancelledCount),
      ].join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'lesson-stats.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const openLogs = (userId: number, reportType: number) => {
    navigate(`/admin/lesson-stats/${userId}/logs/${reportType}?report_type=${reportType}`, {
      state: { filters, reportType },
    });
  };

  const renderCountCell = (count: number, userId: number, reportType: number) => {
    if (count <= 0) {
      return count;
    }

    return (
      <>
        {count}{' '}
        <a
          href="javascript:void(0)"
          className="link-dotted link-text"
          onClick={(e) => {
            e.preventDefault();
            openLogs(userId, reportType);
          }}
        >
          {lbl('LBL_SESSIONS', 'Sessions')}
        </a>
      </>
    );
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_LESSON_STATS', 'Lesson stats')}</li>
          </ul>
          <div className="action-toolbar">
            <a href="javascript:void(0)" className="btn btn-primary" onClick={onExport}>
              {lbl('LBL_EXPORT', 'Export')}
            </a>
          </div>
        </div>

        <div className="card">
          <div
            className={`card-head js--filter-trigger${filterOpen ? ' active' : ''}`}
            onClick={() => setFilterOpen((v) => !v)}
          >
            <h4>{lbl('LBL_SEARCH', 'Search')}</h4>
          </div>
          <div className="card-body js--filter-target" style={{ display: filterOpen ? 'block' : 'none' }}>
            <AdminModuleSearchForm
              config={searchConfig}
              draft={draft}
              lbl={lbl}
              onDraftChange={setDraft}
              onSearch={onSearch}
              onClear={onClear}
            />
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
                <table className="table table--hovered" width="100%">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_SRNO', 'Sr no')}</th>
                      <th>{lbl('LBL_USER_NAME', 'User name')}</th>
                      <th>{lbl('LBL_USER_EMAIL', 'User email')}</th>
                      <th>{lbl('LBL_USER_TYPE', 'User type')}</th>
                      <th>{lbl('LBL_RESCHEDULED', 'Rescheduled')}</th>
                      <th>{lbl('LBL_CANCELLED', 'Cancelled')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={6} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(page - 1) * pagination.per_page + index + 1}</td>
                          <td>{row.user_full_name}</td>
                          <td>{row.user_email}</td>
                          <td>{formatUserType(row.user_is_teacher, lbl)}</td>
                          <td>{renderCountCell(row.rescheduledCount, row.user_id, LOG_RESCHEDULED)}</td>
                          <td>{renderCountCell(row.cancelledCount, row.user_id, LOG_CANCELLED)}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
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
            </div>
          </div>
        </div>
      </div>
    </main>
  );
}
