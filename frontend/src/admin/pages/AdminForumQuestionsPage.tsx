import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { FORUM_MODULE_CONFIGS } from '../config/adminForumModules';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminForumQuestionActions } from '../components/AdminForumQuestionActions';
import { AdminForumQuestionViewModal } from '../components/AdminForumQuestionViewModal';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type Row = {
  id: number;
  fque_id: number;
  title: string;
  user_name: string;
  language_label: string;
  status_label: string;
  comments_allowed: number;
  comment_count: number;
  added_on: string;
};

const config = FORUM_MODULE_CONFIGS.forum;

export function AdminForumQuestionsPage() {
  const navigate = useNavigate();
  const { lbl } = useSite();
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

  const canEdit = Boolean(privileges.canEditDiscussionForum) || admin?.id === 1;
  const confirms = adminLegacyConfirms(lbl);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('forum', { page, ...filters })
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
    setMeta({ title: lbl('LBL_All_Questions', 'All questions') });
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
    const header = [
      lbl('LBL_Title', 'Title'),
      lbl('LBL_User', 'User'),
      lbl('LBL_FORUM_LANGUAGE', 'Language'),
      lbl('LBL_Status', 'Status'),
      lbl('LBL_Added_On', 'Added on'),
    ].join(',');
    const lines = rows.map((row) =>
      [
        `"${String(row.title).replace(/"/g, '""')}"`,
        `"${String(row.user_name).replace(/"/g, '""')}"`,
        `"${String(row.language_label).replace(/"/g, '""')}"`,
        `"${String(row.status_label).replace(/"/g, '""')}"`,
        `"${String(row.added_on).replace(/"/g, '""')}"`,
      ].join(','),
    );
    const csv = [header, ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'forum-questions.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(confirms.delete)) {
      return;
    }
    try {
      await adminApi.deleteForumQuestion(id);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String(
              (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                'Unable to delete',
            )
          : 'Unable to delete';
      window.alert(message);
    }
  };

  const formatDate = (value: string) => {
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_All_Questions', 'All questions')}</li>
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
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table table--hovered" width="100%">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_SR_NO', 'Sr. No.')}</th>
                      <th>{lbl('LBL_Title', 'Title')}</th>
                      <th>{lbl('LBL_User', 'User')}</th>
                      <th>{lbl('LBL_FORUM_LANGUAGE', 'Language')}</th>
                      <th>{lbl('LBL_Status', 'Status')}</th>
                      <th>{lbl('LBL_Added_On', 'Added on')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={canEdit ? 7 : 6} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(page - 1) * pagination.per_page + index + 1}</td>
                          <td>{row.title}</td>
                          <td>{row.user_name}</td>
                          <td>{row.language_label}</td>
                          <td>{row.status_label}</td>
                          <td>{formatDate(row.added_on)}</td>
                          {canEdit ? (
                            <td className="align-right">
                              <AdminForumQuestionActions
                                questionId={Number(row.fque_id ?? row.id)}
                                commentCount={Number(row.comment_count ?? 0)}
                                commentsAllowed={Number(row.comments_allowed ?? 0) === 1}
                                canEdit={canEdit}
                                labels={{
                                  view: lbl('LBL_View', 'View'),
                                  delete: lbl('LBL_Delete', 'Delete'),
                                  viewComments: lbl('LBL_View_Comments', 'View comments'),
                                }}
                                onView={setViewId}
                                onDelete={onDelete}
                                onViewComments={(id) => navigate(`/admin/forum/${id}/comments`)}
                              />
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

      <AdminForumQuestionViewModal questionId={viewId} onClose={() => setViewId(null)} />
    </main>
  );
}
