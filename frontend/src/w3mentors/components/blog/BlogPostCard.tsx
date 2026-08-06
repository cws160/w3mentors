import { Link } from 'react-router-dom';
import type { BlogPostSummary } from '../../../api/client';
import { AFILE, imageUrl } from '../../utils/assets';
import { formatBlogDate } from '../../utils/blog';
import { BlogCategoryLinks } from './BlogCategoryLinks';

export function BlogPostCard({ post }: { post: BlogPostSummary }) {
  return (
    <div className="col-sm-6 col-lg-4">
      <div className="blog-post">
        <div className="blog-post__head">
          <div className="blog-post-media ratio ratio--16by9">
            <Link to={`/blog/${post.id}`}>
              <img
                src={imageUrl(AFILE.BLOG_POST, post.id, 'MEDIUM')}
                alt={post.title}
                loading="lazy"
              />
            </Link>
          </div>
        </div>
        <div className="blog-post__body">
          <div className="blog-post-date">{formatBlogDate(post.published_at)}</div>
          <BlogCategoryLinks categories={post.categories ?? []} />
          <h4 className="blog-post-title">
            <Link to={`/blog/${post.id}`} title={post.title}>
              {post.title}
            </Link>
          </h4>
        </div>
      </div>
    </div>
  );
}
