import { AdminSpriteIcon } from './AdminSpriteIcon';

const TAG_REQ_PENDING = 0;

type Props = {
  requestId: number;
  status: number;
  canEdit: boolean;
  labels: {
    changeStatus: string;
    na: string;
  };
  onChangeStatus: (id: number) => void;
};

export function AdminForumTagRequestActions({
  requestId,
  status,
  canEdit,
  labels,
  onChangeStatus,
}: Props) {
  if (!canEdit || status !== TAG_REQ_PENDING) {
    return labels.na;
  }

  return (
    <ul className="actions">
      <li title={labels.changeStatus} data-bs-toggle="tooltip" data-placement="top">
        <a
          href="javascript:void(0)"
          onClick={(e) => {
            e.preventDefault();
            onChangeStatus(requestId);
          }}
        >
          <AdminSpriteIcon icon="edit" />
        </a>
      </li>
    </ul>
  );
}
