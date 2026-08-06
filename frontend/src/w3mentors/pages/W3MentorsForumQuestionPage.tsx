import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
  forumApi,
  type ForumComment,
  type ForumMeta,
  type ForumQuestion,
} from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useSite } from '../context/SiteContext';
import { ForumCommentCard } from '../components/forum/ForumCommentCard';
import { ForumSidebar } from '../components/forum/ForumSidebar';
import { ForumVoteBlock } from '../components/forum/ForumVoteBlock';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { useAuthModals } from '../hooks/useAuthModals';
import { useForumStyles } from '../hooks/useForumStyles';
import { bindForumDetailPage } from '../lib/w3mentors-ui';
import { AFILE, firstChar, imageUrl } from '../utils/assets';
import { normalizeLegacyHtml } from '../utils/legacyHtml';
import { forumTagHref } from '../utils/forum';

const STATUS_PUBLISHED = 1;

type SortOption = 'latest' | 'most_liked';

export function W3MentorsForumQuestionPage() {
  useForumStyles();
  const { slug } = useParams();
  const { lbl } = useSite();
  const { user } = useAuth();
  const { openLoginModal } = useAuthModals();
  const navigate = useNavigate();

  const [question, setQuestion] = useState<ForumQuestion | null>(null);
  const [comments, setComments] = useState<ForumComment[]>([]);
  const [sidebar, setSidebar] = useState<
    Pick<ForumMeta, 'popular_tags' | 'top_teachers' | 'recommended_posts'> | null
  >(null);
  const [sort, setSort] = useState<SortOption>('latest');
  const [sortLabel, setSortLabel] = useState('');
  const [commentDraft, setCommentDraft] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const requireLogin = useCallback(() => openLoginModal(), [openLoginModal]);
  const onAskQuestion = useCallback(() => {
    if (!user) openLoginModal();
    else navigate('/dashboard');
  }, [user, openLoginModal, navigate]);

  const loadQuestion = useCallback(
    (sortBy: SortOption) => {
      if (!slug) return;
      setLoading(true);
      setError('');
      forumApi
        .show(slug, { sort: sortBy })
        .then((res) => {
          setQuestion(res.data.data);
          setComments(res.data.comments);
          setSidebar(res.data.sidebar);
        })
        .catch(() => setError(lbl('LBL_Something_went_wrong', 'Question not found.')))
        .finally(() => setLoading(false));
    },
    [slug, lbl]
  );

  useEffect(() => {
    loadQuestion(sort);
  }, [loadQuestion, sort]);

  useEffect(() => {
    setSortLabel(lbl('LBL_Newest_First', 'Newest first'));
  }, [lbl]);

  useEffect(() => bindForumDetailPage(), []);

  function onSortChange(option: SortOption) {
    setSort(option);
    setSortLabel(
      option === 'most_liked'
        ? lbl('LBL_Most_Liked', 'Most liked')
        : lbl('LBL_Newest_First', 'Newest first')
    );
    document.querySelectorAll<HTMLElement>('.sorting-target-js').forEach((el) => {
      el.style.display = 'none';
    });
  }

  function onCommentSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!user) {
      requireLogin();
      return;
    }
    setCommentDraft('');
    document.querySelectorAll<HTMLElement>('.comment-target-js').forEach((el) => {
      el.style.display = 'none';
    });
    document.querySelectorAll('.comment-trigger-js').forEach((el) => {
      el.classList.remove('is-active');
    });
  }

  if (loading && !question) {
    return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  }
  if (error || !question) {
    return <W3MentorsPageMessage message={error || 'Not found'} error />;
  }

  const voteTone = question.vote_tone as '' | 'success' | 'danger';
  const authorId = question.author.id;
  const isOwner = user?.id === authorId;
  const canComment = question.status === STATUS_PUBLISHED && question.comments_allowed;
  const showCommentsBlock = question.comments_allowed || question.comments > 0;
  const shareUrl = typeof window !== 'undefined' ? window.location.href : '';

  return (
    <section className="forum-body bg-grey">
      <div className="container container--narrow">
        <section className="flex-panel">
          <div className="flex-panel__large">
            <article
              className="article-panel"
              id="maindv__js"
              data-owner_id={authorId}
              data-luser_id={user?.id ?? 0}
            >
              <div className="article-grid">
                <div className="article-grid__left">
                  <ForumVoteBlock
                    recordId={question.id}
                    reactType={1}
                    likes={question.likes}
                    dislikes={question.dislikes}
                    voteScore={question.vote_score}
                    voteTone={voteTone}
                    onVote={requireLogin}
                    emptyLabel={lbl('LBL_Awaiting_Best_Answer', 'Awaiting best answer')}
                    upLabel={lbl('LBL_Vote_this_question_up', 'Vote this question up')}
                    downLabel={lbl('LBL_Vote_this_question_down', 'Vote this question down')}
                    upvotesLabel={lbl('LBL_Upvotes', 'Upvotes')}
                    downvotesLabel={lbl('LBL_Downvotes', 'Downvotes')}
                  />
                </div>
                <div className="article-grid__right">
                  <div className="article-content">
                    <h1 className="article-title mb-3 bold-700">{question.title}</h1>
                    <div
                      className="cms-container forum-editor"
                      dangerouslySetInnerHTML={{
                        __html: normalizeLegacyHtml(question.description),
                      }}
                    />
                  </div>
                  {question.tags && question.tags.length > 0 && (
                    <div className="tags">
                      <div className="tags__overflow">
                        {question.tags.map((tag) => (
                          <Link
                            key={tag.id}
                            to={forumTagHref(tag)}
                            className="tags__item badge badge--curve color-primary"
                          >
                            {tag.name}
                          </Link>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              <div className="article-stats">
                <div className="article-stats__left">
                  <div className="article-author">
                    <figure className="article-author__avatar">
                      <div
                        className="avtar avtar--xsmall avtar--round bg-gray-500"
                        data-title={firstChar(
                          question.author.first_name ?? question.author.full_name
                        )}
                      >
                        <img
                          src={imageUrl(AFILE.USER_PROFILE, question.author.id, 'SMALL')}
                          alt={question.author.full_name}
                          title={question.author.first_name}
                        />
                      </div>
                    </figure>
                    <div className="article-author__content">
                      <span>
                        {lbl('LBL_Asked_By:', 'Asked by:')}{' '}
                        <strong>{question.author.full_name},</strong>{' '}
                      </span>
                      <time className="color-gray-1000">{question.time_ago}</time>
                    </div>
                  </div>
                </div>
                <div className="article-stats__right">
                  <nav className="article-actions">
                    <ul>
                      {user && !isOwner && (
                        <li className="spam-lnk-js">
                          <button
                            type="button"
                            className="article-actions__trigger border-0 bg-transparent p-0"
                            onClick={requireLogin}
                          >
                            <svg className="icon icon--report me-1">
                              <use xlinkHref="/images/forum/sprite.svg#icon-report" />
                            </svg>
                            <span>{lbl('LBL_Report', 'Report')}</span>
                          </button>
                        </li>
                      )}
                      {(question.comments > 0 || question.comments_allowed) && (
                        <li>
                          <button
                            type="button"
                            data-target="#_comments"
                            className="goto-comments-js article-actions__trigger border-0 bg-transparent p-0"
                          >
                            <span className="view-comments-section-js" title={lbl('LBL_ANSWERS', 'Answers')}>
                              <svg className="icon icon--chat">
                                <use xlinkHref="/images/forum/sprite.svg#icon-chat" />
                              </svg>
                              <span>{question.comments}</span>
                            </span>
                          </button>
                        </li>
                      )}
                      <li>
                        <span className="article-actions__trigger" title={lbl('LBL_VIEWS', 'Views')}>
                          <svg className="icon icon--views">
                            <use xlinkHref="/images/forum/sprite.svg#icon-views" />
                          </svg>
                          <span>{question.views}</span>
                        </span>
                      </li>
                      <li>
                        <div className="share">
                          <a href="#share-target" className="share__trigger trigger-js">
                            <svg className="icon icon--share">
                              <use xlinkHref="/images/forum/sprite.svg#icon-share" />
                            </svg>
                            <span className="ms-1">{lbl('LBL_SHARE', 'Share')}</span>
                          </a>
                          <div id="share-target" className="share__target" style={{ display: 'none' }}>
                            <ul className="social--share clearfix">
                              <li className="social--fb">
                                <a
                                  href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`}
                                  target="_blank"
                                  rel="noreferrer"
                                  title={lbl('LBL_FACEBOOK', 'Facebook')}
                                >
                                  <img src="/images/forum/social_01.svg" alt={lbl('LBL_FACEBOOK', 'Facebook')} />
                                </a>
                              </li>
                              <li className="social--tw">
                                <a
                                  href={`https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}`}
                                  target="_blank"
                                  rel="noreferrer"
                                  title={lbl('LBL_X', 'X')}
                                >
                                  <img src="/images/forum/social_02.svg" alt={lbl('LBL_X', 'X')} />
                                </a>
                              </li>
                              <li className="social--mail">
                                <a
                                  href={`mailto:?body=${encodeURIComponent(shareUrl)}`}
                                  title={lbl('LBL_EMAIL', 'Email')}
                                >
                                  <img src="/images/forum/social_06.svg" alt={lbl('LBL_EMAIL', 'Email')} />
                                </a>
                              </li>
                            </ul>
                          </div>
                        </div>
                      </li>
                    </ul>
                  </nav>
                </div>
              </div>
            </article>

            {canComment && (
              <article className="article-panel">
                <div className="article-comment">
                  {user ? (
                    <>
                      <div className="article-comment__left">
                        <figure
                          className="avtar avtar--xsmall avtar--round bg-gray-500"
                          data-title={firstChar(user.first_name ?? user.full_name)}
                        >
                          <img
                            src={imageUrl(AFILE.USER_PROFILE, user.id, 'SMALL')}
                            alt={user.full_name}
                          />
                        </figure>
                      </div>
                      <div className="article-comment__right">
                        <div className="comment-panel">
                          <button
                            type="button"
                            className="comment-panel__trigger comment-trigger-js border-0 bg-transparent p-0 text-start w-100"
                          >
                            {lbl(
                              'LBL_Add_a_comment_follow_up_question_or_thank_you_note',
                              'Add a comment, follow-up question, or thank-you note'
                            )}
                          </button>
                          <div id="commentBox" className="comment-panel__target comment-target-js">
                            <form className="form" onSubmit={onCommentSubmit}>
                              <div className="row">
                                <div className="col-md-12">
                                  <div className="field-set">
                                    <div className="field-wraper">
                                      <div className="field_cover field-count">
                                        <textarea
                                          id="fcomm_comment"
                                          name="fcomm_comment"
                                          className="form-control"
                                          rows={4}
                                          placeholder={lbl(
                                            'LBL_Add_a_comment_follow_up_question_or_thank_you_note',
                                            'Add a comment...'
                                          )}
                                          value={commentDraft}
                                          onChange={(e) => setCommentDraft(e.target.value)}
                                        />
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div className="row">
                                <div className="col-md-auto">
                                  <div className="step-actions d-flex gap-2">
                                    <button type="submit" className="btn btn--primary">
                                      {lbl('LBL_Submit', 'Submit')}
                                    </button>
                                    <button
                                      type="button"
                                      className="btn btn--primary-bordered comment-trigger-js"
                                    >
                                      {lbl('LBL_cancel', 'Cancel')}
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    </>
                  ) : (
                    <>
                      <div className="article-comment__left">
                        <figure className="avtar avtar-round avtar--xsmall bg-white">
                          <img
                            src="/images/forum/user.svg"
                            alt={lbl('LBL_Guest_User_image', 'Guest')}
                          />
                        </figure>
                      </div>
                      <div className="article-comment__right">
                        <div className="comment-panel">
                          <button
                            type="button"
                            className="comment-panel__trigger border-0 bg-transparent p-0 text-start"
                            onClick={requireLogin}
                          >
                            {lbl(
                              'LBL_Add_a_comment_follow_up_question_or_thank_you_note',
                              'Add a comment, follow-up question, or thank-you note'
                            )}
                          </button>
                        </div>
                      </div>
                    </>
                  )}
                </div>
              </article>
            )}

            {!canComment && question.comments > 0 && !question.comments_allowed && (
              <article className="article-panel">
                <div className="article-comment">
                  {lbl('LBL_Forum_No_Further_Comments_allowed', 'No further comments allowed.')}
                </div>
              </article>
            )}

            {showCommentsBlock && (
              <div className="article-result">
                <div className="article-result__head" id="_comments">
                  <div>
                    <h4>
                      {lbl('LBL_Forum_question_Comments', 'Answers')}{' '}
                      <span id="comments-count">{question.comments}</span>
                    </h4>
                  </div>
                  {comments.length > 1 && (
                    <div className="sorting" id="sorting-js">
                      <button
                        type="button"
                        className="sorting__trigger sorting-trigger-js border-0 bg-transparent"
                      >
                        <svg className="icon icon--small svg-icon" viewBox="0 0 16 12.632">
                          <path d="M7.579 9.263v1.684H0V9.263zm1.684-4.211v1.684H0V5.053zM7.579.842v1.684H0V.842zM13.474 12.632l-2.527-3.789H16z" />
                          <path d="M12.632 2.105h1.684v7.579h-1.684z" />
                          <path d="M13.473 0L16 3.789h-5.053z" />
                        </svg>
                        <span className="sorting__label">{lbl('LBL_Sort_By', 'Sort by')}:</span>
                        <span className="sorting__value">{sortLabel}</span>
                      </button>
                      <div className="sorting__target sorting-target-js" style={{ display: 'none' }}>
                        <div className="filter-dropdown">
                          <div className="select-list select-list--vertical select-list--scroll">
                            <ul id="sort_radio_list_js">
                              <li>
                                <label className="select-option">
                                  <input
                                    className="select-option__input"
                                    type="radio"
                                    name="sort_option"
                                    value="latest"
                                    checked={sort === 'latest'}
                                    onChange={() => onSortChange('latest')}
                                  />
                                  <span className="select-option__item">{lbl('LBL_Newest_First', 'Newest first')}</span>
                                </label>
                              </li>
                              <li>
                                <label className="select-option">
                                  <input
                                    className="select-option__input"
                                    type="radio"
                                    name="sort_option"
                                    value="most_liked"
                                    checked={sort === 'most_liked'}
                                    onChange={() => onSortChange('most_liked')}
                                  />
                                  <span className="select-option__item">{lbl('LBL_Most_Liked', 'Most liked')}</span>
                                </label>
                              </li>
                            </ul>
                          </div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
                <div className="article-result__body">
                  <div className="article-list">
                    <div id="comments--listing">
                      {comments.length === 0 ? (
                        <div className="message-display">
                          <div className="message-display__media">
                            <img
                              src="/images/forum/no-comments.svg"
                              alt=""
                              style={{ maxWidth: 300 }}
                            />
                          </div>
                          <h4 className="mb-2">
                            {lbl(
                              'MSG_There_have_been_no_answers_to_this_question_yet',
                              'There have been no answers to this question yet'
                            )}
                          </h4>
                          {question.comments_allowed && (
                            <p>
                              {lbl(
                                'MSG_Become_a_first_user_to_post_comment',
                                'Be the first to post a comment'
                              )}
                            </p>
                          )}
                        </div>
                      ) : (
                        comments.map((comment) => (
                          <ForumCommentCard
                            key={comment.id}
                            comment={comment}
                            questionAuthorId={authorId}
                            lbl={lbl}
                            onVote={requireLogin}
                            isQuestionOwner={isOwner}
                          />
                        ))
                      )}
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {sidebar && (
            <ForumSidebar
              recommended={sidebar.recommended_posts}
              popularTags={sidebar.popular_tags ?? []}
              topTeachers={sidebar.top_teachers}
              lbl={lbl}
              onAskQuestion={onAskQuestion}
            />
          )}
        </section>
      </div>
    </section>
  );
}
