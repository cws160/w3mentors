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
  id: string;
  slstat_date: string;
  slstat_total_sales: string;
  slstat_discount: string;
  slstat_credit_discount: string;
  slstat_net_sales: string;
};

const searchConfig: AdminModuleConfig = {
  module: 'sales-report',
  pageLangKey: 'sales-report',
  titleKey: 'LBL_SALES_REPORT',
  titleFallback: 'Sales report',
  searchSubmitCol: 3,
  searchFields: [
    { name: 'slstat_date_from', labelKey: 'LBL_DATE_FROM', labelFallback: 'Date from', type: 'date' },
    { name: 'slstat_date_to', labelKey: 'LBL_DATE_TO', labelFallback: 'Date to', type: 'date' },
  ],
  columns: [],
};

const InfoIcon = () => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="12"
    height="12"
    viewBox="0 0 24 24"
    style={{ marginLeft: 3, marginBottom: -1 }}
  >
    <path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-.001 5.75c.69 0 1.251.56 1.251 1.25s-.561 1.25-1.251 1.25-1.249-.56-1.249-1.25.559-1.25 1.249-1.25zm2.001 12.25h-4v-1c.484-.179 1-.201 1-.735v-4.467c0-.534-.516-.618-1-.797v-1h3v6.265c0 .535.517.558 1 .735v.999z" />
  </svg>
);

export function AdminSalesReportPage() {
  const { lbl, modules } = useSite();
  const { privileges, reportGeneratedAt } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const showClasses = Boolean(modules.group_classes);
  const showCourses = Boolean(modules.courses);

  const disabledInfo = useMemo(() => {
    if (!showCourses && !showClasses) {
      return lbl(
        'LBL_INFO_FOR_DISABLED_COURSE_CLASSES_STATS',
        'This section includes courses and classes data too.',
      );
    }
    if (!showCourses) {
      return lbl('LBL_INFO_FOR_DISABLED_COURSES_STATS', 'This section includes courses data too.');
    }
    if (!showClasses) {
      return lbl('LBL_INFO_FOR_DISABLED_CLASSES_STATS', 'This section includes classes data too.');
    }
    return '';
  }, [lbl, showClasses, showCourses]);

  const columns = useMemo(
    () => [
      {
        key: 'slstat_date',
        label: lbl('LBL_DATE', 'Date'),
        tooltip: '',
      },
      {
        key: 'slstat_total_sales',
        label: lbl('LBL_GROSS_SALES', 'Gross sales'),
        tooltip: lbl('ASR_GROSS_SALES_TOOLTIP', 'Gross sales tooltip'),
      },
      {
        key: 'slstat_discount',
        label: lbl('LBL_DISCOUNT', 'Discount'),
        tooltip: lbl('ASR_DISCOUNT_TOOLTIP', 'Discount tooltip'),
      },
      {
        key: 'slstat_credit_discount',
        label: lbl('LBL_REWARDS', 'Rewards'),
        tooltip: lbl('ASR_CREDIT_DISCOUNT_TOOLTIP', 'Rewards tooltip'),
      },
      {
        key: 'slstat_net_sales',
        label: lbl('LBL_NET_SALES', 'Net sales'),
        tooltip: lbl('ASR_NET_SALES_TOOLTIP', 'Net sales tooltip'),
      },
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
      .moduleList('sales-report', { page, ...filters })
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
    void adminApi.pageText('sales-report').then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_SALES_REPORT', 'Sales report'),
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
        ...columns.map((col) => {
          const value = row[col.key as keyof Row];
          return `"${String(value ?? '').replace(/"/g, '""')}"`;
        }),
      ].join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'sales-report.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const colSpan = 1 + columns.length;

  return (
    <main className="main">
      <div className="container">
        {disabledInfo ? (
          <div className="page-alert">
            <div className="alert alert--info">
              <span>{disabledInfo}</span>
            </div>
          </div>
        ) : null}

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
            <li className="breadcrumb-item">{lbl('LBL_SALES_REPORT', 'Sales report')}</li>
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
                        <th key={col.key} title={col.tooltip || undefined}>
                          {col.label}
                          {col.tooltip ? <InfoIcon /> : null}
                        </th>
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
                          {columns.map((col) => (
                            <td key={col.key}>{row[col.key as keyof Row]}</td>
                          ))}
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
