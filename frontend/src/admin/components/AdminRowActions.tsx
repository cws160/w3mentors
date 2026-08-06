import { Link } from 'react-router-dom';
import { AdminSpriteIcon } from './AdminSpriteIcon';

export type AdminRowActionItem = {
  icon: string;
  title: string;
  onClick?: () => void;
  to?: string;
  target?: string;
};

type CellProps = {
  actions: AdminRowActionItem[];
  className?: string;
};

/** Matches legacy `manager/utilities/Action.php` markup (edit/delete icon list). */
export function AdminRowActionsCell({ actions, className = 'align-right' }: CellProps) {
  if (actions.length === 0) {
    return null;
  }

  return (
    <td className={className}>
      <ul className="actions">
        {actions.map((action) => (
          <li
            key={`${action.icon}-${action.title}`}
            title={action.title}
            data-bs-toggle="tooltip"
            data-placement="top"
          >
            {action.to ? (
              <Link
                to={action.to}
                target={action.target}
                rel={action.target === '_blank' ? 'noreferrer' : undefined}
              >
                <AdminSpriteIcon icon={action.icon} />
              </Link>
            ) : (
              <a
                href="javascript:void(0)"
                onClick={(e) => {
                  e.preventDefault();
                  action.onClick?.();
                }}
              >
                <AdminSpriteIcon icon={action.icon} />
              </a>
            )}
          </li>
        ))}
      </ul>
    </td>
  );
}

type EditDeleteProps = {
  canEdit: boolean;
  editLabel: string;
  deleteLabel: string;
  onEdit: () => void;
  onDelete: () => void;
  className?: string;
};

export function AdminEditDeleteActions({
  canEdit,
  editLabel,
  deleteLabel,
  onEdit,
  onDelete,
  className,
}: EditDeleteProps) {
  if (!canEdit) {
    return null;
  }

  return (
    <AdminRowActionsCell
      className={className}
      actions={[
        { icon: 'edit', title: editLabel, onClick: onEdit },
        { icon: 'delete', title: deleteLabel, onClick: onDelete },
      ]}
    />
  );
}
