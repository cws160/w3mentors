import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { api, dashboardApi, type Paginated } from '../../../api/client';
import { courseToTeacherRow } from '../utils/teacherCourseRows';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { DashboardCourseCard, type TeacherCourseRow } from '../components/DashboardCourseCard';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { useDashboardRole } from '../DashboardShell';

type CourseFilters = {
  categories: { id: number; name: string }[];
  sub_categories: { id: number; parent_id: number; name: string }[];
  statuses: { id: number; label: string }[];
  types: { id: number; label: string }[];
};

const emptyFilters: CourseFilters = {
  categories: [],
  sub_categories: [],
  statuses: [],
  types: [],
};

export function DashboardCoursesPage() {
  const role = useDashboardRole();
  const { lbl } = useSite();
  const [searchOpen, setSearchOpen] = useState(false);
  const [filters, setFilters] = useState<CourseFilters>(emptyFilters);
  const [courses, setCourses] = useState<TeacherCourseRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  const [keyword, setKeyword] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [subCategoryId, setSubCategoryId] = useState('');
  const [status, setStatus] = useState('');
  const [courseType, setCourseType] = useState('');

  const subCategories = useMemo(
    () =>
      categoryId
        ? filters.sub_categories.filter((s) => String(s.parent_id) === categoryId)
        : filters.sub_categories,
    [filters.sub_categories, categoryId]
  );

  const load = useCallback(() => {
    setLoading(true);
    const params = {
      page,
      keyword: keyword || undefined,
      course_cateid: categoryId || undefined,
      course_subcateid: subCategoryId || undefined,
      course_status: status !== '' ? status : undefined,
      course_type: courseType !== '' ? courseType : undefined,
    };

    const applyRows = (data: TeacherCourseRow[], metaValue: Paginated<unknown>['meta'] | null) => {
      setCourses(data);
      setMeta(metaValue);
    };

    api
      .get<{ data: TeacherCourseRow[]; meta: Paginated<unknown>['meta'] }>('/dashboard/courses', { params })
      .then((res) => applyRows(res.data.data, res.data.meta))
      .catch(() =>
        dashboardApi
          .courses({ page })
          .then((res) =>
            applyRows(
              res.data.data.map((row) => courseToTeacherRow(row as never)),
              res.data.meta
            )
          )
          .catch(() => applyRows([], null))
      )
      .finally(() => setLoading(false));
  }, [page, keyword, categoryId, subCategoryId, status, courseType]);

  useEffect(() => {
    if (role !== 'teacher') return;
    api
      .get<{ data: CourseFilters }>('/dashboard/courses/filters')
      .then((res) => setFilters(res.data.data))
      .catch(() => undefined);
  }, [role]);

  useEffect(() => {
    if (role === 'teacher') {
      load();
    }
  }, [role, load]);

  if (role === 'learner') {
    return <Navigate to="/my/courses" replace />;
  }

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    load();
  };

  const clearSearch = () => {
    setKeyword('');
    setCategoryId('');
    setSubCategoryId('');
    setStatus('');
    setCourseType('');
    setPage(1);
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_MANAGE_COURSES', 'Manage courses')}
        subtitle={lbl(
          'LBL_MANAGE_COURSE_PAGE_SUB_HEADING',
          'Create, update, and publish your courses.'
        )}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          <Link to="/courses" className="btn color-secondary btn--bordered">
            <DashboardSpriteIcon id="uploader" className="icon icon--uploader me-2" width={18} height={18} />
            {lbl('LBL_ADD_NEW_COURSE', 'Add new course')}
          </Link>
        }
        searchPanel={
          <form className="form" onSubmit={onSearch}>
            <div className="row">
              <div className="col-md-4">
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
                        placeholder={lbl('LBL_SEARCH_BY_COURSE_TITLE', 'Search by course title')}
                        onChange={(e) => setKeyword(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_CATEGORY', 'Category')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={categoryId}
                        onChange={(e) => {
                          setCategoryId(e.target.value);
                          setSubCategoryId('');
                        }}
                      >
                        <option value="">{lbl('LBL_ALL', 'All')}</option>
                        {filters.categories.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_SUBCATEGORY', 'Subcategory')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={subCategoryId}
                        onChange={(e) => setSubCategoryId(e.target.value)}
                      >
                        <option value="">{lbl('LBL_ALL', 'All')}</option>
                        {subCategories.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_TYPE', 'Type')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={courseType}
                        onChange={(e) => setCourseType(e.target.value)}
                      >
                        <option value="">{lbl('LBL_ALL', 'All')}</option>
                        {filters.types.map((t) => (
                          <option key={t.id} value={t.id}>
                            {t.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_STATUS', 'Status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                      >
                        <option value="">{lbl('LBL_ALL', 'All')}</option>
                        {filters.statuses.map((s) => (
                          <option key={s.id} value={s.id}>
                            {s.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 form-buttons-group">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover d-flex gap-2">
                      <button type="submit" className="btn btn--secondary">
                        {lbl('LBL_SEARCH', 'Search')}
                      </button>
                      <button type="button" className="btn btn--bordered color-secondary" onClick={clearSearch}>
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
          {loading && <p className="color-secondary p-3">{lbl('LBL_LOADING', 'Loading...')}</p>}
          {!loading && courses.length === 0 && <DashboardNoRecord labelKey="LBL_NO_COURSES_FOUND" labelFallback="No courses found" />}
          {!loading && courses.length > 0 && (
            <div className="course-group">
              {courses.map((course) => (
                <DashboardCourseCard key={course.id} course={course} />
              ))}
            </div>
          )}
          <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
        </div>
      </div>
    </div>
  );
}
