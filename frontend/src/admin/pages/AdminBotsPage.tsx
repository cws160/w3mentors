import { type FormEvent, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

export function AdminBotsPage() {
  const { lbl, langId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [botsTxt, setBotsTxt] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const canEdit = Boolean(privileges.canEditRobotsSection) || admin?.id === 1;

  useEffect(() => {
    void adminApi.pageText('bots', langId).then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_ROBOTS.TXT', 'Robots.txt'),
        summary: pageText.summary,
        warning: pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });

    return () => clearMeta();
  }, [clearMeta, langId, lbl, setMeta]);

  useEffect(() => {
    setLoading(true);
    setError('');
    void adminApi
      .botsShow()
      .then((res) => setBotsTxt(String(res.data.data?.bots_txt ?? '')))
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
        );
      })
      .finally(() => setLoading(false));
  }, [lbl]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!canEdit) {
      return;
    }

    setSaving(true);
    setError('');
    setSuccess('');
    try {
      const res = await adminApi.botsSetup(botsTxt);
      setSuccess(res.data.message ?? lbl('LBL_SETUP_SUCCESSFUL', 'Setup successful'));
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_ROBOTS.TXT', 'Robots.txt')}</li>
          </ul>
        </div>

        <div className="card">
          <div className="card-body">
            {loading ? (
              <div className="table-processing loaderJs">
                <div className="spinner spinner--sm spinner--brand" />
              </div>
            ) : (
              <form className="form layout--" name="frmRobots" onSubmit={onSubmit}>
                {error ? <div className="alert alert-danger">{error}</div> : null}
                {success ? <div className="alert alert-success">{success}</div> : null}
                <div className="row">
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label" htmlFor="botsTxt">
                          {lbl('LBL_ROBOTS_FILE_TXT', 'robots.txt file')}
                        </label>
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <textarea
                            id="botsTxt"
                            className="form-control"
                            name="botsTxt"
                            title={lbl('LBL_ROBOTS_FILE_TXT', 'robots.txt file')}
                            rows={18}
                            value={botsTxt}
                            onChange={(e) => setBotsTxt(e.target.value)}
                            readOnly={!canEdit}
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                {canEdit ? (
                  <div className="form-actions">
                    <button type="submit" name="btn_submit" className="btn btn-primary" disabled={saving}>
                      {lbl('LBL_Save_Changes', 'Save changes')}
                    </button>
                  </div>
                ) : null}
              </form>
            )}
          </div>
        </div>
      </div>
    </main>
  );
}
