import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi, type PaginatedMeta } from '../api/adminClient';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

type LearnerRow = {
  id: number;
  full_name: string;
  email: string;
};

export function AdminGroupClassLearnersPage() {
  const { classId: classIdParam } = useParams<{ classId: string }>();
  const classId = Number(classIdParam);
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<LearnerRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState<PaginatedMeta>({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  const load = useCallback(() => {
    if (!classId || Number.isNaN(classId)) {
      setError('Invalid class');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    adminApi
      .groupClassLearners(classId, page)
      .then((res) => {
        setRows(res.data.data as unknown as LearnerRow[]);
        setPagination(res.data.meta);
      })
      .catch((e: unknown) => {
        setError(
          (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load learners',
        );
        setRows([]);
      })
      .finally(() => setLoading(false));
  }, [classId, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({ title: lbl('LBL_View_Learners', 'View learners') });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  const perPage = pagination.per_page || 10;

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/group-classes">{lbl('LBL_GROUP_CLASSES', 'Group classes')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_View_Learners', 'View learners')}</li>
          </ul>
        </div>

        {error ? <div className="alert alert-danger">{error}</div> : null}

        <div className="card">
          <div className="card-table">
            <div className="table-responsive">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table table--hovered" width="100%">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_SRNO', 'Sr.No')}</th>
                      <th>{lbl('LBL_Full_Name', 'Full name')}</th>
                      <th>{lbl('LBL_Email', 'Email')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={3} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(page - 1) * perPage + index + 1}</td>
                          <td>{row.full_name}</td>
                          <td>{row.email}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
              <AdminLegacyPagination
                page={page}
                lastPage={pagination.last_page}
                perPage={perPage}
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
