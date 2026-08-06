import { AdminSpriteIcon } from './AdminSpriteIcon';

const STATUS_PENDING = 0;

type Props = {
  requestId: number;
  courseId: number;
  teacherId: number;
  status: number;
  courseDeleted: boolean;
  canEdit: boolean;
  labels: {
    view: string;
    preview: string;
    changeStatus: string;
  };
  onView: (requestId: number) => void;
  onPreview: (teacherId: number, courseId: number) => void;
  onChangeStatus: (requestId: number) => void;
};

export function AdminCourseRequestsActions({
  requestId,
  courseId,
  teacherId,
  status,
  courseDeleted,
  canEdit,
  labels,
  onView,
  onPreview,
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
      {!courseDeleted ? (
        <li>
          <a {...linkProps(labels.preview, () => onPreview(teacherId, courseId))}>
            <AdminSpriteIcon icon="double-arrow-right" />
          </a>
        </li>
      ) : null}
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
