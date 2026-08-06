import { useCallback, useEffect, useState } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

type HistoryRow = {
  user_id: number;
  is_global: boolean;
  teacher_name: string;
  comm_lessons: string;
  comm_classes: string;
  comm_courses: string;
  created_at: string;
};

export function AdminCommissionHistoryPage() {
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const { userId: userIdParam } = useParams<{ userId: string }>();
  const [searchParams, setSearchParams] = useSearchParams();
  const userId = Number(userIdParam ?? 0);
  const page = Number(searchParams.get('page') ?? 1);

  const [loading, setLoading] = useState(true);
  const [rows, setRows] = useState<HistoryRow[]>([]);
  const [meta, setListMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    classes_enabled: true,
    courses_enabled: true,
  });

  const columnCount =
    3 + (meta.classes_enabled ? 1 : 0) + (meta.courses_enabled ? 1 : 0) + 1;

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .commissionHistory(userId, page)
      .then((res) => {
        setRows((res.data.data ?? []) as HistoryRow[]);
        setListMeta({
          current_page: Number(res.data.meta?.current_page ?? 1),
          per_page: Number(res.data.meta?.per_page ?? 10),
          total: Number(res.data.meta?.total ?? 0),
          last_page: Number(res.data.meta?.last_page ?? 1),
          classes_enabled: Boolean(res.data.meta?.classes_enabled ?? true),
          courses_enabled: Boolean(res.data.meta?.courses_enabled ?? true),
        });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [page, userId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({ title: lbl('LBL_COMMISSION_HISTORY', 'Commission history') });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) {
      return lbl('LBL_NA', 'NA');
    }
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const teacherLabel = (row: HistoryRow) => {
    if (row.is_global) {
      return (
        <span className="label label-success">{lbl('LBL_GLOBAL_COMMISSION', 'Global commission')}</span>
      );
    }
    return row.teacher_name || lbl('LBL_NA', 'NA');
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/commission">{lbl('LBL_Commission', 'Commission')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_COMMISSION_HISTORY', 'Commission history')}</li>
          </ul>
        </div>

        <div className="card">
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table" width="100%">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                      <th>{lbl('LBL_USER', 'User')}</th>
                      <th>{lbl('LBL_LESSON_FEES_[%]', 'Lesson fees [%]')}</th>
                      {meta.classes_enabled ? (
                        <th>{lbl('LBL_CLASS_FEES_[%]', 'Class fees [%]')}</th>
                      ) : null}
                      {meta.courses_enabled ? (
                        <th>{lbl('LBL_COURSE_FEES_[%]', 'Course fees [%]')}</th>
                      ) : null}
                      <th>{lbl('LBL_ADDED_ON', 'Added on')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={columnCount} className="text-center">
                          {lbl('LBL_NO_RECORD_FOUND', 'No record found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={`${row.created_at}-${index}`}>
                          <td>{(page - 1) * meta.per_page + index + 1}</td>
                          <td>{teacherLabel(row)}</td>
                          <td>{row.comm_lessons}</td>
                          {meta.classes_enabled ? <td>{row.comm_classes}</td> : null}
                          {meta.courses_enabled ? <td>{row.comm_courses}</td> : null}
                          <td>{formatDate(row.created_at)}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>

        {!loading && meta.last_page > 1 ? (
          <AdminLegacyPagination
            page={meta.current_page}
            pageCount={meta.last_page}
            recordCount={meta.total}
            pageSize={meta.per_page}
            onPageChange={(nextPage) => {
              const next = new URLSearchParams(searchParams);
              next.set('page', String(nextPage));
              setSearchParams(next);
            }}
          />
        ) : null}
      </div>
    </main>
  );
}
