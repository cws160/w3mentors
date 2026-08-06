import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { AdminThemeModal } from '../components/AdminThemeModal';

type Row = {
  id: number;
  theme_title: string;
  theme_primary_color: string;
  theme_primary_inverse_color: string;
  theme_secondary_color: string;
  theme_secondary_inverse_color: string;
  theme_footer_color: string;
  theme_footer_inverse_color: string;
  theme_is_default: number;
  is_active: boolean;
};

const config = ADMIN_MODULE_CONFIGS.themes;

function themeHex(color: string): string {
  const value = color.trim().replace(/^#/, '');
  return value ? `#${value}` : '#000000';
}

function ThemeColorCell({ color }: { color: string }) {
  const hex = themeHex(color);
  return (
    <>
      <span
        className="theme-color-box"
        style={{
          backgroundColor: hex,
          height: 11,
          width: 11,
          display: 'inline-block',
          borderRadius: 2,
        }}
      />{' '}
      {hex.toUpperCase()}
    </>
  );
}

export function AdminThemesPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [draft, setDraft] = useState<Record<string, string>>({
    keyword: searchParams.get('keyword') ?? '',
  });
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [modalAction, setModalAction] = useState<'update' | 'clone'>('update');
  const [listMeta, setListMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    active_theme_id: 0,
  });

  const canEdit = Boolean(privileges.canEditThemeManagement) || admin?.id === 1;
  const page = Number(searchParams.get('page') ?? 1);
  const columnCount = canEdit ? 8 : 7;
  const legacyOrigin = (import.meta.env.VITE_LEGACY_ORIGIN as string | undefined) ?? '';

  const listParams = useMemo(
    () => ({
      page: String(page),
      lang_id: String(langId),
      ...(searchParams.get('keyword') ? { keyword: searchParams.get('keyword')! } : {}),
    }),
    [langId, page, searchParams],
  );

  useEffect(() => {
    setDraft({ keyword: searchParams.get('keyword') ?? '' });
  }, [searchParams]);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('themes', listParams)
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setListMeta({
          current_page: Number(res.data.meta?.current_page ?? 1),
          per_page: Number(res.data.meta?.per_page ?? 10),
          total: Number(res.data.meta?.total ?? 0),
          last_page: Number(res.data.meta?.last_page ?? 1),
          active_theme_id: Number(res.data.meta?.active_theme_id ?? 0),
        });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [listParams]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;
    setMeta({ title: lbl('LBL_Theme_Management', 'Theme management') });
    void adminApi.pageText('themes', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Theme_Management', 'Theme management'),
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

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const next = new URLSearchParams(searchParams);
    next.set('page', '1');
    if (draft.keyword?.trim()) {
      next.set('keyword', draft.keyword.trim());
    } else {
      next.delete('keyword');
    }
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '' });
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    next.set('page', '1');
    setSearchParams(next);
  };

  const openModal = (themeId: number, action: 'update' | 'clone') => {
    setEditId(themeId);
    setModalAction(action);
    setModalOpen(true);
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_DELETE', 'Do you want to delete?'))) {
      return;
    }
    try {
      await adminApi.deleteTheme(id);
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

  const onActivate = async (id: number) => {
    if (!window.confirm(lbl('LBL_CONFIRM_ACTIVATE', 'Are you sure you want to activate?'))) {
      return;
    }
    try {
      await adminApi.activateTheme(id);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String(
              (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                'Unable to activate',
            )
          : 'Unable to activate';
      window.alert(message);
    }
  };

  const previewUrl = (themeId: number) => {
    if (legacyOrigin) {
      return `${legacyOrigin.replace(/\/$/, '')}/manager/themes/preview/${themeId}`;
    }
    return `/manager/themes/preview/${themeId}`;
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_Themes', 'Themes')}</li>
          </ul>
        </div>

        <AdminLegacyFilterCard title={lbl('LBL_Search', 'Search')}>
          <AdminModuleSearchForm
            config={config}
            draft={draft}
            lbl={lbl}
            onDraftChange={setDraft}
            onSearch={onSearch}
            onClear={onClear}
          />
        </AdminLegacyFilterCard>

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
                      <th>{lbl('LBL_Theme_Color', 'Theme color')}</th>
                      <th>{lbl('LBL_Primary_Color', 'Primary color')}</th>
                      <th>{lbl('LBL_Primary_Inverse_Color', 'Primary inverse color')}</th>
                      <th>{lbl('LBL_Secondary_Color', 'Secondary color')}</th>
                      <th>{lbl('LBL_Secondary_Inverse_Color', 'Secondary inverse color')}</th>
                      <th>{lbl('LBL_Footer_Color', 'Footer color')}</th>
                      <th>{lbl('LBL_Footer_Inverse_Color', 'Footer inverse color')}</th>
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
                      rows.map((row) => {
                        const actions = [];
                        if (canEdit) {
                          if (row.theme_is_default === 0) {
                            actions.push({
                              icon: 'edit',
                              title: lbl('LBL_EDIT', 'Edit'),
                              onClick: () => openModal(row.id, 'update'),
                            });
                            actions.push({
                              icon: 'delete',
                              title: lbl('LBL_Delete', 'Delete'),
                              onClick: () => void onDelete(row.id),
                            });
                          }
                          actions.push({
                            icon: 'clone',
                            title: lbl('LBL_Clone', 'Clone'),
                            onClick: () => openModal(row.id, 'clone'),
                          });
                          actions.push({
                            icon: 'view',
                            title: lbl('LBL_Preview', 'Preview'),
                            onClick: () => window.open(previewUrl(row.id), '_blank', 'noopener,noreferrer'),
                          });
                          if (!row.is_active) {
                            actions.push({
                              icon: 'active',
                              title: lbl('LBL_Click_To_Activate', 'Click to activate'),
                              onClick: () => void onActivate(row.id),
                            });
                          }
                        }

                        return (
                          <tr key={row.id} id={String(row.id)}>
                            <td>
                              {row.theme_title}
                              {row.is_active ? (
                                <>
                                  {' '}
                                  <i
                                    className="icon ion-checkmark-circled is--active"
                                    title={lbl('LBL_ACTIVE_THEME', 'Active theme')}
                                  />
                                </>
                              ) : null}
                            </td>
                            <td>
                              <ThemeColorCell color={row.theme_primary_color} />
                            </td>
                            <td>
                              <ThemeColorCell color={row.theme_primary_inverse_color} />
                            </td>
                            <td>
                              <ThemeColorCell color={row.theme_secondary_color} />
                            </td>
                            <td>
                              <ThemeColorCell color={row.theme_secondary_inverse_color} />
                            </td>
                            <td>
                              <ThemeColorCell color={row.theme_footer_color} />
                            </td>
                            <td>
                              <ThemeColorCell color={row.theme_footer_inverse_color} />
                            </td>
                            {canEdit ? <AdminRowActionsCell actions={actions} /> : null}
                          </tr>
                        );
                      })
                    )}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>

        {!loading && listMeta.last_page > 1 ? (
          <AdminLegacyPagination
            page={listMeta.current_page}
            pageCount={listMeta.last_page}
            recordCount={listMeta.total}
            pageSize={listMeta.per_page}
            onPageChange={(nextPage) => {
              const next = new URLSearchParams(searchParams);
              next.set('page', String(nextPage));
              setSearchParams(next);
            }}
          />
        ) : null}
      </div>

      <AdminThemeModal
        open={modalOpen}
        themeId={editId}
        action={modalAction}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
