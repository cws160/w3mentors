import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { QuizAttachQuestionsModal } from './QuizAttachQuestionsModal';

export type QuizQuestionRow = {
  question_id: number;
  quiz_id: number;
  order: number;
  title: string;
  type: number;
  type_label: string;
  category_name: string;
  subcategory_name: string;
};

type Props = {
  quizId: number;
  onChanged?: (count: number) => void;
};

const SortIcon = () => (
  <svg className="svg-icon" viewBox="0 0 16 12.632">
    <path d="M7.579 9.263v1.684H0V9.263zm1.684-4.211v1.684H0V5.053zM7.579.842v1.684H0V.842zM13.474 12.632l-2.527-3.789H16z" />
    <path d="M12.632 2.105h1.684v7.579h-1.684z" />
    <path d="M13.473 0L16 3.789h-5.053z" />
  </svg>
);

/** Legacy dashboard/views/quizzes/questions.php */
export function QuizFormQuestionsStep({ quizId, onChanged }: Props) {
  const { lbl } = useSite();
  const { showModal, closeModal } = useModal();
  const [rows, setRows] = useState<QuizQuestionRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [dragId, setDragId] = useState<number | null>(null);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<{ data: QuizQuestionRow[] }>(`/dashboard/quizzes/${quizId}/questions`)
      .then((res) => {
        setRows(res.data.data);
        onChanged?.(res.data.data.length);
      })
      .catch(() => {
        setRows([]);
        onChanged?.(0);
      })
      .finally(() => setLoading(false));
  }, [quizId, onChanged]);

  useEffect(() => {
    load();
  }, [load]);

  const remove = async (questionId: number) => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'))) return;
    try {
      await api.delete(`/dashboard/quizzes/${quizId}/questions/${questionId}`);
      load();
      onChanged?.();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    }
  };

  const persistOrder = async (ordered: QuizQuestionRow[]) => {
    try {
      await api.put(`/dashboard/quizzes/${quizId}/questions/order`, {
        order: ordered.map((r) => r.question_id),
      });
      onChanged?.();
    } catch {
      load();
    }
  };

  const onDrop = (targetId: number) => {
    if (dragId === null || dragId === targetId) return;
    const fromIndex = rows.findIndex((r) => r.question_id === dragId);
    const toIndex = rows.findIndex((r) => r.question_id === targetId);
    if (fromIndex < 0 || toIndex < 0) return;
    const next = [...rows];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);
    setRows(next);
    setDragId(null);
    persistOrder(next);
  };

  const openAttach = () => {
    showModal(
      <QuizAttachQuestionsModal
        quizId={quizId}
        onClose={closeModal}
        onAttached={() => {
          closeModal();
          load();
          onChanged?.();
        }}
      />,
      { size: 'modal-xl' }
    );
  };

  return (
    <div className="box-panel">
      <div className="box-panel__head border-bottom">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h4>{lbl('LBL_SETUP_QUIZ', 'Setup quiz')}</h4>
          </div>
        </div>
      </div>
      <div className="box-panel__body">
        <div className="box-panel__container">
          <div className="page">
            <div className="page__head pt-0">
              <div className="d-flex align-items-center justify-content-between">
                <div>
                  <b>
                    {rows.length > 0
                      ? `${lbl('LBL_TOTAL_QUESTIONS', 'Total questions')}: ${rows.length}`
                      : ''}
                  </b>
                </div>
                <div>
                  <button type="button" className="btn color-secondary btn--bordered addQuesJs" onClick={openAttach}>
                    <svg
                      className="icon icon--uploader icon--small me-2"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                    >
                      <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-1-5h2v2h-2v-2zm2-1.645V14h-2v-1.5a1 1 0 0 1 1-1 1.5 1.5 0 1 0-1.471-1.794l-1.962-.393A3.501 3.501 0 1 1 13 13.355z" />
                    </svg>
                    {lbl('LBL_ADD_QUESTIONS', 'Add questions')}
                  </button>
                </div>
              </div>
            </div>
            <div className="page__body">
              {loading ? (
                <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
              ) : (
                <div className="table-scroll sortableWrapperJs">
                  <table className="table table--responsive table--bordered">
                    <thead>
                      <tr className="title-row">
                        <th>
                          <i className="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move">
                            <SortIcon />
                          </i>
                        </th>
                        <th>{lbl('LBL_TITLE', 'Title')}</th>
                        <th>{lbl('LBL_TYPE', 'Type')}</th>
                        <th>{lbl('LBL_CATEGORY', 'Category')}</th>
                        <th>{lbl('LBL_SUB_CATEGORY', 'Sub category')}</th>
                        <th>{lbl('LBL_ACTION', 'Action')}</th>
                      </tr>
                    </thead>
                    <tbody className="sortableJs">
                      {rows.length === 0 ? (
                        <tr>
                          <td colSpan={6}>{lbl('LBL_NO_QUESTIONS_FOUND', 'No questions found')}</td>
                        </tr>
                      ) : (
                        rows.map((row) => (
                          <tr
                            key={row.question_id}
                            data-id={row.question_id}
                            draggable
                            onDragStart={() => setDragId(row.question_id)}
                            onDragOver={(e) => e.preventDefault()}
                            onDrop={() => onDrop(row.question_id)}
                          >
                            <td>
                              <a
                                href="javascript:void(0)"
                                className="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move sortHandlerJs"
                              >
                                <SortIcon />
                              </a>
                            </td>
                            <td>
                              <DashboardFlexCell label={lbl('LBL_TITLE', 'Title')}>
                                <div style={{ maxWidth: 250 }}>
                                  <p className="mb-1 bold-600 color-black">{row.title}</p>
                                </div>
                              </DashboardFlexCell>
                            </td>
                            <td>
                              <DashboardFlexCell label={lbl('LBL_TYPE', 'Type')}>
                                <div style={{ maxWidth: 250 }}>{row.type_label}</div>
                              </DashboardFlexCell>
                            </td>
                            <td>
                              <DashboardFlexCell label={lbl('LBL_CATEGORY', 'Category')}>
                                <div style={{ maxWidth: 250 }}>{row.category_name}</div>
                              </DashboardFlexCell>
                            </td>
                            <td>
                              <DashboardFlexCell label={lbl('LBL_SUB_CATEGORY', 'Sub category')}>
                                <div style={{ maxWidth: 250 }}>{row.subcategory_name}</div>
                              </DashboardFlexCell>
                            </td>
                            <td>
                              <DashboardFlexCell label={lbl('LBL_ACTION', 'Action')}>
                                <div className="actions-group">
                                  <button
                                    type="button"
                                    className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                    onClick={() => remove(row.question_id)}
                                    title={lbl('LBL_REMOVE', 'Remove')}
                                  >
                                    <DashboardSpriteIcon id="trash" className="icon icon--issue icon--small" />
                                    <div className="tooltip tooltip--top bg-black">
                                      {lbl('LBL_REMOVE', 'Remove')}
                                    </div>
                                  </button>
                                </div>
                              </DashboardFlexCell>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
