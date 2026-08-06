import type { ReactNode } from 'react';

type Props = {
  sidebar?: ReactNode;
  children: ReactNode;
};

/** Legacy white card: page-panel + content-panel (optional left step menu). */
export function DashboardContentPanel({ sidebar, children }: Props) {
  return (
    <div
      className={`page-panel page-panel--flex min-height-500${sidebar ? '' : ' dashboard-panel--solo'}`}
    >
      {sidebar}
      <div className="page-panel__large">
        <div className="content-panel">{children}</div>
      </div>
    </div>
  );
}
