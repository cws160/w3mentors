import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';
import type { AdminModuleConfig } from '../config/adminModuleTypes';
import { adminReportLabel } from '../utils/adminReportLabels';

type ReportKind = 'lesson-languages' | 'class-languages';

type Row = {
  id: number;
  tlang_id: number;
  language: string;
  unscheduled?: number;
  scheduled: number;
  completed: number;
  cancelled: number;
  totalsold: number;
};

const REPORT_CONFIG: Record<
  ReportKind,
  {
    module: ReportKind;
    titleKey: string;
    titleFallback: string;
    languageField: string;
    languageIdField: string;
    viewPath: string;
    viewQuery: Record<string, string>;
    viewPrivilege: 'canViewLessonsOrders' | 'canViewClassesOrders';
    showUnscheduled: boolean;
  }
> = {
  'lesson-languages': {
    module: 'lesson-languages',
    titleKey: 'EXP_LESSON_LANGUAGES',
    titleFallback: 'Lesson languages',
    languageField: 'ordles_tlang',
    languageIdField: 'ordles_tlang_id',
    viewPath: '/admin/lessons',
    viewQuery: { order_payment_status: '1' },
    viewPrivilege: 'canViewLessonsOrders',
    showUnscheduled: true,
  },
  'class-languages': {
    module: 'class-languages',
    titleKey: 'EXP_CLASS_LANGUAGES',
    titleFallback: 'Class languages',
    languageField: 'grpcls_tlang',
    languageIdField: 'grpcls_tlang_id',
    viewPath: '/admin/classes',
    viewQuery: { order_payment_status: '1' },
    viewPrivilege: 'canViewClassesOrders',
    showUnscheduled: false,
  },
};

type Props = {
  kind: ReportKind;
};

export function AdminTopLanguagesReportPage({ kind }: Props) {
  const navigate = useNavigate();
  const { lbl } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const report = REPORT_CONFIG[kind];

  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const canView = Boolean(privileges[report.viewPrivilege]);

  const reportLbl = useCallback(
    (key: string, fallback?: string) => adminReportLabel(lbl, key, fallback),
    [lbl],
  );

  const pageTitle = reportLbl(report.titleKey, report.titleFallback);

  const searchConfig = useMemo<AdminModuleConfig>(
    () => ({
      module: report.module,
      pageLangKey: report.module,
      titleKey: report.titleKey,
      titleFallback: report.titleFallback,
      searchDateInputClass: 'small dateTimeFld field--calender',
      searchSubmitCol: 3,
      searchFields: [
        {
          name: report.languageField,
          labelKey: 'LBL_LANGUAGE',
          labelFallback: 'Language',
          type: 'text',
        },
        {
          name: 'order_addedon_from',
          labelKey: 'LBL_DATE_FROM',
          labelFallback: 'Date from',
          type: 'date',
        },
        {
          name: 'order_addedon_to',
          labelKey: 'LBL_DATE_TO',
          labelFallback: 'Date to',
          type: 'date',
        },
      ],
      columns: [],
    }),
    [report],
  );

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList(report.module, { page, ...filters })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [filters, page, report.module]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({ title: pageTitle });
    return () => clearMeta();
  }, [clearMeta, pageTitle, setMeta]);

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
      reportLbl('LBL_LANGUAGE', 'Language'),
      ...(report.showUnscheduled ? [lbl('LBL_UNSCHEDULED', 'Unscheduled')] : []),
      lbl('LBL_SCHEDULED', 'Scheduled'),
      lbl('LBL_COMPLETED', 'Completed'),
      lbl('LBL_CANCELLED', 'Cancelled'),
      lbl('LBL_TOTAL_SOLD', 'Total sold'),
    ];
    const lines = rows.map((row) =>
      [
        `"${String(row.language).replace(/"/g, '""')}"`,
        ...(report.showUnscheduled ? [String(row.unscheduled ?? 0)] : []),
        String(row.scheduled),
        String(row.completed),
        String(row.cancelled),
        String(row.totalsold),
      ].join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${report.module}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const onView = (tlangId: number) => {
    const params = new URLSearchParams({
      ...report.viewQuery,
      [kind === 'lesson-languages' ? 'ordles_tlang_id' : 'ordcls_tlang_id']: String(tlangId),
    });
    navigate(`${report.viewPath}?${params.toString()}`);
  };

  const colSpan =
    2 +
    (report.showUnscheduled ? 1 : 0) +
    4 +
    (canView ? 1 : 0);

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{pageTitle}</li>
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
              lbl={reportLbl}
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
                      <th>{reportLbl('LBL_LANGUAGE', 'Language')}</th>
                      {report.showUnscheduled ? (
                        <th>{lbl('LBL_UNSCHEDULED', 'Unscheduled')}</th>
                      ) : null}
                      <th>{lbl('LBL_SCHEDULED', 'Scheduled')}</th>
                      <th>{lbl('LBL_COMPLETED', 'Completed')}</th>
                      <th>{lbl('LBL_CANCELLED', 'Canceled')}</th>
                      <th>{lbl('LBL_TOTAL_SOLD', 'Total sold')}</th>
                      {canView ? <th>{lbl('LBL_ACTION', 'Action')}</th> : null}
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
                          <td>{row.language}</td>
                          {report.showUnscheduled ? <td>{row.unscheduled ?? 0}</td> : null}
                          <td>{row.scheduled}</td>
                          <td>{row.completed}</td>
                          <td>{row.cancelled}</td>
                          <td>{row.totalsold}</td>
                          {canView ? (
                            <td>
                              <ul className="actions">
                                <li title={lbl('LBL_View', 'View')} data-bs-toggle="tooltip" data-placement="top">
                                  <a
                                    href="javascript:void(0)"
                                    onClick={(e) => {
                                      e.preventDefault();
                                      onView(Number(row.tlang_id ?? row.id));
                                    }}
                                  >
                                    <AdminSpriteIcon icon="view" />
                                  </a>
                                </li>
                              </ul>
                            </td>
                          ) : null}
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

export function AdminLessonLanguagesPage() {
  return <AdminTopLanguagesReportPage kind="lesson-languages" />;
}

export function AdminClassLanguagesPage() {
  return <AdminTopLanguagesReportPage kind="class-languages" />;
}
