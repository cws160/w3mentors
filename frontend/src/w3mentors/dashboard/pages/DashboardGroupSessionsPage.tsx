import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useModal } from '../../context/ModalContext';
import { useSite } from '../../context/SiteContext';
import { DashboardAddIcon } from '../components/DashboardAddIcon';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardPageLayout } from '../DashboardPageLayout';
import { GroupClassCardLandscape, type GroupClassCardItem } from '../groupClasses/GroupClassCardLandscape';
import { GRPCLS_STATUS } from '../groupClasses/groupClassStatus';
import { AttachQuizzesModal } from '../quizzes/AttachQuizzesModal';
import { ATTACH_QUIZ_RECORD } from '../quizzes/attachQuizConstants';

type Group = { key: string; classes: GroupClassCardItem[] };

type ListResponse = {
  data: GroupClassCardItem[];
  groups: Group[];
  meta: Paginated<unknown>['meta'];
};

type Mode = 'classes' | 'packages';

const VIEW_LISTING = 1;
const VIEW_CALENDAR = 2;

const CONFIG: Record<
  Mode,
  {
    endpoint: string;
    titleKey: string;
    titleFallback: string;
    addKey: string;
    addFallback: string;
    emptyKey: string;
    emptyFallback: string;
  }
> = {
  classes: {
    endpoint: 'classes',
    titleKey: 'LBL_MANAGE_CLASSES',
    titleFallback: 'Manage classes',
    addKey: 'LBL_ADD_CLASS',
    addFallback: 'Add class',
    emptyKey: 'LBL_NO_GROUP_CLASS',
    emptyFallback: 'No group class',
  },
  packages: {
    endpoint: 'packages',
    titleKey: 'LBL_MANAGE_CLASS_PACKAGES',
    titleFallback: 'Manage class packages',
    addKey: 'LBL_ADD_PACKAGE',
    addFallback: 'Add package',
    emptyKey: 'LBL_NO_CLASS_PACKAGE_FOUND',
    emptyFallback: 'No class package found',
  },
};

function formatGroupDate(key: string): string {
  if (/^\d{4}-\d{2}-\d{2}$/.test(key)) {
    return new Date(`${key}T12:00:00`)
      .toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
      })
      .toUpperCase();
  }
  return key;
}

type Props = { mode: Mode };

