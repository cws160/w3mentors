import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { renderLegacyAdminHtml } from '../utils/adminLegacyHtml';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type CommentRow = {
  id: number;
  comment: string;
  accepted: number;
  likes: number;
  dislikes: number;
  added_on: string;
  user_name: string;
  fque_id: number;
  fque_deleted: number;
};

export function AdminForumQuestionCommentsPage() {
  const { questionId } = useParams();
  const queId = Number(questionId ?? 0);
  const { lbl } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<CommentRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const canEdit = Boolean(privileges.canEditDiscussionForum) || admin?.id === 1;
  const confirms = adminLegacyConfirms(lbl);
  const columnCount = canEdit ? 8 : 7;

  const load = useCallback(() => {
    if (queId < 1) {
      setRows([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    void adminApi
      .forumQuestionComments(queId, page)
      .then((res) => {
        setRows((res.data.data ?? []) as CommentRow[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [page, queId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;

    void adminApi.pageText('forum/comments').then((res) => {
      if (cancelled) {
        return;
      }
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_COMMENTS', 'Comments'),
        summary: pageText.summary || 'View the list of comments added by the users',
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

  const formatDate = (value: string) => {
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : lbl('LBL_NA', 'NA');
  };

  const formatAccepted = (value: number) =>
    value === 1 ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No');

  const onDelete = async (commentId: number) => {
    if (!window.confirm(confirms.delete)) {
      return;
    }
    try {
      await adminApi.deleteForumQuestionComment(queId, commentId);
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

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/forum">{lbl('LBL_FORUM', 'Forum')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_COMMENTS', 'Comments')}</li>
          </ul>
        </div>

        <div className="card">
          <div className="card-table">
            <div id="listing" className="table-responsive">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <>
                  <table className="table table--hovered" width="100%">
                    <thead>
                      <tr>
                        <th>{lbl('LBL_SRNO', 'Sr no')}</th>
                        <th>{lbl('LBL_FORUM_COMMENT', 'Comment')}</th>
                        <th>{lbl('LBL_FORUM_COMMENTED_BY', 'Comment by')}</th>
                        <th>{lbl('LBL_FORUM_COMMENT_ACCEPTED', 'Comment accepted')}</th>
                        <th>{lbl('LBL_FORUM_COMMENT_LIKES', 'Comment likes')}</th>
                        <th>{lbl('LBL_FORUM_COMMENT_DISLIKES', 'Comment dislikes')}</th>
                        <th>{lbl('LBL_FORUM_COMMENT_ADDED_ON', 'Comment added on')}</th>
                        {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                      </tr>
                    </thead>
                    <tbody>
                      {rows.length === 0 ? (
                        <tr>
                          <td colSpan={columnCount} className="text-center">
                            {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                          </td>
                        </tr>
                      ) : (
                        rows.map((row, index) => (
                          <tr
                            key={row.id}
                            id={String(row.fque_id)}
                            className={row.fque_deleted === 1 ? 'disabled' : undefined}
                          >
                            <td>{(page - 1) * pagination.per_page + index + 1}</td>
                            <td>
                              <div
                                dangerouslySetInnerHTML={{ __html: renderLegacyAdminHtml(row.comment) }}
                              />
                            </td>
                            <td>{row.user_name}</td>
                            <td>{formatAccepted(row.accepted)}</td>
                            <td>{row.likes}</td>
                            <td>{row.dislikes}</td>
                            <td>{formatDate(row.added_on)}</td>
                            {canEdit ? (
                              <AdminRowActionsCell
                                actions={[
                                  {
                                    icon: 'delete',
                                    title: lbl('LBL_Delete', 'Delete'),
                                    onClick: () => void onDelete(row.id),
                                  },
                                ]}
                              />
                            ) : null}
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
