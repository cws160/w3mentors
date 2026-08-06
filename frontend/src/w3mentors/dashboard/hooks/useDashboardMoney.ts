import { useSite } from '../../context/SiteContext';

export function useDashboardMoney(): string {
  const { site } = useSite();
  return site?.currency_code ? `${site.currency_code} ` : '$';
}
