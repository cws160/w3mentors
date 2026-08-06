import { useSite } from '../../w3mentors/context/SiteContext';
import {
  ADMIN_DURATION_OPTIONS,
  ADMIN_DURATION_TYPE_ALL,
  formatAdminDurationRange,
  getAdminDurationLabel,
} from '../config/adminDurationTypes';

type Props = {
  value?: number;
  excludeToday?: boolean;
  onChange: (interval: number, label: string) => void;
};

export function AdminDashboardDurationSelect({ value = ADMIN_DURATION_TYPE_ALL, excludeToday, onChange }: Props) {
  const { lbl } = useSite();
  const options = excludeToday
    ? ADMIN_DURATION_OPTIONS.filter((item) => item.id !== 1)
    : ADMIN_DURATION_OPTIONS;
  const currentLabel = getAdminDurationLabel(value, lbl);

  return (
    <div className="dropdown">
      <button
        type="button"
        className="btn btn-select dropdown-toggle dropdownBtnJs"
        data-bs-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
      >
        {currentLabel}
      </button>
      <div
        className="nav nav-tabs navTabsJs dropdown-menu dropdown-menu-right dropdown-menu-anim"
        role="tablist"
      >
        <div className="dropdown-menu-scroll">
          <ul>
            {options.map((option) => {
              const label = lbl(option.labelKey, option.fallback);
              const range = formatAdminDurationRange(option.id);
              return (
                <li key={option.id}>
                  <button
                    type="button"
                    className="dropdown-item"
                    onClick={() => onChange(option.id, label)}
                  >
                    {label} <span>( {range} )</span>
                  </button>
                </li>
              );
            })}
          </ul>
        </div>
      </div>
    </div>
  );
}
