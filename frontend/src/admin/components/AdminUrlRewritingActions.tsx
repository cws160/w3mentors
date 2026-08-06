import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  seoUrlId: number;
  canEdit: boolean;
  labels: {
    edit: string;
    delete: string;
  };
  onEdit: (id: number) => void;
  onDelete: (id: number) => void;
};

export function AdminUrlRewritingActions({
  seoUrlId,
  canEdit,
  labels,
  onEdit,
  onDelete,
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
      {link(labels.edit, 'edit', () => onEdit(seoUrlId))}
      {link(labels.delete, 'delete', () => onDelete(seoUrlId))}
    </ul>
  );
}
