import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import {
  FORUM_SEARCH_TYPES,
  forumApi,
  type ForumMeta,
  type ForumQuestion,
} from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useSite } from '../context/SiteContext';
import { ForumQuestionCard } from '../components/forum/ForumQuestionCard';
import { ForumSidebar } from '../components/forum/ForumSidebar';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { W3MentorsPagination } from '../components/W3MentorsPagination';
import { SpriteIcon } from '../components/SpriteIcon';
import { useAuthModals } from '../hooks/useAuthModals';
import { useForumStyles } from '../hooks/useForumStyles';
import { parseForumTagParam, forumTagHref } from '../utils/forum';

const SEARCH_TABS = [
  { id: FORUM_SEARCH_TYPES.ALL, labelKey: 'LBL_All_Questions', fallback: 'All questions' },
  { id: FORUM_SEARCH_TYPES.ACTIVE, labelKey: 'LBL_Active_Questions', fallback: 'Active questions' },
  { id: FORUM_SEARCH_TYPES.ANSWERED, labelKey: 'LBL_Answered_Questions', fallback: 'Answered questions' },
  { id: FORUM_SEARCH_TYPES.POPULAR, labelKey: 'LBL_Popular_Questions', fallback: 'Popular questions' },
] as const;

export function W3MentorsForumPage() {
  useForumStyles();
  const { lbl } = useSite();
  const { user } = useAuth();
  const { openLoginModal } = useAuthModals();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();

  const parsedTag = useMemo(
    () => parseForumTagParam(searchParams.get('tag')),
    [searchParams]
  );

  const [meta, setMeta] = useState<ForumMeta | null>(null);
  const [questions, setQuestions] = useState<ForumQuestion[]>([]);
  const [keyword, setKeyword] = useState(parsedTag?.name ?? searchParams.get('keyword') ?? '');
  const [searchType, setSearchType] = useState(
    Number(searchParams.get('search_type')) || FORUM_SEARCH_TYPES.ALL
  );
  const [page, setPage] = useState(Number(searchParams.get('page')) || 1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [showReset, setShowReset] = useState(!!keyword || !!parsedTag);

  const requireLogin = useCallback(() => openLoginModal(), [openLoginModal]);
  const onAskQuestion = useCallback(() => {
    if (!user) openLoginModal();
    else navigate('/dashboard');
  }, [user, openLoginModal, navigate]);

  useEffect(() => {
    forumApi
      .meta()
      .then((res) => setMeta(res.data))
      .catch(() => setMeta(null));
  }, []);

  useEffect(() => {
    setLoading(true);
    forumApi
      .questions({
        keyword: parsedTag ? undefined : keyword || undefined,
        tag_id: parsedTag?.tagId,
        search_type: searchType,
        page,
      })
      .then((res) => {
        setQuestions(res.data.data);
        setLastPage(res.data.meta.last_page);
      })
      .catch(() => setQuestions([]))
      .finally(() => setLoading(false));
  }, [keyword, parsedTag?.tagId, searchType, page]);

  useEffect(() => {
    if (parsedTag) {
      setKeyword(parsedTag.name);
      setShowReset(true);
    }
  }, [parsedTag]);

  function resetSearch() {
    setKeyword('');
    setSearchType(FORUM_SEARCH_TYPES.ALL);
    setPage(1);
    setShowReset(false);
    setSearchParams({});
  }

  function onSearchSubmit(e: React.FormEvent) {
    e.preventDefault();
    setPage(1);
    setShowReset(!!keyword.trim());
    const next = new URLSearchParams();
    if (keyword.trim()) next.set('keyword', keyword.trim());
    if (searchType !== FORUM_SEARCH_TYPES.ALL) next.set('search_type', String(searchType));
    setSearchParams(next);
  }

  function onTabChange(type: number) {
    setSearchType(type);
    setPage(1);
  }

  const tutorCount = meta?.total_tutors ?? 0;
  const headingSub = lbl(
    'LBL_Ask_{tot-tutots-count}+_expert_tutors_from_all_over_the_world!',
    `Ask ${tutorCount}+ expert tutors from all over the world!`
  ).replace('{tot-tutots-count}', String(tutorCount));

  if (!meta && loading) {
    return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  }

  return (
    <>
      <section className="forum-header section bg-gradiant section--page-header text-center">
        <div className="container container--narrow" id="maindv__js">
          <hgroup>
            <h1>{lbl('LBL_Got_a_question?', 'Got a question?')}</h1>
            <h4>{headingSub}</h4>
          </hgroup>
          <div className="main-search">
            <div className="forum-search">
              <form onSubmit={onSearchSubmit}>
                <div className="main-search__field">
                  <input
                    id="keyword"
                    name="keyword"
                    type="text"
                    placeholder={lbl('LBL_Search', 'Search')}
                    value={keyword}
                    onChange={(e) => {
                      setKeyword(e.target.value);
                      if (!e.target.value) {
                        setShowReset(false);
                        resetSearch();
                      }
                    }}
                  />
                </div>
                <div className="main-search__action">
                  <button type="submit" className="main-search__submit border-0 bg-transparent p-0">
                    <SpriteIcon id="search" className="icon icon--search" />
                  </button>
                  {showReset && (
                    <button
                      type="button"
                      className="main-search__reset border-0 bg-transparent"
                      title={lbl('LBL_Reset', 'Reset')}
                      onClick={resetSearch}
                    >
                      <span className="close" />
                    </button>
                  )}
                </div>
              </form>
            </div>
          </div>
          {(meta?.popular_tags.length ?? 0) > 0 && (
            <div className="tags tags--overflow mt-3 d-sm-block d-none">
              <span className="d-block d-sm-inline-flex mb-3">
                {lbl('LBL_Popular_Tags', 'Popular tags')}:
              </span>
              <div className="tags__overflow">
                {meta!.popular_tags.map((tag) => (
                  <Link
                    key={tag.id}
                    to={forumTagHref(tag)}
                    className="tags__item badge color-primary badge--curve color-primary"
                  >
                    {tag.name}
                  </Link>
                ))}
              </div>
            </div>
          )}
        </div>
      </section>

      <section className="forum-body">
        <div className="container container--narrow">
          <nav className="tabs-wrapper mb-5">
            <nav className="tabs text-center tabs-scrollable-js">
              <ul id="srch_type_tabs" className="d-inline-flex">
                {SEARCH_TABS.map((tab) => (
                  <li
                    key={tab.id}
                    className={`srch_type ${searchType === tab.id ? 'is-active' : ''}`}
                  >
                    <button
                      type="button"
                      className={`search-type border-0 bg-transparent ${tab.id === FORUM_SEARCH_TYPES.ALL ? 'default_srch_type' : ''}`}
                      onClick={() => onTabChange(tab.id)}
                    >
                      {lbl(tab.labelKey, tab.fallback)}
                    </button>
                  </li>
                ))}
              </ul>
            </nav>
          </nav>

          <div className="forum-stat mb-5">
            <div className="forum-stat__content">
              {!user ? (
                <>
                  <h3 className="mb-3">
                    {lbl(
                      'LBL_Forum_questions_listing_page_guest_user_main_heading',
                      'Join the community'
                    )}
                  </h3>
                  <p className="mb-5">
                    {lbl(
                      'LBL_Forum_Questions_Listing_Page_guest_user_sub_heading',
                      'Sign in to ask questions and get answers from expert tutors.'
                    )}
                  </p>
                  <button type="button" className="btn btn--primary" onClick={requireLogin}>
                    {lbl('LBL_Login', 'Login')}
                  </button>
                </>
              ) : (
                <>
                  <h3 className="mb-3 bold-700">
                    {lbl(
                      'LBL_Join_the_biggest_community_of_learners_for_free',
                      'Join the biggest community of learners for free'
                    )}
                  </h3>
                  <p className="mb-5">
                    {lbl(
                      'LBL_Sign_up_to_ask_our_experts_any_questions_and_get_helpful_tips_in_your_inbox',
                      'Ask our experts any questions and get helpful tips.'
                    )}
                  </p>
                  <div className="d-flex gap-3 flex-wrap">
                    <Link to="/teachers" className="btn btn--primary">
                      <span>{lbl('LBL_Find_Community_Experts', 'Find community experts')}</span>
                    </Link>
                    <Link to="/dashboard" className="btn btn--primary-bordered">
                      <span>{lbl('LBL_Ask_a_Question', 'Ask a question')}</span>
                    </Link>
                  </div>
                </>
              )}
            </div>
            <div className="forum-stat__count">
              <div className="forum-counts">
                <span className="forum-counts__item">
                  <h5>{meta?.total_questions ?? 0}</h5>
                  <p>{lbl('LBL_questions_asked', 'Questions asked')}</p>
                </span>
                <span className="forum-counts__item">
                  <h5>{meta?.total_comments ?? 0}</h5>
                  <p>{lbl('LBL_tutors_answers', "Tutors' answers")}</p>
                </span>
                <span className="forum-counts__item">
                  <h5>{meta?.total_tutors ?? 0}</h5>
                  <p>{lbl('LBL_active_tutors', 'Active tutors')}</p>
                </span>
              </div>
            </div>
            <div className="forum-stat__media">
              <img src="/images/forum/cta-graphic.svg" alt="" />
            </div>
          </div>

          <section className="flex-panel">
            <div className="flex-panel__large" id="listing">
              {loading ? (
                <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />
              ) : questions.length === 0 ? (
                <div className="page-listing__body">
                  <div className="box -padding-30" style={{ marginBottom: 30 }}>
                    <div className="message-display">
                      <h5>{lbl('LBL_NO_RESULT_FOUND!', 'No results found')}</h5>
                    </div>
                  </div>
                </div>
              ) : (
                <>
                  {questions.map((q) => (
                    <ForumQuestionCard
                      key={q.id}
                      question={q}
                      lbl={lbl}
                      onVote={requireLogin}
                    />
                  ))}
                  <W3MentorsPagination current={page} last={lastPage} onPage={setPage} />
                </>
              )}
            </div>
            {meta && (
              <ForumSidebar
                recommended={meta.recommended_posts}
                popularTags={meta.popular_tags}
                topTeachers={meta.top_teachers}
                lbl={lbl}
                onAskQuestion={onAskQuestion}
              />
            )}
          </section>
        </div>
      </section>
    </>
  );
}
