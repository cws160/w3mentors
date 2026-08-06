import { useSite } from '../../context/SiteContext';

type Props = {
  totalFilled: number;
  totalFields: number;
};

/** Legacy: profile-info.php profile-progress block with progress__step segments. */
export function ProfileProgressBar({ totalFilled, totalFields }: Props) {
  const { lbl } = useSite();
  const steps = totalFields > 0 ? totalFields : 6;
  const activeSteps = Math.min(Math.max(0, totalFilled), steps);

  return (
    <div className="profile-progress mt-2">
      <div className="profile-progress__meta mb-2">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <span className="small">{lbl('LBL_PROFILE_PROGRESS', 'Profile progress')}</span>
          </div>
          <div>
            <span className="small bold-700 progress-count-js">
              {totalFilled}/{steps}
            </span>
          </div>
        </div>
      </div>
      <div className="profile-progress__bar">
        <div className="progress progress--small progress--round">
          <div className="progress-bar">
            {Array.from({ length: steps }, (_, index) => (
              <div
                key={index}
                className={`progress__step${index < activeSteps ? ' is-active' : ''}`}
              />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
