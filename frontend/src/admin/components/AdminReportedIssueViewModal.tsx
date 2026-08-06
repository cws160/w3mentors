import { useEffect, useMemo, useState } from 'react';
import moment from 'moment';
import { adminApi } from '../api/adminClient';
import { useSite } from '../../w3mentors/context/SiteContext';
import { AdminModal } from './AdminModal';

type IssueRow = {
  repiss_id?: number;
  id?: number;
};

type IssueLog = {
  date?: string;
  author?: string;
  role?: string;
  message?: string;
  comments?: string;
  class_name?: string;
  icon?: string;
};

type DetailRow = {
  label?: string;
  value?: string;
  is_note?: boolean;
};

type IssueDetail = {
  logs?: IssueLog[];
  record_details?: DetailRow[];
};

type Props = {
  row: IssueRow | null;
  onClose: () => void;
};

type DetailState = {
  issueId: number;
  detail: IssueDetail | null;
  error: string;
};

function LogIcon({ icon }: { icon?: string }) {
  if (icon === 'check') {
    return (
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M10,15.172l9.192-9.193,1.415,1.414L10,18,3.636,11.636,5.05,10.222Z" />
      </svg>
    );
  }

  if (icon === 'sync') {
    return (
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M5.463,4.433A10,10,0,0,1,20.19,17.74L17,12h3A8,8,0,0,0,6.46,6.228l-1-1.8ZM18.537,19.567A10,10,0,0,1,3.81,6.26L7,12H4a8,8,0,0,0,13.54,5.772Z" />
      </svg>
    );
  }

  return (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
      <path d="M12,22A10,10,0,1,1,22,12,10,10,0,0,1,12,22Zm0-2a8,8,0,1,0-8-8A8,8,0,0,0,12,20Zm-1-5h2v2H11Zm0-8h2v6H11Z" />
    </svg>
  );
}

export function AdminReportedIssueViewModal({ row, onClose }: Props) {
  const { lbl } = useSite();
  const [detailState, setDetailState] = useState<DetailState>({ issueId: 0, detail: null, error: '' });

  const issueId = useMemo(() => Number(row?.repiss_id ?? row?.id ?? 0), [row]);

  useEffect(() => {
    if (!issueId) {
      return;
    }

    let active = true;

    adminApi.reportedIssueDetail(issueId)
      .then((response) => {
        if (active) {
          setDetailState({ issueId, detail: response.data.data as IssueDetail, error: '' });
        }
      })
      .catch(() => {
        if (active) {
          setDetailState({
            issueId,
            detail: null,
            error: lbl('LBL_UNABLE_TO_LOAD_DETAILS', 'Unable to load details'),
          });
        }
      });

    return () => {
      active = false;
    };
  }, [issueId, lbl]);

  const formatDate = (value: string | undefined) => {
    if (!value) return lbl('LBL_NA', 'N/A');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm').toUpperCase() : value;
  };

  const valueOrNa = (value: string | undefined) => {
    const text = String(value ?? '').trim();
    return text || lbl('LBL_NA', 'N/A');
  };

  const detailValue = (item: DetailRow) => (
    String(item.label ?? '').toLowerCase().includes('time') ? formatDate(item.value) : valueOrNa(item.value)
  );

  const loading = Boolean(issueId) && detailState.issueId !== issueId;
  const error = detailState.issueId === issueId ? detailState.error : '';
  const detail = detailState.issueId === issueId ? detailState.detail : null;

  return (
    <AdminModal
      open={Boolean(row)}
      onClose={onClose}
      title={lbl('LBL_ISSUE_LOGS', 'Issue logs')}
      size="lg"
    >
      {loading ? (
        <div className="p-4">{lbl('LBL_PROCESSING', 'Processing...')}</div>
      ) : error ? (
        <div className="alert alert-danger m-4">{error}</div>
      ) : detail ? (
        <>
          <div className="card">
            <div className="log-list">
              {(detail.logs ?? []).map((log, index) => (
                <div className={`log-item ${log.class_name ?? 'is-rejected'}`} key={`${log.date}-${index}`}>
                  <div className="log-item__media">
                    <span className="log-icon">
                      <LogIcon icon={log.icon} />
                    </span>
                  </div>
                  <div className="log-item__content">
                    <span className="log-date">{formatDate(log.date)}</span>
                    <span className="log-title">
                      <span className="log-author">
                        {valueOrNa(log.author)} ({valueOrNa(log.role)})
                      </span>
                      <span className="log-message"> {valueOrNa(log.message)}</span>
                    </span>
                    <div style={{ display: 'block' }}>
                      <div className="log-comments">
                        <div className="repeat-element">
                          <div className="repeat-element__title">{lbl('LBL_COMMENTS', 'Comments')}</div>
                          <div className="repeat-element__content">{valueOrNa(log.comments)}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="table-group">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_RECORD_DETAILS', 'Record details')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  {(detail.record_details ?? []).map((item, index) => (
                    item.is_note ? (
                      <tr key={`note-${index}`}>
                        <td colSpan={2}>
                          <span className="link-primary">{valueOrNa(item.value)}</span>
                        </td>
                      </tr>
                    ) : (
                      <tr key={`${item.label}-${index}`}>
                        <th width="40%">{valueOrNa(item.label)}:</th>
                        <td>{detailValue(item)}</td>
                      </tr>
                    )
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </>
      ) : null}
    </AdminModal>
  );
}
