import { useCallback, useEffect, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { api, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';
import { DashboardAddIcon } from '../components/DashboardAddIcon';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { DashboardStatusSwitch } from '../components/DashboardStatusSwitch';
import { formatLegacyDateTime, formatPassPercent, formatQuizDuration } from '../quiz/quizFormat';

type QuizRow = {
  id: number;
  title: string;
  type: number;
  type_label: string;
  question_count: number;
  duration: number;
  attempts: number;
  pass_percent: number | null;
  status: number;
  status_label: string;
  active: number;
  is_active: boolean;
  created_at: string | null;
};

const QUIZ_TYPES = [
  { value: '', labelKey: 'LBL_SELECT', fallback: 'Select' },
  { value: '1', labelKey: 'LBL_AUTO_GRADED', fallback: 'Auto graded' },
  { value: '2', labelKey: 'LBL_NON_GRADED', fallback: 'Non graded' },
] as const;

const QUIZ_STATUSES = [
  { value: '', labelKey: 'LBL_SELECT', fallback: 'Select' },
  { value: '1', labelKey: 'LBL_DRAFTED', fallback: 'Drafted' },
  { value: '2', labelKey: 'LBL_PUBLISHED', fallback: 'Published' },
] as const;

const ACTIVE_OPTIONS = [
  { value: '', labelKey: 'LBL_SELECT', fallback: 'Select' },
  { value: '1', labelKey: 'LBL_ACTIVE', fallback: 'Active' },
  { value: '0', labelKey: 'LBL_INACTIVE', fallback: 'Inactive' },
] as const;

/** Legacy dashboard/views/quizzes/index.php + search.php */
export function DashboardQuizzesPage() {
  const role = useDashboardRole();
  const { lbl } = useSite();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [type, setType] = useState('');
  const [status, setStatus] = useState('');
  const [active, setActive] = useState('');
  const [rows, setRows] = useState<QuizRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    if (role !== 'teacher') return;
    setLoading(true);
    api
      .get<{ data: QuizRow[]; meta: Paginated<unknown>['meta'] }>('/dashboard/quizzes', {
        params: {
          page,
          keyword: keyword || undefined,
          type: type || undefined,
          status: status || undefined,
          active: active !== '' ? active : undefined,
        },
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
  }, [role, page, keyword, type, status, active]);

  useEffect(() => {
    load();
  }, [load]);

  if (role !== 'teacher') {
    return <Navigate to={dashboardPath('learner')} replace />;
  }

  const toggleActive = async (row: QuizRow) => {
    if (!window.confirm(lbl('LBL_CONFIRM_UPDATE_STATUS', 'Update status?'))) return;
    try {
      await api.patch(`/dashboard/quizzes/${row.id}/active`, { active: row.active });
      load();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    }
  };

  const removeQuiz = async (id: number) => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'))) return;
    try {
      await api.delete(`/dashboard/quizzes/${id}`);
      load();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    }
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_QUIZZES', 'Quizzes')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          <Link to={dashboardPath('teacher', 'quizzes/form')} className="btn color-secondary btn--bordered">
            <DashboardAddIcon />
            {lbl('LBL_ADD_QUIZ', 'Add quiz')}
          </Link>
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
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_TYPE', 'Type')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={type} onChange={(e) => setType(e.target.value)}>
                        {QUIZ_TYPES.map((opt) => (
                          <option key={opt.value || 'all'} value={opt.value}>
                            {lbl(opt.labelKey, opt.fallback)}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_STATUS', 'Status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                        {QUIZ_STATUSES.map((opt) => (
                          <option key={opt.value || 'all'} value={opt.value}>
                            {lbl(opt.labelKey, opt.fallback)}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_ACTIVE', 'Active')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={active} onChange={(e) => setActive(e.target.value)}>
                        {ACTIVE_OPTIONS.map((opt) => (
                          <option key={opt.value === '' ? 'all' : opt.value} value={opt.value}>
                            {lbl(opt.labelKey, opt.fallback)}
                          </option>
                        ))}
                      </select>
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
                          setType('');
                          setStatus('');
                          setActive('');
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
        <div className="page-content" id="quiz-listing">
          {loading ? (
            <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
          ) : rows.length === 0 ? (
            <DashboardNoRecord />
          ) : (
            <div className="table-scroll">
              <table className="table table--styled table--responsive table--aligned-middle">
                <tr className="title-row">
                  <th>{lbl('LBL_TITLE', 'Title')}</th>
                  <th>{lbl('LBL_TYPE', 'Type')}</th>
                  <th>{lbl('LBL_NO._OF_QUESTIONS', 'No. of questions')}</th>
                  <th>{lbl('LBL_DURATION', 'Duration')}</th>
                  <th>{lbl('LBL_ATTEMPTS', 'Attempts')}</th>
                  <th>{lbl('LBL_PASS_PERCENT', 'Pass percent')}</th>
                  <th>{lbl('LBL_STATUS', 'Status')}</th>
                  <th>{lbl('LBL_ACTIVE', 'Active')}</th>
                  <th>{lbl('LBL_DATE', 'Date')}</th>
                  <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                </tr>
                {rows.map((row) => (
                  <tr key={row.id}>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_TITLE', 'Title')}>{row.title}</DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_TYPE', 'Type')}>
                        {row.type_label || row.type}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_NO._OF_QUESTIONS', 'No. of questions')}>
                        {row.question_count}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_DURATION', 'Duration')}>
                        {formatQuizDuration(row.duration)}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ATTEMPTS', 'Attempts')}>
                        {row.attempts > 0 ? row.attempts : '—'}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_PASS_PERCENT', 'Pass percent')}>
                        {formatPassPercent(row.pass_percent)}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_STATUS', 'Status')}>
                        {row.status_label || row.status}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ACTIVE', 'Active')}>
                        <DashboardStatusSwitch
                          checked={row.is_active}
                          value={row.active}
                          onChange={() => toggleActive(row)}
                        />
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_DATE', 'Date')}>
                        {formatLegacyDateTime(row.created_at)}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ACTIONS', 'Actions')}>
                        <div className="actions-group">
                          <Link
                            to={dashboardPath('teacher', `quizzes/form/${row.id}`)}
                            className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                            title={lbl('LBL_EDIT', 'Edit')}
                          >
                            <DashboardSpriteIcon id="edit" className="icon icon--edit icon--small" />
                            <div className="tooltip tooltip--top bg-black">{lbl('LBL_EDIT', 'Edit')}</div>
                          </Link>
                          <button
                            type="button"
                            className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                            onClick={() => removeQuiz(row.id)}
                          >
                            <DashboardSpriteIcon id="trash" className="icon icon--issue icon--small" />
                            <div className="tooltip tooltip--top bg-black">{lbl('LBL_DELETE', 'Delete')}</div>
                          </button>
                        </div>
                      </DashboardFlexCell>
                    </td>
                  </tr>
                ))}
              </table>
            </div>
          )}
          <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
        </div>
      </div>
    </div>
  );
}
