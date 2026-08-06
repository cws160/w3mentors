import type { CourseFilters } from '../../api/client';
import { SpriteIcon } from './SpriteIcon';

export type CourseFilterState = {
  categories: number[];
  levels: number[];
  languages: number[];
  ratings: number | null;
  priceFrom: string;
  priceTill: string;
};

type Props = {
  filters: CourseFilters;
  state: CourseFilterState;
  onChange: (state: CourseFilterState) => void;
  onClear: () => void;
  lbl: (key: string, fallback?: string) => string;
};

export function CoursesFilterSidebar({ filters, state, onChange, onClear, lbl }: Props) {
  const toggleInList = (list: number[], id: number) =>
    list.includes(id) ? list.filter((v) => v !== id) : [...list, id];

  return (
    <div className="sidebar-filters" id="filter-panel">
      <button type="button" className="icon-close d-xl-none js-filter-close" onClick={onClear}>
        ×
      </button>
      <div className="sidebar-filters__head">
        <h6>{lbl('LBL_FILTERS', 'Filters')}</h6>
        <button type="button" className="link" onClick={onClear}>
          {lbl('LBL_Clear_all_Filters', 'Clear all filters')}
        </button>
      </div>
      <div className="sidebar-filters__body" id="accordionParent">
        <div className="filter-widget filter-js">
          <div
            className="filter-widget__head"
            data-bs-toggle="collapse"
            data-bs-target="#all-subjects"
            aria-expanded="false"
          >
            <span>
              <SpriteIcon id="icon-category" className="icon" />
            </span>
            <span>{lbl('LBL_CATEGORIES', 'Categories')}</span>
          </div>
          <div className="filter-widget__body collapse show" data-bs-parent="#accordionParent" id="all-subjects">
            <div className="filter-widget__inner">
              <div className="filters-scroll options-filter-js">
                <ul className="list-vertical">
                  {filters.categories.map((cat) => (
                    <li key={cat.id} className={cat.sub_categories.length ? 'has-child' : ''}>
                      <label className="form-check">
                        <input
                          className="form-check-input"
                          type="checkbox"
                          checked={state.categories.includes(cat.id)}
                          onChange={() =>
                            onChange({ ...state, categories: toggleInList(state.categories, cat.id) })
                          }
                        />
                        <span className="form-check-label">{cat.name}</span>
                      </label>
                      {cat.sub_categories.length > 0 && (
                        <ul className="list-vertical-child">
                          {cat.sub_categories.map((sub) => (
                            <li key={sub.id}>
                              <label className="form-check">
                                <input
                                  className="form-check-input"
                                  type="checkbox"
                                  checked={state.categories.includes(sub.id)}
                                  onChange={() =>
                                    onChange({ ...state, categories: toggleInList(state.categories, sub.id) })
                                  }
                                />
                                <span className="form-check-label">{sub.name}</span>
                              </label>
                            </li>
                          ))}
                        </ul>
                      )}
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div className="filter-widget filter-js">
          <div className="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#all-price">
            <span>
              <SpriteIcon id="icon-price" className="icon" />
            </span>
            <span>{lbl('LBL_ALL_PRICES', 'All prices')}</span>
          </div>
          <div className="filter-widget__body collapse show" id="all-price">
            <div className="filter-widget__inner">
              <div className="price-filter">
                <div className="price-filter__form mt-3 text-filter-js">
                  <div className="row">
                    <div className="col-6">
                      <div className="field-set">
                        <input
                          type="text"
                          className="priceSliderValue"
                          placeholder={lbl('LBL_PRICE_FROM', 'Price from')}
                          value={state.priceFrom}
                          onChange={(e) => onChange({ ...state, priceFrom: e.target.value })}
                        />
                      </div>
                    </div>
                    <div className="col-6">
                      <div className="field-set">
                        <input
                          type="text"
                          className="priceSliderValue"
                          placeholder={lbl('LBL_PRICE_TILL', 'Price till')}
                          value={state.priceTill}
                          onChange={(e) => onChange({ ...state, priceTill: e.target.value })}
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="filter-widget filter-js">
          <div className="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-ratings">
            <span>
              <SpriteIcon id="icon-teacher-level" className="icon" />
            </span>
            <span>{lbl('LBL_RATING', 'Rating')}</span>
          </div>
          <div className="filter-widget__body collapse show" id="filter-ratings">
            <div className="filter-widget__inner">
              <div className="filters-scroll options-filter-js">
                <ul className="list-vertical">
                {filters.ratings.map((rating) => (
                  <li key={rating.id}>
                    <label className="form-check">
                      <input
                        className="form-check-input"
                        type="radio"
                        name="course_ratings"
                        checked={state.ratings === rating.id}
                        onChange={() => onChange({ ...state, ratings: rating.id })}
                      />
                      <span className="form-check-label">
                        <span className="d-flex align-items-center">
                          <SpriteIcon id="rating" className="rating__media" />
                          <span>{rating.name}</span>
                        </span>
                      </span>
                    </label>
                  </li>
                ))}
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div className="filter-widget filter-js">
          <div className="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-course-levels">
            <span>
              <SpriteIcon id="icon-lesson-included" className="icon" />
            </span>
            <span>{lbl('LBL_COURSE_LEVELS', 'Course levels')}</span>
          </div>
          <div className="filter-widget__body collapse show" id="filter-course-levels">
            <div className="filter-widget__inner">
              <div className="filters-scroll options-filter-js">
                <ul className="list-vertical">
                {filters.levels.map((level) => (
                  <li key={level.id}>
                    <label className="form-check">
                      <input
                        className="form-check-input"
                        type="checkbox"
                        checked={state.levels.includes(level.id)}
                        onChange={() =>
                          onChange({ ...state, levels: toggleInList(state.levels, level.id) })
                        }
                      />
                      <span className="form-check-label">{level.name}</span>
                    </label>
                  </li>
                ))}
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div className="filter-widget filter-js">
          <div className="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#course-languages">
            <span>
              <SpriteIcon id="icon-globe-2" className="icon" />
            </span>
            <span>{lbl('LBL_COURSE_LANGUAGE', 'Course language')}</span>
          </div>
          <div className="filter-widget__body collapse show" id="course-languages">
            <div className="filter-widget__inner">
              <div className="filters-scroll options-filter-js">
                <ul className="list-vertical">
                {filters.languages.map((lang) => (
                  <li key={lang.id}>
                    <label className="form-check">
                      <input
                        className="form-check-input"
                        type="checkbox"
                        checked={state.languages.includes(lang.id)}
                        onChange={() =>
                          onChange({ ...state, languages: toggleInList(state.languages, lang.id) })
                        }
                      />
                      <span className="form-check-label">{lang.name}</span>
                    </label>
                  </li>
                ))}
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
