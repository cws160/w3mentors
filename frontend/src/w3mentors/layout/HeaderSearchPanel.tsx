import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { searchApi, type SearchSuggestItem } from '../../api/client';
import { SpriteIcon } from '../components/SpriteIcon';
import { useSite } from '../context/SiteContext';

type Suggestion = SearchSuggestItem & { kind: 'course' | 'teacher' | 'class' | 'language' };

function highlightMatch(text: string, keyword: string) {
  if (!keyword) {
    return text;
  }
  const parts = text.split(new RegExp(`(${keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'));
  return parts.map((part, i) =>
    part.toLowerCase() === keyword.toLowerCase() ? <b key={i}>{part}</b> : part
  );
}

export function HeaderSearchPanel() {
  const { lbl, langId, searchFilters } = useSite();
  const navigate = useNavigate();
  const [keyword, setKeyword] = useState('');
  const [filterType, setFilterType] = useState(0);
  const [filterOpen, setFilterOpen] = useState(false);
  const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
  const [suggestOpen, setSuggestOpen] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const filterOptions = useMemo(() => {
    const entries = Object.entries(searchFilters ?? { 0: 'LBL_ALL', 3: 'LBL_TEACHERS' });
    return entries.map(([key, labelKey]) => ({
      type: Number(key),
      label: lbl(labelKey, labelKey.replace('LBL_', '').replace(/_/g, ' ')),
    }));
  }, [searchFilters, lbl]);

  const selectedFilterLabel =
    filterOptions.find((f) => f.type === filterType)?.label ??
    lbl('LBL_ALL', 'All');

  const fetchSuggestions = useCallback(
    async (value: string, type: number) => {
      if (value.length < 3) {
        setSuggestions([]);
        setSuggestOpen(false);
        return;
      }
      try {
        const { data } = await searchApi.autocomplete(value, type, langId);
        const items: Suggestion[] = [
          ...data.courses.map((item) => ({ ...item, kind: 'course' as const })),
          ...data.languages.map((item) => ({ ...item, kind: 'language' as const })),
          ...data.classes.map((item) => ({ ...item, kind: 'class' as const })),
          ...data.teachers.map((item) => ({ ...item, kind: 'teacher' as const })),
        ];
        setSuggestions(items);
        setSuggestOpen(true);
      } catch {
        setSuggestions([]);
        setSuggestOpen(false);
      }
    },
    [langId]
  );

  useEffect(() => {
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }
    debounceRef.current = setTimeout(() => {
      fetchSuggestions(keyword, filterType);
    }, 500);
    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, [keyword, filterType, fetchSuggestions]);

  function clearKeyword() {
    setKeyword('');
    setSuggestions([]);
    setSuggestOpen(false);
  }

  function runSearch() {
    const q = keyword.trim();
    if (!q) {
      return;
    }
    switch (filterType) {
      case 2:
        navigate(`/courses?search=${encodeURIComponent(q)}`);
        break;
      case 1:
        navigate(`/group-classes?search=${encodeURIComponent(q)}`);
        break;
      case 3:
        navigate(`/teachers?search=${encodeURIComponent(q)}`);
        break;
      case 4:
        if (suggestions[0]?.url) {
          navigate(suggestions[0].url);
        } else {
          navigate(`/teachers?search=${encodeURIComponent(q)}`);
        }
        break;
      default:
        navigate(`/teachers?search=${encodeURIComponent(q)}`);
    }
    setSuggestOpen(false);
  }

  const iconByKind: Record<Suggestion['kind'], string> = {
    course: 'icon-course-filter',
    language: 'icon-subject-filter',
    class: 'icon-class-filter',
    teacher: 'icon-teacher-filter',
  };

  const classByKind: Record<Suggestion['kind'], string> = {
    course: 'is-suggestion-course',
    language: 'is-suggestion-subject',
    class: 'is-suggestion-groupclass',
    teacher: 'is-suggestion-teacher',
  };

  return (
    <div className="header-search" id="HEADER-SEARCH">
      <div className="main-search">
        <div className="main-search__field">
          <input
            type="text"
            name="keyword"
            id="homeSearchFld"
            className="form-control"
            autoComplete="off"
            value={keyword}
            onChange={(e) => setKeyword(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                runSearch();
              }
            }}
            placeholder={lbl(
              'LBL_START_LEARNING_-_FIND_YOUR_NEXT_SESSION_OR_INSTRUCTOR...',
              'Start learning - find your next session or instructor...'
            )}
          />
          <div className="main-search__action">
            {!keyword && (
              <button
                type="button"
                className="main-search__submit srchBtnJs"
                title="Search"
                onClick={runSearch}
              >
                <SpriteIcon id="search" className="icon icon--search" />
              </button>
            )}
            {keyword && (
              <button
                type="button"
                className="main-search__reset resetBtnJs"
                title="Reset"
                onClick={clearKeyword}
              >
                <span className="close" />
              </button>
            )}
          </div>
          <div
            className="main-search__target search-target-js"
            style={{ display: suggestOpen ? undefined : 'none' }}
          >
            <div className="auto-suggest autoSuggestJs">
              <ul>
                {suggestions.length > 0 ? (
                  suggestions.map((item) => (
                    <li key={`${item.kind}-${item.id}`} className={classByKind[item.kind]}>
                      <Link to={item.url} onClick={() => setSuggestOpen(false)}>
                        <span className="auto-suggest__item">
                          <span className="auto-suggest__media">
                            <svg className="icon icon--course">
                              <use xlinkHref={`/images/sprite.svg#${iconByKind[item.kind]}`} />
                            </svg>
                          </span>
                          <span className="auto-suggest__content">
                            {highlightMatch(item.name, keyword)}
                          </span>
                        </span>
                      </Link>
                    </li>
                  ))
                ) : keyword.length >= 3 ? (
                  <li className="is-suggestion-course">
                    <span className="auto-suggest__item">
                      <span className="auto-suggest__content">
                        {lbl('LBL_No_Record_Found', 'No record found')}
                      </span>
                    </span>
                  </li>
                ) : null}
              </ul>
            </div>
          </div>
        </div>
        {filterOptions.length > 1 && (
          <div className="main-search__dropdown">
            <div className={`search-dropdown${filterOpen ? ' is-active' : ''}`}>
              <span
                className="cursor-pointer search-dropdown__trigger expand-trigger-js selectedFilterJs"
                onClick={() => setFilterOpen((open) => !open)}
                onKeyDown={() => undefined}
                role="button"
                tabIndex={0}
              >
                {selectedFilterLabel}
              </span>
              <div
                className="search-dropdown__target expand-target-js"
                style={{ display: filterOpen ? undefined : 'none' }}
              >
                <div className="selection-listing">
                  <ul className="filterTypeJs">
                    {filterOptions.map((opt) => (
                      <li key={opt.type}>
                        <span
                          className={`filterLinkJs${filterType === opt.type ? ' is-active' : ''}`}
                          data-filter={opt.type}
                          onClick={() => {
                            setFilterType(opt.type);
                            setFilterOpen(false);
                          }}
                          onKeyDown={() => undefined}
                          role="button"
                          tabIndex={0}
                        >
                          {opt.label}
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
              <input type="hidden" name="type" value={filterType} />
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
