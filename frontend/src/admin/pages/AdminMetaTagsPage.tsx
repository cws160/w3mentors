import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminEditDeleteActions, AdminRowActionsCell } from '../components/AdminRowActions';
import { AdminMetaTagModal, type MetaTagEditTarget } from '../components/AdminMetaTagModal';
import type { AdminModuleConfig } from '../config/adminModuleTypes';

type MetaRow = {
  id: number;
  meta_id: number | null;
  meta_record_id: string;
  meta_identifier: string;
  meta_title: string;
  url: string;
  teacher_name: string;
  grpcls_title: string;
  cpage_title: string;
  bpcategory_identifier: string;
  post_identifier: string;
  course_title: string;
  tlang_name: string;
  has_tag_associated: boolean;
  meta_type: number;
};

type Column = { key: string; label: string };

const META_DEFAULT = -1;
const META_OTHER = 0;
const META_TEACHER = 1;
const META_GRP_CLASS = 2;
const META_CMS_PAGE = 3;
const META_BLOG_CATEGORY = 4;
const META_BLOG_POST = 5;
const META_COURSE = 6;
const META_TEACH_LANGUAGE = 10;

function buildSearchConfig(metaType: number, showTagFilter: boolean): AdminModuleConfig {
  const fields: AdminModuleConfig['searchFields'] = [];

  if (metaType !== META_DEFAULT) {
    fields.push({ name: 'keyword', labelKey: 'LBL_Keyword', labelFallback: 'Keyword', type: 'text' });
  }

  if (showTagFilter) {
    fields.push({
      name: 'hasTagsAssociated',
      labelKey: 'LBL_Tags_Associated',
      labelFallback: 'Tags associated',
      type: 'select',
      options: [
        { value: '', labelKey: 'LBL_Does_not_Matter', labelFallback: 'Does not matter' },
        { value: '1', labelKey: 'LBL_YES', labelFallback: 'Yes' },
        { value: '0', labelKey: 'LBL_NO', labelFallback: 'No' },
      ],
    });
  }

  return {
    module: 'meta-tags',
    pageLangKey: 'meta-tags',
    titleKey: 'LBL_META_TAGS',
    titleFallback: 'Meta tags',
    searchSubmitCol: 6,
    searchFields: fields,
    columns: [],
  };
}

