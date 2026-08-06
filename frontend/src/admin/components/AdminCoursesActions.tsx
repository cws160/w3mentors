import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  courseId: number;
  teacherId: number;
  canEdit: boolean;
  labels: {
    view: string;
    preview: string;
  };
  onView: (courseId: number) => void;
  onPreview: (teacherId: number, courseId: number) => void;
};

export function AdminCoursesActions({
  courseId,
  teacherId,
  canEdit,
  labels,
  onView,
  onPreview,
}: Props) {
  if (!courseId || Number.isNaN(courseId)) {
    return null;
  }

  return (
    <ul className="actions">
      <li title={labels.view} data-bs-toggle="tooltip" data-placement="top">
        <a
          href="javascript:void(0)"
          onClick={(e) => {
            e.preventDefault();
            onView(courseId);
          }}
        >
          <AdminSpriteIcon icon="view" />
        </a>
      </li>
      {canEdit ? (
        <li title={labels.preview} data-bs-toggle="tooltip" data-placement="top">
          <a
            href="javascript:void(0)"
            onClick={(e) => {
              e.preventDefault();
              onPreview(teacherId, courseId);
            }}
          >
            <AdminSpriteIcon icon="preview" />
          </a>
        </li>
      ) : null}
    </ul>
  );
}

export function openCoursePreview(_teacherId: number, courseId: number): void {
  if (!courseId || courseId < 1) {
    window.alert('Invalid course.');
    return;
  }

  const popup = window.open(`/admin/courses/${courseId}/preview`, '_blank');
  if (!popup) {
    window.alert('Please allow pop-ups for this site to open course preview.');
  }
}
