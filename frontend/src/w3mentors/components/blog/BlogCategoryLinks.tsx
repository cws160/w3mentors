import { Link } from 'react-router-dom';
import type { BlogCategoryLink } from '../../utils/blog';

export function BlogCategoryLinks({
  categories,
  className = 'blog-post-category',
}: {
  categories: BlogCategoryLink[];
  className?: string;
}) {
  if (!categories.length) return null;
  const last = categories[categories.length - 1];
  const Tag = className?.includes('blog-cate') ? 'span' : 'div';

  return (
    <Tag className={className}>
      {categories.map((cat) => (
        <span key={cat.id}>
          <Link to={`/blog?category=${cat.id}`} className="text--dark">
            {cat.name}
          </Link>
          {cat.id !== last.id ? ', ' : null}
        </span>
      ))}
    </Tag>
  );
}