function columnsForType(metaType: number, lbl: (key: string, fallback: string) => string): Column[] {
  switch (metaType) {
    case META_OTHER:
      return [
        { key: 'url', label: lbl('LBL_Slug', 'Slug') },
        { key: 'meta_identifier', label: lbl('LBL_META_IDENTIFIER', 'Meta identifier') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
      ];
    case META_CMS_PAGE:
      return [
        { key: 'cpage_title', label: lbl('LBL_CMS_PAGE', 'CMS page') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
        { key: 'has_tag_associated', label: lbl('LBL_Tags_Associated', 'Tags associated') },
      ];
    case META_TEACHER:
      return [
        { key: 'teacher_name', label: lbl('LBL_Teacher_Name', 'Teacher name') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
        { key: 'has_tag_associated', label: lbl('LBL_Tags_Associated', 'Tags associated') },
      ];
    case META_GRP_CLASS:
      return [
        { key: 'grpcls_title', label: lbl('LBL_Group_Class', 'Group class') },
        { key: 'teacher_name', label: lbl('LBL_Teacher_Name', 'Teacher name') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
        { key: 'has_tag_associated', label: lbl('LBL_Tags_Associated', 'Tags associated') },
      ];
    case META_BLOG_CATEGORY:
      return [
        { key: 'bpcategory_identifier', label: lbl('LBL_Blog_Categories', 'Blog categories') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
        { key: 'has_tag_associated', label: lbl('LBL_Tags_Associated', 'Tags associated') },
      ];
    case META_BLOG_POST:
      return [
        { key: 'post_identifier', label: lbl('LBL_Post_Title', 'Post title') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
        { key: 'has_tag_associated', label: lbl('LBL_Tags_Associated', 'Tags associated') },
      ];
    case META_COURSE:
      return [
        { key: 'course_title', label: lbl('LBL_Course_Title', 'Course title') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
        { key: 'has_tag_associated', label: lbl('LBL_Tags_Associated', 'Tags associated') },
      ];
    case META_TEACH_LANGUAGE:
      return [
        { key: 'tlang_name', label: lbl('LBL_LANGUAGE_TITLE', 'Language title') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
        { key: 'has_tag_associated', label: lbl('LBL_Tags_Associated', 'Tags associated') },
      ];
    default:
      return [
        { key: 'meta_identifier', label: lbl('LBL_META_IDENTIFIER', 'Meta identifier') },
        { key: 'meta_title', label: lbl('LBL_META_TITLE', 'Meta title') },
      ];
  }
}

function cellValue(row: MetaRow, key: string, lbl: (key: string, fallback: string) => string): string {
  if (key === 'has_tag_associated') {
    return row.has_tag_associated ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No');
  }

  const value = row[key as keyof MetaRow];
  return value === null || value === undefined ? '' : String(value);
}

export function AdminMetaTagsPage() {
  const { lbl, langId } = useSite();
  const { features, privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [metaType, setMetaType] = useState(META_DEFAULT);
  const [rows, setRows] = useState<MetaRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filterOpen, setFilterOpen] = useState(true);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });
  const [editTarget, setEditTarget] = useState<MetaTagEditTarget | null>(null);

  const canEdit = Boolean(privileges.canEditMetaTags) || admin?.id === 1;
  const showFilters = metaType !== META_DEFAULT;
  const showTagFilter = metaType !== META_DEFAULT && metaType !== META_OTHER;

  const tabs = useMemo(() => {
    const items = [
      { type: META_DEFAULT, labelKey: 'METALBL_DEFAULT', labelFallback: 'Default' },
      { type: META_OTHER, labelKey: 'METALBL_Others', labelFallback: 'Others' },
      { type: META_TEACHER, labelKey: 'METALBL_Teachers', labelFallback: 'Teachers' },
      { type: META_CMS_PAGE, labelKey: 'METALBL_CMS_Page', labelFallback: 'CMS Page' },
      { type: META_BLOG_CATEGORY, labelKey: 'METALBL_Blog_Categories', labelFallback: 'Blog categories' },
      { type: META_BLOG_POST, labelKey: 'METALBL_Blog_Posts', labelFallback: 'Blog posts' },
      { type: META_TEACH_LANGUAGE, labelKey: 'METALBL_TEACH_LANGUAGE', labelFallback: 'Teach language' },
    ];

    if (features?.courses_enabled) {
      items.push({ type: META_COURSE, labelKey: 'METALBL_Courses', labelFallback: 'Courses' });
    }

    if (features?.group_classes_enabled) {
      items.push({
        type: META_GRP_CLASS,
        labelKey: 'METALBL_Group_Classes',
        labelFallback: 'Group classes',
      });
    }

    return items;
  }, [features?.courses_enabled, features?.group_classes_enabled]);

  const columns = useMemo(() => columnsForType(metaType, lbl), [lbl, metaType]);
  const searchConfig = useMemo(() => buildSearchConfig(metaType, showTagFilter), [metaType, showTagFilter]);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('meta-tags', {
        page,
        metaType: String(metaType),
        lang_id: String(langId),
        ...filters,
      })
      .then((res) => {
        setRows((res.data.data ?? []) as MetaRow[]);
        setPagination(res.data.meta ?? { current_page: 1, per_page: 20, total: 0, last_page: 1 });
      })
      .catch(() => {
        setRows([]);
        setPagination({ current_page: 1, per_page: 20, total: 0, last_page: 1 });
      })
      .finally(() => setLoading(false));
  }, [filters, langId, metaType, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    void adminApi.pageText('meta-tags', langId).then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_META_TAGS', 'Meta tags'),
        summary: pageText.summary,
        warning: pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });

    return () => clearMeta();
  }, [clearMeta, langId, lbl, setMeta]);

  const onTabChange = (type: number) => {
    setMetaType(type);
    setPage(1);
    setDraft({});
    setFilters({});
    setFilterOpen(type !== META_DEFAULT);
  };

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    setFilters({ ...draft });
    setPage(1);
  };

  const onClear = () => {
    setDraft({});
    setFilters({});
    setPage(1);
  };

  const openEdit = (row: MetaRow) => {
    setEditTarget({
      metaId: row.meta_id ?? 0,
      metaType,
      recordId: row.meta_record_id,
    });
  };

  const onDelete = async (row: MetaRow) => {
    if (!row.meta_id) {
      return;
    }
    if (!window.confirm(lbl('LBL_CONFIRM_DELETE', 'Are you sure you want to delete this record?'))) {
      return;
    }
    try {
      await adminApi.deleteMetaTag(row.meta_id);
      load();
    } catch {
      window.alert(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    }
  };

  const onExport = () => {
    const headers = [lbl('LBL_SRNO', 'Sr. No.'), ...columns.map((col) => col.label)];
    if (canEdit) {
      headers.push(lbl('LBL_Action', 'Action'));
    }
    const lines = rows.map((row, index) => {
      const values = columns.map((col) => `"${cellValue(row, col.key, lbl).replace(/"/g, '""')}"`);
      return [String((page - 1) * pagination.per_page + index + 1), ...values].join(',');
    });
    const csv = [headers.join(','), ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'meta-tags.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const colSpan = 1 + columns.length + (canEdit ? 1 : 0);

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_META_TAGS', 'Meta tags')}</li>
          </ul>
          <div className="action-toolbar">
            <a href="javascript:void(0)" className="btn btn-primary" onClick={onExport}>
              {lbl('LBL_EXPORT', 'Export')}
            </a>
          </div>
        </div>

        <div className="grid-layout">
          <div className="grid-layout-left">
            <div className="card card-sticky">
              <nav className="tab tab-vertical tabs-nav-js">
                <ul>
                  {tabs.map((tab) => (
                    <li key={tab.type}>
                      <a
                        href="javascript:void(0)"
                        className={metaType === tab.type ? 'active' : ''}
                        onClick={() => onTabChange(tab.type)}
                      >
                        {lbl(tab.labelKey, tab.labelFallback)}
                      </a>
                    </li>
                  ))}
                </ul>
              </nav>
            </div>
          </div>

          <div className="grid-layout-right">
            <div className="meta-tag-tbl">
              {showFilters ? (
                <div className="card">
                  <div
                    className={`card-head js--filter-trigger${filterOpen ? ' active' : ''}`}
                    onClick={() => setFilterOpen((v) => !v)}
                  >
                    <h4>{lbl('LBL_Search...', 'Search...')}</h4>
                  </div>
                  <div className="card-body js--filter-target" style={{ display: filterOpen ? 'block' : 'none' }}>
                    <AdminModuleSearchForm
                      config={searchConfig}
                      draft={draft}
                      lbl={lbl}
                      onDraftChange={setDraft}
                      onSearch={onSearch}
                      onClear={onClear}
                    />
                  </div>
                </div>
              ) : null}

              <div className="card">
                {canEdit && metaType === META_OTHER ? (
                  <div className="card-head">
                    <div className="card-head-label">
                      <h3 className="card-head-title">
                        {lbl('LBL_OTHER_META_TAGS_LISTING', 'Other meta tags listing')}
                      </h3>
                    </div>
                    <a
                      href="javascript:void(0)"
                      className="btn btn-primary"
                      onClick={() => setEditTarget({ metaId: 0, metaType: META_OTHER, recordId: '0' })}
                    >
                      {lbl('LBL_ADD_NEW', 'Add new')}
                    </a>
                  </div>
                ) : null}
                <div className="card-table">
                  <div className="table-responsive" id="listing">
                    {loading ? (
                      <div className="table-processing loaderJs">
                        <div className="spinner spinner--sm spinner--brand" />
                      </div>
                    ) : (
                      <table className="table" width="100%">
                        <thead>
                          <tr>
                            <th>{lbl('LBL_SRNO', 'Sr. No.')}</th>
                            {columns.map((col) => (
                              <th key={col.key}>{col.label}</th>
                            ))}
                            {canEdit ? <th className="text-end">{lbl('LBL_Action', 'Action')}</th> : null}
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
                              <tr key={`${metaType}-${row.meta_id ?? row.meta_record_id}-${index}`}>
                                <td>{(page - 1) * pagination.per_page + index + 1}</td>
                                {columns.map((col) => (
                                  <td key={col.key}>{cellValue(row, col.key, lbl)}</td>
                                ))}
                                {canEdit ? (
                                  metaType === META_OTHER && row.meta_id ? (
                                    <AdminEditDeleteActions
                                      canEdit={canEdit}
                                      editLabel={lbl('LBL_EDIT', 'Edit')}
                                      deleteLabel={lbl('LBL_DELETE', 'Delete')}
                                      onEdit={() => openEdit(row)}
                                      onDelete={() => void onDelete(row)}
                                    />
                                  ) : (
                                    <AdminRowActionsCell
                                      actions={[
                                        {
                                          icon: 'edit',
                                          title: lbl('LBL_EDIT', 'Edit'),
                                          onClick: () => openEdit(row),
                                        },
                                      ]}
                                    />
                                  )
                                ) : null}
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    )}
                    <AdminLegacyPagination
                      page={page}
                      lastPage={pagination.last_page}
                      perPage={pagination.per_page}
                      total={pagination.total}
                      onPageChange={setPage}
                      labels={{
                        showing: lbl('LBL_Showing', 'Showing'),
                        to: lbl('LBL_to', 'to'),
                        of: lbl('LBL_of', 'of'),
                        entries: lbl('LBL_Entries', 'Entries'),
                      }}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <AdminMetaTagModal
        target={editTarget}
        onClose={() => setEditTarget(null)}
        onSaved={load}
      />
    </main>
  );
}
