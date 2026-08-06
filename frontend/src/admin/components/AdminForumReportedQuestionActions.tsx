import { AdminSpriteIcon } from './AdminSpriteIcon';

const REPORT_PENDING = 0;

type Props = {
  reportId: number;
  status: number;
  canEdit: boolean;
  labels: {
    view: string;
    action: string;
  };
  onView: (id: number) => void;
  onAction: (id: number) => void;
};

export function AdminForumReportedQuestionActions({
  reportId,
  status,
  canEdit,
  labels,
  onView,
  onAction,
}: Props) {
  const link = (title: string, icon: string, onClick: () => void) => (
    <li title={title} data-bs-toggle="tooltip" data-placement="top">
      <a
        href="javascript:void(0)"
        onClick={(e) => {
          e.preventDefault();
          onClick();
        }}
      >
        <AdminSpriteIcon icon={icon} />
      </a>
    </li>
  );

  return (
    <ul className="actions">
      {link(labels.view, 'view', () => onView(reportId))}
      {canEdit && status === REPORT_PENDING
        ? link(labels.action, 'edit', () => onAction(reportId))
        : null}
    </ul>
  );
}
