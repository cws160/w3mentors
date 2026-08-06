import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  questionId: number;
  labels: {
    view: string;
  };
  onView: (questionId: number) => void;
};

export function AdminQuestionsActions({ questionId, labels, onView }: Props) {
  return (
    <ul className="actions">
      <li>
        <a
          href="javascript:void(0)"
          title={labels.view}
          data-bs-toggle="tooltip"
          data-placement="top"
          onClick={(e) => {
            e.preventDefault();
            onView(questionId);
          }}
        >
          <AdminSpriteIcon icon="view" />
        </a>
      </li>
    </ul>
  );
}
