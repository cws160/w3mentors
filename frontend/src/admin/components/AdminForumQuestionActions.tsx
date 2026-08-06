import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  questionId: number;
  commentCount: number;
  commentsAllowed: boolean;
  canEdit: boolean;
  labels: {
    view: string;
    delete: string;
    viewComments: string;
  };
  onView: (id: number) => void;
  onDelete: (id: number) => void;
  onViewComments: (id: number) => void;
};

export function AdminForumQuestionActions({
  questionId,
  commentCount,
  commentsAllowed,
  canEdit,
  labels,
  onView,
  onDelete,
  onViewComments,
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
      {link(labels.view, 'view', () => onView(questionId))}
      {link(labels.delete, 'delete', () => onDelete(questionId))}
      {commentCount > 0 && commentsAllowed
        ? link(labels.viewComments, 'comment', () => onViewComments(questionId))
        : null}
    </ul>
  );
}
