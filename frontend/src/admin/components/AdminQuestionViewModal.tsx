import { useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type QuestionDetail = {
  id: number;
  title: string;
  detail: string;
  type: number;
  type_label: string;
  teacher_name: string;
  category_name: string;
  subcategory_name: string;
  status_label: string;
  marks: number;
  hint: string;
  created_at: string;
  options: string[];
  answers: string[];
};

type Props = {
  questionId: number | null;
  onClose: () => void;
};

const TYPE_TEXT = 3;

export function AdminQuestionViewModal({ questionId, onClose }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [data, setData] = useState<QuestionDetail | null>(null);

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'N/A');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY HH:mm') : value;
  };

  const load = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.questionShow(id);
      setData(res.data.data as unknown as QuestionDetail);
    } catch (e: unknown) {
      setData(null);
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load question',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (questionId) {
      void load(questionId);
    } else {
      setData(null);
      setError('');
    }
  }, [load, questionId]);

  return (
    <AdminModal
      open={questionId !== null}
      title={lbl('LBL_QUESTIONS_DETAIL', 'Questions detail')}
      size="lg"
      onClose={onClose}
    >
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {data && !loading ? (
        <div className="form-edit-body p-0">
          <table className="table table-coloum">
            <tbody>
              <tr>
                <th width="40%">{lbl('LBL_TITLE', 'Title')}</th>
                <td>{data.title}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_DESCRIPTION', 'Description')}</th>
                <td>{data.detail ? data.detail : lbl('LBL_NA', 'N/A')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_TYPE', 'Type')}</th>
                <td>{data.type_label}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_TEACHER_NAME', 'Teacher name')}</th>
                <td>{data.teacher_name}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_CATEGORY', 'Category')}</th>
                <td>{data.category_name}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_SUB_CATEGORY', 'Sub category')}</th>
                <td>{data.subcategory_name || lbl('LBL_NA', 'N/A')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_STATUS', 'Status')}</th>
                <td>{data.status_label}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_MARKS', 'Marks')}</th>
                <td>{data.marks}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_HINT', 'Hint')}</th>
                <td>{data.hint || lbl('LBL_NA', 'N/A')}</td>
              </tr>
              <tr>
                <th>{lbl('LBL_ADDED_ON', 'Added on')}</th>
                <td>{formatDate(data.created_at)}</td>
              </tr>
              {data.type !== TYPE_TEXT ? (
                <>
                  <tr>
                    <th>{lbl('LBL_OPTIONS', 'Options')}</th>
                    <td>
                      {data.options.length > 0 ? (
                        <ul>
                          {data.options.map((option) => (
                            <li key={option}>{option}</li>
                          ))}
                        </ul>
                      ) : (
                        lbl('LBL_NA', 'N/A')
                      )}
                    </td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_ANSWERS', 'Answers')}</th>
                    <td>
                      {data.answers.length > 0 ? (
                        <ul>
                          {data.answers.map((answer) => (
                            <li key={answer}>{answer}</li>
                          ))}
                        </ul>
                      ) : (
                        lbl('LBL_NA', 'N/A')
                      )}
                    </td>
                  </tr>
                </>
              ) : null}
            </tbody>
          </table>
        </div>
      ) : null}
    </AdminModal>
  );
}
