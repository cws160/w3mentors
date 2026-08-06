import { Link, useSearchParams } from 'react-router-dom';
import type { BlogCategory } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

function categoryTotal(cat: BlogCategory): number {
  const childSum = (cat.children ?? []).reduce((n, c) => n + categoryTotal(c), 0);
  return (cat.post_count ?? 0) + childSum;
}

function CategoryItem({
  category,
  activeId,
  depth = 0,
}: {
  category: BlogCategory;
  activeId: number;
  depth?: number;
}) {
  const total = categoryTotal(category);
  if (total <= 0) return null;

  const children = (category.children ?? []).filter((c) => categoryTotal(c) > 0);
  const childPostSum = children.reduce((n, c) => n + (c.post_count ?? 0), 0);
  const hasDropdown = children.length > 0 && childPostSum > 0;
  const isActive = activeId === category.id;

  if (depth > 0) {
    return (
      <li className={isActive ? 'is-active' : ''}>
        <Link className="nav-vertical-link" to={`/blog?category=${category.id}`}>
          {category.name}
          {category.post_count ? ` (${category.post_count})` : ''}
        </Link>
      </li>
    );
  }

  return (
    <li
      className={[
        hasDropdown ? 'has-categories-dropdown' : '',
        isActive ? 'is-active' : '',
      ]
        .filter(Boolean)
        .join(' ')}
    >
      <Link to={`/blog?category=${category.id}`}>
        {category.name}
        {total > 0 ? ` (${total})` : ''}
      </Link>
      {hasDropdown ? (
        <>
          <span className="categories-touch-trigger cate-trigger-js" />
          <div className="has-categories-target cate-target-js">
            <nav className="nav nav--toggled d-block">
              <ul className="nav-vertical-list">
                {children.map((child) => (
                  <CategoryItem key={child.id} category={child} activeId={activeId} depth={1} />
                ))}
              </ul>
            </nav>
          </div>
        </>
      ) : null}
    </li>
  );
}

export function BlogCategoryNav({ categories }: { categories: BlogCategory[] }) {
  const { lbl } = useSite();
  const [searchParams] = useSearchParams();
  const activeId = Number(searchParams.get('category') || 0);
  const onIndex = !searchParams.get('category');

  if (!categories.length) return null;

  return (
    <section className="section section--nav p-0">
      <div className="container container--fixed">
        <span className="overlay overlay--blog blog-toggle-js" />
        <nav className="nav-categories">
          <ul>
            <li className={onIndex ? 'is-active' : ''}>
              <Link to="/blog">{lbl('LBL_All_Blogs', 'All Blogs')}</Link>
            </li>
            {categories.map((cat) => (
              <CategoryItem key={cat.id} category={cat} activeId={activeId} />
            ))}
          </ul>
        </nav>
      </div>
    </section>
  );
}
