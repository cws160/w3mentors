import type { CSSProperties } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminLogoSrc, adminSiteTitle } from '../utils/adminBranding';

type Props = {
  className?: string;
  style?: CSSProperties;
  variant?: 'default' | 'white';
};

export function AdminLogo({ className, style, variant = 'default' }: Props) {
  const { site } = useSite();

  return (
    <img
      src={adminLogoSrc(null, variant)}
      alt={adminSiteTitle(site?.name)}
      className={className}
      style={style}
    />
  );
}
