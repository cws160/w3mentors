import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminSubOrderViewModal } from '../components/AdminSubOrderViewModal';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

type Row = {
  id: number;
  admtxn_id: number;
  admtxn_amount: string;
  admtxn_record_id: number;
  admtxn_record_type: number;
  admtxn_record_type_label_key: string;
  admtxn_comment: string;
  admtxn_datetime: string;
};

const TYPE_LESSON = 1;
const TYPE_GCLASS = 2;
const TYPE_COURSE = 3;
const TYPE_SUBPLAN = 4;

const searchConfig: AdminModuleConfig = {
  module: 'admin-earnings',
  pageLangKey: 'admin-earnings',
  titleKey: 'LBL_ADMIN_EARNINGS',
  titleFallback: 'Admin earnings',
  searchSubmitCol: 3,
  searchFields: [
    {
      name: 'admtxn_record_type',
      labelKey: 'LBL_TYPE',
      labelFallback: 'Type',
      type: 'select',
      options: [
        { value: '', labelKey: 'LBL_SELECT_TYPE', labelFallback: 'Select type' },
        { value: '1', labelKey: 'ADMTXN_LESSON', labelFallback: 'Lesson' },
        { value: '2', labelKey: 'ADMTXN_GROUP_CLASS', labelFallback: 'Group class' },
        { value: '3', labelKey: 'ADMTXN_COURSE', labelFallback: 'Course' },
      ],
    },
    { name: 'admtxn_date_from', labelKey: 'LBL_DATE_FROM', labelFallback: 'Date from', type: 'date' },
    { name: 'admtxn_date_to', labelKey: 'LBL_DATE_TO', labelFallback: 'Date to', type: 'date' },
  ],
  columns: [],
};

export function AdminAdminEarningsPage() {
  const { lbl } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });
  const [viewModal, setViewModal] = useState<{
    module: 'lessons' | 'classes' | 'order-subscription-plans';
    row: Record<string, unknown>;
  } | null>(null);

  const columns = useMemo(
    () => [
      { key: 'admtxn_amount', label: lbl('LBL_EARNING', 'Earning') },
      { key: 'admtxn_record_type', label: lbl('LBL_EARNING_TYPE', 'Earning type') },
      { key: 'admtxn_datetime', label: lbl('LBL_DATETIME', 'Datetime') },
      { key: 'admtxn_comment', label: lbl('LBL_DESCRIPTION', 'Description') },
      { key: 'action', label: lbl('LBL_ACTION', 'Action') },
    ],
    [lbl],
  );

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('admin-earnings', { page, ...filters })
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
    void adminApi.pageText('admin-earnings').then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_ADMIN_EARNINGS', 'Admin earnings'),
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
        `"${row.admtxn_amount.replace(/"/g, '""')}"`,
        `"${lbl(row.admtxn_record_type_label_key, row.admtxn_record_type_label_key).replace(/"/g, '""')}"`,
        `"${row.admtxn_datetime.replace(/"/g, '""')}"`,
        `"${row.admtxn_comment.replace(/"/g, '""')}"`,
        '""',
      ].join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'admin-earnings.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const renderAction = (row: Row) => {
    const { admtxn_record_type: type, admtxn_record_id: recordId } = row;

    if (type === TYPE_LESSON && privileges.canViewLessonsOrders) {
      return (
        <ul className="actions">
          <li>
            <a
              href="javascript:void(0)"
              className="button small green"
              title={lbl('LBL_View', 'View')}
              onClick={() => setViewModal({ module: 'lessons', row: { ordles_id: recordId } })}
            >
              <svg className="svg" width="18" height="18">
                <use xlinkHref="/manager/views/images/retina/sprite-actions.svg#view" />
              </svg>
            </a>
          </li>
        </ul>
      );
    }

    if (type === TYPE_GCLASS && privileges.canViewClassesOrders) {
      return (
        <ul className="actions">
          <li>
            <a
              href="javascript:void(0)"
              className="button small green"
              title={lbl('LBL_View', 'View')}
              onClick={() => setViewModal({ module: 'classes', row: { ordcls_id: recordId } })}
            >
              <svg className="svg" width="18" height="18">
                <use xlinkHref="/manager/views/images/retina/sprite-actions.svg#view" />
              </svg>
            </a>
          </li>
        </ul>
      );
    }

    if (type === TYPE_COURSE && privileges.canViewCoursesOrders && privileges.canViewOrders) {
      return (
        <ul className="actions">
          <li>
            <Link
              to={`/admin/orders/${recordId}/view`}
              className="button small green"
              title={lbl('LBL_View', 'View')}
              target="_blank"
            >
              <svg className="svg" width="18" height="18">
                <use xlinkHref="/manager/views/images/retina/sprite-actions.svg#view" />
              </svg>
            </Link>
          </li>
        </ul>
      );
    }

    if (type === TYPE_SUBPLAN && privileges.canViewOrders) {
      return (
        <ul className="actions">
          <li>
            <a
              href="javascript:void(0)"
              className="button small green"
              title={lbl('LBL_View', 'View')}
              onClick={() =>
                setViewModal({
                  module: 'order-subscription-plans',
                  row: { ordsplan_id: recordId },
                })
              }
            >
              <svg className="svg" width="18" height="18">
                <use xlinkHref="/manager/views/images/retina/sprite-actions.svg#view" />
              </svg>
            </a>
          </li>
        </ul>
      );
    }

    return (
      <ul className="actions">
        <li>
          <a href="javascript:void(0)" className="button small green disabled" title={lbl('LBL_View', 'View')}>
            <svg className="svg" width="18" height="18">
              <use xlinkHref="/manager/views/images/retina/sprite-actions.svg#view" />
            </svg>
          </a>
        </li>
      </ul>
    );
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
            <li className="breadcrumb-item">{lbl('LBL_ADMIN_EARNINGS', 'Admin earnings')}</li>
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
                          <td>{row.admtxn_amount}</td>
                          <td>
                            {row.admtxn_record_type_label_key
                              ? lbl(
                                  row.admtxn_record_type_label_key,
                                  row.admtxn_record_type_label_key.replace('ADMTXN_', '').replace(/_/g, ' '),
                                )
                              : ''}
                          </td>
                          <td>{row.admtxn_datetime}</td>
                          <td>{row.admtxn_comment}</td>
                          <td>{renderAction(row)}</td>
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

      {viewModal ? (
        <AdminSubOrderViewModal
          module={viewModal.module}
          row={viewModal.row}
          lbl={lbl}
          onClose={() => setViewModal(null)}
        />
      ) : null}
    </main>
  );
}
