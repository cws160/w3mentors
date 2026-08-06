import { Link } from 'react-router-dom';
import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  quizId: number;
  canViewQuestions: boolean;
  labels: {
    view: string;
    questionBank: string;
  };
  onView: (quizId: number) => void;
};

export function AdminQuizzesActions({ quizId, canViewQuestions, labels, onView }: Props) {
  const linkProps = (title: string, onClick?: () => void) => ({
    href: 'javascript:void(0)',
    title,
    'data-bs-toggle': 'tooltip',
    'data-placement': 'top',
    onClick: (e: React.MouseEvent) => {
      e.preventDefault();
      onClick?.();
    },
  });

  return (
    <ul className="actions">
      <li>
        <a {...linkProps(labels.view, () => onView(quizId))}>
          <AdminSpriteIcon icon="view" />
        </a>
      </li>
      {canViewQuestions ? (
        <li>
          <Link
            to={`/admin/questions?quiz_id=${quizId}`}
            title={labels.questionBank}
            data-bs-toggle="tooltip"
            data-placement="top"
          >
            <AdminSpriteIcon icon="list" />
          </Link>
        </li>
      ) : null}
    </ul>
  );
}
