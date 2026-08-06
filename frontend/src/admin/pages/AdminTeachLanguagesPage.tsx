import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminTeachLanguageModal } from '../components/AdminTeachLanguageModal';
import { AdminEditDeleteActions } from '../components/AdminRowActions';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';

type Row = {
  id: number;
  identifier: string;
  title: string;
  active: number;
  featured: number;
  featured_label: string;
  subcategories: number;
  subcategories_label: string;
  min_price_label: string;
  max_price_label: string;
  hourly_price_label: string;
};

type PageContext = {
  parent_id: number;
  back_id: number;
  level: number;
  manage_prices: boolean;
  show_subcategories: boolean;
  show_featured: boolean;
  breadcrumb: Array<{ id: number; title: string; path: string }>;
};

export function AdminTeachLanguagesPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams] = useSearchParams();
  const parentId = Number(searchParams.get('parent_id') || 0) || 0;

  const [rows, setRows] = useState<Row[]>([]);
  const [pageContext, setPageContext] = useState<PageContext | null>(null);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [dragId, setDragId] = useState<number | null>(null);

  const canEdit = Boolean(privileges.canEditTeachLanguage) || admin?.id === 1;
  const managePrices = Boolean(pageContext?.manage_prices);
  const showSubcategories = Boolean(pageContext?.show_subcategories);
  const showFeatured = Boolean(pageContext?.show_featured);
  const backId = pageContext?.back_id ?? 0;

  const loadContext = useCallback(() => {
    void adminApi.teachLanguageContext(parentId, langId).then((res) => {
      setPageContext(res.data.data as PageContext);
    });
  }, [langId, parentId]);

  const load = useCallback(() => {
    setLoading(true);
    void Promise.all([
      adminApi.moduleList('teach-language', { parent_id: String(parentId), lang_id: langId }),
      adminApi.teachLanguageContext(parentId, langId),
    ])
      .then(([listRes, contextRes]) => {
        setRows((listRes.data.data ?? []) as Row[]);
        setPageContext(contextRes.data.data as PageContext);
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [langId, parentId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;
    void adminApi.pageText('teach-language').then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_TEACHING_LANGUAGE', 'Teaching subjects'),
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

  const breadcrumbNodes = useMemo(() => {
    const nodes = pageContext?.breadcrumb ?? [];
    if (nodes.length > 0) {
      return nodes;
    }
    return [{ id: 0, title: lbl('LBL_ROOT_LANGUAGES', 'Root languages'), path: '/admin/teach-language' }];
  }, [lbl, pageContext?.breadcrumb]);

  const onExport = () => {
    const cols = [
      lbl('LBL_LANGUAGE_IDENTIFIER', 'Identifier'),
      lbl('LBL_Teach_Language_name', 'Name'),
    ];
    if (showSubcategories) {
      cols.push(lbl('LBL_SUB_LANGUAGES', 'Sub languages'));
    }
    if (managePrices) {
      cols.push(lbl('LBL_PRICE/HOUR', 'Price/hour'));
    } else {
      cols.push(lbl('LBL_MIN_PRICE/HOUR', 'Min price/hour'), lbl('LBL_MAX_PRICE/HOUR', 'Max price/hour'));
    }
    if (showFeatured) {
      cols.push(lbl('LBL_FEATURED_TLANG', 'Featured'));
    }
    cols.push(lbl('LBL_STATUS', 'Status'));

    const lines = rows.map((row) => {
      const values = [row.identifier, row.title];
      if (showSubcategories) {
        values.push(row.subcategories_label);
      }
      if (managePrices) {
        values.push(row.hourly_price_label);
      } else {
        values.push(row.min_price_label, row.max_price_label);
      }
      if (showFeatured) {
        values.push(row.featured_label);
      }
      values.push(row.active === 1 ? lbl('LBL_ACTIVE', 'Active') : lbl('LBL_INACTIVE', 'Inactive'));
      return values.map((val) => `"${String(val).replace(/"/g, '""')}"`).join(',');
    });

    const csv = [cols.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'teaching-subjects.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const confirms = adminLegacyConfirms(lbl);

  const onDelete = async (id: number) => {
    if (!window.confirm(confirms.delete)) {
      return;
    }
    try {
      await adminApi.deleteTeachLanguage(id);
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
      await adminApi.updateTeachLanguageOrder(nextRows.map((row) => row.id));
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

  const colSpan =
    3 +
    (canEdit ? 1 : 0) +
    (showSubcategories ? 1 : 0) +
    (managePrices ? 1 : 2) +
    (showFeatured ? 1 : 0) +
    1 +
    (canEdit ? 1 : 0);

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/teach-language">{lbl('LBL_TEACHING_LANGUAGE', 'Teaching subjects')}</Link>
            </li>
            {breadcrumbNodes.slice(1).map((node) => (
              <li key={node.id} className="breadcrumb-item">
                {node.id === parentId ? (
                  node.title
                ) : (
                  <Link to={node.path}>{node.title}</Link>
                )}
              </li>
            ))}
          </ul>
          <div className="action-toolbar">
            {parentId > 0 ? (
              <Link
                to={backId > 0 ? `/admin/teach-language?parent_id=${backId}` : '/admin/teach-language'}
                className="btn btn-primary"
              >
                {lbl('LBL_BACK', 'Back')}
              </Link>
            ) : null}
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
                <table className="table table--hovered table-dragable" width="100%" id="teachingLangages">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_SRNO', 'Sr. No.')}</th>
                      <th>{lbl('LBL_LANGUAGE_IDENTIFIER', 'Identifier')}</th>
                      <th>{lbl('LBL_Teach_Language_name', 'Name')}</th>
                      {showSubcategories ? (
                        <th>{lbl('LBL_SUB_LANGUAGES', 'Sub languages')}</th>
                      ) : null}
                      {managePrices ? (
                        <th>{lbl('LBL_PRICE/HOUR', 'Price/hour')}</th>
                      ) : (
                        <>
                          <th>{lbl('LBL_MIN_PRICE/HOUR', 'Min price/hour')}</th>
                          <th>{lbl('LBL_MAX_PRICE/HOUR', 'Max price/hour')}</th>
                        </>
                      )}
                      {showFeatured ? <th>{lbl('LBL_FEATURED_TLANG', 'Featured')}</th> : null}
                      <th>{lbl('LBL_STATUS', 'Status')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={colSpan} className="text-center">
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
                          {showSubcategories ? (
                            <td>
                              {row.subcategories > 0 ? (
                                <Link
                                  to={`/admin/teach-language?parent_id=${row.id}`}
                                  className="link-text link-underline"
                                >
                                  {row.subcategories_label}
                                </Link>
                              ) : (
                                '—'
                              )}
                            </td>
                          ) : null}
                          {managePrices ? (
                            <td>{row.hourly_price_label}</td>
                          ) : (
                            <>
                              <td>{row.min_price_label}</td>
                              <td>{row.max_price_label}</td>
                            </>
                          )}
                          {showFeatured ? <td>{row.featured_label}</td> : null}
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.active === 1}
                              disabled={!canEdit}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              confirmMessage={confirms.updateStatus}
                              onToggle={async (next) => {
                                try {
                                  await adminApi.updateTeachLanguageStatus(row.id, next);
                                  load();
                                } catch (err: unknown) {
                                  const message =
                                    (err as { response?: { data?: { message?: string } } })?.response
                                      ?.data?.message ?? 'Unable to update status';
                                  window.alert(message);
                                  loadContext();
                                }
                              }}
                            />
                          </td>
                          <AdminEditDeleteActions
                            canEdit={canEdit}
                            editLabel={lbl('LBL_EDIT', 'Edit')}
                            deleteLabel={lbl('LBL_Delete', 'Delete')}
                            onEdit={() => {
                              setEditId(row.id);
                              setModalOpen(true);
                            }}
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

      <AdminTeachLanguageModal
        open={modalOpen}
        tlangId={editId}
        parentId={parentId}
        managePrices={managePrices}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
