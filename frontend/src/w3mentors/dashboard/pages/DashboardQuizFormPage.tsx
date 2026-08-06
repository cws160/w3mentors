import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, Navigate, useNavigate, useParams } from 'react-router-dom';
import { api } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';
import { QuizFormNavigation, type QuizCompletionStatus } from '../quizzes/QuizFormNavigation';
import { QuizFormQuestionsStep } from '../quizzes/QuizFormQuestionsStep';
import { QuizFormSettingsStep, type QuizFormSettingsStepHandle } from '../quizzes/QuizFormSettingsStep';

type QuizFormData = {
  id: number;
  title: string;
  type: number;
  detail: string;
};

type FormMeta = {
  types: { value: number; label: string }[];
};

type Step = 'general' | 'questions' | 'settings';

/** Legacy dashboard/views/quizzes/form.php + basic.php + questions.php + setting.php */
export function DashboardQuizFormPage() {
  const role = useDashboardRole();
  const navigate = useNavigate();
  const { lbl } = useSite();
  const { quizId: quizIdParam } = useParams();
  const urlQuizId = quizIdParam ? Number(quizIdParam) : 0;
  const [activeQuizId, setActiveQuizId] = useState(urlQuizId);

  const [step, setStep] = useState<Step>('general');
  const [meta, setMeta] = useState<FormMeta | null>(null);
  const [form, setForm] = useState({ title: '', type: '1', detail: '' });
  const [completion, setCompletion] = useState<QuizCompletionStatus | null>(null);
  const [questionCount, setQuestionCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const settingsRef = useRef<QuizFormSettingsStepHandle>(null);

  useEffect(() => {
    setActiveQuizId(urlQuizId);
  }, [urlQuizId]);

  const loadCompletion = useCallback(() => {
    if (activeQuizId < 1) {
      setCompletion(null);
      return;
    }
    api
      .get<{ data: QuizCompletionStatus }>(`/dashboard/quizzes/${activeQuizId}/completion-status`)
      .then((res) => setCompletion(res.data.data))
      .catch(() => setCompletion(null));
  }, [activeQuizId]);

  const loadBasic = useCallback(() => {
    setLoading(true);
    setError('');
    const path = urlQuizId > 0 ? `/dashboard/quizzes/form/${urlQuizId}` : '/dashboard/quizzes/form/0';
    return api
      .get<{ data: QuizFormData; meta: FormMeta }>(path)
      .then((res) => {
        const data = res.data.data;
        setMeta(res.data.meta);
        setForm({
          title: data.title,
          type: String(data.type || 1),
          detail: data.detail,
        });
      })
      .catch(() => setError(lbl('LBL_Something_went_wrong', 'Something went wrong.')))
      .finally(() => setLoading(false));
  }, [urlQuizId, lbl]);

  useEffect(() => {
    loadBasic();
  }, [loadBasic]);

  useEffect(() => {
    loadCompletion();
  }, [loadCompletion]);

  useEffect(() => {
    if (activeQuizId < 1) {
      setQuestionCount(0);
      return;
    }
    api
      .get<{ data: unknown[] }>(`/dashboard/quizzes/${activeQuizId}/questions`)
      .then((res) => setQuestionCount(res.data.data.length))
      .catch(() => setQuestionCount(0));
  }, [activeQuizId]);

  if (role !== 'teacher') {
    return <Navigate to={dashboardPath('learner')} replace />;
  }

  const saveGeneral = async () => {
    setSaving(true);
    setError('');
    try {
      const res = await api.post<{ data: { id: number } }>('/dashboard/quizzes/form', {
        quiz_id: activeQuizId,
        title: form.title.trim(),
        type: Number(form.type),
        detail: form.detail.trim(),
      });
      const newId = res.data.data.id;
      setActiveQuizId(newId);
      setStep('questions');
      navigate(dashboardPath('teacher', `quizzes/form/${newId}`), { replace: activeQuizId === 0 });
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_Something_went_wrong', 'Something went wrong.');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const goToSettings = () => {
    if (questionCount < 1) {
      window.alert(lbl('LBL_SELECT_QUESTIONS', 'Please select questions'));
      return;
    }
    setStep('settings');
  };

  const saveSettings = async () => {
    setSaving(true);
    const ok = await settingsRef.current?.save();
    setSaving(false);
    if (ok) {
      navigate(dashboardPath('teacher', 'quizzes'));
    }
  };

  const primaryAction = () => {
    if (step === 'general') return saveGeneral();
    if (step === 'questions') return goToSettings();
    return saveSettings();
  };

  const primaryLabel =
    step === 'general'
      ? lbl('LBL_SAVE_&_NEXT', 'Save & next')
      : step === 'questions'
        ? lbl('LBL_SAVE_&_NEXT', 'Save & next')
        : lbl('LBL_SAVE', 'Save');

  const typeOptions = meta?.types ?? [
    { value: 1, label: lbl('LBL_AUTO_GRADED', 'Auto graded') },
    { value: 2, label: lbl('LBL_NON_GRADED', 'Non graded') },
  ];

  return (
    <div className="container container--fixed">
      <div className="page__head">
        <Link to={dashboardPath('teacher', 'quizzes')} className="page-back">
          <DashboardSpriteIcon id="arrow-back" className="icon icon--back me-2" />
          {lbl('LBL_BACK_TO_QUIZZES', 'Back to quizzes')}
        </Link>
        <div className="row align-items-center justify-content-between">
          <div className="col-sm-8">
            <h1>{lbl('LBL_MANAGE_QUIZZES', 'Manage quizzes')}</h1>
            <p className="m-0">&nbsp;</p>
          </div>
        </div>
      </div>
      <div className="page__body" id="pageContentJs">
        {loading && step === 'general' ? (
          <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : error && !form.title && activeQuizId === 0 ? (
          <p className="color-secondary padding-6">{error}</p>
        ) : (
          <div className="page-layout">
            <div className="page-layout__small">
              <QuizFormNavigation
                quizId={activeQuizId}
                active={step}
                completion={completion}
                onGeneral={() => activeQuizId > 0 && setStep('general')}
                onQuestions={() => activeQuizId > 0 && setStep('questions')}
                onSettings={() => activeQuizId > 0 && questionCount > 0 && setStep('settings')}
                primaryLabel={primaryLabel}
                onPrimary={primaryAction}
                saving={saving}
              />
            </div>
            <div className="page-layout__large">
              {step === 'general' && (
                <div className="box-panel">
                  <div className="box-panel__head border-bottom">
                    <h4>{lbl('LBL_SETUP_QUIZ', 'Setup quiz')}</h4>
                  </div>
                  <div className="box-panel__body">
                    <div className="box-panel__container">
                      <div className="row">
                        <div className="col-md-12">
                          <div className="field-set">
                            <div className="caption-wraper">
                              <label className="field_label">{lbl('LBL_TITLE', 'Title')}</label>
                            </div>
                            <div className="field-wraper">
                              <div className="field_cover">
                                <input
                                  type="text"
                                  className="form-control"
                                  value={form.title}
                                  onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="row">
                        <div className="col-md-12">
                          <div className="field-set">
                            <div className="caption-wraper">
                              <label className="field_label">{lbl('LBL_TYPE', 'Type')}</label>
                            </div>
                            <div className="field-wraper">
                              <div className="field_cover">
                                <select
                                  className="form-control"
                                  value={form.type}
                                  disabled={activeQuizId > 0}
                                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}
                                >
                                  {typeOptions.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                      {opt.label}
                                    </option>
                                  ))}
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="row">
                        <div className="col-md-12">
                          <div className="field-set">
                            <div className="caption-wraper">
                              <label className="field_label">
                                {lbl('LBL_INSTRUCTIONS', 'Instructions')}
                              </label>
                            </div>
                            <div className="field-wraper">
                              <div className="field_cover">
                                <textarea
                                  className="form-control"
                                  rows={8}
                                  value={form.detail}
                                  onChange={(e) => setForm((f) => ({ ...f, detail: e.target.value }))}
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      {error && <p className="text-danger small">{error}</p>}
                    </div>
                  </div>
                </div>
              )}
              {step === 'questions' && activeQuizId > 0 && (
                <QuizFormQuestionsStep
                  quizId={activeQuizId}
                  onChanged={(count) => {
                    setQuestionCount(count);
                    loadCompletion();
                  }}
                />
              )}
              {step === 'settings' && activeQuizId > 0 && (
                <QuizFormSettingsStep
                  ref={settingsRef}
                  quizId={activeQuizId}
                  onSaved={loadCompletion}
                />
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
