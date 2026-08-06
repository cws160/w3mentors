import { useCallback, useEffect, useState } from 'react';
import { api, type Paginated } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { formatLegacyDateTime } from '../quiz/quizFormat';

type CommentRow = {
  id: number;
  comment: string;
  accepted: boolean;
  added_on: string | null;
  user_name: string;
  likes: number;
  dislikes: number;
};

type Props = {
  questionId: number;
  onClose: () => void;
};

/** Legacy dashboard/views/forum/my-question-comments.php */
export function ForumQuestionCommentsModal({ questionId, onClose }: Props) {
  const { lbl } = useSite();
  const [rows, setRows] = useState<CommentRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    api
      .get<{ data: CommentRow[]; meta: Paginated<unknown>['meta'] }>(
        `/dashboard/forum/${questionId}/comments`,
        { params: { page } }
      )
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
      })
      .catch((err: unknown) => {
        const msg =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_Something_went_wrong', 'Something went wrong.');
        setError(msg);
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [questionId, page, lbl]);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <>
      <div className="modal-header">
        <h5>{lbl('LBL_QUESTION_COMMENTS', 'Question comments')}</h5>
        <button
          type="button"
          className="btn-close w3mentorsmodalJs"
          data-bs-dismiss="modal"
          aria-label=""
          onClick={onClose}
        />
      </div>
      <div className="modal-body">
        {loading ? (
          <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : error ? (
          <p className="text-danger">{error}</p>
        ) : rows.length === 0 ? (
          <DashboardNoRecord />
        ) : (
          <>
            <table className="table table--bordered table--responsive">
              <tr className="title-row">
                <th>{lbl('LBL_Comment_basic_info', 'Comment basic info')}</th>
                <th style={{ width: '65%' }}>{lbl('LBL_Forum_Comment', 'Comment')}</th>
              </tr>
              {rows.map((row) => (
                <tr key={row.id} id={`myqueid_${row.id}`}>
                  <td>
                    <DashboardFlexCell label={lbl('LBL_Comment_basic_info', 'Comment basic info')}>
                      <div className="d-sm-block">
                        <div>
                          <strong>{lbl('LBL_User', 'User')}:</strong> {row.user_name}
                        </div>
                        <div>
                          <strong>{lbl('LBL_Accepted', 'Accepted')}:</strong>{' '}
                          {row.accepted ? lbl('LBL_Yes', 'Yes') : '-'}
                        </div>
                        <div>
                          <strong>{lbl('LBL_Added_on', 'Added on')}:</strong>{' '}
                          {formatLegacyDateTime(row.added_on)}
                        </div>
                        <div>
                          <strong>{lbl('LBL_Likes', 'Likes')}:</strong> {row.likes}
                        </div>
                        <div>
                          <strong>{lbl('LBL_Dislikes', 'Dislikes')}:</strong> {row.dislikes}
                        </div>
                      </div>
                    </DashboardFlexCell>
                  </td>
                  <td>
                    <DashboardFlexCell label={lbl('LBL_Forum_Comment', 'Comment')}>
                      <div style={{ whiteSpace: 'pre-wrap' }}>{row.comment}</div>
                    </DashboardFlexCell>
                  </td>
                </tr>
              ))}
            </table>
            {meta && (
              <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
            )}
          </>
        )}
      </div>
    </>
  );
}
