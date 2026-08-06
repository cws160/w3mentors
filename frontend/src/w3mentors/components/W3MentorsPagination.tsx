type Props = {
  current: number;
  last: number;
  onPage: (page: number) => void;
};

export function W3MentorsPagination({ current, last, onPage }: Props) {
  if (last <= 1) return null;

  const pages = Array.from({ length: last }, (_, i) => i + 1);

  return (
    <div className="pagination pagination--centered mt-5">
      <ul className="pagination-list">
        <li>
          <button
            type="button"
            className="pagination-list__item"
            disabled={current <= 1}
            onClick={() => onPage(current - 1)}
          >
            ‹
          </button>
        </li>
        {pages.map((page) => (
          <li key={page}>
            <button
              type="button"
              className={`pagination-list__item ${page === current ? 'is-active' : ''}`}
              onClick={() => onPage(page)}
            >
              {page}
            </button>
          </li>
        ))}
        <li>
          <button
            type="button"
            className="pagination-list__item"
            disabled={current >= last}
            onClick={() => onPage(current + 1)}
          >
            ›
          </button>
        </li>
      </ul>
    </div>
  );
}
