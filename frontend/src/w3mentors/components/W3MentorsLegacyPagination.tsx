import { useSite } from '../context/SiteContext';

type Props = {
  current: number;
  last: number;
  onPage: (page: number) => void;
  linksToShow?: number;
};

function pageWindow(current: number, last: number, linksToShow: number): number[] {
  if (last <= 1) {
    return [];
  }

  let start = Math.max(1, current - Math.floor(linksToShow / 2));
  const end = Math.min(last, start + linksToShow - 1);
  start = Math.max(1, end - linksToShow + 1);

  return Array.from({ length: end - start + 1 }, (_, index) => start + index);
}

export function W3MentorsLegacyPagination({
  current,
  last,
  onPage,
  linksToShow = 3,
}: Props) {
  const { lbl } = useSite();

  if (last <= 1) {
    return null;
  }

  const pages = pageWindow(current, last, linksToShow);
  const prevTitle = lbl('LBL_Previous', 'Previous');
  const nextTitle = lbl('LBL_Next_2', 'Next');
  const firstTitle = lbl('LBL_First', 'First');
  const lastTitle = lbl('LBL_Last', 'Last');

  function navButton(
    page: number,
    className: string,
    title: string,
    disabled: boolean
  ) {
    return (
      <li key={`${className}-${page}`}>
        <button
          type="button"
          className={`${className}${disabled ? ' is-disabled' : ''}`}
          title={title}
          disabled={disabled}
          onClick={() => !disabled && onPage(page)}
        />
      </li>
    );
  }

  return (
    <div className="table-controls padding-6">
      <div className="pagination pagination--centered">
        <ul>
          {navButton(1, 'is-backward', firstTitle, current <= 1)}
          {navButton(current - 1, 'is-prev', prevTitle, current <= 1)}
          {pages.map((page) => (
            <li key={page}>
              <button
                type="button"
                className={page === current ? 'is-active' : undefined}
                onClick={() => onPage(page)}
              >
                {page}
              </button>
            </li>
          ))}
          {navButton(current + 1, 'is-next', nextTitle, current >= last)}
          {navButton(last, 'is-forward', lastTitle, current >= last)}
        </ul>
      </div>
    </div>
  );
}
