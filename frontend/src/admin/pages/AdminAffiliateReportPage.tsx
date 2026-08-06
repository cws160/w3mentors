import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

type Row = {
  id: number;
  user_id: number;
  affiliate_name: string;
  afstat_referees: number;
  afstat_referee_sessions: number;
  afstat_signup_revenue: string;
  afstat_order_revenue: string;
  total_revenue: string;
};

const searchConfig: AdminModuleConfig = {
  module: 'affiliate-report',
  pageLangKey: 'affiliate-report',
  titleKey: 'LBL_AFFILIATE_REPORT',
  titleFallback: 'Affiliate report',
  searchSubmitCol: 3,
  searchFields: [{ name: 'keyword', labelKey: 'LBL_USER', labelFallback: 'User', type: 'text' }],
  columns: [],
};

export function AdminAffiliateReportPage() {
  const { lbl } = useSite();
  const { features } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const affiliateEnabled = Boolean(features?.affiliate_enabled);

  const columns = useMemo(
    () => [
      { key: 'affiliate_name', label: lbl('LBL_AFFILIATE', 'Affiliate') },
      { key: 'afstat_referees', label: lbl('LBL_REFEREE_COUNT', 'Referee count') },
      { key: 'afstat_referee_sessions', label: lbl('LBL_SESSIONS_COUNT', 'Sessions count') },
      { key: 'afstat_signup_revenue', label: lbl('LBL_SIGN-UP_REVENUE', 'Sign-up revenue') },
      { key: 'afstat_order_revenue', label: lbl('LBL_SESSION_REVENUE', 'Session revenue') },
      { key: 'total_revenue', label: lbl('LBL_TOTAL_REVENUE', 'Total revenue') },
    ],
    [lbl],
  );

  const load = useCallback(() => {
    if (!affiliateEnabled) {
      setRows([]);
      setLoading(false);
      return;
    }

    setLoading(true);
    void adminApi
      .moduleList('affiliate-report', { page, ...filters })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [affiliateEnabled, filters, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    void adminApi.pageText('affiliate-report').then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_AFFILIATE_REPORT', 'Affiliate report'),
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
          return typeof value === 'string' ? `"${value.replace(/"/g, '""')}"` : String(value ?? '');
        }),
      ].join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'affiliate-report.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const colSpan = 1 + columns.length;

  if (!affiliateEnabled) {
    return (
      <main className="main">
        <div className="container">
          <div className="page-alert">
            <div className="alert alert--info">
              <span>{lbl('LBL_AFFILIATE_MODULE_DISABLED', 'Affiliate module is disabled.')}</span>
            </div>
          </div>
        </div>
      </main>
    );
  }

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_AFFILIATE_REPORT', 'Affiliate report')}</li>
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
