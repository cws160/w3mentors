import type { ReactNode } from 'react';
import { DashboardPageLayout } from '../DashboardPageLayout';
import { DashboardContentPanel } from './DashboardContentPanel';

type Props = {
  title: string;
  actions?: ReactNode;
  /** Shown in content-panel__head (defaults to title). */
  sectionTitle?: string;
  /** Content above the white panel (e.g. teacher profile infobar). */
  header?: ReactNode;
  /** Filters / toolbar below section title. */
  toolbar?: ReactNode;
  children: ReactNode;
};

/** Standard dashboard inner page — same shell as account settings (without side tabs). */
export function DashboardStandardPage({
  title,
  actions,
  sectionTitle,
  header,
  toolbar,
  children,
}: Props) {
  const panelTitle = sectionTitle ?? title;

  return (
    <DashboardPageLayout title={title} actions={actions}>
      {header}
      <DashboardContentPanel>
        <div className="content-panel__head">
          <h5>{panelTitle}</h5>
          {toolbar}
        </div>
        <div className="content-panel__body">{children}</div>
      </DashboardContentPanel>
    </DashboardPageLayout>
  );
}
