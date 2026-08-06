import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminAffiliateCommissionHistoryModal } from '../components/AdminAffiliateCommissionHistoryModal';
import { AdminAffiliateCommissionModal } from '../components/AdminAffiliateCommissionModal';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';

type Row = {
  id: number;
  user_id: number;
  is_global: boolean;
  affiliate_name: string;
  commission: string;
};

const config = ADMIN_MODULE_CONFIGS['affiliate-commission'];

export function AdminAffiliateCommissionPage() {
  const { lbl, langId } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [draft, setDraft] = useState<Record<string, string>>({
    keyword: searchParams.get('keyword') ?? '',
  });
  const [formOpen, setFormOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [historyOpen, setHistoryOpen] = useState(false);
  const [historyUserId, setHistoryUserId] = useState(0);
  const [meta, setListMeta] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  const canEdit = Boolean(privileges.canEditAffiliateCommission);
  const page = Number(searchParams.get('page') ?? 1);
  const columnCount = canEdit ? 4 : 3;

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
      .moduleList('affiliate-commission', listParams)
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
    setMeta({ title: lbl('LBL_AFFILIATE_COMMISSION', 'Affiliate commission') });
    void adminApi.pageText('affiliate-commission', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_AFFILIATE_COMMISSION', 'Affiliate commission'),
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
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '' });
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    next.set('page', '1');
    setSearchParams(next);
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    try {
      await adminApi.deleteAffiliateCommission(id);
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to delete')
          : 'Unable to delete';
      window.alert(message);
    }
  };

  const affiliateCell = (row: Row) => {
    if (row.is_global) {
      return (
        <span className="label label-success">{lbl('LBL_GLOBAL_COMMISSION', 'Global commission')}</span>
      );
    }
    return row.affiliate_name || lbl('LBL_NA', 'NA');
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_AFFILIATE_COMMISSION', 'Affiliate commission')}</li>
          </ul>
          {canEdit ? (
            <div className="action-toolbar">
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={(e) => {
                  e.preventDefault();
                  setEditId(0);
                  setFormOpen(true);
                }}
              >
                {lbl('LBL_ADD_NEW', 'Add new')}
              </a>
            </div>
          ) : null}
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
                      <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                      <th>{lbl('LBL_AFFILIATE', 'Affiliate')}</th>
                      <th>{lbl('LBL_COMMISSION_[%]', 'Commission (%)')}</th>
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
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(page - 1) * meta.per_page + index + 1}</td>
                          <td>{affiliateCell(row)}</td>
                          <td>{row.commission}</td>
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
                                      setFormOpen(true);
                                    }}
                                  >
                                    <AdminSpriteIcon icon="edit" />
                                  </a>
                                </li>
                                {!row.is_global ? (
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
                                ) : null}
                                <li>
                                  <a
                                    href="javascript:void(0)"
                                    title={lbl('LBL_HISTORY', 'History')}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      setHistoryUserId(row.user_id);
                                      setHistoryOpen(true);
                                    }}
                                  >
                                    <AdminSpriteIcon icon="history" />
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

      <AdminAffiliateCommissionModal
        open={formOpen}
        commissionId={editId}
        onClose={() => setFormOpen(false)}
        onSaved={load}
      />

      <AdminAffiliateCommissionHistoryModal
        open={historyOpen}
        userId={historyUserId}
        onClose={() => setHistoryOpen(false)}
      />
    </main>
  );
}
