import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  page: number;
  lastPage: number;
  perPage: number;
  total: number;
  onPageChange: (page: number) => void;
  labels: {
    showing: string;
    to: string;
    of: string;
    entries: string;
  };
};

function pageRange(current: number, last: number, linksToShow = 3): number[] {
  const half = Math.floor(linksToShow / 2);
  let start = Math.max(1, current - half);
  let end = Math.min(last, start + linksToShow - 1);
  start = Math.max(1, end - linksToShow + 1);

  const pages: number[] = [];
  for (let i = start; i <= end; i += 1) {
    pages.push(i);
  }
  return pages;
}

export function AdminLegacyPagination({ page, lastPage, perPage, total, onPageChange, labels }: Props) {
  if (lastPage <= 1 || total <= 0) {
    return null;
  }

  const safePage = Math.min(Math.max(page, 1), lastPage);
  const startIdx = (safePage - 1) * perPage + 1;
  const endIdx = Math.min(total, safePage * perPage);
  const pages = pageRange(safePage, lastPage);

  return (
    <div className="section footinfo">
      <aside className="grid_1">
        <ul className="pagination">
          {safePage > 1 ? (
            <>
              <li>
                <a href="javascript:void(0)" onClick={() => onPageChange(1)}>
                  <AdminSpriteIcon icon="arrow-left" width={14} height={14} />
                  <AdminSpriteIcon icon="arrow-left" width={14} height={14} />
                </a>
              </li>
              <li>
                <a href="javascript:void(0)" onClick={() => onPageChange(safePage - 1)}>
                  <AdminSpriteIcon icon="arrow-left" width={14} height={14} />
                </a>
              </li>
            </>
          ) : null}
          {pages.map((p) => (
            <li key={p} className={p === safePage ? 'selected' : undefined}>
              {p === safePage ? (
                <a href="javascript:void(0)">{p}</a>
              ) : (
                <a href="javascript:void(0)" onClick={() => onPageChange(p)}>
                  {p}
                </a>
              )}
            </li>
          ))}
          {safePage < lastPage ? (
            <>
              <li>
                <a href="javascript:void(0)" onClick={() => onPageChange(safePage + 1)}>
                  <AdminSpriteIcon icon="arrow-right" width={14} height={14} />
                </a>
              </li>
              <li>
                <a href="javascript:void(0)" onClick={() => onPageChange(lastPage)}>
                  <AdminSpriteIcon icon="arrow-right" width={14} height={14} />
                  <AdminSpriteIcon icon="arrow-right" width={14} height={14} />
                </a>
              </li>
            </>
          ) : null}
        </ul>
      </aside>
      <aside className="grid_2">
        {labels.showing} {startIdx} {labels.to} {endIdx} {labels.of} {total} {labels.entries}
      </aside>
    </div>
  );
}
