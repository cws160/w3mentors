import { Link } from 'react-router-dom';
import type { ForumQuestion } from '../../../api/client';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';
import { normalizeLegacyHtml } from '../../utils/legacyHtml';
import { forumTagHref } from '../../utils/forum';
import { ForumVoteBlock } from './ForumVoteBlock';

type Props = {
  question: ForumQuestion;
  lbl: (key: string, fallback?: string) => string;
  onVote?: () => void;
};

export function ForumQuestionCard({ question, lbl, onVote }: Props) {
  const voteTone = question.vote_tone as '' | 'success' | 'danger';

  return (
    <div className="article-list">
      <article className="article">
        <div className="article__left">
          <ForumVoteBlock
            recordId={question.id}
            reactType={1}
            likes={question.likes}
            dislikes={question.dislikes}
            voteScore={question.vote_score}
            voteTone={voteTone}
            onVote={onVote}
            emptyLabel={lbl('LBL_FORUM_QUESTION_NO_REACTIONS', 'No reactions yet')}
            upLabel={lbl('LBL_Vote_this_question_up', 'Vote this question up')}
            downLabel={lbl('LBL_Vote_this_question_down', 'Vote this question down')}
            upvotesLabel={lbl('LBL_Upvotes', 'Upvotes')}
            downvotesLabel={lbl('LBL_Downvotes', 'Downvotes')}
          />
        </div>
        <div className="article__right">
          <div className="article-content">
            <h4 className="article-title mb-2 bold-700">
              <Link to={`/forum/${question.slug}`} className="snakeline-hover">
                {question.title}
              </Link>
            </h4>
            <div className="article-shortdesc mt-3 mb-4">
              <div className="article-more">
                <div className="article-more__content">
                  <div
                    className="cms-container forum-editor"
                    dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(question.description) }}
                  />
                </div>
              </div>
            </div>
          </div>
          {question.tags && question.tags.length > 0 && (
            <div className="tags">
              <div className="tags__overflow">
                {question.tags.map((tag) => (
                  <Link
                    key={tag.id}
                    to={forumTagHref(tag)}
                    className="tags__item color-primary badge badge--curve"
                  >
                    {tag.name}
                  </Link>
                ))}
              </div>
            </div>
          )}
          <div className="article-stats">
            <div className="article-stats__left">
              <div className="article-author">
                <figure className="article-author__avatar">
                  <div
                    className="avtar avtar--xsmall avtar--round bg-gray-500"
                    data-title={firstChar(question.author.first_name ?? question.author.full_name)}
                  >
                    <img
                      src={imageUrl(AFILE.USER_PROFILE, question.author.id, 'SMALL')}
                      alt={question.author.full_name}
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
                  {(question.comments > 0 || question.comments_allowed) && (
                    <li>
                      <Link
                        to={`/forum/${question.slug}#comments`}
                        className="article-actions__trigger"
                        title={lbl('LBL_ANSWERS', 'Answers')}
                      >
                        <svg className="icon icon--chat">
                          <use xlinkHref="/images/forum/sprite.svg#icon-chat" />
                        </svg>
                        <span>{question.comments}</span>
                      </Link>
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
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </article>
    </div>
  );
}
