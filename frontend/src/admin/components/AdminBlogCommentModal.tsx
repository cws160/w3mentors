import { type FormEvent, useCallback, useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type CommentDetail = {
  bpcomment_id: number;
  bpcomment_author_name: string;
  bpcomment_author_email: string;
  bpcomment_content: string;
  bpcomment_approved: number;
  bpcomment_added_on: string;
  bpcomment_user_ip: string;
  bpcomment_user_agent: string;
  post_title: string;
};

type Props = {
  open: boolean;
  commentId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminBlogCommentModal({ open, commentId, onClose, onSaved }: Props) {
  const { lbl, langId } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [detail, setDetail] = useState<CommentDetail | null>(null);
  const [approved, setApproved] = useState('0');

  const reset = useCallback(() => {
    setError('');
    setDetail(null);
    setApproved('0');
  }, []);

  useEffect(() => {
    if (!open || commentId < 1) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .blogCommentShow(commentId, langId)
      .then((res) => {
        const data = res.data.data as CommentDetail;
        setDetail(data);
        setApproved(String(data.bpcomment_approved ?? 0));
      })
      .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
      .finally(() => setLoading(false));
  }, [commentId, langId, lbl, open, reset]);

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'NA');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const statusLabel = (value: number) =>
    value === 1 ? lbl('LBL_Approved', 'Approved') : lbl('LBL_Pending', 'Pending');

  const onSaveStatus = async (e: FormEvent) => {
    e.preventDefault();
    if (commentId < 1) return;
    setSaving(true);
    setError('');
    try {
      await adminApi.updateBlogCommentStatus(commentId, { bpcomment_approved: Number(approved) });
      onSaved();
      onClose();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to save')
          : 'Unable to save';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_Comment_Details', 'Comment details')}
      size="lg"
      onClose={onClose}
    >
      {error ? <div className="alert alert-danger m-4">{error}</div> : null}
      {loading ? (
        <div className="table-processing loaderJs">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : detail ? (
        <div className="form-edit-body p-0">
          <div className="table-group mb-1">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_Details', 'Details')}</h6>
            </div>
            <div className="table-group-body">
              <table className="table table-coloum">
                <tbody>
                  <tr>
                    <th style={{ width: '35%' }}>{lbl('LBL_Full_Name', 'Full name')}</th>
                    <td>{detail.bpcomment_author_name}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_Email', 'Email')}</th>
                    <td>{detail.bpcomment_author_email}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_Posted_On', 'Posted on')}</th>
                    <td>{formatDate(detail.bpcomment_added_on)}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_Blog_Post_Title', 'Blog post title')}</th>
                    <td>{detail.post_title}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_Comment', 'Comment')}</th>
                    <td style={{ whiteSpace: 'pre-wrap' }}>{detail.bpcomment_content}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_User_IP', 'User IP')}</th>
                    <td>{detail.bpcomment_user_ip}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_User_Agent', 'User agent')}</th>
                    <td>{detail.bpcomment_user_agent}</td>
                  </tr>
                  <tr>
                    <th>{lbl('LBL_Status', 'Status')}</th>
                    <td>{statusLabel(detail.bpcomment_approved)}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div className="table-group">
            <div className="table-group-head">
              <h6 className="mb-0">{lbl('LBL_Update_Status', 'Update status')}</h6>
            </div>
            <div className="table-group-body">
              <form className="form form_horizontal" onSubmit={onSaveStatus}>
                <div className="row">
                  <div className="col-sm-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">{lbl('LBL_Comment_Status', 'Comment status')}</label>
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <select
                            className="form-control"
                            value={approved}
                            onChange={(e) => setApproved(e.target.value)}
                          >
                            <option value="1">{lbl('LBL_Approved', 'Approved')}</option>
                            <option value="0">{lbl('LBL_Pending', 'Pending')}</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="form-actions">
                  <button type="submit" name="btn_submit" className="btn btn-brand" disabled={saving}>
                    {saving
                      ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                      : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      ) : null}
    </AdminModal>
  );
}
