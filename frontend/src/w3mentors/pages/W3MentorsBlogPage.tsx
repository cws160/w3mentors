import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { blogApi, type BlogCategory, type BlogPostSummary } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { W3MentorsLegacyPagination } from '../components/W3MentorsLegacyPagination';
import { SpriteIcon } from '../components/SpriteIcon';
import { BlogCategoryNav } from '../components/blog/BlogCategoryNav';
import { BlogPostCard } from '../components/blog/BlogPostCard';
import { bindBlogPage } from '../lib/w3mentors-ui';

export function W3MentorsBlogPage() {
  const { lbl, languages } = useSite();
  const [searchParams, setSearchParams] = useSearchParams();
  const categoryId = Number(searchParams.get('category') || 0);
  const keyword = searchParams.get('search') ?? '';
  const page = Number(searchParams.get('page') || 1);
  const langId = languages[0]?.id ?? 1;

  const [posts, setPosts] = useState<BlogPostSummary[]>([]);
  const [categories, setCategories] = useState<BlogCategory[]>([]);
  const [categoryName, setCategoryName] = useState('');
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [searchInput, setSearchInput] = useState(keyword);

  useEffect(() => {
    setSearchInput(keyword);
  }, [keyword]);

  useEffect(() => {
    setLoading(true);
    blogApi
      .list({
        search: keyword || undefined,
        category: categoryId || undefined,
        page,
        lang_id: langId,
      })
      .then((res) => {
        setPosts(res.data.data);
        setCategories(res.data.categories ?? []);
        setCategoryName(res.data.category_name ?? '');
        setLastPage(res.data.meta.last_page);
      })
      .catch(() => {
        setPosts([]);
        setCategories([]);
      })
      .finally(() => setLoading(false));
  }, [keyword, categoryId, page, langId]);

  useEffect(() => bindBlogPage(), []);

  const breadcrumbLabel = categoryName || lbl('LBL_All', 'All');

  function submitSearch(e: React.FormEvent) {
    e.preventDefault();
    const next = new URLSearchParams(searchParams);
    if (searchInput.trim()) {
      next.set('search', searchInput.trim());
    } else {
      next.delete('search');
    }
    next.delete('page');
    setSearchParams(next);
  }

  function clearKeyword() {
    setSearchInput('');
    const next = new URLSearchParams(searchParams);
    next.delete('search');
    next.delete('page');
    setSearchParams(next);
  }

  return (
    <>
      <section className="forum-header section bg-gradiant section--page-header text-center">
        <div className="container container--narrow">
          <h1>{lbl('LBL_Blog', 'Blog')}</h1>
          <p className="p-large">
            {lbl('LBL_The_place_where_we_write_some_words', 'Stories and updates')}
          </p>
          <div className="main-search d-flex gap-4">
            <form className="flex-1" onSubmit={submitSearch}>
              <div className="main-search__field">
                <input
                  type="text"
                  name="keyword"
                  className="blog-keyword"
                  placeholder={lbl('LBL_Blog_Search', 'Search blog')}
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                />
              </div>
              <span className="main-search__action">
                <button type="submit" className="main-search__submit">
                  <SpriteIcon id="search" className="icon icon--search" />
                </button>
                {keyword ? (
                  <div
                    className="main-search__reset"
                    style={{ display: 'block' }}
                    title="Reset"
                    role="button"
                    tabIndex={0}
                    onClick={clearKeyword}
                    onKeyDown={(e) => e.key === 'Enter' && clearKeyword()}
                  >
                    <span className="close" />
                  </div>
                ) : null}
              </span>
            </form>
            <a
              href="#!"
              className="blog-toggle blog-toggle-js d-xl-none"
              onClick={(e) => e.preventDefault()}
            >
              <span />
            </a>
          </div>
        </div>
      </section>

      <BlogCategoryNav categories={categories} />

      <section className="section section--blogs pt-0">
        <div className="container container--narrow">
          <div className="row">
            <div className="container">
              <div className="breadcrumb-list pb-0">
                <ul>
                  <li>
                    <Link to="/">{lbl('LBL_Home', 'Home')}</Link>
                  </li>
                  <li>
                    <Link to="/blog">{lbl('LBL_Blog', 'Blog')}</Link>
                  </li>
                  <li>{breadcrumbLabel}</li>
                </ul>
              </div>
            </div>
          </div>

          {loading ? (
            <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />
          ) : posts.length === 0 ? (
            <div className="box -padding-30" style={{ marginBottom: 30 }}>
              <div className="message-display">
                <h5>{lbl('LBL_No_Result_Found!!', 'No results found')}</h5>
              </div>
            </div>
          ) : (
            <>
              <div className="row g-3 g-md-5">
                {posts.map((post) => (
                  <BlogPostCard key={post.id} post={post} />
                ))}
              </div>
              <W3MentorsLegacyPagination
                current={page}
                last={lastPage}
                onPage={(p) => {
                  const next = new URLSearchParams(searchParams);
                  next.set('page', String(p));
                  setSearchParams(next);
                }}
              />
            </>
          )}
        </div>
      </section>
    </>
  );
}
