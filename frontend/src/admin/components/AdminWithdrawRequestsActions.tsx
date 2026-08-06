import { AdminSpriteIcon } from './AdminSpriteIcon';

const STATUS_PENDING = 1;
const STATUS_COMPLETED = 2;
const STATUS_DECLINED = 3;

type Props = {
  withdrawalId: number;
  status: number;
  userDeleted: boolean;
  canEdit: boolean;
  labels: {
    approve: string;
    decline: string;
  };
  onUpdateStatus: (withdrawalId: number, status: number, statusName: string) => void;
};

export function AdminWithdrawRequestsActions({
  withdrawalId,
  status,
  userDeleted,
  canEdit,
  labels,
  onUpdateStatus,
}: Props) {
  if (!canEdit || userDeleted || status !== STATUS_PENDING) {
    return null;
  }

  const linkProps = (title: string, onClick: () => void) => ({
    href: 'javascript:void(0)',
    title,
    'data-bs-toggle': 'tooltip',
    'data-placement': 'top',
    onClick: (e: React.MouseEvent) => {
      e.preventDefault();
      onClick();
    },
  });

  return (
    <ul className="actions">
      <li>
        <a {...linkProps(labels.approve, () => onUpdateStatus(withdrawalId, STATUS_COMPLETED, labels.approve))}>
          <AdminSpriteIcon icon="icon-completed" />
        </a>
      </li>
      <li>
        <a {...linkProps(labels.decline, () => onUpdateStatus(withdrawalId, STATUS_DECLINED, labels.decline))}>
          <AdminSpriteIcon icon="close" />
        </a>
      </li>
    </ul>
  );
}
