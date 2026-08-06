import { useEffect, useState } from 'react';
import type { AxiosResponse } from 'axios';
import { ADMIN_DURATION_TYPE_ALL } from '../config/adminDurationTypes';
import { AdminDashboardDurationSelect } from './AdminDashboardDurationSelect';
import { AdminDashboardRankList } from './AdminDashboardRankList';

type RankRow = { label: string; count: number };

type RankRecord = {
  language?: string;
  category?: string;
  totalsold: number;
};

type Props = {
  title: string;
  titleTag?: 'h2' | 'h3';
  className?: string;
  enabled?: boolean;
  labelKey: 'language' | 'category';
  fetchRows: (interval: number) => Promise<AxiosResponse<{ data: RankRecord[] }>>;
};

export function AdminDashboardRankCard({
  title,
  titleTag = 'h3',
  className = '',
  enabled = true,
  labelKey,
  fetchRows,
}: Props) {
  const TitleTag = titleTag;
  const [interval, setInterval] = useState(ADMIN_DURATION_TYPE_ALL);
  const [rows, setRows] = useState<RankRow[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!enabled) {
      setRows([]);
      setLoading(false);
      return;
    }

    let cancelled = false;
    setLoading(true);

    fetchRows(interval)
      .then((res) => {
        if (cancelled) return;
        setRows(
          (res.data.data ?? []).map((item) => ({
            label: String(item[labelKey] ?? ''),
            count: Number(item.totalsold) || 0,
          }))
        );
      })
      .catch(() => {
        if (!cancelled) setRows([]);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [enabled, fetchRows, interval, labelKey]);

  if (!enabled) {
    return null;
  }

  return (
    <div className={`card card-height ${className}`.trim()}>
      <div className="card-head">
        <div className="card-head-label">
          <TitleTag className="card-head-caption">{title}</TitleTag>
        </div>
        <div className="card-head-toolbar">
          <AdminDashboardDurationSelect
            value={interval}
            onChange={(nextInterval) => setInterval(nextInterval)}
          />
        </div>
      </div>
      <div className="card-table h-100">
        <AdminDashboardRankList rows={rows} loading={loading} />
      </div>
    </div>
  );
}
