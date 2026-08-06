import { AdminEditDeleteActions } from './AdminRowActions';

type Props = {
  preferId: number;
  canEdit: boolean;
  labels: {
    edit: string;
    delete: string;
  };
  onEdit: (preferId: number) => void;
  onDelete: (preferId: number) => void;
};

export function AdminPreferencesActions({ preferId, canEdit, labels, onEdit, onDelete }: Props) {
  return (
    <AdminEditDeleteActions
      canEdit={canEdit}
      editLabel={labels.edit}
      deleteLabel={labels.delete}
      onEdit={() => onEdit(preferId)}
      onDelete={() => onDelete(preferId)}
    />
  );
}
