import { useCallback, useEffect, useState } from 'react';
import { api, dashboardApi, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';

type ResourceRow = {
  id: number;
  name: string;
  type: string;
  size: string;
  created_at: string;
  icon: string;
};

function formatDate(value: string): string {
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
}

export function DashboardCourseResourcesPage() {
  const { lbl } = useSite();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [rows, setRows] = useState<ResourceRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    const mapRow = (row: Record<string, unknown>): ResourceRow => ({
      id: Number(row.id),
      name: String(row.name ?? row.title ?? ''),
      type: String(row.type ?? ''),
      size: String(row.size ?? ''),
      created_at: String(row.created_at ?? ''),
      icon: String(row.icon ?? 'attach'),
    });

    api
      .get<{ data: ResourceRow[]; meta: Paginated<unknown>['meta'] }>(
        '/dashboard/teacher/resources/search',
        { params: { page, keyword: keyword || undefined } }
      )
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
      })
      .catch(() =>
        dashboardApi
          .list('resources', { page })
          .then((res) => {
            setRows((res.data.data as Record<string, unknown>[]).map(mapRow));
            setMeta(res.data.meta);
          })
          .catch(() => {
            setRows([]);
            setMeta(null);
          })
      )
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
        title={lbl('LBL_MANAGE_COURSE_RESOURCES', 'Manage course resources')}
        subtitle={lbl(
          'LBL_MANAGE_COURSE_RESOURCES_PAGE_SUB_HEADING',
          'Upload and manage files for your course lectures.'
        )}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          <button type="button" className="btn color-secondary btn--bordered" disabled title={lbl('LBL_BULK_UPLOADER', 'Bulk uploader')}>
            <DashboardSpriteIcon id="uploader" className="icon icon--uploader me-2" width={18} height={18} />
            {lbl('LBL_BULK_UPLOADER', 'Bulk uploader')}
          </button>
        }
        searchPanel={
          <form className="form" onSubmit={onSearch}>
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
                        placeholder={lbl('LBL_KEYWORD', 'Keyword')}
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
                    <div className="field_cover d-flex gap-2">
                      <button type="submit" className="btn btn--secondary">
                        {lbl('LBL_SEARCH', 'Search')}
                      </button>
                      <button
                        type="button"
                        className="btn btn--bordered color-secondary"
                        onClick={() => {
                          setKeyword('');
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
        <div className="page-content">
          <div className="table-scroll">
            {loading && <p className="color-secondary p-3">{lbl('LBL_LOADING', 'Loading...')}</p>}
            {!loading && rows.length === 0 && (
              <DashboardNoRecord labelKey="LBL_NO_RESOURCE_UPLOADED" labelFallback="No resources found" />
            )}
            {!loading && rows.length > 0 && (
              <table className="table table--styled table--responsive">
                <tbody>
                  <tr className="title-row">
                    <th>{lbl('LBL_FILENAME', 'File name')}</th>
                    <th>{lbl('LBL_TYPE', 'Type')}</th>
                    <th>{lbl('LBL_DATE', 'Date')}</th>
                    <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                  </tr>
                  {rows.map((resrc) => (
                    <tr key={resrc.id}>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_FILENAME', 'File name')}</div>
                          <div className="flex-cell__content">
                            <div className="file-attachment">
                              <div className="d-flex">
                                <div className="file-attachment__media d-none d-sm-flex">
                                  <svg className="attached-media">
                                    <use xlinkHref={`/dashboard/images/sprite.svg#${resrc.icon}`} />
                                  </svg>
                                </div>
                                <div className="file-attachment__content">
                                  <p className="mb-0 bold-600 color-black">{resrc.name}</p>
                                  <span className="m-0 style-italic me-3 color-gray-900">{resrc.size}</span>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_TYPE', 'Type')}</div>
                          <div className="flex-cell__content">{resrc.type.toUpperCase()}</div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_DATE', 'Date')}</div>
                          <div className="flex-cell__content">{formatDate(resrc.created_at)}</div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_ACTIONS', 'Actions')}</div>
                          <div className="flex-cell__content">
                            <div className="actions-group">
                              <button
                                type="button"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                disabled
                                title={lbl('LBL_DELETE', 'Delete')}
                              >
                                <DashboardSpriteIcon id="trash" className="icon icon--issue icon--small" width={16} height={16} />
                                <div className="tooltip tooltip--top bg-black">{lbl('LBL_DELETE', 'Delete')}</div>
                              </button>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
          <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
        </div>
      </div>
    </div>
  );
}
