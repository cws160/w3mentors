import { Link } from 'react-router-dom';
import { useSite } from '../../context/SiteContext';
import { dashboardPath } from '../dashboardPaths';

type Step = 'general' | 'questions' | 'settings';

export type QuizCompletionStatus = {
  general: boolean;
  questions: boolean;
  settings: boolean;
  is_complete: boolean;
};

type Props = {
  quizId: number;
  active: Step;
  completion?: QuizCompletionStatus | null;
  onGeneral?: () => void;
  onQuestions?: () => void;
  onSettings?: () => void;
  primaryLabel?: string;
  onPrimary?: () => void;
  saving?: boolean;
};

/** Legacy `quizzes/navigation.php` step sidebar */
export function QuizFormNavigation({
  quizId,
  active,
  completion,
  onGeneral,
  onQuestions,
  onSettings,
  primaryLabel,
  onPrimary,
  saving,
}: Props) {
  const { lbl } = useSite();
  const canNavigate = quizId > 0;

  const tabClass = (step: Step, tabKey: keyof QuizCompletionStatus) => {
        const parts = [`${step === 'general' ? 'general' : step === 'questions' ? 'questions' : 'settings'}TabJs`];
        if (completion) {
          parts.push(completion[tabKey] ? 'is-completed' : 'is-progress');
        } else {
          parts.push('is-error');
        }
        if (active === step) {
          parts.push('is-active');
        }
        return parts.join(' ');
      };

  const renderTab = (step: Step, tabKey: keyof QuizCompletionStatus, label: string, onClick?: () => void) => {
    const clickable = canNavigate && active !== step && onClick;
    return (
      <li className={tabClass(step, tabKey)}>
        {clickable ? (
          <a href="javascript:void(0)" onClick={onClick}>
            {label}
            <span className="step-sign" />
          </a>
        ) : (
          <a href="javascript:void(0)">
            {label}
            <span className="step-sign" />
          </a>
        )}
      </li>
    );
  };

  return (
    <div className="page-layout__sticky">
      <div className="page-steps mb-4 tabs-scrollable-js">
        <ul>
          {renderTab('general', 'general', lbl('LBL_General', 'General'), onGeneral)}
          {renderTab('questions', 'questions', lbl('LBL_QUESTION_BANK', 'Question bank'), onQuestions)}
          {renderTab('settings', 'settings', lbl('LBL_SETTINGS', 'Settings'), onSettings)}
        </ul>
      </div>
      {primaryLabel && onPrimary && (
        <div className="page-actions">
          <div className="page-actions__group">
            <button type="button" className="btn btn--primary" disabled={saving} onClick={onPrimary}>
              {saving ? lbl('LBL_LOADING', 'Loading...') : primaryLabel}
            </button>
          </div>
        </div>
      )}
      {quizId > 0 && (
        <p className="small mt-3">
          <Link to={dashboardPath('teacher', 'quizzes')} className="color-secondary">
            {lbl('LBL_BACK_TO_QUIZZES', 'Back to quizzes')}
          </Link>
        </p>
      )}
    </div>
  );
}
