import { useState, type ReactNode } from 'react';

type Props = {
  title: string;
  children: ReactNode;
  defaultOpen?: boolean;
};

/** Legacy admin collapsible search card (`card-head js--filter-trigger` + `card-body js--filter-target`). */
export function AdminLegacyFilterCard({ title, children, defaultOpen = false }: Props) {
  const [open, setOpen] = useState(defaultOpen);

  return (
    <div className="card">
      <div
        className={`card-head js--filter-trigger${open ? ' active' : ''}`}
        onClick={() => setOpen((value) => !value)}
      >
        <h4>{title}</h4>
      </div>
      <div className="card-body js--filter-target" style={{ display: open ? 'block' : 'none' }}>
        {children}
      </div>
    </div>
  );
}
