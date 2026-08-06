import { FormEvent, useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import { legacyFileUrl, legacyImageUrl } from '../utils/adminMedia';

const STATUS_CANCELLED = 2;

export type AdminTeacherRequestModalType = 'view' | 'change-status';

type TeacherRequestView = {
  id: number;
  user_id: number;
  reference: string;
  requested_on: string;
  status: number;
  status_label: string;
  comments: string;
  first_name: string;
  last_name: string;
  gender: string;
  phone_display: string;
  video_link: string;
  biography: string;
  teach_languages: string[];
  speak_languages: string[];
  profile_image_type: number;
  profile_image_user_id: number;
  photo_id_file_id: number | null;
  photo_id_file_name: string;
};

type Props = {
  active: { type: AdminTeacherRequestModalType; requestId: number; userId: number } | null;
  onClose: () => void;
  onUpdated: () => void;
};

export function AdminTeacherRequestModals({ active, onClose, onUpdated }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [viewData, setViewData] = useState<TeacherRequestView | null>(null);
  const [statusValue, setStatusValue] = useState('');
  const [statusComments, setStatusComments] = useState('');
  const [saving, setSaving] = useState(false);

  const modalType = active?.type ?? null;
  const requestId = active?.requestId ?? 0;

  const resetMessages = () => {
    setError('');
    setSuccess('');
  };

  const formatDate = (value: string) => {
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY hh:mm A') : value;
  };

  const loadView = useCallback(async (id: number) => {
    setLoading(true);
    resetMessages();
    try {
      const res = await adminApi.teacherRequestView(id);
      setViewData(res.data.data as unknown as TeacherRequestView);
    } catch (e: unknown) {
      setError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Failed to load request');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadStatusForm = useCallback(async (id: number) => {
    setLoading(true);
    resetMessages();
    setStatusValue('');
    setStatusComments('');
    try {
      await adminApi.teacherRequestStatusForm(id);
    } catch (e: unknown) {
      setError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Failed to load form');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!active) return;
    setViewData(null);
    resetMessages();
    if (active.type === 'view') void loadView(active.requestId);
    if (active.type === 'change-status') void loadStatusForm(active.requestId);
  }, [active, loadStatusForm, loadView]);

  const submitStatus = async (e: FormEvent) => {
    e.preventDefault();
    if (!requestId) return;
    setSaving(true);
    resetMessages();
    try {
      await adminApi.updateTeacherRequestStatus(requestId, {
        status: Number(statusValue),
        comments: statusComments,
      });
      setSuccess(lbl('LBL_STATUS_UPDATED_SUCCESSFULLY', 'Status updated successfully'));
      onUpdated();
      setTimeout(onClose, 600);
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Update failed');
    } finally {
      setSaving(false);
    }
  };

  const modalTitle = () => {
    switch (modalType) {
      case 'view':
        return lbl('LBL_TEACHER_REQUEST_DETAIL', 'Teacher request detail');
      case 'change-status':
        return lbl('LBL_UPDATE_STATUS', 'Update status');
      default:
        return '';
    }
  };

  return (
    <AdminModal open={!!active} title={modalTitle()} size="md" onClose={onClose}>
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {success ? <div className="alert alert-success m-3">{success}</div> : null}

      {modalType === 'view' && viewData && !loading ? (
        <div className="form-edit-body p-0">
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_REQUEST_INFORMATION', 'Request information')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <tr>
                    <th width="40%">{lbl('LBL_REFERENCE_NUMBER', 'Reference number')}</th>
                    <td>{viewData.reference}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_REQUESTED_ON', 'Requested on')}</th>
                    <td>{formatDate(viewData.requested_on)}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                    <td>{viewData.status_label}</td>
                  </tr>
                  {viewData.comments ? (
                    <tr>
                      <th>{lbl('LBL_COMMENTS/REASON', 'Comments/reason')}</th>
                      <td style={{ whiteSpace: 'pre-wrap' }}>{viewData.comments}</td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          </div>
          <div className="table-group">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_PROFILE_INFORMATION', 'Profile information')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <tr>
                    <th>{lbl('LBL_PROFILE_PICTURE', 'Profile picture')}</th>
                    <td>
                      <img
                        src={legacyImageUrl(viewData.profile_image_type, viewData.profile_image_user_id)}
                        alt=""
                        style={{ width: 80 }}
                      />
                    </td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_PHOTO_ID', 'Photo ID')}</th>
                    <td>
                      {viewData.photo_id_file_id ? (
                        <a
                          className="link-text"
                          href={legacyFileUrl(viewData.photo_id_file_id)}
                          target="_blank"
                          rel="noreferrer"
                        >
                          {viewData.photo_id_file_name || lbl('LBL_DOWNLOAD', 'Download')}
                        </a>
                      ) : (
                        '—'
                      )}
                    </td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_FIRST_NAME', 'First name')}</th>
                    <td>{viewData.first_name}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_LAST_NAME', 'Last name')}</th>
                    <td>{viewData.last_name}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_GENDER', 'Gender')}</th>
                    <td>{viewData.gender}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_PHONE_NUMBER', 'Phone number')}</th>
                    <td dir="ltr">{viewData.phone_display || lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_YOU_TUBE_VIDEO_LINK', 'YouTube video link')}</th>
                    <td>{viewData.video_link || lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_PROFILE_INFO', 'Profile info')}</th>
                    <td style={{ whiteSpace: 'pre-wrap' }}>{viewData.biography || lbl('LBL_NA', 'N/A')}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_TREQUEST_TEACHING_LANGUAGE', 'Teaching language')}</th>
                    <td>
                      <ul>
                        {viewData.teach_languages.map((lang) => (
                          <li key={lang}>{lang}</li>
                        ))}
                      </ul>
                    </td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_SPOKEN_LANGUAGE', 'Spoken language')}</th>
                    <td>
                      {viewData.speak_languages.length > 0
                        ? viewData.speak_languages.map((lang) => <div key={lang}>{lang}</div>)
                        : lbl('LBL_NA', 'N/A')}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      ) : null}

      {modalType === 'change-status' && !loading ? (
        <div className="form-edit-body">
          <form className="form form_horizontal" onSubmit={submitStatus}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_STATUS', 'Status')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <select
                      className="form-control"
                      value={statusValue}
                      onChange={(e) => setStatusValue(e.target.value)}
                      required
                    >
                      <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                      <option value="1">{lbl('LBL_Approved', 'Approved')}</option>
                      <option value="2">{lbl('LBL_Cancelled_Teacher_Req', 'Cancelled')}</option>
                    </select>
                  </div>
                </div>
              </div>
              {Number(statusValue) === STATUS_CANCELLED ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">{lbl('LBL_REASON_FOR_CANCELLATION', 'Reason for cancellation')}</label>
                    </div>
                    <div className="field-wraper">
                      <textarea
                        id="comments"
                        className="form-control"
                        value={statusComments}
                        onChange={(e) => setStatusComments(e.target.value)}
                        rows={4}
                      />
                    </div>
                  </div>
                </div>
              ) : null}
              <div className="col-md-12">
                <button type="submit" className="btn btn-brand" disabled={saving}>
                  {lbl('LBL_UPDATE', 'Update')}
                </button>
              </div>
            </div>
          </form>
        </div>
      ) : null}
    </AdminModal>
  );
}
