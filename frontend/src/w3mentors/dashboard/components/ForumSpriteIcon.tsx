type Props = {
  id: string;
  className?: string;
};

const FORUM_SPRITE = '/images/forum/sprite.svg';

export function ForumSpriteIcon({ id, className = 'icon' }: Props) {
  return (
    <svg className={className} aria-hidden>
      <use xlinkHref={`${FORUM_SPRITE}#${id}`} />
    </svg>
  );
}
