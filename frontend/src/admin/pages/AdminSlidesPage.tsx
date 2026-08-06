import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminEditDeleteActions } from '../components/AdminRowActions';
import { AdminSlideModal } from '../components/AdminSlideModal';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type SlideRow = {
  id: number;
  title: string;
  identifier: string;
  active: number;
  display_order: number;
};

export function AdminSlidesPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<SlideRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [draggedId, setDraggedId] = useState<number | null>(null);

  const canEdit = Boolean(privileges.canEditSlides) || admin?.id === 1;
  const confirms = adminLegacyConfirms(lbl);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('slides', { lang_id: langId, per_page: 50 })
      .then((res) => setRows((res.data.data ?? []) as SlideRow[]))
      .finally(() => setLoading(false));
  }, [langId]);

  useEffect(() => load(), [load]);

  useEffect(() => {
    let cancelled = false;
    void adminApi.pageText('slides', langId).then((res) => {
      if (cancelled) return;
      const text = res.data.data ?? {};
      setMeta({
        title: text.title || lbl('LBL_HOMEPAGE_SLIDES', 'Home Page Slides'),
        summary: text.summary,
        warning: text.warning,
        recommendations: text.recommendations,
        helpingText: text.helping_text,
        plangId: text.plang_id,
      });
    });
    return () => {
      cancelled = true;
      clearMeta();
    };
  }, [clearMeta, langId, lbl, setMeta]);

  const openForm = (slideId: number) => {
    setEditId(slideId);
    setModalOpen(true);
  };

  const deleteSlide = async (slideId: number) => {
    if (!window.confirm(confirms.delete)) return;
    await adminApi.deleteSlide(slideId);
    load();
  };

  const dropOn = async (targetId: number) => {
    if (draggedId === null || draggedId === targetId) return;
    const reordered = [...rows];
    const from = reordered.findIndex((row) => row.id === draggedId);
    const to = reordered.findIndex((row) => row.id === targetId);
    if (from < 0 || to < 0) return;
    const [moved] = reordered.splice(from, 1);
    reordered.splice(to, 0, moved);
    setRows(reordered);
    setDraggedId(null);
    await adminApi.updateSlidesOrder(reordered.map((row) => row.id));
  };

  return (
    <main className="main admin-slides-page">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_BANNERS', 'Banners')}</li>
          </ul>
          {canEdit ? (
            <div className="action-toolbar">
              <a href="javascript:void(0)" className="btn btn-primary" onClick={() => openForm(0)}>
                {lbl('LBL_ADD_NEW', 'Add new')}
              </a>
            </div>
          ) : null}
        </div>

        <div className="card">
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table table--hovered" id="slideList" width="100%">
                  <thead>
                    <tr>
                      {canEdit ? <th width="5%"><i className="ion-arrow-move icon" /></th> : null}
                      <th>{lbl('LBL_SRNO', 'Sr no')}</th>
                      <th>{lbl('LBL_Title', 'Title')}</th>
                      <th>{lbl('LBL_Status', 'Status')}</th>
                      {canEdit ? <th>{lbl('LBL_Action', 'Action')}</th> : null}
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
                          onDragOver={(event) => canEdit && event.preventDefault()}
                          onDrop={() => void dropOn(row.id)}
                        >
                          {canEdit ? (
                            <td
                              className={row.active === 1 ? 'dragHandle' : undefined}
                              draggable={row.active === 1}
                              onDragStart={() => setDraggedId(row.id)}
                            >
                              {row.active === 1 ? <i className="ion-arrow-move icon" /> : null}
                            </td>
                          ) : null}
                          <td>{index + 1}</td>
                          <td>{row.identifier}</td>
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.active === 1}
                              disabled={!canEdit}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              confirmMessage={confirms.updateStatus}
                              onToggle={async (next) => {
                                await adminApi.updateSlideStatus(row.id, next);
                                load();
                              }}
                            />
                          </td>
                          {canEdit ? (
                            <AdminEditDeleteActions
                              canEdit
                              className=""
                              editLabel={lbl('LBL_EDIT', 'Edit')}
                              deleteLabel={lbl('LBL_Delete', 'Delete')}
                              onEdit={() => openForm(row.id)}
                              onDelete={() => void deleteSlide(row.id)}
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

      <AdminSlideModal
        open={modalOpen}
        slideId={editId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
