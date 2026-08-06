type Props = {
  recordId: number;
  reactType: 1 | 2;
  likes: number;
  dislikes: number;
  voteScore: number;
  voteTone?: '' | 'success' | 'danger';
  onVote?: () => void;
  emptyLabel: string;
  upLabel: string;
  downLabel: string;
  upvotesLabel: string;
  downvotesLabel: string;
  countsClassName?: string;
};

export function ForumVoteBlock({
  recordId,
  reactType,
  likes,
  dislikes,
  voteScore,
  voteTone = '',
  onVote,
  emptyLabel,
  upLabel,
  downLabel,
  upvotesLabel,
  downvotesLabel,
  countsClassName = 'counts',
}: Props) {
  const hasVotes = likes > 0 || dislikes > 0 || voteScore > 0;
  const prefix = `${reactType}_${recordId}`;

  return (
    <div className={countsClassName}>
      <span className="counts__up">
        <button
          type="button"
          className="vote vote--up is-hover border-0 bg-transparent p-0"
          onClick={onVote}
          aria-label={upLabel}
        >
          <svg className="icon icon--upvote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M2 9h3v12H2a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1zm5.293-1.293l6.4-6.4a.5.5 0 0 1 .654-.047l.853.64a1.5 1.5 0 0 1 .553 1.57L14.6 8H21a2 2 0 0 1 2 2v2.104a2 2 0 0 1-.15.762l-3.095 7.515a1 1 0 0 1-.925.619H8a1 1 0 0 1-1-1V8.414a1 1 0 0 1 .293-.707z" />
          </svg>
          <div className="tooltip tooltip--top bg-black">{upLabel}</div>
        </button>
      </span>
      <span className="counts__middle is-hover">
        <span className={`vote-counts ${voteTone ? `color-${voteTone}` : ''}`}>{voteScore}</span>
        {hasVotes ? (
          <div className="tooltip tooltip--right bg-black show">
            <span>{likes}</span> {upvotesLabel}
            <br />
            <span>{dislikes}</span> {downvotesLabel}
          </div>
        ) : (
          <div className="tooltip tooltip--right bg-black show">{emptyLabel}</div>
        )}
      </span>
      <span className="counts__down">
        <button
          type="button"
          className="vote vote--down is-hover border-0 bg-transparent p-0"
          onClick={onVote}
          aria-label={downLabel}
        >
          <svg className="icon icon--downvote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M22 15h-3V3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1zm-5.293 1.293l-6.4 6.4a.5.5 0 0 1-.654.047L8.8 22.1a1.5 1.5 0 0 1-.553-1.57L9.4 16H3a2 2 0 0 1-2-2v-2.104a2 2 0 0 1 .15-.762L4.246 3.62A1 1 0 0 1 5.17 3H16a1 1 0 0 1 1 1v11.586a1 1 0 0 1-.293.707z" />
          </svg>
          <div className="tooltip tooltip--bottom bg-black">{downLabel}</div>
        </button>
      </span>
      <span className="visually-hidden" id={`tot_counts${prefix}`} />
    </div>
  );
}
