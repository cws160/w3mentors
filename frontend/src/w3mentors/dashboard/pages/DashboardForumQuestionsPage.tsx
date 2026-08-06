import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api, type Paginated } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { dashboardPath } from '../dashboardPaths';
import { useDashboardRole } from '../DashboardShell';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { ForumSpriteIcon } from '../components/ForumSpriteIcon';
import { ForumQuestionCommentsModal } from '../forum/ForumQuestionCommentsModal';
import { formatLegacyDateTime } from '../quiz/quizFormat';

type ForumQuestionRow = {
  id: number;
  serial: number;
  title: string;
  slug: string;
  status: number;
  status_label: string;
  comment_count: number;
  can_edit: boolean;
  can_view_comments: boolean;
  can_view_public: boolean;
  created_at: string | null;
};

/** Legacy dashboard/views/forum/index.php + my-questions-search.php */
export function DashboardForumQuestionsPage() {
  const { lbl } = useSite();
  const role = useDashboardRole();
  const navigate = useNavigate();
  const { showModal, closeModal } = useModal();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [rows, setRows] = useState<ForumQuestionRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<{ data: ForumQuestionRow[]; meta: Paginated<unknown>['meta'] }>('/dashboard/forum', {
        params: { page, keyword: keyword || undefined },
      })
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [page, keyword]);

  useEffect(() => {
    load();
  }, [load]);

  const openForm = (questionId = 0) => {
    navigate(
      questionId > 0
        ? dashboardPath(role, `forum/form/${questionId}`)
        : dashboardPath(role, 'forum/form')
    );
  };

  const openComments = (questionId: number) => {
    showModal(<ForumQuestionCommentsModal questionId={questionId} onClose={closeModal} />, {
      size: 'modal-lg',
    });
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_MY_QUESTIONS', 'My questions')}
        subtitle={lbl('LBL_MY_QUESTIONS_SUBHEADING', 'My questions subheading')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          <a
            href={dashboardPath(role, 'forum/form')}
            className="btn color-secondary btn--bordered"
            onClick={(e) => {
              e.preventDefault();
              openForm(0);
            }}
          >
            <ForumSpriteIcon id="add" className="icon icon--add icon--small me-2" />
            {lbl('LBL_ASK_QUESTION', 'Ask question')}
          </a>
        }
        searchPanel={
          <form
            className="form form--small"
            onSubmit={(e) => {
              e.preventDefault();
              setPage(1);
              load();
            }}
          >
            <div className="row">
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_KEYWORD', 'Keyword')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="text"
                        className="form-control"
                        value={keyword}
                        onChange={(e) => setKeyword(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6 form-buttons-group">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input type="submit" className="btn btn--primary" value={lbl('LBL_SEARCH', 'Search')} />
                      <input
                        type="button"
                        className="btn btn--secondary ms-2"
                        value={lbl('LBL_CLEAR', 'Clear')}
                        onClick={() => {
                          setKeyword('');
                          setPage(1);
                        }}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        }
      />
      <div className="page__body">
        <div className="page-content">
          <div id="forum-listing" className="table-scroll">
            {loading ? (
              <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
            ) : rows.length === 0 ? (
              <DashboardNoRecord />
            ) : (
              <>
                <table className="table table--styled table--responsive table--aligned-middle">
                  <tr className="title-row">
                    <th>{lbl('LBL_SRNO', 'Sr no')}</th>
                    <th>{lbl('LBL_TITLE', 'Title')}</th>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                    <th>{lbl('LBL_ADDED', 'Added')}</th>
                    <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                  </tr>
                  {rows.map((row) => (
                    <tr key={row.id} id={`myqueid_${row.id}`}>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_SRNO', 'Sr no')}>{row.serial}</DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_TITLE', 'Title')}>
                          <div className="d-sm-block">
                            <div>{row.title}</div>
                            {row.comment_count > 0 && (
                              <div>
                                <strong>
                                  {lbl('LBL_COMMENTS', 'Comments')} {row.comment_count}
                                </strong>
                              </div>
                            )}
                          </div>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_STATUS', 'Status')}>
                          {row.status_label}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_ADDED', 'Added')}>
                          {formatLegacyDateTime(row.created_at)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_ACTIONS', 'Actions')}>
                          <div className="actions-group">
                            {row.can_edit && (
                              <a
                                href={dashboardPath(role, `forum/form/${row.id}`)}
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                title={lbl('LBL_Forum_My_question_edit', 'Edit')}
                                data-row_id={row.id}
                                onClick={(e) => {
                                  e.preventDefault();
                                  openForm(row.id);
                                }}
                              >
                                <ForumSpriteIcon id="edit" className="icon icon--edit icon--small" />
                              </a>
                            )}
                            {row.can_view_comments && (
                              <a
                                href="javascript:void(0);"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                title={lbl('LBL_Forum_My_question_View_Comments', 'View comments')}
                                data-record_id={row.id}
                                onClick={(e) => {
                                  e.preventDefault();
                                  openComments(row.id);
                                }}
                              >
                                <ForumSpriteIcon id="message" className="icon icon--message" />
                              </a>
                            )}
                            {row.can_view_public && row.slug && (
                              <Link
                                to={`/forum/${row.slug}`}
                                target="_blank"
                                rel="noreferrer"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                title={lbl('LBL_Forum_My_question_View', 'View')}
                              >
                                <ForumSpriteIcon id="view-icon" className="icon icon--message icon--small" />
                              </Link>
                            )}
                          </div>
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
        </div>
      </div>
    </div>
  );
}
