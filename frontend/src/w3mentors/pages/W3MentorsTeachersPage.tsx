import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { teachersApi, type TeacherListing } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { W3MentorsPagination } from '../components/W3MentorsPagination';
import { W3MentorsTeacherCard } from '../components/W3MentorsTeacherCard';
import { SpriteIcon } from '../components/SpriteIcon';

export function W3MentorsTeachersPage() {
  const { lbl } = useSite();
  const [searchParams] = useSearchParams();
  const [keyword, setKeyword] = useState(() => searchParams.get('search') ?? '');
  const [teachers, setTeachers] = useState<TeacherListing[]>([]);
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
    teachersApi
      .list({ search: keyword || undefined, page })
      .then((res) => {
        setTeachers(res.data.data);
        setTotal(res.data.meta.total);
        setLastPage(res.data.meta.last_page);
      })
      .catch(() => setTeachers([]))
      .finally(() => setLoading(false));
  }, [keyword, page]);

  const recordLabel = lbl(
    'LBL_FOUND_THE_BEST_{recordcount}_TEACHERS_FOR_YOU',
    'Found the best {recordcount} teachers for you'
  ).replace('{recordcount}', String(total));

  return (
    <>
      <section className="section bg-gradiant section--page-header text-center">
        <div className="container container--xl">
          <h2>{lbl('LBL_TEACHER_SEARCH_HEADLINE', 'Find your tutor')}</h2>
          <div className="main-search">
            <div className="main-search__field">
              <input
                type="text"
                name="keyword_search"
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
              <button type="button" className="main-search__submit" title="Search">
                <SpriteIcon id="search" className="icon icon--search" />
              </button>
              {keyword && (
                <div
                  className="main-search__reset"
                  title="Reset"
                  onClick={() => setKeyword('')}
                  role="button"
                  tabIndex={0}
                >
                  <span className="close" />
                </div>
              )}
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
                  <div className="search-result">
                    <h3 className="record-count-header">{recordLabel}</h3>
                  </div>
                </div>
                <div className="col-auto">
                  <div className="sorting-options" />
                </div>
              </div>
            </div>
            <div className="page-body">
              <div className="page-panel">
                <div className="page-panel__large" style={{ width: '100%' }}>
                  <div className="page-listing" id="listing">
                    {loading ? (
                      <p className="text-center">{lbl('LBL_Loading', 'Loading...')}</p>
                    ) : teachers.length ? (
                      <>
                        <div className="box-wrapper">
                          {teachers.map((teacher) => (
                            <W3MentorsTeacherCard key={teacher.id} teacher={teacher} lbl={lbl} />
                          ))}
                        </div>
                        <W3MentorsPagination current={page} last={lastPage} onPage={setPage} />
                      </>
                    ) : (
                      <div className="page-listing__body">
                        <div className="box -padding-30" style={{ marginBottom: 30 }}>
                          <div className="message-display">
                            <h5>{lbl('LBL_NO_RESULT_FOUND!', 'No results found')}</h5>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
