import moment from 'moment';
import { useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { renderLegacyAdminHtml } from '../utils/adminLegacyHtml';
import { AdminModal } from './AdminModal';

type QuestionDetail = {
  fque_id: number;
  fque_title: string;
  fque_description: string;
  fque_status: number;
  status_label: string;
  fque_added_on: string;
  user_name: string;
  tags: string[];
};

type Props = {
  questionId: number | null;
  onClose: () => void;
};

export function AdminForumQuestionViewModal({ questionId, onClose }: Props) {
  const { lbl, langId } = useSite();
  const [loading, setLoading] = useState(false);
  const [detail, setDetail] = useState<QuestionDetail | null>(null);

  useEffect(() => {
    if (!questionId) {
      setDetail(null);
      return;
    }
    setLoading(true);
    void adminApi
      .forumQuestionShow(questionId, langId)
      .then((res) => setDetail(res.data.data as QuestionDetail))
      .catch(() => setDetail(null))
      .finally(() => setLoading(false));
  }, [langId, questionId]);

  const formatDate = (value: string | undefined) => {
    if (!value) return lbl('LBL_NA', 'N/A');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const detailRow = (label: string, value: string) => (
    <tr key={label}>
      <th width="40%">{label}</th>
      <td>{value || lbl('LBL_NA', 'N/A')}</td>
    </tr>
  );

  const htmlRow = (label: string, value: string | null | undefined) => {
    const html = renderLegacyAdminHtml(value);
    return (
      <tr key={label}>
        <th width="40%">{label}</th>
        <td>
          {html ? (
            <div className="editor-content" dangerouslySetInnerHTML={{ __html: html }} />
          ) : (
            lbl('LBL_NA', 'N/A')
          )}
        </td>
      </tr>
    );
  };

  return (
    <AdminModal
      open={questionId !== null}
      onClose={onClose}
      title={lbl('LBL_QUESTION_Detail', 'Question detail')}
      size="lg"
    >
      {loading ? (
        <div className="table-processing loaderJs">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : detail ? (
        <div className="form-edit-body p-0">
          <table className="table table-coloum">
            <tbody>
              {detailRow(lbl('LBL_Added_By', 'Added by'), detail.user_name)}
              {detailRow(lbl('LBL_Added_On', 'Added on'), formatDate(detail.fque_added_on))}
              {detailRow(lbl('LBL_STATUS', 'Status'), detail.status_label)}
              {htmlRow(lbl('LBL_Title', 'Title'), detail.fque_title)}
              {htmlRow(lbl('LBL_Description', 'Description'), detail.fque_description)}
              <tr>
                <th width="40%">{lbl('LBL_Binded_Tags', 'Tags')}</th>
                <td>
                  <div className="tags">
                    {detail.tags.length === 0 ? (
                      lbl('LBL_NA', 'N/A')
                    ) : (
                      detail.tags.map((tag) => (
                        <a key={tag} href="javascript:void(0)" className="badge bg-fill-dark mb-1">
                          {tag}
                        </a>
                      ))
                    )}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      ) : null}
    </AdminModal>
  );
}
