import { useCallback, useEffect, useState } from 'react';
import { api, dashboardApi, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';

type EditRequestRow = {
  id: number;
  course_title: string;
  status: number;
  status_label: string;
  created_at: string;
  expired_at: string | null;
};

type StatusFilter = { id: number; label: string };

function formatDate(value: string | null): string {
  if (!value) {
    return '—';
  }
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
}

export function DashboardCourseEditRequestsPage() {
  const { lbl } = useSite();
  const [searchOpen, setSearchOpen] = useState(true);
  const [keyword, setKeyword] = useState('');
  const [status, setStatus] = useState('');
  const [statuses, setStatuses] = useState<StatusFilter[]>([]);
  const [rows, setRows] = useState<EditRequestRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<{ data: EditRequestRow[]; meta: Paginated<unknown>['meta'] }>(
        '/dashboard/teacher/course-edit-requests/search',
        { params: { page, keyword: keyword || undefined, status: status !== '' ? status : undefined } }
      )
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
      })
      .catch(() =>
        dashboardApi
          .list('course-edit-requests', { page })
          .then((res) => {
            setRows(
              (res.data.data as Record<string, unknown>[]).map((row) => ({
                id: Number(row.id),
                course_title: String(row.course_title ?? ''),
                status: Number(row.status ?? 0),
                status_label: String(row.status ?? ''),
                created_at: String(row.created_at ?? ''),
                expired_at: null,
              }))
            );
            setMeta(res.data.meta);
          })
          .catch(() => {
            setRows([]);
            setMeta(null);
          })
      )
      .finally(() => setLoading(false));
  }, [page, keyword, status]);

  useEffect(() => {
    api
      .get<{ data: { statuses: StatusFilter[] } }>('/dashboard/teacher/course-edit-requests/filters')
      .then((res) => setStatuses(res.data.data.statuses))
      .catch(() => undefined);
  }, []);

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
        title={lbl('LBL_EDIT_REQUESTS', 'Edit requests')}
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
                    <label className="field_label">{lbl('LBL_REQUEST_STATUS', 'Request status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                        <option value="">{lbl('LBL_ALL', 'All')}</option>
                        {statuses.map((s) => (
                          <option key={s.id} value={s.id}>
                            {s.label}
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
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper form-buttons-group">
                    <div className="field_cover d-flex gap-2">
                      <button type="submit" className="btn btn--secondary">
                        {lbl('LBL_SEARCH', 'Search')}
                      </button>
                      <button
                        type="button"
                        className="btn btn--bordered color-secondary"
                        onClick={() => {
                          setKeyword('');
                          setStatus('');
                          setPage(1);
                        }}
                      >
                        {lbl('LBL_CLEAR', 'Clear')}
                      </button>
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
          {loading && <p className="color-secondary p-3">{lbl('LBL_LOADING', 'Loading...')}</p>}
          {!loading && rows.length === 0 && (
            <DashboardNoRecord labelKey="LBL_NO_RECORD_FOUND" labelFallback="No record found" />
          )}
          {!loading && rows.length > 0 && (
            <div className="table-scroll">
              <table className="table table--styled table--responsive table--aligned-middle">
                <tbody>
                  <tr className="title-row">
                    <th>{lbl('LBL_COURSE_TITLE', 'Course title')}</th>
                    <th>{lbl('LBL_CREATED_DATE', 'Created date')}</th>
                    <th>{lbl('LBL_EXPIRY_DATE', 'Expiry date')}</th>
                    <th>{lbl('LBL_REQUEST_STATUS', 'Request status')}</th>
                    <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                  </tr>
                  {rows.map((row) => (
                    <tr key={row.id}>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_COURSE_TITLE', 'Course title')}</div>
                          <div className="flex-cell__content">
                            <p className="bold-600 color-black m-0">{row.course_title}</p>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_CREATED_DATE', 'Created date')}</div>
                          <div className="flex-cell__content">{formatDate(row.created_at)}</div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_EXPIRY_DATE', 'Expiry date')}</div>
                          <div className="flex-cell__content">{formatDate(row.expired_at)}</div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_REQUEST_STATUS', 'Request status')}</div>
                          <div className="flex-cell__content">{row.status_label}</div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_ACTIONS', 'Actions')}</div>
                          <div className="flex-cell__content">
                            <button
                              type="button"
                              className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                              disabled
                              title={lbl('LBL_VIEW_REQUEST', 'View request')}
                            >
                              <DashboardSpriteIcon id="view" className="icon icon--cancel icon--small" width={16} height={16} />
                              <div className="tooltip tooltip--top bg-black">
                                {lbl('LBL_VIEW_REQUEST', 'View request')}
                              </div>
                            </button>
                          </div>
                        </div>
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
