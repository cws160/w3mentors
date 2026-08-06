type Props = {
  id: string;
  className?: string;
  width?: number | string;
  height?: number | string;
  /** Sprite sheet URL (default: dashboard sprite). */
  spritePath?: string;
};

/** Dashboard area sprites (legacy `CONF_WEBROOT_URL . 'images/sprite.svg'`). */
export function DashboardSpriteIcon({
  id,
  className = 'icon',
  width,
  height,
  spritePath = '/dashboard/images/sprite.svg',
}: Props) {
  const iconClass = className.includes('icon--') ? className : `${className} icon--${id}`;
  return (
    <svg className={iconClass} width={width} height={height} aria-hidden>
      <use xlinkHref={`${spritePath}#${id}`} />
    </svg>
  );
}
