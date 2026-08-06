import { useSite } from '../../context/SiteContext';

type Props = {
  titleKey: string;
  titleFallback: string;
};

export function DashboardLegacySectionNotice({ titleKey, titleFallback }: Props) {
  const { lbl } = useSite();
  return (
    <div className="padding-6">
      <div className="content-panel__head">
        <h5>{lbl(titleKey, titleFallback)}</h5>
      </div>
      <div className="content-panel__body">
        <p className="color-secondary mb-2">
          {lbl(
            'LBL_DASHBOARD_SECTION_MIGRATION',
            'This section will be fully editable here soon. You can still manage it from the legacy teacher dashboard if needed.'
          )}
        </p>
      </div>
    </div>
  );
}
