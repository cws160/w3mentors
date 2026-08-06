import moment from 'moment';
import { useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import { renderLegacyAdminHtml } from '../utils/adminLegacyHtml';

type ReportDetail = {
  fquerep_id: number;
  report_title: string;
  fquerep_comments: string;
  reporter_name: string;
  fquerep_added_on: string;
  fquerep_status: number;
  fquerep_admin_comments: string;
  fquerep_updated_on: string;
};

type Props = {
  reportId: number | null;
  onClose: () => void;
};

export function AdminForumReportedQuestionViewModal({ reportId, onClose }: Props) {
  const { lbl, langId } = useSite();
  const [loading, setLoading] = useState(false);
  const [detail, setDetail] = useState<ReportDetail | null>(null);

  useEffect(() => {
    if (!reportId) {
      setDetail(null);
      return;
    }
    setLoading(true);
    void adminApi
      .forumReportedQuestionShow(reportId, langId)
      .then((res) => setDetail(res.data.data as ReportDetail))
      .catch(() => setDetail(null))
      .finally(() => setLoading(false));
  }, [langId, reportId]);

  const formatDate = (value: string | undefined) => {
    if (!value) {
      return lbl('LBL_Na', 'N/A');
    }
    const parsed = moment(value);
    if (!parsed.isValid() || parsed.unix() <= 0) {
      return lbl('LBL_Na', 'N/A');
    }
    return parsed.format('MMM D, YYYY HH:mm');
  };

  const reportStatusLabel = (value: number) => {
    if (value === 1) {
      return lbl('LBL_Accepted', 'Accepted');
    }
    if (value === 2) {
      return lbl('LBL_Cancelled', 'Cancelled');
    }
    return lbl('LBL_Pending', 'Pending');
  };

  return (
    <AdminModal
      open={reportId !== null}
      onClose={onClose}
      title={lbl('LBL_REPORT_INFORMATION', 'Report information')}
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
                <tr>
                  <th width="40%">{lbl('LBL_REPORT_TITLE', 'Report reason')}</th>
                  <td>{detail.report_title}</td>
                </tr>
                <tr>
                  <th width="40%">{lbl('LBL_COMMENT', 'Comment')}</th>
                  <td
                    dangerouslySetInnerHTML={{
                      __html: renderLegacyAdminHtml(detail.fquerep_comments).replace(/\n/g, '<br />'),
                    }}
                  />
                </tr>
                <tr>
                  <th width="40%">{lbl('LBL_REPORTED_BY', 'Reported by')}</th>
                  <td>{detail.reporter_name}</td>
                </tr>
                <tr>
                  <th width="40%">{lbl('LBL_REPORTED_ON', 'Reported on')}</th>
                  <td>{formatDate(detail.fquerep_added_on)}</td>
                </tr>
                <tr>
                  <th width="40%">{lbl('LBL_ACTION', 'Action')}</th>
                  <td>{reportStatusLabel(detail.fquerep_status)}</td>
                </tr>
                <tr>
                  <th width="40%">{lbl('LBL_ADMIN_COMMENT', 'Admin comment')}</th>
                  <td>
                    {detail.fquerep_admin_comments ? (
                      <span
                        dangerouslySetInnerHTML={{
                          __html: renderLegacyAdminHtml(detail.fquerep_admin_comments).replace(/\n/g, '<br />'),
                        }}
                      />
                    ) : (
                      lbl('LBL_Na', 'N/A')
                    )}
                  </td>
                </tr>
                <tr>
                  <th width="40%">{lbl('LBL_ACTION_ON', 'Action on')}</th>
                  <td>{formatDate(detail.fquerep_updated_on)}</td>
                </tr>
              </tbody>
            </table>
        </div>
      ) : null}
    </AdminModal>
  );
}
