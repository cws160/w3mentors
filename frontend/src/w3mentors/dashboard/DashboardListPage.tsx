import { useCallback, useEffect, useState } from 'react';
import { dashboardApi, type Paginated } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { DashboardListTable, type DashboardListColumn } from './components/DashboardListTable';
import { DashboardStandardPage } from './components/DashboardStandardPage';

export type DashboardListConfig = {
  endpoint: string;
  titleKey: string;
  titleFallback: string;
  columns: DashboardListColumn[];
  teacherOnly?: boolean;
  learnerOnly?: boolean;
  emptyLabelKey?: string;
  emptyLabelFallback?: string;
};

type Props = {
  config: DashboardListConfig;
};

export function DashboardListPage({ config }: Props) {
  const { lbl } = useSite();
  const [rows, setRows] = useState<Record<string, unknown>[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);

  const load = useCallback(() => {
    setLoading(true);
    dashboardApi
      .list(config.endpoint, { page })
      .then((res) => {
        setRows(res.data.data as Record<string, unknown>[]);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [config.endpoint, page]);

  useEffect(() => {
    load();
  }, [load]);

  const title = lbl(config.titleKey, config.titleFallback);
  const lastPage = meta?.last_page ?? 1;

  const pagination =
    meta && meta.total > meta.per_page ? (
      <div className="d-flex gap-2 align-items-center justify-content-end mt-3">
        <button
          type="button"
          className="btn btn--bordered color-secondary btn--small"
          disabled={page <= 1 || loading}
          onClick={() => setPage((p) => Math.max(1, p - 1))}
        >
          {lbl('LBL_PREVIOUS', 'Previous')}
        </button>
        <span className="small color-secondary">
          {page} / {lastPage}
        </span>
        <button
          type="button"
          className="btn btn--secondary btn--small"
          disabled={page >= lastPage || loading}
          onClick={() => setPage((p) => p + 1)}
        >
          {lbl('LBL_NEXT', 'Next')}
        </button>
      </div>
    ) : null;

  return (
    <DashboardStandardPage title={title}>
      <DashboardListTable
        columns={config.columns}
        rows={rows}
        loading={loading}
        emptyLabelKey={config.emptyLabelKey}
        emptyLabelFallback={config.emptyLabelFallback}
      />
      {pagination}
    </DashboardStandardPage>
  );
}
