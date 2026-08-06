import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminCurrencyFixerModal } from '../components/AdminCurrencyFixerModal';
import { AdminCurrencyModal } from '../components/AdminCurrencyModal';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type Row = {
  id: number;
  currency_name: string;
  currency_code: string;
  currency_symbol: string;
  currency_active: number;
  currency_is_default: number;
};

function formatLastSynced(lbl: (key: string, fallback?: string) => string, value: string): string {
  if (!value) {
    return '';
  }
  const formatted = value.replace('T', ' ').slice(0, 16);
  return lbl('LBL_LAST_SYNCED_ON_{datetime}', 'Last synced on {datetime}').replace('{datetime}', formatted);
}

export function AdminCurrencyManagementPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [configOpen, setConfigOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [dragId, setDragId] = useState<number | null>(null);
  const [fixerActive, setFixerActive] = useState(false);
  const [lastSynced, setLastSynced] = useState('');

  const canEdit = Boolean(privileges.canEditCurrencyManagement) || admin?.id === 1;
  const confirms = adminLegacyConfirms(lbl);
  const columnCount = canEdit ? 6 : 4;

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('currency-management', { lang_id: langId })
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setFixerActive(Number(res.data.meta?.fixer_status ?? 0) === 1);
        setLastSynced(formatLastSynced(lbl, String(res.data.meta?.fixer_last_synced ?? '')));
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [langId, lbl]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;
    setMeta({ title: lbl('LBL_Currency_Management', 'Currency management') });
    void adminApi.pageText('currency-management', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Currency_Management', 'Currency management'),
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

  const reorder = async (nextRows: Row[]) => {
    setRows(nextRows);
    try {
      await adminApi.updateCurrencyOrder(nextRows.map((row) => row.id));
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

  const onSyncRates = async () => {
    try {
      const res = await adminApi.currencySyncRates();
      setLastSynced(formatLastSynced(lbl, String(res.data.data?.last_synced ?? '')));
      load();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String(
              (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                'Unable to sync rates',
            )
          : 'Unable to sync rates';
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
            <li className="breadcrumb-item">{lbl('LBL_Currency_Management', 'Currency management')}</li>
          </ul>
          {canEdit ? (
            <div className="action-toolbar">
              {fixerActive && lastSynced ? (
                <span id="last-sync" className="-color-secondary span-right sync-rates-js">
                  {lastSynced}
                </span>
              ) : null}
              {fixerActive ? (
                <a href="javascript:void(0)" className="btn btn-primary sync-rates-js" onClick={() => void onSyncRates()}>
                  {lbl('LBL_SYNC_RATES', 'Sync rates')}
                </a>
              ) : null}
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={(e) => {
                  e.preventDefault();
                  setConfigOpen(true);
                }}
              >
                {lbl('LBL_CONFIGURATION', 'Configuration')}
              </a>
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
            </div>
          ) : null}
        </div>

        <div className="card">
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className="table table--hovered table-dragable" width="100%" id="currencyList">
                  <thead>
                    <tr>
                      {canEdit ? (
                        <th style={{ width: '5%' }}>
                          <i className="ion-arrow-move icon" />
                        </th>
                      ) : null}
                      <th>{lbl('LBL_SRNO', 'Sr. No.')}</th>
                      <th>{lbl('LBL_CURRENCY_NAME', 'Currency name')}</th>
                      <th>{lbl('LBL_CURRENCY_CODE', 'Currency code')}</th>
                      <th>{lbl('LBL_CURRENCY_SYMBOL', 'Currency symbol')}</th>
                      <th>{lbl('LBL_STATUS', 'Status')}</th>
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
                        <tr
                          key={row.id}
                          id={String(row.id)}
                          onDragOver={(e) => {
                            if (canEdit && row.currency_active === 1) {
                              e.preventDefault();
                            }
                          }}
                          onDrop={(e) => {
                            e.preventDefault();
                            if (canEdit && row.currency_active === 1) {
                              onDrop(row.id);
                            }
                          }}
                        >
                          {canEdit ? (
                            <td className={row.currency_active === 1 ? 'dragHandle' : undefined}>
                              {row.currency_active === 1 ? (
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
                          <td>
                            {row.currency_name || row.currency_code}
                            {row.currency_is_default === 1 ? (
                              <>
                                <br />
                                <small>[{lbl('LBL_THIS_IS_YOUR_DEFAULT_CURRENCY', 'This is your default currency')}]</small>
                              </>
                            ) : null}
                          </td>
                          <td>{row.currency_code}</td>
                          <td>{row.currency_symbol}</td>
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.currency_active === 1}
                              disabled={!canEdit || row.currency_is_default === 1}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              confirmMessage={confirms.updateStatus}
                              onToggle={async (next) => {
                                await adminApi.updateCurrencyStatus(row.id, next);
                                load();
                              }}
                            />
                          </td>
                          {canEdit ? (
                            <AdminRowActionsCell
                              actions={[
                                {
                                  icon: 'edit',
                                  title: lbl('LBL_EDIT', 'Edit'),
                                  onClick: () => {
                                    setEditId(row.id);
                                    setModalOpen(true);
                                  },
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
      </div>

      <AdminCurrencyModal
        open={modalOpen}
        currencyId={editId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />

      <AdminCurrencyFixerModal
        open={configOpen}
        onClose={() => setConfigOpen(false)}
        onSaved={(synced) => {
          setLastSynced(formatLastSynced(lbl, synced));
          setFixerActive(true);
          load();
        }}
      />
    </main>
  );
}
