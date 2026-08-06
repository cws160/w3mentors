import { Link } from 'react-router-dom';
import type { ForumRecommended, ForumTag, TeacherListing } from '../../../api/client';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';
import { forumTagHref } from '../../utils/forum';
import { SpriteIcon } from '../SpriteIcon';

type Props = {
  recommended: ForumRecommended[];
  popularTags: ForumTag[];
  topTeachers: TeacherListing[];
  lbl: (key: string, fallback?: string) => string;
  onAskQuestion?: () => void;
  showAskButton?: boolean;
};

export function ForumSidebar({
  recommended,
  popularTags,
  topTeachers,
  lbl,
  onAskQuestion,
  showAskButton = true,
}: Props) {
  return (
    <aside className="flex-panel__small">
      <div className="article-side">
        {recommended.length > 0 && (
          <div className="article-widget border-bottom">
            <div className="article-widget__head">
              <h5>{lbl('LBL_Recommended_Posts', 'Recommended posts')}</h5>
            </div>
            <div className="article-widget__body p-0">
              <div className="article-list">
                {recommended.map((post) => (
                  <div className="mini-article" key={post.id}>
                    <p className="mini-article__title mb-2">
                      <Link to={`/forum/${post.slug}`} className="snakeline-hover">
                        {post.title}
                      </Link>
                    </p>
                    <div className="mini-article__stats">
                      <small className="me-3">
                        {post.likes} {lbl('LBL_Upvotes', 'Upvotes')}
                      </small>
                      <small className="me-3">
                        {post.comments} {lbl('LBL_Answers', 'Answers')}
                      </small>
                      <small>
                        {post.views} {lbl('LBL_Views', 'Views')}
                      </small>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {popularTags.length > 0 && (
          <div className="article-widget border-bottom">
            <div className="article-widget__head">
              <h5>{lbl('LBL_Popular_Tags', 'Popular tags')}</h5>
            </div>
            <div className="article-widget__body">
              <div className="tags">
                {popularTags.map((tag) => (
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
          </div>
        )}

        {topTeachers.length > 0 && (
          <div className="article-widget border-bottom">
            <div className="article-widget__head">
              <h5>{lbl('LBL_Community_Experts', 'Community experts')}</h5>
            </div>
            <div className="article-widget__body p-0">
              <div className="article-authors">
                {topTeachers.map((teacher) => (
                  <div className="article-authors__item" key={teacher.id}>
                    <Link
                      to={teacher.username ? `/teachers/${teacher.username}` : `/teachers/${teacher.id}`}
                      className="profile-meta d-flex align-items-start gap-3"
                    >
                      <div className="profile-meta__media">
                        <span
                          className="avtar avtar--medium avtar--round"
                          data-title={firstChar(teacher.first_name ?? teacher.full_name)}
                        >
                          <img
                            src={imageUrl(AFILE.USER_PROFILE, teacher.id, 'MEDIUM')}
                            alt={teacher.full_name}
                          />
                        </span>
                      </div>
                      <div className="profile-meta__details">
                        <span className="bold-600 color-black mb-1 d-block">{teacher.full_name}</span>
                        <div className="rating">
                          <SpriteIcon id="rating" className="rating__media" />
                          <span className="rating__value">{teacher.ratings}</span>
                          <span className="rating__count">
                            {teacher.reviews} {lbl('LBL_REVIEW(S)', 'Review(s)')}
                          </span>
                        </div>
                      </div>
                    </Link>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}
      </div>

      <div className="article-side article-side--sticky">
        <div className="article-widget">
          <div className="article-widget__head align-center">
            <h5>{lbl('LBL_Feeling_Stuck?', 'Feeling stuck?')}</h5>
          </div>
          <div className="article-widget__body">
            <div className="align-center">
              <img
                src="/images/forum/stuck-meda.svg"
                alt=""
                style={{ margin: '0 auto' }}
              />
              <div className="m-3 p-2 mb-0">
                <p>
                  {lbl('LBL_Ask_our_expert_tutors_a_question', 'Ask our expert tutors a question')}{' '}
                  <span className="color-secondary bold-600">{lbl('LBL_Its_free', "It's free")}</span>
                </p>
              </div>
            </div>
            {showAskButton && onAskQuestion && (
              <button type="button" className="btn btn--secondary btn--block" onClick={onAskQuestion}>
                <SpriteIcon id="q-mark" className="icon icon--qmark me-2" />
                <span>{lbl('LBL_Ask_a_Question', 'Ask a question')}</span>
              </button>
            )}
          </div>
        </div>
      </div>
    </aside>
  );
}
