import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { groupClassesApi, type GroupClassItem } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { GroupClassCard } from '../components/GroupClassCard';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { W3MentorsPagination } from '../components/W3MentorsPagination';
import { SpriteIcon } from '../components/SpriteIcon';

export function W3MentorsGroupClassesPage() {
  const { lbl } = useSite();
  const [searchParams] = useSearchParams();
  const [keyword, setKeyword] = useState(() => searchParams.get('search') ?? '');
  const [classes, setClasses] = useState<GroupClassItem[]>([]);
  const [page, setPage] = useState(1);

  useEffect(() => {
    const q = searchParams.get('search');
    if (q !== null) {
      setKeyword(q);
      setPage(1);
    }
  }, [searchParams]);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    groupClassesApi
      .list({ search: keyword || undefined, page })
      .then((res) => {
        setClasses(res.data.data);
        setTotal(res.data.meta.total);
        setLastPage(res.data.meta.last_page);
      })
      .catch(() => setClasses([]))
      .finally(() => setLoading(false));
  }, [keyword, page]);

  const recordLabel = lbl(
    'LBL_FOUND_THE_BEST_{recordcount}_CLASSES_FOR_YOU',
    'Found the best {recordcount} classes for you'
  ).replace('{recordcount}', String(total));

  return (
    <>
      <section className="section bg-gradiant section--page-header text-center">
        <div className="container container--xl">
          <h2>{lbl('LBL_CLASS_SEARCH_HEADLINE', 'Find group classes')}</h2>
          <div className="main-search">
            <div className="main-search__field">
              <input
                type="text"
                className="keyword-field-js"
                placeholder={lbl('LBL_BY_KEYWORD', 'Search by keyword')}
                value={keyword}
                onChange={(e) => {
                  setKeyword(e.target.value);
                  setPage(1);
                }}
              />
            </div>
            <div className="main-search__action">
              <span className="main-search__submit" title="Search">
                <SpriteIcon id="search" className="icon icon--search" />
              </span>
            </div>
          </div>
        </div>
      </section>
      <section className="section">
        <div className="container container--lg">
          <div className="page">
            <div className="page-header mb-xl-5 mb-4">
              <div className="row g-3 justify-content-between align-items-center">
                <div className="col-auto">
                  <h3 className="record-count-header">{recordLabel}</h3>
                </div>
              </div>
            </div>
            <div className="page-body">
              {loading ? (
                <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />
              ) : classes.length === 0 ? (
                <W3MentorsPageMessage message={lbl('LBL_NO_RESULT_FOUND!', 'No results found')} />
              ) : (
                <>
                  <div className="row g-3 g-md-4">
                    {classes.map((item) => (
                      <GroupClassCard key={item.id} item={item} lbl={lbl} />
                    ))}
                  </div>
                  <W3MentorsPagination current={page} last={lastPage} onPage={setPage} />
                </>
              )}
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
