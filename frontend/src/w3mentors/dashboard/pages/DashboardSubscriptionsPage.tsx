import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { dashboardApi, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';

type SubRow = {
  id: number;
  order_id: number;
  status: number;
  starts_at: string | null;
  ends_at: string | null;
  lesson_count: number;
  counterparty_name: string;
  counterparty_id: number;
};

function formatDate(value: string | null): string {
  if (!value) return '—';
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString();
}

export function DashboardSubscriptionsPage() {
  const { lbl } = useSite();
  const role = useDashboardRole();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [rows, setRows] = useState<SubRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const nameLabel = role === 'learner' ? lbl('LBL_TEACHER', 'Teacher') : lbl('LBL_LEARNER', 'Learner');

  const load = useCallback(() => {
    setLoading(true);
    dashboardApi
      .list('subscriptions', { page, keyword: keyword || undefined })
      .then((res) => {
        setRows(res.data.data as unknown as SubRow[]);
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

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    load();
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_RECURRING_LESSONS', 'Recurring lessons')}
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
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper form-buttons-group">
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
        <div className="page-content" id="listing">
          {loading ? (
            <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
          ) : rows.length === 0 ? (
            <DashboardNoRecord />
          ) : (
            <div className="table-scroll">
              <table className="table table--styled table--responsive table--aligned-middle">
                <tbody>
                  <tr className="title-row">
                    <th>{nameLabel}</th>
                    <th>{lbl('LBL_START_DATE', 'Start date')}</th>
                    <th>{lbl('LBL_END_DATE', 'End date')}</th>
                    <th>{lbl('LBL_LESSONS', 'Lessons')}</th>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                    <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                  </tr>
                  {rows.map((sub) => (
                    <tr key={sub.id}>
                      <td>
                        <DashboardFlexCell label={nameLabel}>
                          <div className="profile-meta">
                            <div className="profile-meta__media">
                              <span
                                className="avtar avtar--medium avtar--round"
                                data-title={firstChar(sub.counterparty_name)}
                              >
                                <img
                                  src={imageUrl(AFILE.USER_PROFILE, sub.counterparty_id, 'SMALL')}
                                  alt={sub.counterparty_name}
                                />
                              </span>
                            </div>
                            <div className="profile-meta__details">
                              <p className="bold-600 color-black m-0">{sub.counterparty_name}</p>
                            </div>
                          </div>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_START_DATE', 'Start date')}>
                          {formatDate(sub.starts_at)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_END_DATE', 'End date')}>
                          {formatDate(sub.ends_at)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_LESSONS', 'Lessons')}>
                          {sub.lesson_count}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_STATUS', 'Status')}>
                          {sub.status}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_ACTIONS', 'Actions')}>
                          <div className="actions-group">
                            <Link
                              to={`${dashboardPath(role, 'lessons')}?status=all&order_id=${sub.order_id}`}
                              className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                              title={lbl('LBL_VIEW_LESSONS', 'View lessons')}
                            >
                              <DashboardSpriteIcon id="view" className="icon icon--cancel icon--small" />
                              <div className="tooltip tooltip--top bg-black">
                                {lbl('LBL_VIEW_LESSONS', 'View lessons')}
                              </div>
                            </Link>
                          </div>
                        </DashboardFlexCell>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
        </div>
      </div>
    </div>
  );
}
