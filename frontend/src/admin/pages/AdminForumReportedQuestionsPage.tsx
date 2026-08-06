import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { FORUM_MODULE_CONFIGS } from '../config/adminForumModules';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminForumReportedQuestionActions } from '../components/AdminForumReportedQuestionActions';
import { AdminForumReportedQuestionViewModal } from '../components/AdminForumReportedQuestionViewModal';
import { AdminForumReportedQuestionActionModal } from '../components/AdminForumReportedQuestionActionModal';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';

type Row = {
  id: number;
  fquerep_id: number;
  report_title: string;
  question_title: string;
  reporter_name: string;
  status: number;
  status_label: string;
  added_on: string;
};

const REPORT_ACCEPTED = 1;
const REPORT_CANCELLED = 2;
const config = FORUM_MODULE_CONFIGS['forum-reported-questions'];

export function AdminForumReportedQuestionsPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });
  const [viewId, setViewId] = useState<number | null>(null);
  const [actionId, setActionId] = useState(0);

  const canEdit = Boolean(privileges.canEditDiscussionForum) || admin?.id === 1;
  const columnCount = canEdit ? 7 : 6;

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('forum-reported-questions', { page, lang_id: langId, ...filters })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [filters, langId, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;

    void adminApi.pageText('forum-reported-questions').then((res) => {
      if (cancelled) {
        return;
      }
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Reported_Questions', 'Reported questions'),
        summary: pageText.summary,
        warning: pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });

    return () => {
      cancelled = true;
      clearMeta();
    };
  }, [clearMeta, lbl, setMeta]);

  const reportStatusLabel = (value: number) => {
    if (value === REPORT_ACCEPTED) {
      return lbl('LBL_Accepted', 'Accepted');
    }
    if (value === REPORT_CANCELLED) {
      return lbl('LBL_Cancelled', 'Cancelled');
    }
    return lbl('LBL_Pending', 'Pending');
  };

  const formatDate = (value: string) => {
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : lbl('LBL_NA', 'NA');
  };

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
    const header = [
      lbl('LBL_REPORT_TITLE', 'Report reason'),
      lbl('LBL_Question', 'Question'),
      lbl('LBL_Reported_By', 'Reported by'),
      lbl('LBL_Status', 'Status'),
      lbl('LBL_ADDED_ON', 'Added on'),
    ].join(',');
    const lines = rows.map((row) =>
      [
        `"${String(row.report_title).replace(/"/g, '""')}"`,
        `"${String(row.question_title).replace(/"/g, '""')}"`,
        `"${String(row.reporter_name).replace(/"/g, '""')}"`,
        `"${String(reportStatusLabel(row.status)).replace(/"/g, '""')}"`,
        `"${String(row.added_on).replace(/"/g, '""')}"`,
      ].join(','),
    );
    const csv = [header, ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'forum-reported-questions.csv';
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
            <li className="breadcrumb-item">{lbl('LBL_Reported_Questions', 'Reported questions')}</li>
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
              config={config}
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
            <div id="listing" className="table-responsive">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : null}
              <table className="table table--hovered table-dragable" width="100%" id="teachingLangages">
                <thead>
                  <tr>
                    <th>{lbl('LBL_SR_NO', 'Sr no')}</th>
                    <th>{lbl('LBL_Report_Title', 'Report reason')}</th>
                    <th>{lbl('LBL_Question', 'Question')}</th>
                    <th>{lbl('LBL_Reported_By', 'Reported by')}</th>
                    <th>{lbl('LBL_Status', 'Status')}</th>
                    <th>{lbl('LBL_ADDED_ON', 'Added on')}</th>
                    {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                  </tr>
                </thead>
                <tbody>
                  {rows.length === 0 && !loading ? (
                    <tr>
                      <td colSpan={columnCount} className="text-center">
                        {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                      </td>
                    </tr>
                  ) : (
                    rows.map((row, index) => (
                      <tr key={row.id} id={String(row.fquerep_id ?? row.id)}>
                        <td>{(page - 1) * pagination.per_page + index + 1}</td>
                        <td>{row.report_title}</td>
                        <td>{row.question_title}</td>
                        <td>{row.reporter_name}</td>
                        <td>{reportStatusLabel(row.status)}</td>
                        <td>{formatDate(row.added_on)}</td>
                        {canEdit ? (
                          <td className="align-right">
                            <AdminForumReportedQuestionActions
                              reportId={Number(row.fquerep_id ?? row.id)}
                              status={Number(row.status ?? 0)}
                              canEdit={canEdit}
                              labels={{
                                view: lbl('LBL_View', 'View'),
                                action: lbl('LBL_Action', 'Action'),
                              }}
                              onView={setViewId}
                              onAction={setActionId}
                            />
                          </td>
                        ) : null}
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
              {!loading ? (
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
              ) : null}
            </div>
          </div>
        </div>
      </div>

      <AdminForumReportedQuestionViewModal reportId={viewId} onClose={() => setViewId(null)} />
      <AdminForumReportedQuestionActionModal
        open={actionId > 0}
        reportId={actionId}
        onClose={() => setActionId(0)}
        onSaved={load}
      />
    </main>
  );
}
