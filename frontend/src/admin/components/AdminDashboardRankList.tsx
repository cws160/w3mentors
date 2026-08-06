import { useSite } from '../../w3mentors/context/SiteContext';

type RankRow = {
  label: string;
  count: number;
};

type Props = {
  rows: RankRow[];
  loading?: boolean;
};

export function AdminDashboardRankList({ rows, loading }: Props) {
  const { lbl } = useSite();

  if (loading) {
    return (
      <div className="table-processing loaderJs h-100">
        <div className="spinner spinner--sm spinner--brand" />
      </div>
    );
  }

  if (rows.length === 0) {
    return (
      <div className="no-record-found h-100 d-flex align-items-center justify-content-center">
        {lbl('LBL_NO_RECORD_FOUND', 'No record found')}
      </div>
    );
  }

  return (
    <div className="scrollbar">
      <ul className="list list--table">
        {rows.map((row) => (
          <li key={row.label}>
            {row.label}{' '}
            <span className="count">
              {row.count} {lbl('LBL_SOLD', 'Sold')}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}
