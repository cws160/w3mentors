import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';

type Props = {
  onClick: () => void;
};

export function DashboardSearchToggle({ onClick }: Props) {
  const { lbl } = useSite();

  return (
    <button type="button" className="btn btn--secondary slide-toggle-js" onClick={onClick}>
      <DashboardSpriteIcon id="search" className="icon icon--search icon--small me-2" width={16} height={16} />
      {lbl('LBL_SEARCH', 'Search')}
    </button>
  );
}
