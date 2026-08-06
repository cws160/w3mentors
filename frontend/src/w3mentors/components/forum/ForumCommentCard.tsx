import type { ForumComment } from '../../../api/client';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';
import { normalizeLegacyHtml } from '../../utils/legacyHtml';
import { ForumVoteBlock } from './ForumVoteBlock';

type Props = {
  comment: ForumComment;
  questionAuthorId: number;
  lbl: (key: string, fallback?: string) => string;
  onVote?: () => void;
  isQuestionOwner?: boolean;
};

export function ForumCommentCard({
  comment,
  lbl,
  onVote,
  isQuestionOwner = false,
}: Props) {
  const voteTone =
    comment.vote_score > 0 && comment.likes > comment.dislikes
      ? 'success'
      : comment.likes < comment.dislikes
        ? 'danger'
        : '';

  const showBestMark = comment.is_accepted || isQuestionOwner;

  return (
    <article className={`article-panel ${comment.is_accepted ? 'is-completed' : ''}`}>
      <div className="article-card">
        <div className="article-card__left">
          {showBestMark && (
            <button
              type="button"
              className={`order-2 article-mark is-hover border-0 bg-transparent p-0 ${
                comment.is_accepted ? 'is-active' : ''
              } ${!isQuestionOwner ? 'article-check mt-0' : ''}`}
              title={
                comment.is_accepted
                  ? lbl('LBL_Question_has_best_answer', 'Best answer')
                  : lbl('LBL_Set_as_best_answer', 'Set as best answer')
              }
            >
              <div className="tooltip tooltip--top bg-black">
                {comment.is_accepted
                  ? lbl('LBL_Question_has_best_answer', 'Best answer')
                  : lbl('LBL_Set_as_best_answer', 'Set as best answer')}
              </div>
            </button>
          )}
          <ForumVoteBlock
            recordId={comment.id}
            reactType={2}
            likes={comment.likes}
            dislikes={comment.dislikes}
            voteScore={comment.vote_score}
            voteTone={voteTone as '' | 'success' | 'danger'}
            onVote={onVote}
            emptyLabel={lbl('LBL_Awaiting_Best_Answer', 'Awaiting best answer')}
            upLabel={lbl('LBL_Vote_this_question_up', 'Vote up')}
            downLabel={lbl('LBL_Vote_this_question_down', 'Vote down')}
            upvotesLabel={lbl('LBL_Upvotes', 'Upvotes')}
            downvotesLabel={lbl('LBL_Downvotes', 'Downvotes')}
            countsClassName="counts order-1"
          />
        </div>
        <div className="article-card__right">
          <div className="article-author">
            <figure className="article-author__avatar">
              <div
                className="avtar avtar--small avtar--round bg-gray-500"
                data-title={firstChar(comment.author.first_name ?? comment.author.full_name)}
              >
                <img
                  src={imageUrl(AFILE.USER_PROFILE, comment.author.id, 'SMALL')}
                  alt={comment.author.full_name}
                />
              </div>
            </figure>
            <div className="article-author__content">
              <span>
                {lbl('LBL_Posted_By:', 'Posted by:')}{' '}
                <strong>{comment.author.full_name}</strong>,
              </span>
              <time className="color-gray-1000">{comment.time_ago}</time>
            </div>
          </div>
          <div
            className="editor-content"
            dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(comment.comment) }}
          />
        </div>
      </div>
    </article>
  );
}
