import { useEffect, useState } from 'react';
import { faqApi, type FaqData } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { SpriteIcon } from '../components/SpriteIcon';

export function W3MentorsFaqPage() {
  const { lbl } = useSite();
  const [data, setData] = useState<FaqData | null>(null);
  const [activeCat, setActiveCat] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    faqApi
      .get()
      .then((res) => {
        setData(res.data);
        const first = res.data.categories[0]?.id ?? null;
        setActiveCat(first);
      })
      .catch(() => setData(null))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  if (!data?.categories.length) {
    return <W3MentorsPageMessage message={lbl('LBL_NO_RESULT_FOUND!', 'No results found')} />;
  }

  const activeId = activeCat ?? data.categories[0].id;
  const faqs = data.faqs_by_category[activeId] ?? [];

  return (
    <>
      <section className="section bg-gradiant section--page-header text-center">
        <div className="container container--xl">
          <h6 className="small-title">{lbl('LBL_FAQ', 'FAQ').toUpperCase()}</h6>
          <h2>{lbl('LBL_faq_title_second', 'Frequently asked questions')}</h2>
          <div className="main-search">
            <div className="main-search__field">
              <input
                type="text"
                name="faq_search"
                placeholder={lbl('LBL_FAQ_SEARCH_PLACEHOLDER_TXT', 'Search FAQ')}
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
      <section className="section section--faq" id="faq-area">
        <div className="container container--narrow">
          <nav className="tabs-wrapper text-center mb-3">
            <ul className="nav nav-pills justify-content-md-center gap-3" role="tablist">
              {data.categories.map((cat) => (
                <li className="nav-item" key={cat.id}>
                  <button
                    type="button"
                    className={`nav-link ${activeId === cat.id ? 'active' : ''}`}
                    onClick={() => setActiveCat(cat.id)}
                  >
                    {cat.name}
                  </button>
                </li>
              ))}
            </ul>
          </nav>
          <div className="section__body">
            <div className="faq-container border-0">
              {faqs.map((faq) => (
                <div className="faq-row faq-group-js" key={faq.id}>
                  <button
                    type="button"
                    className="faq-title faq__trigger collapsed"
                    data-bs-toggle="collapse"
                    data-bs-target={`#description${faq.id}`}
                  >
                    <h5>{faq.title}</h5>
                  </button>
                  <div
                    className="faq__target faq__target-js collapse"
                    id={`description${faq.id}`}
                  >
                    <div className="faq-answer">
                      <div className="editor-content">{faq.description}</div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
