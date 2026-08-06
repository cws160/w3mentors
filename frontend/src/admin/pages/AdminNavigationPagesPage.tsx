import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';
import { AdminNavigationLinkModal } from '../components/AdminNavigationLinkModal';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

type NavigationLinkRow = {
  id: number;
  navigation_id: number;
  identifier: string;
  caption: string;
  active: number;
  can_update_status: boolean;
};

export function AdminNavigationPagesPage() {
  const { navigationId } = useParams();
  const navId = Number(navigationId ?? 0);
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<NavigationLinkRow[]>([]);
  const [title, setTitle] = useState(lbl('LBL_NAVIGATION_PAGES', 'Navigation Pages'));
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [linkModalOpen, setLinkModalOpen] = useState(false);
  const [linkEditId, setLinkEditId] = useState(0);
  const [dragId, setDragId] = useState<number | null>(null);
  const canEdit = Boolean(privileges.canEditNavigationManagement) || admin?.id === 1;

  useEffect(() => {
    setMeta({
      title: lbl('LBL_NAVIGATION_PAGES', 'Navigation Pages'),
      summary: lbl(
        'LBL_ADD_AND_MANAGE_NAVIGATION_PAGES',
        'Add and manage the Quick links, Header navigation and source pages to be displayed under a navigation on the front-end.',
      ),
      recommendations: lbl(
        'LBL_NAVIGATION_PAGES_ORDER_HELP',
        'Rearrange the list of navigation pages using the drag and drop icon button. The order of pages is updated accordingly on the front-end.',
      ),
    });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  useEffect(() => {
    if (navId < 1) {
      setError(lbl('LBL_INVALID_REQUEST', 'Invalid request'));
      setLoading(false);
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .navigationPages(navId, langId || 1)
      .then((res) => {
        const data = res.data.data;
        setTitle(String(data.navigation?.title ?? lbl('LBL_NAVIGATION_PAGES', 'Navigation Pages')));
        setRows(
          (data.links ?? []).map((row) => ({
            id: Number(row.id ?? 0),
            navigation_id: Number(row.navigation_id ?? navId),
            identifier: String(row.identifier ?? ''),
            caption: String(row.caption ?? ''),
            active: Number(row.active ?? 1),
            can_update_status: Boolean(row.can_update_status),
          })),
        );
      })
      .catch((err: unknown) => {
        setRows([]);
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
        );
      })
      .finally(() => setLoading(false));
  }, [langId, lbl, navId]);

  const reload = async () => {
    const res = await adminApi.navigationPages(navId, langId || 1);
    const data = res.data.data;
    setTitle(String(data.navigation?.title ?? lbl('LBL_NAVIGATION_PAGES', 'Navigation Pages')));
    setRows(
      (data.links ?? []).map((row) => ({
        id: Number(row.id ?? 0),
        navigation_id: Number(row.navigation_id ?? navId),
        identifier: String(row.identifier ?? ''),
        caption: String(row.caption ?? ''),
        active: Number(row.active ?? 1),
        can_update_status: Boolean(row.can_update_status),
      })),
    );
  };

  const reorderRows = async (targetId: number) => {
    if (!dragId || dragId === targetId) return;
    const current = [...rows];
    const from = current.findIndex((row) => row.id === dragId);
    const to = current.findIndex((row) => row.id === targetId);
    if (from < 0 || to < 0) return;
    const [moved] = current.splice(from, 1);
    current.splice(to, 0, moved);
    setRows(current);
    setDragId(null);
    await adminApi.updateNavigationLinkOrder(navId, current.map((row) => row.id));
  };

  return (
    <main className="main admin-navigation-pages-page">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/navigations">{lbl('LBL_NAVIGATIONS', 'Navigations')}</Link>
            </li>
            <li className="breadcrumb-item">{title}</li>
          </ul>
          <div className="action-toolbar">
            <Link to="/admin/navigations" className="btn btn-primary">
              {lbl('LBL_BACK', 'Back')}
            </Link>
            {canEdit ? (
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={(event) => {
                  event.preventDefault();
                  setLinkEditId(0);
                  setLinkModalOpen(true);
                }}
              >
                {lbl('LBL_ADD_NEW', 'Add new')}
              </a>
            ) : null}
          </div>
        </div>

        {error ? <div className="alert alert-danger">{error}</div> : null}

        <div className="card">
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table table--hovered" width="100%" id="pageList">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th width="5%">
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_Sr._No', 'Sr. No')}</th>
                      <th>{lbl('LBL_IDENTIFIER', 'Identifier')}</th>
                      <th>{lbl('LBL_CAPTION', 'Caption')}</th>
                      <th>{lbl('LBL_STATUS', 'Status')}</th>
                      {canEdit ? <th>{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={canEdit ? 6 : 4} className="text-center">
                          {lbl('LBL_No_Records_Found', 'No records found')}
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr key={row.id} id={String(row.id)}>
                          {canEdit ? (
                            <td
                              className="dragHandle"
                              draggable
                              onDragStart={() => setDragId(row.id)}
                              onDragOver={(event) => event.preventDefault()}
                              onDrop={(event) => {
                                event.preventDefault();
                                void reorderRows(row.id);
                              }}
                            >
                              <i className="ion-arrow-move icon" />
                            </td>
                          ) : null}
                          <td>{index + 1}</td>
                          <td>{row.identifier}</td>
                          <td>{row.caption}</td>
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.active === 1}
                              disabled={!canEdit || !row.can_update_status}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              confirmMessage={row.can_update_status ? lbl('LBL_ARE_YOU_SURE_YOU_WANT_TO_UPDATE_STATUS?', 'Are you sure you want to update the status?') : undefined}
                              onToggle={async (next) => {
                                await adminApi.updateNavigationLinkStatus(row.id, next);
                                const res = await adminApi.navigationPages(navId, langId || 1);
                                setRows(
                                  (res.data.data.links ?? []).map((link) => ({
                                    id: Number(link.id ?? 0),
                                    navigation_id: Number(link.navigation_id ?? navId),
                                    identifier: String(link.identifier ?? ''),
                                    caption: String(link.caption ?? ''),
                                    active: Number(link.active ?? 1),
                                    can_update_status: Boolean(link.can_update_status),
                                  })),
                                );
                              }}
                            />
                          </td>
                          {canEdit ? (
                            <td>
                              <ul className="actions">
                                <li>
                                  <a
                                    href="javascript:void(0)"
                                    title={lbl('LBL_Edit', 'Edit')}
                                    onClick={(event) => {
                                      event.preventDefault();
                                      setLinkEditId(row.id);
                                      setLinkModalOpen(true);
                                    }}
                                  >
                                    <AdminSpriteIcon icon="edit" />
                                  </a>
                                </li>
                                <li>
                                  <a
                                    href="javascript:void(0)"
                                    title={lbl('LBL_Delete', 'Delete')}
                                    onClick={(event) => {
                                      event.preventDefault();
                                      if (!window.confirm(lbl('LBL_CONFIRM_DELETE', 'Do you want to delete this record?'))) {
                                        return;
                                      }
                                      void adminApi.deleteNavigationLink(row.id).then(() => {
                                        setRows((current) => current.filter((item) => item.id !== row.id));
                                      });
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
        <AdminNavigationLinkModal
          open={linkModalOpen}
          navigationId={navId}
          linkId={linkEditId}
          onClose={() => setLinkModalOpen(false)}
          onSaved={() => {
            void reload();
          }}
        />
      </div>
    </main>
  );
}
