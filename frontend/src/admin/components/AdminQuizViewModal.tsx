import { useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type QuizDetail = {
  id: number;
  title: string;
  detail: string;
  type_label: string;
  teacher_name: string;
  active_label: string;
  status_label: string;
  created_at: string;
  duration_label: string | null;
  attempts: number;
  passmark_label: string | null;
  validity: number;
  certificate_label: string;
  questions_count: number;
  pass_message: string;
  fail_message: string;
};

type Props = {
  quizId: number | null;
  onClose: () => void;
};

export function AdminQuizViewModal({ quizId, onClose }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [data, setData] = useState<QuizDetail | null>(null);

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'N/A');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY HH:mm') : value;
  };

  const load = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.quizShow(id);
      setData(res.data.data as unknown as QuizDetail);
    } catch (e: unknown) {
      setData(null);
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load quiz',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (quizId) {
      void load(quizId);
    } else {
      setData(null);
      setError('');
    }
  }, [load, quizId]);

  return (
    <AdminModal
      open={quizId !== null}
      title={lbl('LBL_QUIZ_DETAIL', 'Quiz detail')}
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
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_BASIC_DETAILS', 'Basic details')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <tr>
                    <th width="40%">{lbl('LBL_TITLE', 'Title')}</th>
                    <td>{data.title}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_INSTRUCTIONS', 'Instructions')}</th>
                    <td>
                      {data.detail ? (
                        <div className="editor-content" dangerouslySetInnerHTML={{ __html: data.detail }} />
                      ) : (
                        lbl('LBL_NA', 'N/A')
                      )}
                    </td>
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
                    <th>{lbl('LBL_ACTIVE', 'Active')}</th>
                    <td>{data.active_label}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                    <td>{data.status_label}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_ADDED_ON', 'Added on')}</th>
                    <td>{formatDate(data.created_at)}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_SETTINGS', 'Settings')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <tr>
                    <th width="40%">{lbl('LBL_DURATION', 'Duration')}</th>
                    <td>{data.duration_label ?? lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_NO_OF_ATTEMPTS_ALLOWED', 'No. of attempts allowed')}</th>
                    <td>{data.attempts > 0 ? data.attempts : lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_PASS_PERCENTAGE', 'Pass percentage')}</th>
                    <td>{data.passmark_label ?? lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_VALIDITY', 'Validity')}</th>
                    <td>
                      {data.validity > 0
                        ? lbl('LBL_{validity}_HOUR(S)', '{validity} hour(s)').replace(
                            '{validity}',
                            String(data.validity),
                          )
                        : lbl('LBL_NA', 'N/A')}
                    </td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_CERTIFICATE', 'Certificate')}</th>
                    <td>{data.certificate_label}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_NO_OF_QUESTIONS', 'No. of questions')}</th>
                    <td>{data.questions_count}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_PASS_MESSAGE', 'Pass message')}</th>
                    <td>{data.pass_message || lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_FAIL_MESSAGE', 'Fail message')}</th>
                    <td>{data.fail_message || lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      ) : null}
    </AdminModal>
  );
}
