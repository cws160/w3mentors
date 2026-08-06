import type { ReactNode } from 'react';
import { DashboardPageLayout } from '../DashboardPageLayout';
import { DashboardContentPanel } from './DashboardContentPanel';

export type DashboardPanelTab = {
  id: string;
  label: string;
  progress?: boolean;
  completed?: boolean;
};

type Props = {
  title: string;
  tabs: DashboardPanelTab[];
  activeTab: string;
  onTabChange: (id: string) => void;
  header?: ReactNode;
  children: ReactNode;
};

/** Account-style page with vertical step menu (same page-panel shell as other pages). */
export function DashboardPanelLayout({
  title,
  tabs,
  activeTab,
  onTabChange,
  header,
  children,
}: Props) {
  const sidebar = (
    <div className="page-panel__small">
      <nav className="menu menu--vertical menu--steps">
        <ul>
          {tabs.map((tab) => (
            <li
              key={tab.id}
              className={`menu__item${tab.progress ? ' profile--progress--menu' : ''}${
                tab.completed ? ' is-completed' : ''
              }${activeTab === tab.id ? ' is-active' : ''}`}
            >
              <a
                href="javascript:void(0);"
                onClick={(e) => {
                  e.preventDefault();
                  onTabChange(tab.id);
                }}
              >
                {tab.label}
                <span className="menu__icon" />
              </a>
            </li>
          ))}
        </ul>
      </nav>
    </div>
  );

  return (
    <DashboardPageLayout title={title}>
      {header}
      <DashboardContentPanel sidebar={sidebar}>{children}</DashboardContentPanel>
    </DashboardPageLayout>
  );
}
