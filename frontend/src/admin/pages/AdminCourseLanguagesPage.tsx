import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminCourseLanguageModal } from '../components/AdminCourseLanguageModal';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';

type Row = {
  id: number;
  identifier: string;
  title: string;
  active: number;
};

export function AdminCourseLanguagesPage() {
  const { lbl } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [dragId, setDragId] = useState<number | null>(null);

  const canEdit = Boolean(privileges.canEditCourseLanguage);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('course-languages', {})
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;
    void adminApi.pageText('course-languages').then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_COURSE_LANGUAGES', 'Course languages'),
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
  }, [clearMeta, lbl, setMeta]);

  const onExport = () => {
    void adminApi.exportCourseLanguages().then((blob) => {
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'course-languages.csv';
      link.click();
      URL.revokeObjectURL(link.href);
    });
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    try {
      await adminApi.deleteCourseLanguage(id);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to delete')
          : 'Unable to delete';
      window.alert(message);
    }
  };

  const reorder = async (nextRows: Row[]) => {
    setRows(nextRows);
    await adminApi.updateCourseLanguageOrder(nextRows.map((row) => row.id));
    load();
  };

  const onDrop = (targetId: number) => {
    if (dragId === null || dragId === targetId) return;
    const fromIndex = rows.findIndex((row) => row.id === dragId);
    const toIndex = rows.findIndex((row) => row.id === targetId);
    if (fromIndex < 0 || toIndex < 0) return;
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
            <li className="breadcrumb-item">{lbl('LBL_COURSE_LANGUAGES', 'Course languages')}</li>
          </ul>
          <div className="action-toolbar">
            {canEdit ? (
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={(e) => {
                  e.preventDefault();
                  setEditId(0);
                  setModalOpen(true);
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
                <table className="table table--hovered" width="100%" id="courseLanguages">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_SRNO', 'Sr no')}</th>
                      <th>{lbl('LBL_COURSE_LANGUAGE_IDENTIFIER', 'Identifier')}</th>
                      <th>{lbl('LBL_COURSE_LANGUAGE_NAME', 'Name')}</th>
                      <th>{lbl('LBL_STATUS', 'Status')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={canEdit ? 6 : 4} className="text-center">
                          {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr
                          key={row.id}
                          id={String(row.id)}
                          onDragOver={(e) => {
                            if (canEdit && row.active === 1) e.preventDefault();
                          }}
                          onDrop={(e) => {
                            e.preventDefault();
                            if (canEdit && row.active === 1) onDrop(row.id);
                          }}
                        >
                          {canEdit ? (
                            <td className={row.active === 1 ? 'dragHandle' : undefined}>
                              {row.active === 1 ? (
                                <i
                                  className="ion-arrow-move icon"
                                  draggable
                                  onDragStart={() => setDragId(row.id)}
                                  onDragEnd={() => setDragId(null)}
                                />
                              ) : null}
                            </td>
                          ) : null}
                          <td>{index + 1}</td>
                          <td>{row.identifier}</td>
                          <td>{row.title}</td>
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.active === 1}
                              disabled={!canEdit}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              onToggle={async (next) => {
                                await adminApi.updateCourseLanguageStatus(row.id, next ? 1 : 0);
                                load();
                              }}
                            />
                          </td>
                          {canEdit ? (
                            <td className="align-right">
                              <ul className="actions">
                                <li>
                                  <a
                                    href="javascript:void(0)"
                                    title={lbl('LBL_EDIT', 'Edit')}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      setEditId(row.id);
                                      setModalOpen(true);
                                    }}
                                  >
                                    <AdminSpriteIcon icon="edit" />
                                  </a>
                                </li>
                                <li>
                                  <a
                                    href="javascript:void(0)"
                                    title={lbl('LBL_Delete', 'Delete')}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      void onDelete(row.id);
                                    }}
                                  >
                                    <AdminSpriteIcon icon="delete" />
                                  </a>
                                </li>
                              </ul>
                            </td>
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

      <AdminCourseLanguageModal
        open={modalOpen}
        clangId={editId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
