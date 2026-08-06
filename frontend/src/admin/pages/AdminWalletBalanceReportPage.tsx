import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

type Row = {
  id: number;
  user_id: number;
  user_name: string;
  user_wallet_balance: string;
  user_is_teacher: number;
  user_is_affiliate: number;
  user_registered_as: number | null;
};

const USER_TEACHER = 2;

const searchConfig: AdminModuleConfig = {
  module: 'wallet-balance-report',
  pageLangKey: 'wallet-balance-report',
  titleKey: 'LBL_WALLET_BALANCE_REPORT',
  titleFallback: 'Wallet balance report',
  searchSubmitCol: 3,
  searchFields: [
    { name: 'keyword', labelKey: 'LBL_USER', labelFallback: 'User', type: 'text' },
    {
      name: 'user_type',
      labelKey: 'LBL_USER_TYPE',
      labelFallback: 'User type',
      type: 'select',
      options: [
        { value: '', labelKey: 'LBL_SELECT_USER_TYPE', labelFallback: 'Select user type' },
        { value: '1', labelKey: 'LBL_Learner', labelFallback: 'Learner' },
        { value: '2', labelKey: 'LBL_Teacher', labelFallback: 'Teacher' },
        { value: '5', labelKey: 'LBL_Affiliate', labelFallback: 'Affiliate' },
      ],
    },
  ],
  columns: [],
};

function renderUserType(row: Row, lbl: (key: string, fallback: string) => string) {
  if (row.user_is_affiliate) {
    return (
      <ul className="chips">
        <li className="chip supplier">{lbl('LBL_Affiliate', 'Affiliate')}</li>
      </ul>
    );
  }

  if (row.user_is_teacher) {
    return (
      <ul className="chips">
        <li className="chip supplier">{lbl('LBL_Learner', 'Learner')}</li>
        <li className="chip advertiser">{lbl('LBL_Teacher', 'Teacher')}</li>
      </ul>
    );
  }

  if (row.user_registered_as === USER_TEACHER) {
    return (
      <ul className="chips">
        <li className="chip supplier">{lbl('LBL_Learner', 'Learner')}</li>
        <li>
          <small className="badge badge-danger">
            {lbl('LBL_SIGNING_UP_FOR_TEACHER', 'Signing up for teacher')}
          </small>
        </li>
      </ul>
    );
  }

  return (
    <ul className="chips">
      <li className="chip supplier">{lbl('LBL_Learner', 'Learner')}</li>
    </ul>
  );
}

export function AdminWalletBalanceReportPage() {
  const { lbl } = useSite();
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
      { key: 'user_name', label: lbl('LBL_USER_NAME', 'User name') },
      { key: 'type', label: lbl('LBL_USER_TYPE', 'User type') },
      { key: 'user_wallet_balance', label: lbl('LBL_REMAINING_BALANCE', 'Remaining balance') },
    ],
    [lbl],
  );

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('wallet-balance-report', { page, ...filters })
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
    void adminApi.pageText('wallet-balance-report').then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_WALLET_BALANCE_REPORT', 'Wallet balance report'),
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
        `"${lbl('LBL_Learner', 'Learner')}"`,
        `"${row.user_wallet_balance.replace(/"/g, '""')}"`,
      ].join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'wallet-balance-report.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const colSpan = 1 + columns.length;

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_WALLET_BALANCE_REPORT', 'Wallet balance report')}</li>
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
                          <td>{row.user_name}</td>
                          <td>{renderUserType(row, lbl)}</td>
                          <td>{row.user_wallet_balance}</td>
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
