import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi, type PaginatedMeta } from '../api/adminClient';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { legacyFileUrl } from '../utils/adminMedia';

type QualificationRow = {
  id: number;
  experience_type: string;
  experience_years: string;
  title: string;
  description: string;
  institute: string;
  file_id: number | null;
  file_name: string;
};

export function AdminTeacherRequestQualificationsPage() {
  const { userId: userIdParam } = useParams<{ userId: string }>();
  const userId = Number(userIdParam);
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<QualificationRow[]>([]);
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
    if (!userId || Number.isNaN(userId)) {
      setError('Invalid user');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    adminApi
      .teacherRequestQualifications(userId, page)
      .then((res) => {
        setRows(res.data.data as unknown as QualificationRow[]);
        setPagination(res.data.meta);
      })
      .catch((e: unknown) => {
        setError(
          (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load qualifications',
        );
        setRows([]);
      })
      .finally(() => setLoading(false));
  }, [page, userId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({
      title: lbl('LBL_QUALIFICATIONS', 'Qualifications'),
    });
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
              <Link to="/admin/teacher-requests">{lbl('LBL_TEACHER_REQUESTS', 'Teacher requests')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_QUALIFICATIONS', 'Qualifications')}</li>
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
                <table className="table" width="100%">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_SRNO', 'Sr no')}</th>
                      <th>{lbl('LBL_TYPE', 'Type')}</th>
                      <th>{lbl('LBL_TITLE', 'Title')}</th>
                      <th>{lbl('LBL_UPLOADED_CERTIFICATE', 'Uploaded certificate')}</th>
                      <th>{lbl('LBL_DESCRIPTION', 'Description')}</th>
                      <th>{lbl('LBL_INSTITUTE', 'Institute')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={6}>{lbl('LBL_NO_RECORDS_FOUND', 'No records found')}</td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(page - 1) * perPage + index + 1}</td>
                          <td>
                            {row.experience_type}
                            {row.experience_years ? (
                              <>
                                <br />
                                {row.experience_years}
                              </>
                            ) : null}
                          </td>
                          <td>{row.title}</td>
                          <td>
                            {row.file_id ? (
                              <a
                                className="link-text"
                                href={legacyFileUrl(row.file_id)}
                                target="_blank"
                                rel="noreferrer"
                              >
                                <div className="attachment-file">
                                  <div className="inline-icon -display-inline -color-fill">
                                    <span className="svg-icon">
                                      <img src="/manager/views/images/attach.svg" alt="" />
                                    </span>
                                  </div>
                                  {row.file_name}
                                </div>
                              </a>
                            ) : null}
                          </td>
                          <td style={{ whiteSpace: 'pre-wrap' }}>{row.description}</td>
                          <td style={{ whiteSpace: 'pre-wrap' }}>{row.institute}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>

        {!loading ? (
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
        ) : null}
      </div>
    </main>
  );
}
