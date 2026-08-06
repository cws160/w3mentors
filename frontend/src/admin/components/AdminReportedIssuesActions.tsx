import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  issueId: number;
  labels: {
    view: string;
  };
  onView: (issueId: number) => void;
};

export function AdminReportedIssuesActions({ issueId, labels, onView }: Props) {
  const actionLink = (title: string, onClick: () => void) => ({
    href: '#',
    title,
    onClick: (e: React.MouseEvent) => {
      e.preventDefault();
      e.stopPropagation();
      onClick();
    },
  });

  return (
    <ul className="actions">
      <li title={labels.view}>
        <a {...actionLink(labels.view, () => onView(issueId))}>
          <AdminSpriteIcon icon="view" />
        </a>
      </li>
    </ul>
  );
}
