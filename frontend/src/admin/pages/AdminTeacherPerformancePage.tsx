import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

type Row = {
  id: number;
  user_id: number;
  teacher_name: string;
  testat_lessons: number;
  testat_classes: number;
  testat_courses: number;
  testat_students: number;
  testat_reviewes: number;
  testat_ratings: string;
};

const searchConfig: AdminModuleConfig = {
  module: 'teacher-performance',
  pageLangKey: 'teacher-performance',
  titleKey: 'LBL_TEACHER_PERFORMANCE',
  titleFallback: 'Teacher performance',
  searchSubmitCol: 3,
  searchFields: [
    { name: 'keyword', labelKey: 'LBL_USER', labelFallback: 'User', type: 'text' },
  ],
  columns: [],
};

export function AdminTeacherPerformancePage() {
  const { lbl, modules } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const showClasses = Boolean(modules.group_classes);
  const showCourses = Boolean(modules.courses);

  const disabledInfo = useMemo(() => {
    if (!showCourses && !showClasses) {
      return lbl(
        'LBL_INFO_FOR_DISABLED_COURSE_CLASSES_STATS',
        'This section includes courses and classes data too.',
      );
    }
    if (!showCourses) {
      return lbl('LBL_INFO_FOR_DISABLED_COURSES_STATS', 'This section includes courses data too.');
    }
    if (!showClasses) {
      return lbl('LBL_INFO_FOR_DISABLED_CLASSES_STATS', 'This section includes classes data too.');
    }
    return '';
  }, [lbl, showClasses, showCourses]);

  const columns = useMemo(() => {
    const cols = [
      { key: 'teacher_name', label: lbl('LBL_TEACHER', 'Teacher') },
      { key: 'testat_lessons', label: lbl('LBL_LESSONS', 'Lessons') },
    ];
    if (showClasses) {
      cols.push({ key: 'testat_classes', label: lbl('LBL_CLASSES', 'Classes') });
    }
    if (showCourses) {
      cols.push({ key: 'testat_courses', label: lbl('LBL_COURSES', 'Courses') });
    }
    cols.push(
      { key: 'testat_students', label: lbl('LBL_STUDENTS', 'Students') },
      { key: 'testat_reviewes', label: lbl('LBL_REVIEWES', 'Reviews') },
      { key: 'testat_ratings', label: lbl('LBL_RATINGS', 'Average rating') },
    );
    return cols;
  }, [lbl, showClasses, showCourses]);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('teacher-performance', { page, ...filters })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [filters, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    void adminApi.pageText('teacher-performance').then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_TEACHER_PERFORMANCE', 'Teacher performance'),
        summary: pageText.summary,
        warning: pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });

    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    setFilters({ ...draft });
    setPage(1);
  };

  const onClear = () => {
    setDraft({});
    setFilters({});
    setPage(1);
  };

  const onExport = () => {
    const headers = columns.map((col) => col.label);
    const lines = rows.map((row) =>
      columns
        .map((col) => {
          const value = row[col.key as keyof Row];
          if (col.key === 'teacher_name') {
            return `"${String(value).replace(/"/g, '""')}"`;
          }
          return String(value ?? '');
        })
        .join(','),
    );
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'teacher-performance.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const colSpan = 1 + columns.length;

  return (
    <main className="main">
      <div className="container">
        {disabledInfo ? (
          <div className="page-alert">
            <div className="alert alert--info">
              <span>{disabledInfo}</span>
            </div>
          </div>
        ) : null}

        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_TEACHER_PERFORMANCE', 'Teacher performance')}</li>
          </ul>
          <div className="action-toolbar">
            <a href="javascript:void(0)" className="btn btn-primary" onClick={onExport}>
              {lbl('LBL_EXPORT', 'Export')}
            </a>
          </div>
        </div>

        <div className="card">
          <div
            className={`card-head js--filter-trigger${filterOpen ? ' active' : ''}`}
            onClick={() => setFilterOpen((v) => !v)}
          >
            <h4>{lbl('LBL_Search...', 'Search...')}</h4>
          </div>
          <div className="card-body js--filter-target" style={{ display: filterOpen ? 'block' : 'none' }}>
            <AdminModuleSearchForm
              config={searchConfig}
              draft={draft}
              lbl={lbl}
              onDraftChange={setDraft}
              onSearch={onSearch}
              onClear={onClear}
            />
          </div>
        </div>

        <div className="card">
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table table--hovered" width="100%">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_SRNO', 'Sr no')}</th>
                      {columns.map((col) => (
                        <th key={col.key}>{col.label}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={colSpan} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(page - 1) * pagination.per_page + index + 1}</td>
                          {columns.map((col) => (
                            <td key={col.key}>{row[col.key as keyof Row]}</td>
                          ))}
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
              <AdminLegacyPagination
                page={page}
                lastPage={pagination.last_page}
                perPage={pagination.per_page}
                total={pagination.total}
                onPageChange={setPage}
                labels={{
                  showing: lbl('LBL_Showing', 'Showing'),
                  to: lbl('LBL_to', 'to'),
                  of: lbl('LBL_of', 'of'),
                  entries: lbl('LBL_Entries', 'Entries'),
                }}
              />
            </div>
          </div>
        </div>
      </div>
    </main>
  );
}
