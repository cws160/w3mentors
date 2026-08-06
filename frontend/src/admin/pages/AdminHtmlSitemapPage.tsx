import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { SitemapHtmlContent } from '../../w3mentors/components/SitemapHtmlContent';
import type { SitemapHtmlData } from '../../api/client';
import { adminApi } from '../api/adminClient';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

export function AdminHtmlSitemapPage() {
  const { lbl, langId } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();

  const [data, setData] = useState<SitemapHtmlData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    void adminApi.pageText('html-sitemap', langId).then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_HTML_SITEMAP', 'HTML sitemap'),
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
      .sitemapHtml(langId)
      .then((res) => setData(res.data.data))
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
        );
      })
      .finally(() => setLoading(false));
  }, [langId, lbl]);

  const publicUrl = data?.public_url ?? '/sitemap';

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_HTML_SITEMAP', 'HTML sitemap')}</li>
          </ul>
          <div className="action-toolbar">
            <a href={publicUrl} className="btn btn-primary" target="_blank" rel="noreferrer">
              {lbl('LBL_OPEN_IN_NEW_TAB', 'Open in new tab')}
            </a>
          </div>
        </div>

        <div className="card">
          <div className="card-body">
            {loading ? (
              <div className="table-processing loaderJs">
                <div className="spinner spinner--sm spinner--brand" />
              </div>
            ) : error ? (
              <div className="alert alert-danger">{error}</div>
            ) : data ? (
              <SitemapHtmlContent data={data} lbl={lbl} />
            ) : (
              <div className="alert alert--info">
                {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
              </div>
            )}
          </div>
        </div>
      </div>
    </main>
  );
}
