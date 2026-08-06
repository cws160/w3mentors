import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router-dom';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminCategoryModal } from '../components/AdminCategoryModal';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';

type Row = {
  id: number;
  identifier: string;
  title: string;
  subcategories: number;
  records: number;
  featured: number;
  active: number;
  updated_at: string;
  parent_id: number;
};

export function AdminCategoriesPage() {
  const { lbl } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const { pathname } = useLocation();
  const [searchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [parentName, setParentName] = useState('');
  const [loading, setLoading] = useState(true);
  const [dragId, setDragId] = useState<number | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [needsRefresh, setNeedsRefresh] = useState(false);
  const needsRefreshRef = useRef(false);

  const isQuiz = pathname.includes('/categories/quiz');
  const cateType = isQuiz ? 2 : 1;
  const basePath = isQuiz ? '/admin/categories/quiz' : '/admin/categories';
  const parentId = Number(searchParams.get('parent_id') ?? 0);
  const canEdit = Boolean(isQuiz ? privileges.canEditQuizCategories : privileges.canEditCourseCategories);
  const canViewRecords = Boolean(isQuiz ? privileges.canViewQuestions : privileges.canViewCourses);

  const listParams = useMemo(
    () => ({
      parent_id: String(parentId),
      cate_type: String(cateType),
    }),
    [cateType, parentId],
  );

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('categories', listParams)
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
    const pageKey = isQuiz ? 'categories-quiz' : 'categories';
    void adminApi.pageText(pageKey).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      const rootTitle = lbl('LBL_ROOT_CATEGORIES', 'Root categories');
      setMeta({
        title: parentId > 0 ? parentName || rootTitle : pageText.title || rootTitle,
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
  }, [clearMeta, isQuiz, lbl, parentId, parentName, setMeta]);

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    try {
      await adminApi.deleteCategory(id, cateType);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to delete')
          : 'Unable to delete';
      window.alert(message);
    }
  };

  const openCreate = () => {
    setEditId(0);
    needsRefreshRef.current = false;
    setNeedsRefresh(false);
    setModalOpen(true);
  };

  const openEdit = (id: number) => {
    setEditId(id);
    needsRefreshRef.current = false;
    setNeedsRefresh(false);
    setModalOpen(true);
  };

  const reorder = async (nextRows: Row[]) => {
    setRows(nextRows);
    await adminApi.updateCategoryOrder(
      nextRows.map((row) => row.id),
      cateType,
    );
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

  const formatUpdated = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'NA');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY HH:mm') : value;
  };

  const recordsHref = (rowId: number) => {
    if (isQuiz) {
      return parentId > 0
        ? `/admin/questions?ques_cate_id=${parentId}&ques_subcate_id=${rowId}`
        : `/admin/questions?ques_cate_id=${rowId}`;
    }
    return parentId > 0
      ? `/admin/courses?course_cateid=${parentId}&course_subcateid=${rowId}`
      : `/admin/courses?course_cateid=${rowId}`;
  };

  const recordsTitle = isQuiz ? lbl('LBL_QUESTIONS', 'Questions') : lbl('LBL_COURSES', 'Courses');

  const renderRecords = (row: Row) => {
    if (row.records <= 0) return 0;
    if (!canViewRecords) {
      return <span title={recordsTitle}>{row.records}</span>;
    }
    return (
      <Link to={recordsHref(row.id)} className="link-text link-underline" title={recordsTitle}>
        {row.records}
      </Link>
    );
  };

  const showSubcategories = parentId === 0;
  const showFeatured = parentId === 0 && !isQuiz;
  const rootCategoriesLabel = lbl('LBL_ROOT_CATEGORIES', 'Root categories');

  const onExport = () => {
    const headers = [
      lbl('LBL_Sr._No', 'Sr. No'),
      lbl('LBL_IDENTIFIER', 'Identifier'),
      lbl('LBL_NAME', 'Name'),
      ...(showSubcategories ? [lbl('LBL_SUB_CATEGORIES', 'Sub categories')] : []),
      lbl('LBL_RECORDS', 'Records'),
      ...(showFeatured ? [lbl('LBL_FEATURED', 'Featured')] : []),
      lbl('LBL_UPDATED', 'Updated'),
      lbl('LBL_STATUS', 'Status'),
    ];
    const lines = rows.map((row, index) => {
      const cells: Array<string | number> = [
        index + 1,
        row.identifier,
        row.title,
        ...(showSubcategories ? [row.subcategories] : []),
        row.records,
        ...(showFeatured ? [row.featured ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No')] : []),
        formatUpdated(row.updated_at),
        row.active === 1 ? lbl('LBL_Active', 'Active') : lbl('LBL_Inactive', 'Inactive'),
      ];
      return cells.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',');
    });
    const csv = [headers.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(','), ...lines].join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    link.download = isQuiz ? 'quiz-categories.csv' : 'categories.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

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
                  <Link to={basePath}>{rootCategoriesLabel}</Link>
                </li>
                <li className="breadcrumb-item">{parentName || '…'}</li>
              </>
            ) : (
              <li className="breadcrumb-item">{rootCategoriesLabel}</li>
            )}
          </ul>
          <div className="action-toolbar">
            {parentId > 0 ? (
              <Link to={basePath} className="btn btn-primary">
                {lbl('LBL_BACK', 'Back')}
              </Link>
            ) : null}
            {canEdit ? (
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={(e) => {
                  e.preventDefault();
                  openCreate();
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
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table" width="100%" id="categoriesList">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_Sr._No', 'Sr. No')}</th>
                      <th>{lbl('LBL_IDENTIFIER', 'Identifier')}</th>
                      <th>{lbl('LBL_NAME', 'Name')}</th>
                      {showSubcategories ? (
                        <th>{lbl('LBL_SUB_CATEGORIES', 'Sub categories')}</th>
                      ) : null}
                      <th>{lbl('LBL_RECORDS', 'Records')}</th>
                      {showFeatured ? <th>{lbl('LBL_FEATURED', 'Featured')}</th> : null}
                      <th>{lbl('LBL_UPDATED', 'Updated')}</th>
                      <th>{lbl('LBL_STATUS', 'Status')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td
                          colSpan={
                            (canEdit ? 1 : 0) +
                            5 +
                            (showSubcategories ? 1 : 0) +
                            (showFeatured ? 1 : 0) +
                            (canEdit ? 1 : 0)
                          }
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
                          <td>{row.identifier}</td>
                          <td>{row.title}</td>
                          {showSubcategories ? (
                            <td>
                              {row.subcategories > 0 ? (
                                <Link
                                  to={`${basePath}?parent_id=${row.id}`}
                                  className="link-text link-underline"
                                  title={lbl('LBL_SUB_CATEGORIES', 'Sub categories')}
                                >
                                  {row.subcategories}
                                </Link>
                              ) : (
                                0
                              )}
                            </td>
                          ) : null}
                          <td>{renderRecords(row)}</td>
                          {showFeatured ? (
                            <td>{row.featured ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No')}</td>
                          ) : null}
                          <td>{formatUpdated(row.updated_at)}</td>
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.active === 1}
                              disabled={!canEdit}
                              activeLabel={lbl('LBL_Active', 'Active')}
                              inactiveLabel={lbl('LBL_Inactive', 'Inactive')}
                              onToggle={async (next) => {
                                await adminApi.updateCategoryStatus(row.id, next ? 1 : 0, cateType);
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
                                      openEdit(row.id);
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

      <AdminCategoryModal
        open={modalOpen}
        cateId={editId}
        cateType={cateType}
        defaultParentId={parentId}
        onClose={() => {
          setModalOpen(false);
          if (needsRefreshRef.current || needsRefresh) {
            needsRefreshRef.current = false;
            setNeedsRefresh(false);
            load();
          }
        }}
        onSaved={() => {
          needsRefreshRef.current = true;
          setNeedsRefresh(true);
        }}
      />
    </main>
  );
}
