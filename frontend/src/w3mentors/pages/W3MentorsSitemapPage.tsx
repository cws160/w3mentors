import { useEffect, useState } from 'react';
import { sitemapApi, type SitemapHtmlData } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { SitemapHtmlContent } from '../components/SitemapHtmlContent';

export function W3MentorsSitemapPage() {
  const { lbl, langId } = useSite();
  const [data, setData] = useState<SitemapHtmlData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void sitemapApi
      .html(langId)
      .then((res) => setData(res.data.data))
      .catch(() => setData(null))
      .finally(() => setLoading(false));
  }, [langId]);

  if (loading) {
    return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  }

  if (!data?.sections?.length) {
    return <W3MentorsPageMessage message={lbl('LBL_NO_RESULT_FOUND!', 'No results found')} />;
  }

  return <SitemapHtmlContent data={data} lbl={lbl} />;
}
