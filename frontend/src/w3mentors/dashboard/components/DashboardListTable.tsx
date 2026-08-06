import type { ReactNode } from 'react';
import { useSite } from '../../context/SiteContext';

export type DashboardListColumn = {
  key: string;
  labelKey: string;
  labelFallback: string;
  render?: (row: Record<string, unknown>) => ReactNode;
};

type Props = {
  columns: DashboardListColumn[];
  rows: Record<string, unknown>[];
  loading: boolean;
  emptyLabelKey?: string;
  emptyLabelFallback?: string;
};

function formatCell(value: unknown): ReactNode {
  if (value === null || value === undefined || value === '') {
    return '—';
  }
  if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
  }
  return String(value);
}

export function DashboardListTable({
  columns,
  rows,
  loading,
  emptyLabelKey = 'LBL_NO_RECORDS_FOUND',
  emptyLabelFallback = 'No records found',
}: Props) {
  const { lbl } = useSite();

  if (loading) {
    return <p className="muted p-4">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  if (rows.length === 0) {
    return <p className="muted p-4">{lbl(emptyLabelKey, emptyLabelFallback)}</p>;
  }

  return (
    <div className="table-scroll">
      <table className="table table--styled">
        <thead>
          <tr>
            {columns.map((col) => (
              <th key={col.key}>{lbl(col.labelKey, col.labelFallback)}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row, index) => (
            <tr key={String(row.id ?? index)}>
              {columns.map((col) => (
                <td key={col.key}>
                  {col.render ? col.render(row) : formatCell(row[col.key])}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
