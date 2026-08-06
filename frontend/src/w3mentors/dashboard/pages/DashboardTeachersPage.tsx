import { useEffect, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { dashboardApi, type DashboardTeacherContact } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { DashboardStandardPage } from '../components/DashboardStandardPage';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';

export function DashboardTeachersPage() {
  const role = useDashboardRole();
  const { lbl } = useSite();
  const [keyword, setKeyword] = useState('');
  const [teachers, setTeachers] = useState<DashboardTeacherContact[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (role !== 'learner') return;
    setLoading(true);
    dashboardApi
      .teachers({ keyword: keyword || undefined })
      .then((res) => setTeachers(res.data.data))
      .catch(() => setTeachers([]))
      .finally(() => setLoading(false));
  }, [keyword, role]);

  if (role !== 'learner') {
    return <Navigate to={dashboardPath('teacher')} replace />;
  }

  const toolbar = (
    <div className="mt-3">
      <input
        type="search"
        className="form-control"
        placeholder={lbl('LBL_KEYWORD', 'Keyword')}
        value={keyword}
        onChange={(e) => setKeyword(e.target.value)}
      />
    </div>
  );

  return (
    <DashboardStandardPage
      title={lbl('LBL_MY_TEACHERS', 'My Teachers')}
      toolbar={toolbar}
    >
      {loading && <p className="muted">{lbl('LBL_LOADING', 'Loading...')}</p>}
      {!loading && teachers.length === 0 && (
        <p className="muted">{lbl('LBL_NO_TEACHERS_FOUND', 'No teachers found')}</p>
      )}
      {!loading && teachers.length > 0 && (
        <div className="table-scroll">
          <table className="table table--styled">
            <thead>
              <tr>
                <th>{lbl('LBL_NAME', 'Name')}</th>
                <th>{lbl('LBL_LESSONS', 'Lessons')}</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {teachers.map((t) => (
                <tr key={t.id}>
                  <td>{t.full_name}</td>
                  <td>{t.lessons_count}</td>
                  <td>
                    <Link
                      to={t.username ? `/teachers/${t.username}` : `/teachers/${t.id}`}
                      className="btn btn--bordered btn--small"
                    >
                      {lbl('LBL_VIEW', 'View')}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </DashboardStandardPage>
  );
}
