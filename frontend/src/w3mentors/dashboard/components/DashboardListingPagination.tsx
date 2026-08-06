import { useSite } from '../../context/SiteContext';

type Meta = {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
};

type Props = {
  meta: Meta | null;
  page: number;
  loading?: boolean;
  onPageChange: (page: number) => void;
};

export function DashboardListingPagination({ meta, page, loading, onPageChange }: Props) {
  const { lbl } = useSite();

  if (!meta || meta.total <= meta.per_page) {
    return null;
  }

  return (
    <nav className="pagination justify-content-end mt-4" aria-label="Pagination">
      <button
        type="button"
        className="btn btn--bordered color-secondary btn--small me-2"
        disabled={page <= 1 || loading}
        onClick={() => onPageChange(page - 1)}
      >
        {lbl('LBL_PREVIOUS', 'Previous')}
      </button>
      <span className="small color-secondary align-self-center me-2">
        {page} / {meta.last_page}
      </span>
      <button
        type="button"
        className="btn btn--secondary btn--small"
        disabled={page >= meta.last_page || loading}
        onClick={() => onPageChange(page + 1)}
      >
        {lbl('LBL_NEXT', 'Next')}
      </button>
    </nav>
  );
}
