import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { useReportRegenerate } from '../hooks/useReportRegenerate';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

type Row = {
  id: number;
  user_id: number;
  user_name: string;
  hts_lesson_duration: string;
  hts_class_duration: string;
  total_duration: string;
};

const searchConfig: AdminModuleConfig = {
  module: 'hours-taught-report',
  pageLangKey: 'hours-taught-report',
  titleKey: 'LBL_HOURS_TAUGHT_REPORT',
  titleFallback: 'Hours taught report',
  searchSubmitCol: 3,
  searchFields: [
    { name: 'keyword', labelKey: 'LBL_TEACHER', labelFallback: 'Teacher', type: 'text' },
    { name: 'fromDate', labelKey: 'LBL_DATE_FROM', labelFallback: 'Date from', type: 'date' },
    { name: 'toDate', labelKey: 'LBL_DATE_TO', labelFallback: 'Date to', type: 'date' },
  ],
  columns: [],
};

export function AdminHoursTaughtReportPage() {
  const { lbl } = useSite();
  const { privileges, reportGeneratedAt } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const columns = useMemo(
    () => [
      { key: 'user_name', label: lbl('LBL_TEACHER_NAME', 'Teacher name') },
      { key: 'hts_lesson_duration', label: lbl('LBL_TIME_TAUGHT_IN_LESSONS', 'Time taught in lessons') },
      { key: 'hts_class_duration', label: lbl('LBL_TIME_TAUGHT_IN_CLASSES', 'Time taught in classes') },
      { key: 'total_duration', label: lbl('LBL_TOTAL_TIME_TAUGHT', 'Total time taught') },
    ],
    [lbl],
  );

  const reportGeneratedLabel = useMemo(() => {
    if (!reportGeneratedAt) {
      return '';
    }
    return lbl('LBL_REPORT_GENERATED_ON_{datetime}', 'Report generated on {datetime}').replace(
      '{datetime}',
      reportGeneratedAt,
    );
  }, [lbl, reportGeneratedAt]);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('hours-taught-report', { page, ...filters })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [filters, page]);

  const { regenerating, successMessage, onRegenerate } = useReportRegenerate(load);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    void adminApi.pageText('hours-taught-report').then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_HOURS_TAUGHT_REPORT', 'Hours taught report'),
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
    const headers = [lbl('LBL_SRNO', 'Sr no'), ...columns.map((col) => col.label)];
    const lines = rows.map((row, index) =>
      [
        String((page - 1) * pagination.per_page + index + 1),
        `"${row.user_name.replace(/"/g, '""')}"`,
        `"${row.hts_lesson_duration.replace(/"/g, '""')}"`,
        `"${row.hts_class_duration.replace(/"/g, '""')}"`,
        `"${row.total_duration.replace(/"/g, '""')}"`,
      ].join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'hours-taught-report.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const colSpan = 1 + columns.length;

  return (
    <main className="main">
      <div className="container">
        {successMessage ? (
          <div className="page-alert">
            <div className="alert alert-success">
              <span>{successMessage}</span>
            </div>
          </div>
        ) : null}

        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_HOURS_TAUGHT_REPORT', 'Hours taught report')}</li>
          </ul>
          <div className="action-toolbar">
            {reportGeneratedLabel ? (
              <span className="-color-secondary span-right pt-2">{reportGeneratedLabel}</span>
            ) : null}
            {privileges.canViewSalesReportRegenerate ? (
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={onRegenerate}
                aria-disabled={regenerating}
              >
                {regenerating
                  ? lbl('LBL_PLEASE_WAIT', 'Please wait...')
                  : lbl('LBL_REGENERATE', 'Regenerate')}
              </a>
            ) : null}
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
            <h4>{lbl('LBL_Search...', 'Search...')}</h4>
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
                      {columns.map((col) => (
                        <th key={col.key}>{col.label}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={colSpan} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(page - 1) * pagination.per_page + index + 1}</td>
                          <td>
                            {row.user_name}
                            <br />
                            <small>
                              {lbl('LBL_USER_ID', 'User ID')}: {row.user_id}
                            </small>
                          </td>
                          <td>{row.hts_lesson_duration}</td>
                          <td>{row.hts_class_duration}</td>
                          <td>{row.total_duration}</td>
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
