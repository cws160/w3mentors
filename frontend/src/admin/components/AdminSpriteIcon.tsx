import { useEffect, useState } from 'react';
import { ensureAdminSprites } from '../utils/adminSprite';

type Props = {
  icon: string;
  width?: number;
  height?: number;
  className?: string;
};

export function AdminSpriteIcon({ icon, width = 18, height = 18, className = 'svg' }: Props) {
  const [ready, setReady] = useState(
    () =>
      typeof document !== 'undefined' &&
      !!document.getElementById('admin-actions-sprite') &&
      !!document.getElementById('admin-aside-sprite'),
  );

  useEffect(() => {
    void ensureAdminSprites().then(() => setReady(true));
  }, []);

  if (!ready) {
    return <span className={className} style={{ display: 'inline-block', width, height }} />;
  }

  return (
    <svg
      width={width}
      height={height}
      className={className}
      aria-hidden="true"
      style={{ pointerEvents: 'none' }}
    >
      <use href={`#${icon}`} xlinkHref={`#${icon}`} />
    </svg>
  );
}
