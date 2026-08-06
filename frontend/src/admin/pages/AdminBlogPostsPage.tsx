import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import moment from 'moment';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminBlogPostModal } from '../components/AdminBlogPostModal';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';

type Row = {
  id: number;
  identifier: string;
  title: string;
  categories: string;
  published: number;
  posted_on: string;
  published_on: string;
};

const config = ADMIN_MODULE_CONFIGS['blog-posts'];

export function AdminBlogPostsPage() {
  const { lbl, langId } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [draft, setDraft] = useState<Record<string, string>>({
    keyword: searchParams.get('keyword') ?? '',
    post_published: searchParams.get('post_published') ?? '',
  });
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [meta, setListMeta] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const canEdit = Boolean(privileges.canEditBlogPosts);
  const page = Number(searchParams.get('page') ?? 1);

  const listParams = useMemo(
    () => ({
      page: String(page),
      lang_id: String(langId),
      ...(searchParams.get('keyword') ? { keyword: searchParams.get('keyword')! } : {}),
      ...(searchParams.get('post_published') ? { post_published: searchParams.get('post_published')! } : {}),
    }),
    [langId, page, searchParams],
  );

  useEffect(() => {
    setDraft({
      keyword: searchParams.get('keyword') ?? '',
      post_published: searchParams.get('post_published') ?? '',
    });
  }, [searchParams]);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('blog-posts', listParams)
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setListMeta({
          current_page: Number(res.data.meta?.current_page ?? 1),
          per_page: Number(res.data.meta?.per_page ?? 10),
          total: Number(res.data.meta?.total ?? 0),
          last_page: Number(res.data.meta?.last_page ?? 1),
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
    setMeta({ title: lbl('LBL_Blog_Posts', 'Blog Posts') });
    void adminApi.pageText('blog-posts', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Blog_Posts', 'Blog Posts'),
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

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    const next = new URLSearchParams(searchParams);
    next.set('page', '1');
    if (draft.keyword?.trim()) {
      next.set('keyword', draft.keyword.trim());
    } else {
      next.delete('keyword');
    }
    if (draft.post_published) {
      next.set('post_published', draft.post_published);
    } else {
      next.delete('post_published');
    }
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '', post_published: '' });
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    next.delete('post_published');
    next.set('page', '1');
    setSearchParams(next);
  };

  const onExport = () => {
    const headers = [
      lbl('LBL_Sr._No', 'Sr no'),
      lbl('LBL_POST_IDENTIFIER', 'Post Identifier'),
      lbl('LBL_Post_Title', 'Post title'),
      lbl('LBL_Category', 'Category'),
      lbl('LBL_Added_Date', 'Added date'),
      lbl('LBL_Published_Date', 'Published date'),
      lbl('LBL_Post_Status', 'Post status'),
    ];
    const lines = rows.map((row, index) => {
      const cells = [
        (page - 1) * meta.per_page + index + 1,
        row.identifier,
        row.title,
        row.categories,
        formatDate(row.posted_on),
        formatDate(row.published_on),
        row.published === 1 ? lbl('LBL_PUBLISHED', 'Published') : lbl('LBL_IN_DRAFT', 'In draft'),
      ];
      return cells.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',');
    });
    const csv = [headers.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(','), ...lines].join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    link.download = 'blog-posts.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    try {
      await adminApi.deleteBlogPost(id);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to delete')
          : 'Unable to delete';
      window.alert(message);
    }
  };

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'NA');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const columnCount = canEdit ? 8 : 7;

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_Blog_Posts', 'Blog posts')}</li>
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
                <table className="table table--hovered" width="100%" id="post">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                      <th>{lbl('LBL_POST_IDENTIFIER', 'Post Identifier')}</th>
                      <th>{lbl('LBL_Post_Title', 'Post title')}</th>
                      <th>{lbl('LBL_Category', 'Category')}</th>
                      <th>{lbl('LBL_Added_Date', 'Added date')}</th>
                      <th>{lbl('LBL_Published_Date', 'Published date')}</th>
                      <th>{lbl('LBL_Post_Status', 'Post status')}</th>
                      {canEdit ? <th className="align-right">{lbl('LBL_Action', 'Action')}</th> : null}
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
                      rows.map((row, index) => (
                        <tr key={row.id} id={row.published === 1 ? String(row.id) : undefined}>
                          <td>{(page - 1) * meta.per_page + index + 1}</td>
                          <td>{row.identifier}</td>
                          <td>{row.title}</td>
                          <td>{row.categories}</td>
                          <td>{formatDate(row.posted_on)}</td>
                          <td>{formatDate(row.published_on)}</td>
                          <td>
                            {row.published === 1
                              ? lbl('LBL_PUBLISHED', 'Published')
                              : lbl('LBL_IN_DRAFT', 'In draft')}
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
            <AdminLegacyPagination
              page={meta.current_page}
              lastPage={meta.last_page}
              perPage={meta.per_page}
              total={meta.total}
              onPageChange={(nextPage) => {
                const next = new URLSearchParams(searchParams);
                next.set('page', String(nextPage));
                setSearchParams(next);
              }}
              labels={{
                showing: lbl('LBL_SHOWING', 'Showing'),
                to: lbl('LBL_TO', 'to'),
                of: lbl('LBL_OF', 'of'),
                entries: lbl('LBL_ENTRIES', 'entries'),
              }}
            />
          </div>
        </div>
      </div>

      <AdminBlogPostModal
        open={modalOpen}
        postId={editId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
