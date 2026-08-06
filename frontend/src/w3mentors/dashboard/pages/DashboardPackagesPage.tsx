import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { DashboardAddIcon } from '../components/DashboardAddIcon';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardPageLayout } from '../DashboardPageLayout';
import { PackageCardLandscape, type PackageCardItem } from '../groupClasses/PackageCardLandscape';

type ListResponse = {
  data: PackageCardItem[];
  meta: Paginated<unknown>['meta'];
};

/** Legacy dashboard/views/packages/index.php + search.php */
export function DashboardPackagesPage() {
  const { lbl } = useSite();
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get('page') ?? 1);

  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [offline, setOffline] = useState('');
  const [items, setItems] = useState<PackageCardItem[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchListing = useCallback(
    (filters: { page: number; keyword: string; offline: string }) => {
      setLoading(true);
      const params: Record<string, string | number> = {
        page: filters.page,
        per_page: 20,
        status: 'all',
      };
      if (filters.keyword.trim()) params.keyword = filters.keyword.trim();
      if (filters.offline !== '') params.offline = filters.offline;

      return api
        .get<ListResponse>('/dashboard/packages', { params })
        .then((res) => {
          setItems(res.data.data ?? []);
          setMeta(res.data.meta);
        })
        .catch(() => {
          setItems([]);
          setMeta(null);
        })
        .finally(() => setLoading(false));
    },
    []
  );

  useEffect(() => {
    fetchListing({ page, keyword, offline });
  }, [fetchListing, page, keyword, offline]);

  const setPage = (p: number) => {
    const next = new URLSearchParams(searchParams);
    next.set('page', String(p));
    setSearchParams(next);
  };

  const clearSearch = () => {
    setKeyword('');
    setOffline('');
    const next = new URLSearchParams(searchParams);
    next.delete('page');
    setSearchParams(next);
    fetchListing({ page: 1, keyword: '', offline: '' });
  };

  return (
    <DashboardPageLayout
      title={lbl('LBL_MANAGE_CLASS_PACKAGES', 'Manage class packages')}
      actions={
        <>
          <a
            href="javascript:void(0)"
            className="btn btn--secondary slide-toggle-js"
            onClick={(e) => {
              e.preventDefault();
              setSearchOpen((v) => !v);
            }}
          >
            <DashboardSpriteIcon id="search" className="icon icon--search icon--small me-2" />
            {lbl('LBL_SEARCH', 'Search')}
          </a>
          <button type="button" className="btn color-secondary btn--bordered" disabled>
            <DashboardAddIcon />
            {lbl('LBL_ADD_PACKAGE', 'Add package')}
          </button>
        </>
      }
    >
      <div className="page-filter">
        <form
          className="form"
          id="frmPackageSearch"
          name="frmPackageSearch"
          onSubmit={(e) => {
            e.preventDefault();
            fetchListing({ page: 1, keyword, offline });
          }}
        >
          <div
            className="search-filter slide-target-js"
            style={searchOpen ? undefined : { display: 'none' }}
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
                        name="keyword"
                        className="form-control"
                        placeholder={lbl('LBL_KEYWORD', 'Keyword')}
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
                    <label className="field_label">{lbl('LBL_SERVICE_TYPE', 'Service type')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        name="service_type"
                        className="form-control"
                        value={offline}
                        onChange={(e) => setOffline(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        <option value="0">{lbl('LBL_ONLINE_SESSION', 'Online session')}</option>
                        <option value="1">{lbl('LBL_IN-PERSON_SESSION', 'In-person session')}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper form-buttons-group">
                    <div className="field_cover">
                      <input
                        type="submit"
                        className="btn btn--primary"
                        value={lbl('LBL_SEARCH', 'Search')}
                      />
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
          </div>
        </form>
      </div>

      <div className="page-content" id="listing">
        {loading ? (
          <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : items.length === 0 ? (
          <DashboardNoRecord
            headingKey="LBL_NO_CLASS_PACKAGE_FOUND"
            headingFallback="No class package found"
            action={
              <button type="button" className="btn btn--primary" disabled>
                {lbl('LBL_ADD_PACKAGE', 'Add package')}
              </button>
            }
          />
        ) : (
          <div className="results">
            <div className="-float-right">
              <div className="list-inline-item">
                <span className="box-hint badge--round bg-info m-0 -no-border">&nbsp;</span>
                {lbl('LBL_ONLINE_SESSION', 'Online session')}
              </div>
              <div className="list-inline-item">
                <span className="box-hint badge--round bg-yellow m-0 -no-border">&nbsp;</span>
                {lbl('LBL_IN-PERSON_SESSION', 'In-person session')}
              </div>
            </div>
            {items.map((item) => (
              <PackageCardLandscape key={item.grpcls_id ?? item.id} item={item} lbl={lbl} />
            ))}
          </div>
        )}
        <DashboardListingPagination
          meta={meta}
          page={page}
          loading={loading}
          onPageChange={setPage}
        />
      </div>
    </DashboardPageLayout>
  );
}
