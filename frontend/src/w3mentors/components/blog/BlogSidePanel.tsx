import { Link } from 'react-router-dom';
import type { BlogCategory } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { SpriteIcon } from '../SpriteIcon';

function SidebarCategory({ cat }: { cat: BlogCategory }) {
  const hasChildren = (cat.children?.length ?? 0) > 0;

  return (
    <li className={`nav-vertical-item ${hasChildren ? 'nav-vertical-has-child' : ''}`}>
      <Link className="nav-vertical-link" to={`/blog?category=${cat.id}`}>
        {cat.name}
        {cat.post_count ? `(${cat.post_count})` : ''}
      </Link>
      {hasChildren ? (
        <>
          <span className="nav-vertical-trigger nav-dropdown-trigger-js" />
          <div className="nav-vertical-target nav-dropdown-target-js">
            <ul>
              {(cat.children ?? []).map((child) => (
                <li key={child.id}>
                  <Link to={`/blog?category=${child.id}`}>
                    {child.name}
                    {child.post_count ? `(${child.post_count})` : ''}
                  </Link>
                  {(child.children?.length ?? 0) > 0 ? (
                    <ul>
                      {(child.children ?? []).map((sub) => (
                        <li key={sub.id}>
                          <Link to={`/blog?category=${sub.id}`}>{sub.name}</Link>
                        </li>
                      ))}
                    </ul>
                  ) : null}
                </li>
              ))}
            </ul>
          </div>
        </>
      ) : null}
    </li>
  );
}

export function BlogSidePanel({ categories }: { categories: BlogCategory[] }) {
  const { lbl } = useSite();

  return (
    <div className="col-xl-3 order-xl-2">
      <div className="sticky-section" id="STICKY">
        <div className="form-search form-search--blog">
          <div className="d-flex gap-4">
            <form
              className="flex-1"
              onSubmit={(e) => {
                e.preventDefault();
                const input = (e.target as HTMLFormElement).elements.namedItem(
                  'keyword'
                ) as HTMLInputElement | null;
                if (input?.value.trim()) {
                  window.location.href = `/blog?search=${encodeURIComponent(input.value.trim())}`;
                }
              }}
            >
              <div className="form__element">
                <input
                  className="form__input blog-post-keyword"
                  placeholder={lbl('LBL_BLOG_SEARCHS', 'Search blog')}
                  name="keyword"
                  type="text"
                />
                <span className="form__action-wrap main-search__submit">
                  <input className="form__action" value="" type="submit" readOnly />
                  <SpriteIcon id="search" className="svg-icon" />
                </span>
              </div>
            </form>
            <a href="#!" className="blog-toggle blog-toggle-js d-xl-none" onClick={(e) => e.preventDefault()}>
              <span />
            </a>
          </div>
        </div>
        <div className="box box--cta box--cta-blog padding-8 border align-center mt-4">
          <div className="-hide-mobile">
            <h4 className="-text-bold -color-secondary">{lbl('Lbl_Write_For_Us', 'Write For Us')}</h4>
            <p>
              {lbl(
                'Lbl_We_are_constantly_looking_for_writers_and_contributors_to_help_us_create_great_content_for_our_blog_visitors.',
                'We are constantly looking for writers and contributors.'
              )}
            </p>
          </div>
          <Link to="/blog/contribute" className="btn btn--secondary btn--block btn--large">
            {lbl('Lbl_Contribute', 'Contribute')}
          </Link>
        </div>
        <div className="blog-sidebar">
          <span className="overlay overlay--blog blog-toggle-js" />
          <div className="blog-filters">
            <div className="box border">
              <div className="box__head border-bottom">
                <h5>{lbl('Lbl_Categories', 'Categories')}</h5>
              </div>
              <div className="box__body">
                <div className="box-scroller">
                  <nav className="nav nav--toggled nav--toggled-js d-block">
                    {categories.length > 0 ? (
                      <ul className="nav-vertical-list">
                        {categories.map((cat) => (
                          <SidebarCategory key={cat.id} cat={cat} />
                        ))}
                      </ul>
                    ) : null}
                  </nav>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
