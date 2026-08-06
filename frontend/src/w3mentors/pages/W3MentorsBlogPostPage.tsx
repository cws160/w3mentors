import { type FormEvent, useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { blogApi, type BlogCategory, type BlogComment, type BlogPostDetail } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useSite } from '../context/SiteContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { BlogCategoryLinks } from '../components/blog/BlogCategoryLinks';
import { BlogSidePanel } from '../components/blog/BlogSidePanel';
import { AFILE, firstChar, imageUrl } from '../utils/assets';
import { formatBlogDate } from '../utils/blog';
import { normalizeLegacyHtml } from '../utils/legacyHtml';
import { useW3MentorsSliders } from '../hooks/useW3MentorsSliders';
import { bindBlogPage } from '../lib/w3mentors-ui';

export function W3MentorsBlogPostPage() {
  const { id } = useParams();
  const { lbl, languages } = useSite();
  const { user } = useAuth();
  const langId = languages[0]?.id ?? 1;

  const [post, setPost] = useState<BlogPostDetail | null>(null);
  const [categories, setCategories] = useState<BlogCategory[]>([]);
  const [comments, setComments] = useState<BlogComment[]>([]);
  const [commentsCount, setCommentsCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [commentText, setCommentText] = useState('');
  const [commentError, setCommentError] = useState('');
  const [commentSuccess, setCommentSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const postId = Number(id);

  useEffect(() => {
    if (!postId) return;
    setLoading(true);
    blogApi
      .get(postId, { lang_id: langId })
      .then((res) => {
        setPost(res.data.data);
        setCategories(res.data.categories ?? []);
        setComments(res.data.comments ?? []);
        setCommentsCount(res.data.data.comments_count ?? res.data.comments.length);
      })
      .catch(() => setPost(null))
      .finally(() => setLoading(false));
  }, [postId, langId]);

  useEffect(() => bindBlogPage(), []);

  const sliderDeps = [post?.id, post?.images?.length ?? 0];
  useW3MentorsSliders(sliderDeps);

  async function onCommentSubmit(e: FormEvent) {
    e.preventDefault();
    if (!user || !post) return;
    setCommentError('');
    setCommentSuccess('');
    setSubmitting(true);
    try {
      const res = await blogApi.postComment(post.id, commentText.trim());
      setCommentText('');
      setCommentSuccess(res.data.message);
    } catch {
      setCommentError(lbl('LBL_Something_went_wrong', 'Something went wrong'));
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  if (!post) return <W3MentorsPageMessage message={lbl('LBL_NOT_FOUND', 'Not found')} error />;

  const images =
    post.images && post.images.length > 0
      ? post.images
      : [{ id: 0, url: imageUrl(AFILE.BLOG_POST, post.id, 'LARGE', langId) }];

  const showCommentsBlock =
    (post.comment_opened && user) || commentsCount > 0 || comments.length > 0;

  return (
    <section className="section bg-grey section--blogs pb-5">
      <div className="container container--narrow">
        <div className="row g-3 g-md-5">
          <BlogSidePanel categories={categories} />
          <div className="col-xl-9">
            <div id="listItem" className="blog-search-results">
              <div className="blog-panel">
                <div className="blog-panel__head">
                  <div className="slider-single slider-single-js">
                    {images.map((img) => (
                      <div key={img.id || img.url}>
                        <div className="blog__media">
                          <img src={img.url} alt={post.title} />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
                <div className="blog-panel__body padding-6">
                  <BlogCategoryLinks
                    categories={post.categories ?? []}
                    className="blog-cate color-primary"
                  />
                  <h1 className="blog-h1">{post.title}</h1>
                  <hr />
                  <div className="row row--cols align-items-center justify-content-between">
                    <div className="col-xl-6 col-lg-5 col-md-5 col-sm-5">
                      <span className="blog__author -display-inline">
                        <div
                          className="avtar avtar--xsmall avtar--round -display-inline"
                          data-title={firstChar(post.author ?? 'A')}
                        />
                        <strong>{lbl('Lbl_By', 'By')}</strong>{' '}
                        <span className="text--dark">{post.author}</span>
                      </span>
                    </div>
                    <div className="col-xl-6 col-lg-7 col-md-7 col-sm-7 -align-right">
                      <div className="blog__actions">
                        <span>{formatBlogDate(post.published_at)}</span>
                        {showCommentsBlock ? (
                          <>
                            &nbsp;&nbsp; | &nbsp;&nbsp;
                            <a
                              href="#comments-section"
                              className="blog__action -display-inline goto-comments-js"
                              data-target="#comments-section"
                              onClick={(e) => e.preventDefault()}
                            >
                              {commentsCount}
                            </a>
                          </>
                        ) : null}
                      </div>
                    </div>
                  </div>
                  <hr />
                  <div
                    className="cms-container"
                    dangerouslySetInnerHTML={{
                      __html: normalizeLegacyHtml(post.description ?? post.excerpt ?? ''),
                    }}
                  />
                </div>
                <div className="blog-panel__footer padding-6 border-top">
                  <ul className="social--share clearfix">
                    <li className="social--fb">
                      <a
                        href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        title={lbl('LBL_FACEBOOK', 'Facebook')}
                      >
                        <img src="/images/social_01.svg" alt="" />
                      </a>
                    </li>
                    <li className="social--tw">
                      <a
                        href={`https://twitter.com/intent/tweet?url=${encodeURIComponent(window.location.href)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        title={lbl('LBL_X', 'X')}
                      >
                        <img src="/images/social_02.svg" alt="" />
                      </a>
                    </li>
                  </ul>
                </div>
              </div>

              {showCommentsBlock ? (
                <div className="blog-panel" id="comments-section">
                  <div className="blog-panel__head padding-6 border-bottom">
                    <h5>
                      {commentsCount
                        ? `${lbl('Lbl_Comments', 'Comments')}(${commentsCount})`
                        : lbl('LBL_COMMENTS', 'Comments')}
                    </h5>
                  </div>
                  <div className="blog-panel__body padding-6">
                    {comments.length > 0 ? (
                      <div className="container--repeated">
                        {comments.map((c) => (
                          <div className="comments-list" key={c.id}>
                            <div className="d-flex align-items-center gap-3">
                              <div
                                className="avtar avtar--small avtar--centered avtar--round"
                                data-title={firstChar(c.author_name)}
                              />
                              <span className="date">{formatBlogDate(c.added_on)}</span>
                              <h5>
                                <strong>{c.author_name}</strong>
                              </h5>
                            </div>
                            <div
                              className="comment__desc"
                              dangerouslySetInnerHTML={{
                                __html: normalizeLegacyHtml(c.content.replace(/\n/g, '<br />')),
                              }}
                            />
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="comment">
                        {lbl('MSG_NO_COMMENTS_ON_THIS_BLOG_POST', 'No comments on this post yet.')}
                      </div>
                    )}

                    {post.comment_opened && user ? (
                      <div id="form-comments" className="form--comments">
                        <h4>{lbl('Lbl_Post_your_comments', 'Post your comments')}</h4>
                        {commentSuccess ? (
                          <p className="text-success">{commentSuccess}</p>
                        ) : null}
                        {commentError ? <p className="text-danger">{commentError}</p> : null}
                        <form className="form" onSubmit={onCommentSubmit}>
                          <div className="row">
                            <div className="col-md-12">
                              <div className="field-set">
                                <label className="field_label">
                                  {lbl('LBL_Message', 'Message')} <span className="spn_must_field">*</span>
                                </label>
                                <textarea
                                  className="form-control"
                                  name="bpcomment_content"
                                  rows={5}
                                  required
                                  value={commentText}
                                  onChange={(e) => setCommentText(e.target.value)}
                                />
                              </div>
                            </div>
                          </div>
                          <div className="row">
                            <div className="col-md-6">
                              <div className="field-set">
                                <label className="field_label">{lbl('LBL_Name', 'Name')}</label>
                                <input
                                  type="text"
                                  className="form-control"
                                  readOnly
                                  value={user.full_name}
                                />
                              </div>
                            </div>
                            <div className="col-md-6">
                              <div className="field-set">
                                <label className="field_label">{lbl('LBL_Email', 'Email')}</label>
                                <input
                                  type="email"
                                  className="form-control"
                                  readOnly
                                  value={user.email}
                                />
                              </div>
                            </div>
                          </div>
                          <button type="submit" className="btn btn--primary" disabled={submitting}>
                            {lbl('Btn_Post_Comment', 'Post Comment')}
                          </button>
                        </form>
                      </div>
                    ) : null}
                  </div>
                </div>
              ) : null}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