export function DashboardGroupSessionsPage({ mode }: Props) {
  const { lbl } = useSite();
  const { showModal, closeModal } = useModal();
  const cfg = CONFIG[mode];
  const [searchParams, setSearchParams] = useSearchParams();

  const isClasses = mode === 'classes';
  const statusParam = searchParams.get('status');
  const status =
    statusParam === 'all'
      ? GRPCLS_STATUS.ALL
      : statusParam !== null
        ? Number(statusParam)
        : GRPCLS_STATUS.SCHEDULED;
  const view = Number(searchParams.get('view') ?? VIEW_LISTING);
  const page = Number(searchParams.get('page') ?? 1);

  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [offline, setOffline] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [groups, setGroups] = useState<Group[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchListing = useCallback(
    (filters: {
      page: number;
      keyword: string;
      offline: string;
      startDate: string;
      endDate: string;
    }) => {
      setLoading(true);
      const params: Record<string, string | number> = { page: filters.page, per_page: 20 };
      if (filters.keyword.trim()) params.keyword = filters.keyword.trim();
      if (isClasses) {
        if (status === GRPCLS_STATUS.ALL) {
          params.status = 'all';
        } else if (status >= 0) {
          params.status = status;
        }
      }
      if (filters.offline !== '') params.offline = filters.offline;
      if (filters.startDate) params.start_date = filters.startDate;
      if (filters.endDate) params.end_date = filters.endDate;

      return api
        .get<ListResponse>(`/dashboard/${cfg.endpoint}`, { params })
        .then((res) => {
          setGroups(res.data.groups ?? []);
          setMeta(res.data.meta);
        })
        .catch(() => {
          setGroups([]);
          setMeta(null);
        })
        .finally(() => setLoading(false));
    },
    [cfg.endpoint, status, isClasses]
  );

  const load = useCallback(() => {
    fetchListing({ page, keyword, offline, startDate, endDate });
  }, [fetchListing, page, keyword, offline, startDate, endDate]);

  useEffect(() => {
    load();
  }, [load]);

  const statusTabs = isClasses
    ? [
        { value: GRPCLS_STATUS.ALL, label: lbl('LBL_ALL_CLASSSES', 'All classes') },
        { value: GRPCLS_STATUS.SCHEDULED, label: lbl('LBL_SCHEDULED', 'Scheduled') },
        { value: GRPCLS_STATUS.COMPLETED, label: lbl('LBL_COMPLETED', 'Completed') },
        { value: GRPCLS_STATUS.CANCELLED, label: lbl('LBL_CANCELLED', 'Canceled') },
      ]
    : [];

  const setStatus = (value: number) => {
    const next = new URLSearchParams(searchParams);
    if (value === GRPCLS_STATUS.ALL) {
      next.set('status', 'all');
    } else {
      next.set('status', String(value));
    }
    next.delete('page');
    setSearchParams(next);
  };

  const setView = (nextView: number) => {
    const next = new URLSearchParams(searchParams);
    next.set('view', String(nextView));
    setSearchParams(next);
  };

  const setPage = (p: number) => {
    const next = new URLSearchParams(searchParams);
    next.set('page', String(p));
    setSearchParams(next);
  };

  const openAttachQuizzes = (grpclsId: number) => {
    showModal(
      <AttachQuizzesModal
        recordId={grpclsId}
        recordType={ATTACH_QUIZ_RECORD.GCLASS}
        onClose={closeModal}
        onAttached={load}
      />,
      { size: 'modal-xl' }
    );
  };

  return (
    <DashboardPageLayout
      title={lbl(cfg.titleKey, cfg.titleFallback)}
      actions={
        <>
          <a
            href="javascript:void(0);"
            className="btn btn--secondary slide-toggle-js d-flex d-sm-none"
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
            {lbl(cfg.addKey, cfg.addFallback)}
          </button>
        </>
      }
    >
      <div className="page-filter">
        <form
          className="form"
          id="frmClassSearch"
          onSubmit={(e) => {
            e.preventDefault();
            load();
          }}
        >
          {isClasses && (
            <div className="switch-controls">
              <div className="switch-controls__colum-left">
                <div className="switch-ui">
                  <ul>
                    {statusTabs.map((tab) => (
                      <li key={tab.value}>
                        <label className="switch-ui__item">
                          <input
                            type="radio"
                            className="switch-ui__input"
                            name="grpcls_status"
                            checked={status === tab.value}
                            onChange={() => setStatus(tab.value)}
                          />
                          <span className="switch-ui__label">{tab.label}</span>
                        </label>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
              <div className="switch-controls__colum-right">
                <div className="switch-ui switch-ui--icons">
                  <ul>
                    <li>
                      <label className="switch-ui__item">
                        <input
                          type="radio"
                          className="switch-ui__input"
                          name="view"
                          checked={view === VIEW_LISTING}
                          onChange={() => setView(VIEW_LISTING)}
                        />
                        <span className="switch-ui__label">
                          <DashboardSpriteIcon
                            id="lesson-view"
                            className="icon icon--view icon--small me-2"
                          />
                          {lbl('LBL_LISTING', 'Listing')}
                        </span>
                      </label>
                    </li>
                    <li>
                      <label className="switch-ui__item">
                        <input
                          type="radio"
                          className="switch-ui__input"
                          name="view"
                          checked={view === VIEW_CALENDAR}
                          onChange={() => setView(VIEW_CALENDAR)}
                        />
                        <span className="switch-ui__label">
                          <DashboardSpriteIcon id="calendar" className="icon icon--calendar me-1" />
                          {lbl('LBL_CALENDAR', 'Calendar')}
                        </span>
                      </label>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          )}

          <div
            className={`search-filter slide-target-js${searchOpen ? '' : isClasses ? ' d-none d-md-block' : ''}`}
            style={!isClasses && !searchOpen ? { display: 'none' } : undefined}
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
                    <label className="field_label">
                      {isClasses
                        ? lbl('LBL_SERVICE_TYPE', 'Service type')
                        : lbl('LBL_SERVICE_TYPE', 'Service type')}
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        name="grpcls_offline"
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
              {isClasses && (
                <>
                  <div className="col-lg-4 col-sm-6">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">{lbl('LBL_CLASS_STARTDATE', 'Class start date')}</label>
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <input
                            type="date"
                            name="grpcls_start_datetime"
                            id="start_datetime"
                            className="form-control"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="col-lg-4 col-sm-6">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">{lbl('LBL_CLASS_ENDDATE', 'Class end date')}</label>
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <input
                            type="date"
                            name="grpcls_end_datetime"
                            id="end_datetime"
                            className="form-control"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </>
              )}
              <div className="col-lg-4 col-sm-6">
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
                        onClick={() => {
                          setKeyword('');
                          setOffline('');
                          setStartDate('');
                          setEndDate('');
                          const next = new URLSearchParams(searchParams);
                          next.delete('page');
                          setSearchParams(next);
                          fetchListing({
                            page: 1,
                            keyword: '',
                            offline: '',
                            startDate: '',
                            endDate: '',
                          });
                        }}
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
        {view === VIEW_CALENDAR && isClasses ? (
          <p className="color-secondary padding-6">
            {lbl(
              'LBL_CALENDAR_VIEW_LEGACY',
              'Calendar view is available in the legacy dashboard. Switch to Listing to manage classes here.'
            )}
          </p>
        ) : loading ? (
          <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : groups.length === 0 ? (
          <DashboardNoRecord headingKey={cfg.emptyKey} headingFallback={cfg.emptyFallback} />
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
            {groups.map((group) => (
              <div className="lessons-group mt-5" key={group.key}>
                <time className="date uppercase small bold-600">{formatGroupDate(group.key)}</time>
                {group.classes.map((item) => (
                  <GroupClassCardLandscape
                    key={item.id}
                    item={item}
                    lbl={lbl}
                    onAttachQuiz={isClasses ? openAttachQuizzes : undefined}
                  />
                ))}
              </div>
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
