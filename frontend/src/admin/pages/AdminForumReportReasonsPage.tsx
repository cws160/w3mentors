import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminForumReportReasonModal } from '../components/AdminForumReportReasonModal';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';

type Row = {
  id: number;
  identifier: string;
  title: string;
  active: number;
};

export function AdminForumReportReasonsPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [dragId, setDragId] = useState<number | null>(null);

  const canEdit = Boolean(privileges.canEditDiscussionForum) || admin?.id === 1;

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('forum-report-issue-reasons', { lang_id: langId })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [langId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({ title: lbl('LBL_Report_Reasons', 'Report reasons') });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  const confirms = adminLegacyConfirms(lbl);

  const reorder = async (nextRows: Row[]) => {
    setRows(nextRows);
    try {
      await adminApi.updateForumReportReasonOrder(nextRows.map((row) => row.id));
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
            <li className="breadcrumb-item">{lbl('LBL_Report_Reasons', 'Report reasons')}</li>
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
                <table className="table table--hovered table-dragable" width="100%" id="ForumReportIssueReasons">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_srNo', 'Sr. No.')}</th>
                      <th>{lbl('LBL_IDENTIFIER', 'Identifier')}</th>
                      <th>{lbl('LBL_title', 'Title')}</th>
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
                            if (canEdit && row.active === 1) {
                              e.preventDefault();
                            }
                          }}
                          onDrop={(e) => {
                            e.preventDefault();
                            if (canEdit && row.active === 1) {
                              onDrop(row.id);
                            }
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
                              confirmMessage={confirms.updateStatus}
                              onToggle={async (next) => {
                                await adminApi.updateForumReportReasonStatus(row.id, next);
                                load();
                              }}
                            />
                          </td>
                          <AdminRowActionsCell
                            actions={
                              canEdit
                                ? [
                                    {
                                      icon: 'edit',
                                      title: lbl('LBL_EDIT', 'Edit'),
                                      onClick: () => {
                                        setEditId(row.id);
                                        setModalOpen(true);
                                      },
                                    },
                                  ]
                                : []
                            }
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

      <AdminForumReportReasonModal
        open={modalOpen}
        reasonId={editId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
