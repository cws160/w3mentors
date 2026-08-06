import type { ReactNode } from 'react';

type Props = {
  label: string;
  children: ReactNode;
};

export function DashboardFlexCell({ label, children }: Props) {
  return (
    <div className="flex-cell">
      <div className="flex-cell__label">{label}</div>
      <div className="flex-cell__content">{children}</div>
    </div>
  );
}
