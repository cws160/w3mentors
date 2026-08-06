import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { videosApi, type VideoContentItem } from '../../api/client';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { W3MentorsLegacyPagination } from '../components/W3MentorsLegacyPagination';
import { useSite } from '../context/SiteContext';

export function W3MentorsVideosPage() {
  const { lbl, languages } = useSite();
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get('page') || 1);
  const langId = languages[0]?.id ?? 1;

  const [videos, setVideos] = useState<VideoContentItem[]>([]);
  const [startRecord, setStartRecord] = useState(0);
  const [endRecord, setEndRecord] = useState(0);
  const [totalRecords, setTotalRecords] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    videosApi
      .list({ page, lang_id: langId })
      .then((res) => {
        setVideos(res.data.data);
        setStartRecord(res.data.meta.start_record);
        setEndRecord(res.data.meta.end_record);
        setTotalRecords(res.data.meta.total);
        setLastPage(res.data.meta.last_page);
      })
      .catch(() => {
        setVideos([]);
        setStartRecord(0);
        setEndRecord(0);
        setTotalRecords(0);
        setLastPage(1);
      })
      .finally(() => setLoading(false));
  }, [page, langId]);

  function goToPage(nextPage: number) {
    const next = new URLSearchParams(searchParams);
    if (nextPage <= 1) {
      next.delete('page');
    } else {
      next.set('page', String(nextPage));
    }
    setSearchParams(next);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  return (
    <>
      <section className="forum-header section bg-gradiant section--page-header text-center">
        <div className="container container--narrow">
          <h1>{lbl('LBL_VIDEO_CONTENT', 'Video Content')}</h1>
        </div>
      </section>
      <section className="section">
        <div className="container container--fixed">
          <div className="search-result pb-4">
            <h3>
              {lbl('LBL_Showing', 'Showing')}{' '}
              <span id="start_record">{startRecord}</span> - <span id="end_record">{endRecord}</span>{' '}
              {lbl('LBL_of', 'of')} <span id="total_records">{totalRecords}</span>{' '}
              {lbl('LBL_Videos', 'Videos')}
            </h3>
          </div>
          <div id="bibleListingContainer">
            {loading ? (
              <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />
            ) : videos.length ? (
              <>
                <div className="result-container mt-3">
                  <div className="row g-3 g-md-4 g-xl-5">
                    {videos.map((video) =>
                      video.youtube_id ? (
                        <div className="col-md-6" key={video.id}>
                          <div className="video-card">
                            <h5 className="mb-4">{video.title}</h5>
                            <div className="iframe-box ratio ratio--16by9">
                              <iframe
                                width="100%"
                                height="100%"
                                src={`https://www.youtube.com/embed/${video.youtube_id}`}
                                title={video.title}
                                frameBorder={0}
                                allow="encrypted-media; gyroscope; picture-in-picture"
                                allowFullScreen
                              />
                            </div>
                          </div>
                        </div>
                      ) : null
                    )}
                  </div>
                </div>
                <W3MentorsLegacyPagination current={page} last={lastPage} onPage={goToPage} />
              </>
            ) : (
              <div className="box -padding-30" style={{ marginBottom: 30 }}>
                <div className="message-display">
                  <h5>{lbl('LBL_No_Result_Found!!', 'No results found')}</h5>
                </div>
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
