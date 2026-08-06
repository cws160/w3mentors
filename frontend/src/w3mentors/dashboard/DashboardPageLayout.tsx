import type { ReactNode } from 'react';

type Props = {
  title: string;
  actions?: ReactNode;
  children: ReactNode;
  fluid?: boolean;
};

export function DashboardPageLayout({ title, actions, children, fluid }: Props) {
  return (
    <div className={fluid ? 'container container--fixed' : 'container container--fixed'}>
      <div className="page__head">
        <div className="row align-items-center justify-content-between">
          <div className="col-sm-6">
            <h1>{title}</h1>
          </div>
          {actions && <div className="col-sm-auto"><div className="buttons-group d-flex align-items-center gap-2">{actions}</div></div>}
        </div>
      </div>
      <div className="page__body">{children}</div>
    </div>
  );
}
