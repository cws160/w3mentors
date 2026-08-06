import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { DashboardPageLayout } from '../DashboardPageLayout';
import { LessonCardLandscape, type LessonCardItem } from '../lessons/LessonCardLandscape';
import { LESSON_STATUS } from '../lessonStatus';

type LessonGroup = { key: string; lessons: LessonCardItem[] };

type LessonsResponse = {
  data: LessonCardItem[];
  groups: LessonGroup[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

const VIEW_LISTING = 1;
const VIEW_CALENDAR = 2;

function formatGroupDate(key: string): string {
  if (/^\d{4}-\d{2}-\d{2}$/.test(key)) {
    return new Date(`${key}T12:00:00`).toLocaleDateString(undefined, {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  }

  return key;
}

export function DashboardLessonsPage() {
  const { lbl } = useSite();
  const [searchParams, setSearchParams] = useSearchParams();

  const statusParam = searchParams.get('status');
  const status =
    statusParam === 'all'
      ? LESSON_STATUS.ALL
      : statusParam !== null
        ? Number(statusParam)
        : LESSON_STATUS.SCHEDULED;
  const view = Number(searchParams.get('view') ?? VIEW_LISTING);
  const page = Number(searchParams.get('page') ?? 1);

  const [groups, setGroups] = useState<LessonGroup[]>([]);
  const [meta, setMeta] = useState<LessonsResponse['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [keyword, setKeyword] = useState('');
  const [searchOpen, setSearchOpen] = useState(false);

  const load = useCallback(() => {
    setLoading(true);
    const params: Record<string, string | number> = {
      page,
      per_page: 20,
    };
    if (status >= 0 && status !== LESSON_STATUS.ALL) {
      params.status = status;
    }
    if (keyword.trim()) {
      params.keyword = keyword.trim();
    }

    api
      .get<LessonsResponse>('/lessons', { params })
      .then((res) => {
        setGroups(res.data.groups ?? []);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setGroups([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [status, page, keyword]);

  useEffect(() => {
    load();
  }, [load]);

  const tabs = [
    { value: LESSON_STATUS.SCHEDULED, label: lbl('LBL_SCHEDULED', 'Scheduled') },
    { value: LESSON_STATUS.UNSCHEDULED, label: lbl('LBL_UNSCHEDULED', 'Unscheduled') },
    { value: LESSON_STATUS.COMPLETED, label: lbl('LBL_COMPLETED', 'Completed') },
    { value: LESSON_STATUS.CANCELLED, label: lbl('LBL_CANCELLED', 'Canceled') },
    { value: LESSON_STATUS.ALL, label: lbl('LBL_ALL', 'All') },
  ];

  const setStatus = (value: number) => {
    const next = new URLSearchParams(searchParams);
    if (value === LESSON_STATUS.ALL) {
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

  return (
    <DashboardPageLayout
      title={lbl('LBL_MANAGE_LESSONS', 'Manage lessons')}
      actions={
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
      }
    >
      <div id="upcomingLesson" />
      <div className="page-filter">
        <form
          className="form"
          id="frmLessonSearch"
          onSubmit={(e) => {
            e.preventDefault();
            load();
          }}
        >
          <div className="switch-controls">
            <div className="switch-controls__colum-left">
              <div className="switch-ui">
                <ul>
                  {tabs.map((tab) => (
                    <li key={tab.value}>
                      <label className="switch-ui__item">
                        <input
                          type="radio"
                          className="switch-ui__input"
                          name="ordles_status"
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
          <div className={`search-filter slide-target-js${searchOpen ? '' : ' d-none d-md-block'}`}>
            <div className="row">
              <div className="col-lg-4">
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
              <div className="col-lg-4 col-sm-6 form-buttons-group">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper">
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
                          const next = new URLSearchParams(searchParams);
                          next.delete('page');
                          setSearchParams(next);
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
        {view === VIEW_CALENDAR ? (
          <p className="color-secondary padding-6">
            {lbl(
              'LBL_CALENDAR_VIEW_LEGACY',
              'Calendar view is available in the legacy dashboard. Switch to Listing to manage lessons here.'
            )}
          </p>
        ) : loading ? (
          <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : groups.length === 0 ? (
          <p className="color-secondary padding-6">
            {lbl('LBL_NO_LESSONS_FOUND', 'No lessons found')}
          </p>
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
                <time className="date uppercase small bold-600">
                  {formatGroupDate(group.key)}
                </time>
                {group.lessons.map((lesson) => (
                  <LessonCardLandscape key={lesson.id} lesson={lesson} lbl={lbl} />
                ))}
              </div>
            ))}
            {meta && meta.last_page > 1 && (
              <div className="pagination mt-4 d-flex gap-2">
                <button
                  type="button"
                  className="btn btn--secondary btn--small"
                  disabled={meta.current_page <= 1}
                  onClick={() => {
                    const next = new URLSearchParams(searchParams);
                    next.set('page', String(meta.current_page - 1));
                    setSearchParams(next);
                  }}
                >
                  {lbl('LBL_PREV', 'Prev')}
                </button>
                <span className="small align-self-center">
                  {meta.current_page} / {meta.last_page}
                </span>
                <button
                  type="button"
                  className="btn btn--secondary btn--small"
                  disabled={meta.current_page >= meta.last_page}
                  onClick={() => {
                    const next = new URLSearchParams(searchParams);
                    next.set('page', String(meta.current_page + 1));
                    setSearchParams(next);
                  }}
                >
                  {lbl('LBL_NEXT', 'Next')}
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </DashboardPageLayout>
  );
}
