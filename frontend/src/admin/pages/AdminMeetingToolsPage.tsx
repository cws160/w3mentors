import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminMeetingToolModal } from '../components/AdminMeetingToolModal';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminRowActionsCell } from '../components/AdminRowActions';
import { AdminStatusSwitch } from '../components/AdminStatusSwitch';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';

type Row = {
  id: number;
  code: string;
  info: string;
  status: number;
  active: number;
  can_toggle_status: boolean;
};

const config = ADMIN_MODULE_CONFIGS['meeting-tools'];

export function AdminMeetingToolsPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin, loading: authLoading } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [searchParams, setSearchParams] = useSearchParams();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [editId, setEditId] = useState(0);
  const [listMeta, setListMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });
  const [draft, setDraft] = useState<Record<string, string>>({
    keyword: searchParams.get('keyword') ?? '',
    metool_status: searchParams.get('metool_status') ?? '',
  });

  const canView = Boolean(privileges.canViewMeetingTool) || admin?.id === 1;
  const canEdit = Boolean(privileges.canEditMeetingTool) || admin?.id === 1;
  const confirms = adminLegacyConfirms(lbl);
  const page = Number(searchParams.get('page') ?? 1);
  const columnCount = 4 + (canEdit ? 1 : 0);

  const listParams = useMemo(
    () => ({
      page: String(page),
      lang_id: String(langId),
      ...(searchParams.get('keyword') ? { keyword: searchParams.get('keyword')! } : {}),
      ...(searchParams.get('metool_status') ? { metool_status: searchParams.get('metool_status')! } : {}),
    }),
    [langId, page, searchParams],
  );

  useEffect(() => {
    setDraft({
      keyword: searchParams.get('keyword') ?? '',
      metool_status: searchParams.get('metool_status') ?? '',
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
      .moduleList('meeting-tools', listParams)
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
    setMeta({ title: lbl('LBL_Meeting_Tools', 'Meeting tools') });
    void adminApi.pageText('meeting-tools', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Meeting_Tools', 'Meeting tools'),
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
    if (draft.metool_status?.trim()) {
      next.set('metool_status', draft.metool_status.trim());
    } else {
      next.delete('metool_status');
    }
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '', metool_status: '' });
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    next.delete('metool_status');
    next.set('page', '1');
    setSearchParams(next);
  };

  const onStatusToggle = async (row: Row) => {
    try {
      await adminApi.updateMeetingToolStatus(row.id, true);
      load();
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      window.alert(message);
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
            <li className="breadcrumb-item">{lbl('LBL_Meeting_Tools', 'Meeting tools')}</li>
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
                <table className="table table--hovered" width="100%">
                  <thead>
                    <tr>
                      <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                      <th>{lbl('LBL_code', 'Code')}</th>
                      <th>{lbl('LBL_INFO', 'Info')}</th>
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
                      rows.map((row, index) => (
                        <tr key={row.id}>
                          <td>{(listMeta.current_page - 1) * listMeta.per_page + index + 1}</td>
                          <td>{row.code}</td>
                          <td style={{ maxWidth: 400 }}>
                            {row.info ? (
                              <span dangerouslySetInnerHTML={{ __html: row.info }} />
                            ) : (
                              '—'
                            )}
                          </td>
                          <td>
                            <AdminStatusSwitch
                              id={row.id}
                              active={row.status === 1}
                              disabled={!canEdit || !row.can_toggle_status}
                              activeLabel={lbl('LBL_ACTIVE', 'Active')}
                              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
                              confirmMessage={confirms.updateStatus}
                              onToggle={() => onStatusToggle(row)}
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

        {listMeta.last_page > 1 ? (
          <AdminLegacyPagination
            page={listMeta.current_page}
            lastPage={listMeta.last_page}
            perPage={listMeta.per_page}
            total={listMeta.total}
            labels={{
              previous: lbl('LBL_PREVIOUS', 'Previous'),
              next: lbl('LBL_NEXT', 'Next'),
            }}
            onPageChange={(nextPage) => {
              const next = new URLSearchParams(searchParams);
              next.set('page', String(nextPage));
              setSearchParams(next);
            }}
          />
        ) : null}
      </div>

      <AdminMeetingToolModal
        open={modalOpen}
        toolId={editId}
        onClose={() => setModalOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
