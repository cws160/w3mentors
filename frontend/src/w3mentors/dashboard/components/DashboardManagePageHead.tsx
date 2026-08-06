import type { ReactNode } from 'react';

type Props = {
  title: string;
  subtitle?: string;
  actions?: ReactNode;
  searchToggle?: ReactNode;
  searchPanel?: ReactNode;
  searchOpen?: boolean;
};

/** Legacy dashboard page head (`page__head` with optional search filter). */
export function DashboardManagePageHead({
  title,
  subtitle,
  actions,
  searchToggle,
  searchPanel,
  searchOpen = false,
}: Props) {
  return (
    <div className="page__head">
      <div className="row align-items-center justify-content-between">
        <div className="col-sm-7">
          <h1>{title}</h1>
          {subtitle && <p className="m-0">{subtitle}</p>}
        </div>
        {(actions || searchToggle) && (
          <div className="col-sm-auto">
            <div className="buttons-group d-flex align-items-center gap-2">
              {searchToggle}
              {actions}
            </div>
          </div>
        )}
      </div>
      {searchPanel && (
        <div className="search-filter slide-target-js" style={{ display: searchOpen ? 'block' : 'none' }}>
          {searchPanel}
        </div>
      )}
    </div>
  );
}
