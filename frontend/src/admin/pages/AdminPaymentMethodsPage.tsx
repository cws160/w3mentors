import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminPaymentMethodSettingsModal } from '../components/AdminPaymentMethodSettingsModal';
import { AdminPaymentMethodTxnFeeModal } from '../components/AdminPaymentMethodTxnFeeModal';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type Row = {
  id: number;
  code: string;
  type: number;
  type_label: string;
  active: number;
  is_wallet: boolean;
  has_settings: boolean;
  has_fees: boolean;
  can_toggle_status: boolean;
};

const config = ADMIN_MODULE_CONFIGS['payment-methods'];
const TYPE_PAYOUT = 2;

export function AdminPaymentMethodsPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin, loading: authLoading } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [dragId, setDragId] = useState<number | null>(null);
  const [settingsOpen, setSettingsOpen] = useState(false);
  const [settingsId, setSettingsId] = useState(0);
  const [txnfeeOpen, setTxnfeeOpen] = useState(false);
  const [txnfeeId, setTxnfeeId] = useState(0);
  const [draft, setDraft] = useState<Record<string, string>>({
    keyword: searchParams.get('keyword') ?? '',
  });

  const canView = Boolean(privileges.canViewPaymentMethods) || admin?.id === 1;
  const canEdit = Boolean(privileges.canEditPaymentMethods) || admin?.id === 1;
  const confirms = adminLegacyConfirms(lbl);
  const columnCount = (canEdit ? 1 : 0) + 5 + (canEdit ? 1 : 0);

  const listParams = useMemo(
    () => ({
      lang_id: String(langId),
      ...(searchParams.get('keyword') ? { keyword: searchParams.get('keyword')! } : {}),
    }),
    [langId, searchParams],
  );

  useEffect(() => {
    setDraft({ keyword: searchParams.get('keyword') ?? '' });
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
      .moduleList('payment-methods', listParams)
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
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
    setMeta({ title: lbl('LBL_Payment_Methods', 'Payment methods') });
    void adminApi.pageText('payment-methods', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Payment_Methods', 'Payment methods'),
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
    setSearchParams(next);
  };

  const reorder = async (nextRows: Row[]) => {
    setRows(nextRows);
    try {
      await adminApi.updatePaymentMethodOrder(nextRows.map((row) => row.id));
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
    const fromRow = rows[fromIndex];
    const toRow = rows[toIndex];
    if (fromRow.active !== 1 || toRow.active !== 1) {
      return;
    }
    const next = [...rows];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);
    setDragId(null);
    void reorder(next);
  };

  const onStatusToggle = async (row: Row, next: boolean) => {
    try {
      await adminApi.updatePaymentMethodStatus(row.id, next);
      load();
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      window.alert(message);
    }
  };

  const methodName = (code: string) => lbl(`LBL_${code}`, code);

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
            <li className="breadcrumb-item">{lbl('LBL_Payment_Methods', 'Payment methods')}</li>
          </ul>
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
                <table className="table table--hovered table-dragable" width="100%" id="paymentMethod">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                      <th>{lbl('LBL_Payment_Method_Code', 'Payment method code')}</th>
                      <th>{lbl('LBL_Payment_Method_Name', 'Payment method name')}</th>
                      <th>{lbl('LBL_Type', 'Type')}</th>
                      <th>{lbl('LBL_Status', 'Status')}</th>
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
                      rows.map((row, index) => {
                        const actions = [];
                        if (canEdit && row.has_settings) {
                          actions.push({
                            icon: 'edit',
                            title: lbl('LBL_Settings', 'Settings'),
                            onClick: () => {
                              setSettingsId(row.id);
                              setSettingsOpen(true);
                            },
                          });
                        }
                        if (canEdit && row.has_fees && row.type === TYPE_PAYOUT) {
                          actions.push({
                            icon: 'sync-currency',
                            title: lbl('LBL_TXN_FEE', 'Txn fee'),
                            onClick: () => {
                              setTxnfeeId(row.id);
                              setTxnfeeOpen(true);
                            },
                          });
                        }

                        return (
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
                            <td>{row.code}</td>
                            <td>{methodName(row.code)}</td>
                            <td>{row.type_label}</td>
                            <td>
                              {row.can_toggle_status ? (
                                <AdminStatusSwitch
                                  id={row.id}
                                  active={row.active === 1}
                                  disabled={!canEdit}
                                  activeLabel={lbl('LBL_ACTIVE', 'Active')}
                                  inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                                  confirmMessage={confirms.updateStatus}
                                  onToggle={(next) => onStatusToggle(row, next)}
                                />
                              ) : row.active === 1 ? (
                                lbl('LBL_ACTIVE', 'Active')
                              ) : (
                                lbl('LBL_INACTIVE', 'Inactive')
                              )}
                            </td>
                            {canEdit ? (
                              actions.length > 0 ? (
                                <AdminRowActionsCell actions={actions} />
                              ) : (
                                <td className="align-right" />
                              )
                            ) : null}
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
      </div>

      <AdminPaymentMethodSettingsModal
        open={settingsOpen}
        methodId={settingsId}
        onClose={() => setSettingsOpen(false)}
        onSaved={load}
      />
      <AdminPaymentMethodTxnFeeModal
        open={txnfeeOpen}
        methodId={txnfeeId}
        onClose={() => setTxnfeeOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
