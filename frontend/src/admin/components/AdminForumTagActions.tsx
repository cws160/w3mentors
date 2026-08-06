import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  tagId: number;
  deleted: boolean;
  canEdit: boolean;
  labels: {
    edit: string;
    delete: string;
    restore: string;
  };
  onEdit: (id: number) => void;
  onDelete: (id: number) => void;
  onRestore: (id: number) => void;
};

export function AdminForumTagActions({
  tagId,
  deleted,
  canEdit,
  labels,
  onEdit,
  onDelete,
  onRestore,
}: Props) {
  if (!canEdit) {
    return null;
  }

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
      {deleted
        ? link(labels.restore, 'icon-restore', () => onRestore(tagId))
        : [
            link(labels.edit, 'edit', () => onEdit(tagId)),
            link(labels.delete, 'delete', () => onDelete(tagId)),
          ]}
    </ul>
  );
}
