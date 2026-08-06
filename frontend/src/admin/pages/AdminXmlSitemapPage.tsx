import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { useSitemapGenerate } from '../hooks/useSitemapGenerate';

type SitemapFile = { name: string; url: string };

export function AdminXmlSitemapPage() {
  const { lbl, langId } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const { generating } = useSitemapGenerate();

  const [content, setContent] = useState('');
  const [files, setFiles] = useState<SitemapFile[]>([]);
  const [publicUrl, setPublicUrl] = useState('/sitemap.xml');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const canEdit = Boolean(privileges.canEditSiteMap);

  const load = () => {
    setLoading(true);
    setError('');
    void adminApi
      .sitemapXml()
      .then((res) => {
        const data = res.data.data ?? {};
        setContent(String(data.content ?? ''));
        setFiles((data.files as SitemapFile[]) ?? []);
        setPublicUrl(String(data.public_url ?? '/sitemap.xml'));
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
        );
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    void adminApi.pageText('xml-sitemap', langId).then((res) => {
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('LBL_XML_SITEMAP', 'XML sitemap'),
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
    load();
  }, []);

  const onRegenerate = () => {
    if (generating) {
      return;
    }
    void adminApi
      .generateSitemap()
      .then(() => load())
      .catch(() => {
        window.alert(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
      });
  };

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_XML_SITEMAP', 'XML sitemap')}</li>
          </ul>
          <div className="action-toolbar">
            <a
              href={publicUrl}
              className="btn btn-primary"
              target="_blank"
              rel="noreferrer"
            >
              {lbl('LBL_OPEN_IN_NEW_TAB', 'Open in new tab')}
            </a>
            {canEdit ? (
              <a
                href="javascript:void(0)"
                className="btn btn-primary"
                onClick={onRegenerate}
                aria-disabled={generating}
              >
                {generating
                  ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                  : lbl('LBL_UPDATE_SITEMAP', 'Update sitemap')}
              </a>
            ) : null}
          </div>
        </div>

        <div className="card">
          <div className="card-body">
            {loading ? (
              <div className="table-processing loaderJs">
                <div className="spinner spinner--sm spinner--brand" />
              </div>
            ) : (
              <>
                {error ? <div className="alert alert-danger">{error}</div> : null}
                {!content ? (
                  <div className="alert alert--info">
                    {lbl(
                      'LBL_SITEMAP_NOT_GENERATED_YET',
                      'Sitemap has not been generated yet. Use Update sitemap to create it.',
                    )}
                  </div>
                ) : null}
                <form className="form layout--">
                  <div className="row">
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label" htmlFor="sitemapXml">
                            {lbl('LBL_XML_SITEMAP', 'XML sitemap')}
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <textarea
                              id="sitemapXml"
                              className="form-control"
                              rows={18}
                              readOnly
                              value={content}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>

                {files.length > 0 ? (
                  <div className="mt-4">
                    <h5>{lbl('LBL_SITEMAP_FILES', 'Sitemap files')}</h5>
                    <ul className="list-unstyled">
                      {files.map((file) => (
                        <li key={file.name}>
                          <a href={file.url} target="_blank" rel="noreferrer">
                            {file.name}
                          </a>
                        </li>
                      ))}
                    </ul>
                  </div>
                ) : null}
              </>
            )}
          </div>
        </div>
      </div>
    </main>
  );
}
