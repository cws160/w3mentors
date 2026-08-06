import { useState } from 'react';
import { AFILE, firstChar, imageUrl } from '../utils/assets';

type Props = {
  userId: number;
  firstName: string;
  size: 'small' | 'medium';
};

const sizeClass = {
  small: 'avtar--small',
  medium: 'avtar--medium',
} as const;

export function ProfileAvatar({ userId, firstName, size }: Props) {
  const [showImage, setShowImage] = useState(true);
  const src = `${imageUrl(AFILE.USER_PROFILE, userId, 'SMALL')}?t=${userId}`;

  return (
    <div className={`avtar ${sizeClass[size]} avtar--round`} data-title={firstChar(firstName)}>
      {showImage && (
        <img
          src={src}
          alt=""
          onError={() => setShowImage(false)}
        />
      )}
    </div>
  );
}
