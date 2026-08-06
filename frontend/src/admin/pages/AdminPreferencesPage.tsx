import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminPreferenceModal } from '../components/AdminPreferenceModal';
import { AdminEditDeleteActions } from '../components/AdminRowActions';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';
import { getPreferenceTypeMeta } from '../config/adminTeacherPreferenceModules';

type Row = {
  id: number;
  prefer_id: number;
  identifier: string;
  title: string;
};

export function AdminPreferencesPage() {
  const { typeId = '1' } = useParams();
  const preferType = Number(typeId) || 1;
  const typeMeta = getPreferenceTypeMeta(String(preferType));
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalId, setModalId] = useState<number | null>(null);
  const [dragId, setDragId] = useState<number | null>(null);

  const canEdit = Boolean(privileges.canEditPreferences) || admin?.id === 1;

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('preferences', { type: String(preferType), lang_id: langId })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [langId, preferType]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({
      title: lbl(typeMeta.titleKey, typeMeta.titleFallback),
    });

    let cancelled = false;
    void adminApi.pageText('preferences').then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: lbl(typeMeta.titleKey, typeMeta.titleFallback),
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
  }, [clearMeta, lbl, setMeta, typeMeta.titleFallback, typeMeta.titleKey]);

  const onExport = () => {
    const header = [
      lbl('LBL_PREFERENCE_IDENTIFIER', 'Preference identifier'),
      lbl('LBL_PREFERENCE_TITLE', 'Preference title'),
    ].join(',');
    const lines = rows.map((row) =>
      [`"${row.identifier.replace(/"/g, '""')}"`, `"${row.title.replace(/"/g, '""')}"`].join(','),
    );
    const csv = [header, ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${typeMeta.titleFallback.toLowerCase().replace(/\s+/g, '-')}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const confirms = adminLegacyConfirms(lbl);

  const onDelete = async (id: number) => {
    if (!window.confirm(confirms.delete)) {
      return;
    }
    try {
      await adminApi.deletePreference(id);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String(
              (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                'Unable to delete',
            )
          : 'Unable to delete';
      window.alert(message);
    }
  };

  const reorder = async (nextRows: Row[]) => {
    setRows(nextRows);
    try {
      await adminApi.updatePreferenceOrder(nextRows.map((row) => row.id));
      load();
    } catch {
      load();
    }
  };

  const onDrop = (targetId: number) => {
    if (dragId === null || dragId === targetId) {
      return;
    }
    const fromIndex = rows.findIndex((row) => row.id === dragId);
    const toIndex = rows.findIndex((row) => row.id === targetId);
    if (fromIndex < 0 || toIndex < 0) {
      return;
    }
    const next = [...rows];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);
    setDragId(null);
    void reorder(next);
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
              {lbl(typeMeta.titleKey, typeMeta.titleFallback)}
            </li>
          </ul>
          <div className="action-toolbar">
            {canEdit ? (
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={(e) => {
                  e.preventDefault();
                  setModalId(0);
                }}
              >
                {lbl('LBL_ADD_NEW', 'Add new')}
              </a>
            ) : null}
            <a href="javascript:void(0)" className="btn btn-primary" onClick={onExport}>
              {lbl('LBL_EXPORT', 'Export')}
            </a>
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
                <table className="table table--hovered table-dragable" width="100%" id="preferences">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_SRNO', 'Sr. No.')}</th>
                      <th>{lbl('LBL_PREFERENCE_IDENTIFIER', 'Preference identifier')}</th>
                      <th>{lbl('LBL_PREFERENCE_TITLE', 'Preference title')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={canEdit ? 5 : 3} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr
                          key={row.id}
                          id={String(row.id)}
                          onDragOver={(e) => {
                            if (canEdit) {
                              e.preventDefault();
                            }
                          }}
                          onDrop={(e) => {
                            e.preventDefault();
                            if (canEdit) {
                              onDrop(row.id);
                            }
                          }}
                        >
                          {canEdit ? (
                            <td className="dragHandle">
                              <i
                                className="ion-arrow-move icon"
                                draggable
                                onDragStart={() => setDragId(row.id)}
                                onDragEnd={() => setDragId(null)}
                              />
                            </td>
                          ) : null}
                          <td>{index + 1}</td>
                          <td>{row.identifier}</td>
                          <td>{row.title}</td>
                          <AdminEditDeleteActions
                            canEdit={canEdit}
                            editLabel={lbl('LBL_EDIT', 'Edit')}
                            deleteLabel={lbl('LBL_Delete', 'Delete')}
                            onEdit={() => setModalId(row.id)}
                            onDelete={() => void onDelete(row.id)}
                          />
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

      <AdminPreferenceModal
        preferId={modalId}
        preferType={preferType}
        onClose={() => setModalId(null)}
        onSaved={load}
      />
    </main>
  );
}
