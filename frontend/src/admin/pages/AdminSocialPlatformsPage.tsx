import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { AdminSocialPlatformModal } from '../components/AdminSocialPlatformModal';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type Row = {
  id: number;
  identifier: string;
  url: string;
  active: number;
};

export function AdminSocialPlatformsPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin, loading: authLoading } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);

  const canView = Boolean(privileges.canViewSocialPlatforms) || admin?.id === 1;
  const canEdit = Boolean(privileges.canEditSocialPlatforms) || admin?.id === 1;
  const confirms = adminLegacyConfirms(lbl);
  const columnCount = 4 + (canEdit ? 1 : 0);

  const load = useCallback(() => {
    if (!canView) {
      setRows([]);
      setLoading(false);
      setLoadError(lbl('LBL_UNAUTHORIZED_ACCESS', 'Unauthorized access'));
      return;
    }

    setLoading(true);
    setLoadError('');
    void adminApi
      .moduleList('social-platform', { lang_id: langId })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
      })
      .catch((err: unknown) => {
        setRows([]);
        const message =
          (err as { response?: { data?: { message?: string }; status?: number } })?.response?.data
            ?.message ??
          (err as { response?: { status?: number } })?.response?.status === 403
            ? lbl('LBL_UNAUTHORIZED_ACCESS', 'Unauthorized access')
            : lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
        setLoadError(message);
      })
      .finally(() => setLoading(false));
  }, [canView, langId, lbl]);

  useEffect(() => {
    if (!authLoading) {
      load();
    }
  }, [authLoading, load]);

  useEffect(() => {
    let cancelled = false;
    setMeta({ title: lbl('LBL_SOCIAL_PLATFORMS', 'Social platforms') });
    void adminApi.pageText('social-platform', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_SOCIAL_PLATFORMS', 'Social platforms'),
        summary: pageText.summary,
        warning: pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });
    return () => {
      cancelled = true;
      clearMeta();
    };
  }, [clearMeta, langId, lbl, setMeta]);

  const onStatusToggle = async (row: Row, next: boolean) => {
    try {
      await adminApi.updateSocialPlatformStatus(row.id, next);
      load();
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      window.alert(message);
    }
  };

  if (authLoading) {
    return (
      <main className="main">
        <div className="container">
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        </div>
      </main>
    );
  }

  if (!canView) {
    return (
      <main className="main">
        <div className="container">
          <div className="alert alert-danger">
            {lbl('LBL_UNAUTHORIZED_ACCESS', 'Unauthorized access')}
          </div>
        </div>
      </main>
    );
  }

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_SOCIAL_PLATFORMS', 'Social platforms')}</li>
          </ul>
        </div>

        {loadError ? <div className="alert alert-danger">{loadError}</div> : null}

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
                      <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                      <th>{lbl('LBL_Title', 'Title')}</th>
                      <th>{lbl('LBL_URL', 'URL')}</th>
                      <th>{lbl('LBL_Status', 'Status')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={columnCount} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{index + 1}</td>
                          <td>{row.identifier}</td>
                          <td>{row.url || '—'}</td>
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.active === 1}
                              disabled={!canEdit}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              confirmMessage={confirms.updateStatus}
                              onToggle={(next) => onStatusToggle(row, next)}
                            />
                          </td>
                          {canEdit ? (
                            <AdminRowActionsCell
                              actions={[
                                {
                                  icon: 'edit',
                                  title: lbl('LBL_EDIT', 'Edit'),
                                  onClick: () => {
                                    setEditId(row.id);
                                    setModalOpen(true);
                                  },
                                },
                              ]}
                            />
                          ) : null}
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>
      </div>

      <AdminSocialPlatformModal
        open={modalOpen}
        platformId={editId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
