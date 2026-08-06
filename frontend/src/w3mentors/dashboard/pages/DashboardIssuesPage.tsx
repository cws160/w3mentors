import { useCallback, useEffect, useState } from 'react';
import { dashboardApi, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';
import { useDashboardRole } from '../DashboardShell';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import {
  ISSUE_STATUS,
  formatLegacyDateTime,
  issueStatusLabel,
  sessionStatusLabel,
} from '../issues/issueStatus';

type IssueRow = {
  id: number;
  title: string;
  status: number;
  status_label?: string;
  record_type: number;
  language: string;
  session_time: string | null;
  session_status: number | null;
  session_status_label?: string;
  counterparty_id: number;
  counterparty_name: string;
  counterparty_country?: string;
  can_resolve?: boolean;
};

/** Legacy dashboard/views/issues/index.php + search.php */
export function DashboardIssuesPage() {
  const { lbl } = useSite();
  const role = useDashboardRole();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [classType, setClassType] = useState('');
  const [status, setStatus] = useState('');
  const [rows, setRows] = useState<IssueRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const userLabel = role === 'learner' ? lbl('LBL_TEACHER', 'Teacher') : lbl('LBL_LEARNER', 'Learner');

  const load = useCallback(() => {
    setLoading(true);
    dashboardApi
      .list('issues', {
        page,
        keyword: keyword || undefined,
        class_type: classType || undefined,
        status: status || undefined,
      })
      .then((res) => {
        setRows(res.data.data as unknown as IssueRow[]);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [page, keyword, classType, status]);

  useEffect(() => {
    load();
  }, [load]);

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    load();
  };

  const clearSearch = () => {
    setKeyword('');
    setClassType('');
    setStatus('');
    setPage(1);
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_REPORTED_ISSUES', 'Reported issues')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        searchPanel={
          <form className="form form--small" onSubmit={onSearch}>
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
                        placeholder={lbl('LBL_Search_By_Keyword', 'Search by keyword')}
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
                    <label className="field_label">{lbl('LBL_CLASS_TYPE', 'Class type')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={classType}
                        onChange={(e) => setClassType(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        <option value="1">{lbl('LBL_LESSON', 'Lesson')}</option>
                        <option value="2">{lbl('LBL_GROUP_CLASS', 'Group class')}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_ISSUE_STATUS', 'Issue status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        <option value={ISSUE_STATUS.PROGRESS}>
                          {lbl('STATUS_PROGRESS', 'In progress')}
                        </option>
                        <option value={ISSUE_STATUS.RESOLVED}>
                          {lbl('STATUS_RESOLVED', 'Resolved')}
                        </option>
                        <option value={ISSUE_STATUS.ESCALATED}>
                          {lbl('STATUS_ESCALATED', 'Escalated')}
                        </option>
                        <option value={ISSUE_STATUS.CLOSED}>
                          {lbl('STATUS_CLOSED', 'Closed')}
                        </option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper form-buttons-group">
                    <div className="field_cover">
                      <input type="submit" className="btn btn--primary" value={lbl('LBL_SEARCH', 'Search')} />
                      <input
                        type="button"
                        className="btn btn--secondary ms-2"
                        value={lbl('LBL_CLEAR', 'Clear')}
                        onClick={clearSearch}
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
        <div className="page-content" id="listItems">
          {loading ? (
            <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
          ) : rows.length === 0 ? (
            <DashboardNoRecord />
          ) : (
            <div className="table-scroll">
              <table className="table table--styled table--responsive table--aligned-middle">
                <tr className="title-row">
                  <th>{userLabel}</th>
                  <th>{lbl('LBL_LANGUAGE', 'Language')}</th>
                  <th>{lbl('LBL_SESSION_TIME', 'Session time')}</th>
                  <th>{lbl('LBL_SESSION_STATUS', 'Session status')}</th>
                  <th>{lbl('LBL_ISSUE_TITLE', 'Issue title')}</th>
                  <th>{lbl('LBL_ISSUE_STATUS', 'Issue status')}</th>
                  <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                </tr>
                {rows.map((issue) => (
                  <tr key={issue.id}>
                    <td>
                      <DashboardFlexCell label={userLabel}>
                        <div className="profile-meta">
                          <div className="profile-meta__media">
                            <span
                              className="avtar avtar--medium avtar--round"
                              data-title={firstChar(issue.counterparty_name)}
                            >
                              <img
                                src={imageUrl(AFILE.USER_PROFILE, issue.counterparty_id, 'SMALL')}
                                alt={issue.counterparty_name}
                              />
                            </span>
                          </div>
                          <div className="profile-meta__details">
                            <p className="color-black m-0">
                              {issue.counterparty_name}
                              {issue.counterparty_country ? (
                                <>
                                  <br />
                                  <small>{issue.counterparty_country}</small>
                                </>
                              ) : null}
                            </p>
                          </div>
                        </div>
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_LANGUAGE', 'Language')}>
                        {issue.language || lbl('LBL_NA', 'N/A')}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_SESSION_TIME', 'Session time')}>
                        {formatLegacyDateTime(issue.session_time)}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_SESSION_STATUS', 'Session status')}>
                        <div className="data-group">
                          <span>
                            {issue.session_status_label ??
                              sessionStatusLabel(issue.record_type, issue.session_status, lbl)}
                          </span>
                        </div>
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ISSUE_TITLE', 'Issue title')}>
                        {issue.title}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ISSUE_STATUS', 'Issue status')}>
                        <span className="badge color-secondary badge--curve">
                          {issue.status_label ?? issueStatusLabel(issue.status, lbl)}
                        </span>
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ACTIONS', 'Actions')}>
                        <div className="actions-group">
                          <button
                            type="button"
                            className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                            disabled
                            title={lbl('LBL_VIEW_DETAIL', 'View detail')}
                          >
                            <DashboardSpriteIcon id="view" className="icon icon--issue icon--small" />
                            <div className="tooltip tooltip--top bg-black">
                              {lbl('LBL_VIEW_DETAIL', 'View detail')}
                            </div>
                          </button>
                          {issue.can_resolve && (
                            <button
                              type="button"
                              className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                              disabled
                              title={lbl('LBL_RESOLVE_ISSUE', 'Resolve issue')}
                            >
                              <DashboardSpriteIcon
                                id="resolve-issue"
                                className="icon icon--issue icon--small"
                              />
                              <div className="tooltip tooltip--top bg-black">
                                {lbl('LBL_RESOLVE_ISSUE', 'Resolve issue')}
                              </div>
                            </button>
                          )}
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
