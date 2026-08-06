import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminBlogPostCategoryModal } from '../components/AdminBlogPostCategoryModal';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

type Row = {
  id: number;
  identifier: string;
  title: string;
  subcategories: number;
  active: number;
  parent_id: number;
};

export function AdminBlogPostCategoriesPage() {
  const { lbl } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [parentName, setParentName] = useState('');
  const [loading, setLoading] = useState(true);
  const [keyword, setKeyword] = useState(searchParams.get('keyword') ?? '');
  const [draft, setDraft] = useState<Record<string, string>>({ keyword: searchParams.get('keyword') ?? '' });
  const [filterOpen, setFilterOpen] = useState(true);
  const [dragId, setDragId] = useState<number | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);

  const parentId = Number(searchParams.get('parent_id') ?? 0);
  const canEdit = Boolean(privileges.canEditBlogPostCategories);
  const showSubcategories = parentId === 0;

  const listParams = useMemo(
    () => ({
      parent_id: String(parentId),
      ...(keyword.trim() ? { keyword: keyword.trim() } : {}),
    }),
    [keyword, parentId],
  );

  const searchFormConfig = useMemo<AdminModuleConfig>(
    () => ({
      module: 'blog-post-categories',
      titleKey: 'LBL_BLOG_POST_CATEGORIES',
      titleFallback: 'Blog post categories',
      columns: [],
      searchFields: [
        {
          name: 'keyword',
          labelKey: 'LBL_KEYWORD',
          labelFallback: 'Keyword',
          type: 'text',
          col: 3,
          placeholderKey: 'LBL_SEARCH_BY_KEYWORD',
          placeholderFallback: 'Search by keyword',
        },
      ],
    }),
    [],
  );

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('blog-post-categories', listParams)
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        const meta = res.data.meta as { parent_name?: string | null } | undefined;
        setParentName(meta?.parent_name ?? '');
      })
      .catch(() => {
        setRows([]);
        setParentName('');
      })
      .finally(() => setLoading(false));
  }, [listParams]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;
    void adminApi.pageText('blog-post-categories').then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title:
          parentId > 0
            ? parentName || lbl('LBL_BLOG_POST_CATEGORIES', 'Blog post categories')
            : pageText.title || lbl('LBL_BLOG_POST_CATEGORIES', 'Blog post categories'),
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
  }, [clearMeta, lbl, parentId, parentName, setMeta]);

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    const nextKeyword = draft.keyword?.trim() ?? '';
    setKeyword(nextKeyword);
    const next = new URLSearchParams(searchParams);
    if (nextKeyword) {
      next.set('keyword', nextKeyword);
    } else {
      next.delete('keyword');
    }
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '' });
    setKeyword('');
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    setSearchParams(next);
  };

  const onExport = () => {
    const headers = [
      lbl('LBL_Sr._No', 'Sr. No'),
      lbl('LBL_Category_Name', 'Title'),
      lbl('LBL_CATEGORY_IDENTIFIER', 'Identifier'),
      ...(showSubcategories ? [lbl('LBL_SUBCATEGORIES', 'Subcategories')] : []),
      lbl('LBL_STATUS', 'Status'),
    ];
    const lines = rows.map((row, index) => {
      const cells: Array<string | number> = [
        index + 1,
        row.title,
        row.identifier,
        ...(showSubcategories ? [row.subcategories] : []),
        row.active === 1 ? lbl('LBL_ACTIVE', 'Active') : lbl('LBL_INACTIVE', 'Inactive'),
      ];
      return cells.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',');
    });
    const csv = [headers.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(','), ...lines].join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    link.download = 'blog-post-categories.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    try {
      await adminApi.deleteBlogPostCategory(id);
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
    await adminApi.updateBlogPostCategoryOrder(nextRows.map((row) => row.id));
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

  const rootLabel = lbl('LBL_BLOG_POST_CATEGORIES', 'Blog post categories');

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            {parentId > 0 ? (
              <>
                <li className="breadcrumb-item">
                  <Link to="/admin/blog-post-categories">{rootLabel}</Link>
                </li>
                <li className="breadcrumb-item">{parentName || '…'}</li>
              </>
            ) : (
              <li className="breadcrumb-item">{rootLabel}</li>
            )}
          </ul>
          <div className="action-toolbar">
            {parentId > 0 ? (
              <Link to="/admin/blog-post-categories" className="btn btn-primary">
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
            <a
              href="javascript:void(0)"
              className="btn btn-primary"
              onClick={(e) => {
                e.preventDefault();
                onExport();
              }}
            >
              {lbl('LBL_EXPORT', 'Export')}
            </a>
          </div>
        </div>

        <div className="card">
          <div
            className={`card-head js--filter-trigger${filterOpen ? ' active' : ''}`}
            onClick={() => setFilterOpen((value) => !value)}
          >
            <h4>{lbl('LBL_Search...', 'Search...')}</h4>
          </div>
          <div className="card-body js--filter-target" style={{ display: filterOpen ? 'block' : 'none' }}>
            <AdminModuleSearchForm
              config={searchFormConfig}
              draft={draft}
              lbl={lbl}
              onDraftChange={setDraft}
              onSearch={onSearch}
              onClear={onClear}
            />
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
                <table className="table table--hovered" width="100%" id="bpcategory">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_Sr._No', 'Sr. No')}</th>
                      <th>{lbl('LBL_Category_Name', 'Title')}</th>
                      <th>{lbl('LBL_CATEGORY_IDENTIFIER', 'Identifier')}</th>
                      {showSubcategories ? <th>{lbl('LBL_SUBCATEGORIES', 'Subcategories')}</th> : null}
                      <th>{lbl('LBL_STATUS', 'Status')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td
                          colSpan={(canEdit ? 1 : 0) + 4 + (showSubcategories ? 1 : 0) + (canEdit ? 1 : 0)}
                          className="text-center"
                        >
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
                          <td>{row.title}</td>
                          <td>{row.identifier}</td>
                          {showSubcategories ? (
                            <td>
                              {row.subcategories > 0 ? (
                                <Link
                                  to={`/admin/blog-post-categories?parent_id=${row.id}`}
                                  className="link-text link-underline"
                                  title={lbl('LBL_VIEW_CATEGORIES', 'View categories')}
                                >
                                  {row.subcategories}
                                </Link>
                              ) : (
                                0
                              )}
                            </td>
                          ) : null}
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.active === 1}
                              disabled={!canEdit}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              onToggle={async (next) => {
                                await adminApi.updateBlogPostCategoryStatus(row.id, next ? 1 : 0);
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
                                    title={lbl('LBL_DELETE', 'Delete')}
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

      <AdminBlogPostCategoryModal
        open={modalOpen}
        categoryId={editId}
        defaultParentId={parentId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
