type SpriteIconProps = {
  id: string;
  className?: string;
  width?: number | string;
  height?: number | string;
};

export function SpriteIcon({ id, className = 'icon', width, height }: SpriteIconProps) {
  return (
    <svg className={className} width={width} height={height}>
      <use xlinkHref={`/images/sprite.svg#${id}`} />
    </svg>
  );
}
