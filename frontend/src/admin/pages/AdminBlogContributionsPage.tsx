import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import moment from 'moment';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminBlogContributionModal } from '../components/AdminBlogContributionModal';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';

type Row = {
  id: number;
  author_name: string;
  author_email: string;
  author_phone: string;
  status: number;
  posted_on: string;
};

const config = ADMIN_MODULE_CONFIGS['blog-contributions'];

export function AdminBlogContributionsPage() {
  const { lbl, langId } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [draft, setDraft] = useState<Record<string, string>>({
    keyword: searchParams.get('keyword') ?? '',
    bcontributions_status: searchParams.get('bcontributions_status') ?? '',
  });
  const [modalOpen, setModalOpen] = useState(false);
  const [viewId, setViewId] = useState(0);
  const [meta, setListMeta] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const canEdit = Boolean(privileges.canEditBlogContributions);
  const page = Number(searchParams.get('page') ?? 1);
  const columnCount = canEdit ? 7 : 6;

  const listParams = useMemo(
    () => ({
      page: String(page),
      lang_id: String(langId),
      ...(searchParams.get('keyword') ? { keyword: searchParams.get('keyword')! } : {}),
      ...(searchParams.get('bcontributions_status')
        ? { bcontributions_status: searchParams.get('bcontributions_status')! }
        : {}),
    }),
    [langId, page, searchParams],
  );

  useEffect(() => {
    setDraft({
      keyword: searchParams.get('keyword') ?? '',
      bcontributions_status: searchParams.get('bcontributions_status') ?? '',
    });
  }, [searchParams]);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('blog-contributions', listParams)
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
    setMeta({ title: lbl('LBL_Blog_Contributions', 'Blog contributions') });
    void adminApi.pageText('blog-contributions', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Blog_Contributions', 'Blog contributions'),
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

  const statusLabel = (value: number) => {
    if (value === 1) return lbl('LBL_APPROVED', 'Approved');
    if (value === 2) return lbl('LBL_POSTED', 'Posted');
    if (value === 3) return lbl('LBL_REJECTED', 'Rejected');
    return lbl('LBL_PENDING', 'Pending');
  };

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'NA');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const onSearch = (e: FormEvent) => {
    e.preventDefault();
    const next = new URLSearchParams(searchParams);
    next.set('page', '1');
    if (draft.keyword?.trim()) {
      next.set('keyword', draft.keyword.trim());
    } else {
      next.delete('keyword');
    }
    if (draft.bcontributions_status) {
      next.set('bcontributions_status', draft.bcontributions_status);
    } else {
      next.delete('bcontributions_status');
    }
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '', bcontributions_status: '' });
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    next.delete('bcontributions_status');
    next.set('page', '1');
    setSearchParams(next);
  };

  const onExport = () => {
    const headers = [
      lbl('LBL_Sr._No', 'Sr no'),
      lbl('LBL_Author_Name', 'Author name'),
      lbl('LBL_Author_Email', 'Author email'),
      lbl('LBL_Author_Phone', 'Author phone'),
      lbl('LBL_Status', 'Status'),
      lbl('LBL_Posted_On', 'Posted on'),
    ];
    const lines = rows.map((row, index) => {
      const cells = [
        (page - 1) * meta.per_page + index + 1,
        row.author_name,
        row.author_email,
        row.author_phone,
        statusLabel(row.status),
        formatDate(row.posted_on),
      ];
      return cells.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',');
    });
    const csv = [headers.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(','), ...lines].join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    link.download = 'blog-contributions.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    try {
      await adminApi.deleteBlogContribution(id);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to delete')
          : 'Unable to delete';
      window.alert(message);
    }
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_Blog_Contributions', 'Blog contributions')}</li>
          </ul>
          <div className="action-toolbar">
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
                      <th>{lbl('LBL_Author_Name', 'Author name')}</th>
                      <th>{lbl('LBL_Author_Email', 'Author email')}</th>
                      <th>{lbl('LBL_Author_Phone', 'Author phone')}</th>
                      <th>{lbl('LBL_Status', 'Status')}</th>
                      <th>{lbl('LBL_Posted_On', 'Posted on')}</th>
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
                        <tr key={row.id}>
                          <td>{(page - 1) * meta.per_page + index + 1}</td>
                          <td>{row.author_name}</td>
                          <td>{row.author_email}</td>
                          <td>{row.author_phone}</td>
                          <td>{statusLabel(row.status)}</td>
                          <td>{formatDate(row.posted_on)}</td>
                          {canEdit ? (
                            <td className="align-right">
                              <ul className="actions">
                                <li>
                                  <a
                                    href="javascript:void(0)"
                                    title={lbl('LBL_EDIT', 'Edit')}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      setViewId(row.id);
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

      <AdminBlogContributionModal
        open={modalOpen}
        contributionId={viewId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
