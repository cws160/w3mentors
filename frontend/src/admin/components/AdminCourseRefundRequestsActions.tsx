import { AdminSpriteIcon } from './AdminSpriteIcon';

const STATUS_PENDING = 0;

type Props = {
  requestId: number;
  status: number;
  canEdit: boolean;
  labels: {
    view: string;
    changeStatus: string;
  };
  onView: (requestId: number) => void;
  onChangeStatus: (requestId: number) => void;
};

export function AdminCourseRefundRequestsActions({
  requestId,
  status,
  canEdit,
  labels,
  onView,
  onChangeStatus,
}: Props) {
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
        <a {...linkProps(labels.view, () => onView(requestId))}>
          <AdminSpriteIcon icon="view" />
        </a>
      </li>
      {canEdit && status === STATUS_PENDING ? (
        <li>
          <a {...linkProps(labels.changeStatus, () => onChangeStatus(requestId))}>
            <AdminSpriteIcon icon="edit" />
          </a>
        </li>
      ) : null}
    </ul>
  );
}
