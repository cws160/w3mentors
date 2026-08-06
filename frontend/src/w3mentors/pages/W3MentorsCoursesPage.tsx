import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { coursesApi, type Course, type CourseFilters } from '../../api/client';
import { useSite } from '../context/SiteContext';
import {
  CoursesFilterSidebar,
  type CourseFilterState,
} from '../components/CoursesFilterSidebar';
import { W3MentorsCourseCard } from '../components/W3MentorsCourseCard';
import { W3MentorsPagination } from '../components/W3MentorsPagination';
import { SpriteIcon } from '../components/SpriteIcon';

const emptyFilters = (): CourseFilterState => ({
  categories: [],
  levels: [],
  languages: [],
  ratings: null,
  priceFrom: '',
  priceTill: '',
});

export function W3MentorsCoursesPage() {
  const { lbl } = useSite();
  const [searchParams] = useSearchParams();
  const [filtersMeta, setFiltersMeta] = useState<CourseFilters | null>(null);
  const [filterState, setFilterState] = useState<CourseFilterState>(() => {
    const catg = searchParams.get('catg');
    return catg ? { ...emptyFilters(), categories: [Number(catg)] } : emptyFilters();
  });
  const [keyword, setKeyword] = useState(() => searchParams.get('search') ?? '');

  useEffect(() => {
    const q = searchParams.get('search');
    if (q !== null) {
      setKeyword(q);
      setPage(1);
    }
  }, [searchParams]);
  const [sort, setSort] = useState('newest');
  const [sortOpen, setSortOpen] = useState(false);
  const [filterOpen, setFilterOpen] = useState(false);
  const [courses, setCourses] = useState<Course[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    coursesApi.filters().then((res) => setFiltersMeta(res.data)).catch(() => setFiltersMeta(null));
  }, []);

  const sortOptions = useMemo(
    () => filtersMeta?.sort_options ?? {},
    [filtersMeta]
  );

  useEffect(() => {
    setLoading(true);
    coursesApi
      .list({
        search: keyword || undefined,
        sort,
        page,
        category: filterState.categories.length ? filterState.categories : undefined,
        level: filterState.levels.length ? filterState.levels : undefined,
        language: filterState.languages.length ? filterState.languages : undefined,
        ratings: filterState.ratings ?? undefined,
        price_from: filterState.priceFrom ? Number(filterState.priceFrom) : undefined,
        price_till: filterState.priceTill ? Number(filterState.priceTill) : undefined,
      })
      .then((res) => {
        setCourses(res.data.data);
        setTotal(res.data.meta.total);
        setLastPage(res.data.meta.last_page);
      })
      .catch(() => setCourses([]))
      .finally(() => setLoading(false));
  }, [keyword, sort, page, filterState]);

  useEffect(() => {
    document.body.classList.toggle('is-filter-show', filterOpen);
    return () => document.body.classList.remove('is-filter-show');
  }, [filterOpen]);

  const recordLabel = lbl(
    'LBL_FOUND_THE_BEST_{recordcount}_ONLINE_COURSES_FOR_YOU',
    'Found the best {recordcount} online courses for you'
  ).replace('{recordcount}', String(total));

  const clearFilters = () => {
    setFilterState(emptyFilters());
    setPage(1);
  };

  return (
    <>
      <section className="section bg-gradiant section--page-header text-center">
        <div className="container container--xl">
          <h2>{lbl('LBL_COURSE_LISTING_HEADING', 'Browse courses')}</h2>
          <div className="main-search">
            <div className="main-search__field">
              <input
                type="text"
                name="keyword"
                className="keyword-field-js"
                placeholder={lbl('LBL_BY_COURSE_NAME,_TEACHER_NAME,_TAGS', 'Search courses')}
                value={keyword}
                onChange={(e) => {
                  setKeyword(e.target.value);
                  setPage(1);
                }}
              />
            </div>
            <div className="main-search__action">
              <button type="button" className="main-search__submit" title="Search">
                <SpriteIcon id="search" className="icon icon--search" />
              </button>
              {keyword && (
                <div
                  className="main-search__reset"
                  title="Reset"
                  onClick={() => setKeyword('')}
                  role="button"
                  tabIndex={0}
                >
                  <span className="close" />
                </div>
              )}
            </div>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container container--lg">
          <div className="page">
            <div className="page-header mb-xl-5 mb-4">
              <div className="row g-3 justify-content-between align-items-center">
                <div className="col-auto">
                  <div className="search-result">
                    <h3 className="record-count-header">{recordLabel}</h3>
                  </div>
                </div>
                <div className="col-auto">
                  <div className="sorting-options">
                    <div className="sorting-options__item">
                      <div className="sorting-action">
                        <button
                          type="button"
                          className="sorting-action__trigger sort-trigger-js switch-filter"
                          onClick={() => setSortOpen((v) => !v)}
                        >
                          <span className="sorting-action__label">{lbl('LBL_SORT', 'Sort')}:</span>
                          <span className="sorting-action__value">{sortOptions[sort] ?? sort}</span>
                        </button>
                        {sortOpen && (
                          <div className="sorting-action__target sort-target-js">
                            <div className="filter-dropdown">
                              <div className="select-list select-list--vertical select-list--scroll">
                                <ul>
                                  {Object.entries(sortOptions).map(([id, name]) => (
                                    <li key={id}>
                                      <label className="select-option">
                                        <input
                                          className="select-option__input"
                                          type="radio"
                                          name="sorts"
                                          checked={sort === id}
                                          onChange={() => {
                                            setSort(id);
                                            setSortOpen(false);
                                            setPage(1);
                                          }}
                                        />
                                        <span className="select-option__item">{name}</span>
                                      </label>
                                    </li>
                                  ))}
                                </ul>
                              </div>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                    <div className="sorting-options__item">
                      <button type="button" className="btn--filters" onClick={() => setFilterOpen(true)}>
                        <span className="svg-icon">
                          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 402.577 402.577">
                            <path d="M400.858,11.427c-3.241-7.421-8.85-11.132-16.854-11.136H18.564c-7.993,0-13.61,3.715-16.846,11.136c-3.234,7.801-1.903,14.467,3.999,19.985l140.757,140.753v138.755c0,4.955,1.809,9.232,5.424,12.854l73.085,73.083c3.429,3.614,7.71,5.428,12.851,5.428c2.282,0,4.66-0.479,7.135-1.43c7.426-3.238,11.14-8.851,11.14-16.845V172.166L396.861,31.413C402.765,25.895,404.093,19.231,400.858,11.427z" />
                          </svg>
                        </span>
                        {lbl('LBL_FILTERS', 'Filters')}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="page-body">
              <div className="page-panel">
                <div className={`page-panel__small scrolling ${filterOpen ? 'is-filter-visible' : ''}`} id="STICKY">
                  {filtersMeta && (
                    <CoursesFilterSidebar
                      filters={filtersMeta}
                      state={filterState}
                      onChange={(next) => {
                        setFilterState(next);
                        setPage(1);
                      }}
                      onClear={clearFilters}
                      lbl={lbl}
                    />
                  )}
                </div>
                <div className="page-panel__large">
                  <div className="page-listing" id="listing">
                    {loading ? (
                      <p className="text-center">{lbl('LBL_Loading', 'Loading...')}</p>
                    ) : courses.length ? (
                      <>
                        <div className="course-results">
                          {courses.map((course) => (
                            <W3MentorsCourseCard key={course.id} course={course} lbl={lbl} />
                          ))}
                        </div>
                        <W3MentorsPagination current={page} last={lastPage} onPage={setPage} />
                      </>
                    ) : (
                      <div className="page-listing__body">
                        <div className="box -padding-30" style={{ marginBottom: 30 }}>
                          <div className="message-display">
                            <h5>{lbl('LBL_NO_COURSE_FOUND!', 'No courses found')}</h5>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
