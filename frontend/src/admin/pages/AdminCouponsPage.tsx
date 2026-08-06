import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminCouponModal } from '../components/AdminCouponModal';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type Row = {
  id: number;
  coupon_title: string;
  coupon_code: string;
  coupon_discount: string;
  available: string;
  coupon_active: number;
  is_expired: boolean;
};

const config = ADMIN_MODULE_CONFIGS.coupons;

export function AdminCouponsPage() {
  const { lbl, langId } = useSite();
  const { privileges, loading: authLoading } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [draft, setDraft] = useState<Record<string, string>>({
    keyword: searchParams.get('keyword') ?? '',
    coupon_active: searchParams.get('coupon_active') ?? '',
    coupon_expire: searchParams.get('coupon_expire') ?? '',
  });
  const [formOpen, setFormOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [listMeta, setListMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  const canView = Boolean(privileges.canViewDiscountCoupons);
  const canEdit = Boolean(privileges.canEditDiscountCoupons);
  const confirms = adminLegacyConfirms(lbl);
  const page = Number(searchParams.get('page') ?? 1);
  const columnCount = 7;

  const listParams = useMemo(
    () => ({
      page: String(page),
      lang_id: String(langId),
      ...(searchParams.get('keyword') ? { keyword: searchParams.get('keyword')! } : {}),
      ...(searchParams.get('coupon_active') ? { coupon_active: searchParams.get('coupon_active')! } : {}),
      ...(searchParams.get('coupon_expire') ? { coupon_expire: searchParams.get('coupon_expire')! } : {}),
    }),
    [langId, page, searchParams],
  );

  useEffect(() => {
    setDraft({
      keyword: searchParams.get('keyword') ?? '',
      coupon_active: searchParams.get('coupon_active') ?? '',
      coupon_expire: searchParams.get('coupon_expire') ?? '',
    });
  }, [searchParams]);

  const load = useCallback(() => {
    if (!canView) {
      setRows([]);
      setLoading(false);
      setLoadError(lbl('LBL_UNAUTHORIZED_ACCESS', 'Unauthorized access'));
      return;
    }

    setLoading(true);
    setLoadError('');
    void adminApi
      .moduleList('coupons', listParams)
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setListMeta({
          current_page: Number(res.data.meta?.current_page ?? 1),
          per_page: Number(res.data.meta?.per_page ?? 10),
          total: Number(res.data.meta?.total ?? 0),
          last_page: Number(res.data.meta?.last_page ?? 1),
        });
      })
      .catch((err: unknown) => {
        setRows([]);
        const message =
          (err as { response?: { data?: { message?: string }; status?: number } })?.response?.data
            ?.message ??
          (err as { response?: { status?: number } })?.response?.status === 403
            ? lbl('LBL_UNAUTHORIZED_ACCESS', 'Unauthorized access')
            : lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
        setLoadError(message);
      })
      .finally(() => setLoading(false));
  }, [canView, lbl, listParams]);

  useEffect(() => {
    if (!authLoading) {
      load();
    }
  }, [authLoading, load]);

  useEffect(() => {
    let cancelled = false;
    setMeta({ title: lbl('LBL_Discount_Coupons', 'Discount coupons') });
    void adminApi.pageText('coupons', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Discount_Coupons', 'Discount coupons'),
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
    ['keyword', 'coupon_active', 'coupon_expire'].forEach((key) => {
      if (draft[key]?.trim()) {
        next.set(key, draft[key].trim());
      } else {
        next.delete(key);
      }
    });
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '', coupon_active: '', coupon_expire: '' });
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    next.delete('coupon_active');
    next.delete('coupon_expire');
    next.set('page', '1');
    setSearchParams(next);
  };

  const statusLabel = (active: number) =>
    active === 1 ? lbl('LBL_ACTIVE', 'Active') : lbl('LBL_INACTIVE', 'Inactive');

  const onDelete = async (couponId: number) => {
    if (!(await confirms.remove())) {
      return;
    }
    try {
      await adminApi.deleteCoupon(couponId);
      load();
    } catch {
      // ignore
    }
  };

  if (authLoading) {
    return (
      <main className="main">
        <div className="container">
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        </div>
      </main>
    );
  }

  if (!canView) {
    return (
      <main className="main">
        <div className="container">
          <div className="alert alert-danger">
            {lbl('LBL_UNAUTHORIZED_ACCESS', 'Unauthorized access')}
          </div>
        </div>
      </main>
    );
  }

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_Coupons', 'Coupons')}</li>
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

        {loadError ? <div className="alert alert-danger">{loadError}</div> : null}

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
                      <th>{lbl('LBL_Title', 'Title')}</th>
                      <th>{lbl('LBL_Code', 'Code')}</th>
                      <th>{lbl('LBL_Discount', 'Discount')}</th>
                      <th>{lbl('LBL_Available', 'Available')}</th>
                      <th>{lbl('LBL_Status', 'Status')}</th>
                      {canView ? <th className="align-right">{lbl('LBL_ACTION', 'Action')}</th> : null}
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
                          <td>{(page - 1) * listMeta.per_page + index + 1}</td>
                          <td>{row.coupon_title}</td>
                          <td>{row.coupon_code}</td>
                          <td>{row.coupon_discount}</td>
                          <td>{row.available}</td>
                          <td>{statusLabel(row.coupon_active)}</td>
                          {canView ? (
                            <AdminRowActionsCell
                              actions={[
                                ...(canEdit
                                  ? [
                                      {
                                        icon: 'edit',
                                        title: lbl('LBL_EDIT', 'Edit'),
                                        onClick: () => {
                                          setEditId(row.id);
                                          setFormOpen(true);
                                        },
                                      },
                                      {
                                        icon: 'delete',
                                        title: lbl('LBL_Delete', 'Delete'),
                                        onClick: () => {
                                          void onDelete(row.id);
                                        },
                                      },
                                    ]
                                  : []),
                                {
                                  icon: 'history',
                                  title: lbl('LBL_HISTORY', 'History'),
                                  to: `/admin/coupons/${row.id}/uses`,
                                  target: '_blank',
                                },
                              ]}
                            />
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

        {!loading && listMeta.last_page > 1 ? (
          <AdminLegacyPagination
            page={listMeta.current_page}
            lastPage={listMeta.last_page}
            perPage={listMeta.per_page}
            total={listMeta.total}
            onPageChange={(nextPage) => {
              const next = new URLSearchParams(searchParams);
              next.set('page', String(nextPage));
              setSearchParams(next);
            }}
            labels={{
              showing: lbl('LBL_Showing', 'Showing'),
              to: lbl('LBL_to', 'to'),
              of: lbl('LBL_of', 'of'),
              entries: lbl('LBL_Entries', 'Entries'),
            }}
          />
        ) : null}
      </div>

      <AdminCouponModal
        open={formOpen}
        couponId={editId}
        onClose={() => setFormOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
