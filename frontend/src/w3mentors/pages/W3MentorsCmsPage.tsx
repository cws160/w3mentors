import { useEffect, useMemo, useState } from 'react';
import { Navigate, useParams } from 'react-router-dom';
import { contentApi, type CmsPageData } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { useStatsCounters, useW3MentorsAos, useW3MentorsSliders } from '../hooks/useW3MentorsSliders';
import { bindAboutPage } from '../lib/w3mentors-ui';
import { normalizeLegacyHtml } from '../utils/legacyHtml';

const LAYOUT_HERO = 1;

type W3MentorsCmsPageProps = {
  /** Fixed page id (e.g. About Us at /about). */
  pageId?: string;
  /** Resolve id from site config (terms / privacy CMS pages). */
  legal?: 'terms' | 'privacy';
};

function blockHtml(blocks: CmsPageData['blocks'], blockId: number): string {
  const row = blocks.find((b) => b.block_id === blockId);
  return row?.html?.trim() ?? '';
}

function CmsLayout1({ page }: { page: CmsPageData }) {
  const block1 = blockHtml(page.blocks, 1);
  const block2 = blockHtml(page.blocks, 2);
  const deps = [page.id, block1, block2];

  useW3MentorsAos(deps);
  useW3MentorsSliders(deps);
  useStatsCounters(deps);

  useEffect(() => bindAboutPage(), [page.id, block1, block2]);

  const heroStyle = page.hero_image
    ? { backgroundImage: `url(${page.hero_image})` }
    : undefined;

  return (
    <>
      <section className="section section--hero" style={heroStyle}>
        <div className="container container--xxl">
          <div className="hero-panel">
            <div className="hero-panel__content" data-aos="fade-up" data-aos-duration="1000">
              <div className="content">
                <h4>{page.title}</h4>
                {page.image_title ? <h2>{page.image_title}</h2> : null}
              </div>
            </div>
          </div>
        </div>
      </section>

      {block1 ? (
        <section className="section">
          <div className="editor-content">
            <div className="container container--xxl">
              <div dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(block1) }} />
            </div>
          </div>
        </section>
      ) : null}

      {block2 ? (
        <div className="editor-content pb-5">
          <div dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(block2) }} />
        </div>
      ) : null}
    </>
  );
}

/** Legacy application/views/cms/view.php layout 2 (terms, privacy, etc.). */
function CmsLayout2({ title, html }: { title: string; html: string }) {
  const { lbl } = useSite();
  const content = html.trim();

  return (
    <>
      <section className="section bg-gradiant section--page-header text-center">
        <div className="container container--xl">
          <h1>{title}</h1>
        </div>
      </section>
      <section className="section">
        <div className="container container--narrow">
          {content ? (
            <div
              className="editor-content"
              dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(content) }}
            />
          ) : (
            <div className="editor-content">
              <p>{lbl('LBL_No_content_available', 'No content available.')}</p>
            </div>
          )}
        </div>
      </section>
    </>
  );
}

function cmsFallbackTitle(legal: W3MentorsCmsPageProps['legal'], lbl: (key: string, fallback?: string) => string) {
  if (legal === 'privacy') return lbl('LBL_Privacy_Policy', 'Privacy Policy');
  if (legal === 'terms') return lbl('LBL_Terms_and_Conditions', 'Terms & Conditions');
  return '';
}

export function W3MentorsCmsPage({ pageId: fixedPageId, legal }: W3MentorsCmsPageProps = {}) {
  const { id: routeId } = useParams();
  const { lbl, languages, legalPages } = useSite();

  const id = useMemo(() => {
    if (legal === 'terms') return String(legalPages?.terms ?? 2);
    if (legal === 'privacy') return String(legalPages?.privacy ?? 3);
    return fixedPageId ?? routeId ?? '';
  }, [legal, legalPages, fixedPageId, routeId]);
  const langId = languages[0]?.id ?? 1;

  const canonicalPath = useMemo(() => {
    if (legal || fixedPageId || !routeId) return null;
    const numId = Number(routeId);
    if (!Number.isFinite(numId) || numId <= 0) return null;
    if (numId === (legalPages?.about ?? 1)) return '/about';
    if (numId === (legalPages?.terms ?? 2)) return '/terms-and-conditions';
    if (numId === (legalPages?.privacy ?? 3)) return '/privacy-policy';
    return null;
  }, [legal, legalPages, routeId, fixedPageId]);

  const [page, setPage] = useState<CmsPageData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!id) return;
    let cancelled = false;
    setLoading(true);
    setError('');
    contentApi
      .cms(id, langId)
      .then((res) => {
        if (!cancelled) setPage(res.data.data);
      })
      .catch(() => {
        if (!cancelled) setError(lbl('LBL_NOT_FOUND', 'Page not found'));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [id, langId]);

  const isLayout1 = page?.layout === LAYOUT_HERO;

  if (canonicalPath) {
    return <Navigate to={canonicalPath} replace />;
  }

  if (error) {
    return (
      <>
        <section className="section bg-gradiant section--page-header text-center">
          <div className="container container--xl">
            <h1>{cmsFallbackTitle(legal, lbl)}</h1>
          </div>
        </section>
        <section className="section">
          <div className="container container--narrow">
            <div className="box -padding-30" style={{ marginBottom: 30 }}>
              <div className="message-display">
                <h5>{error}</h5>
              </div>
            </div>
          </div>
        </section>
      </>
    );
  }

  if (!page) {
    if (loading) {
      return <CmsLayout2 title={cmsFallbackTitle(legal, lbl)} html="" />;
    }
    return null;
  }

  if (isLayout1) {
    return <CmsLayout1 page={page} />;
  }

  return <CmsLayout2 title={page.title} html={page.content ?? ''} />;
}
