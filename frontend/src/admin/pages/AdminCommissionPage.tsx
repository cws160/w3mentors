import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { ADMIN_MODULE_CONFIGS } from '../config/adminModules';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminCommissionModal } from '../components/AdminCommissionModal';
import { AdminLegacyFilterCard } from '../components/AdminLegacyFilterCard';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { AdminModuleSearchForm } from '../components/AdminModuleSearchForm';
import { AdminSpriteIcon } from '../components/AdminSpriteIcon';

type Row = {
  id: number;
  user_id: number;
  is_global: boolean;
  teacher_name: string;
  comm_lessons: string;
  comm_classes: string;
  comm_courses: string;
};

const config = ADMIN_MODULE_CONFIGS.commission;

export function AdminCommissionPage() {
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
  const [meta, setListMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    classes_enabled: true,
    courses_enabled: true,
  });

  const canEdit = Boolean(privileges.canEditCommissionSettings);
  const page = Number(searchParams.get('page') ?? 1);

  const listParams = useMemo(
    () => ({
      page: String(page),
      lang_id: String(langId),
      ...(searchParams.get('keyword') ? { keyword: searchParams.get('keyword')! } : {}),
    }),
    [langId, page, searchParams],
  );

  const columnCount =
    3 + (meta.classes_enabled ? 1 : 0) + (meta.courses_enabled ? 1 : 0) + (canEdit ? 1 : 0);

  useEffect(() => {
    setDraft({ keyword: searchParams.get('keyword') ?? '' });
  }, [searchParams]);

  const load = useCallback(() => {
    setLoading(true);
    void adminApi
      .moduleList('commission', listParams)
      .then((res) => {
        setRows((res.data.data ?? []) as Row[]);
        setListMeta({
          current_page: Number(res.data.meta?.current_page ?? 1),
          per_page: Number(res.data.meta?.per_page ?? 10),
          total: Number(res.data.meta?.total ?? 0),
          last_page: Number(res.data.meta?.last_page ?? 1),
          classes_enabled: Boolean(res.data.meta?.classes_enabled ?? true),
          courses_enabled: Boolean(res.data.meta?.courses_enabled ?? true),
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
    setMeta({ title: lbl('LBL_Commission_Settings', 'Commission settings') });
    void adminApi.pageText('commission', langId).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_Commission_Settings', 'Commission settings'),
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
    setSearchParams(next);
  };

  const onClear = () => {
    setDraft({ keyword: '' });
    const next = new URLSearchParams(searchParams);
    next.delete('keyword');
    next.set('page', '1');
    setSearchParams(next);
  };

  const teacherCell = (row: Row) => {
    if (row.is_global) {
      return (
        <span className="label label-success">{lbl('LBL_GLOBAL_COMMISSION', 'Global commission')}</span>
      );
    }
    return row.teacher_name || lbl('LBL_NA', 'NA');
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_Commission', 'Commission')}</li>
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
                      <th>{lbl('LBL_TEACHER', 'Teacher')}</th>
                      <th>{lbl('LBL_LESSON_FEES_[%]', 'Lesson fees [%]')}</th>
                      {meta.classes_enabled ? (
                        <th>{lbl('LBL_CLASS_FEES_[%]', 'Class fees [%]')}</th>
                      ) : null}
                      {meta.courses_enabled ? (
                        <th>{lbl('LBL_COURSES_FEES_[%]', 'Courses fees [%]')}</th>
                      ) : null}
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
                          <td>{teacherCell(row)}</td>
                          <td>{row.comm_lessons}</td>
                          {meta.classes_enabled ? <td>{row.comm_classes}</td> : null}
                          {meta.courses_enabled ? <td>{row.comm_courses}</td> : null}
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
                                <li>
                                  <Link
                                    to={`/admin/commission/${row.user_id}/history`}
                                    title={lbl('LBL_HISTORY', 'History')}
                                  >
                                    <AdminSpriteIcon icon="history" />
                                  </Link>
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

        {!loading && meta.last_page > 1 ? (
          <AdminLegacyPagination
            page={meta.current_page}
            pageCount={meta.last_page}
            recordCount={meta.total}
            pageSize={meta.per_page}
            onPageChange={(nextPage) => {
              const next = new URLSearchParams(searchParams);
              next.set('page', String(nextPage));
              setSearchParams(next);
            }}
          />
        ) : null}
      </div>

      <AdminCommissionModal
        open={formOpen}
        commissionId={editId}
        onClose={() => setFormOpen(false)}
        onSaved={load}
      />
    </main>
  );
}
