import { type FormEvent, useEffect, useState } from 'react';
import { adminApi } from '../api/adminClient';
import { AdminLegacySearchButtons, AdminLegacySearchField } from './AdminLegacySearchField';

type CategoryOption = { id: number; name: string };
type ClangSuggestion = { id: number; name: string };

type Props = {
  draft: Record<string, string>;
  lbl: (key: string, fallback: string) => string;
  onDraftChange: (next: Record<string, string>) => void;
  onSearch: (e: FormEvent) => void;
  onClear: () => void;
};

export function AdminCoursesSearchForm({ draft, lbl, onDraftChange, onSearch, onClear }: Props) {
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [subcategories, setSubcategories] = useState<CategoryOption[]>([]);
  const [clangSuggestions, setClangSuggestions] = useState<ClangSuggestion[]>([]);
  const [showClangSuggestions, setShowClangSuggestions] = useState(false);

  useEffect(() => {
    void adminApi.coursesSearchForm().then((res) => {
      setCategories((res.data.data?.categories as CategoryOption[]) ?? []);
    });
  }, []);

  useEffect(() => {
    const cateId = Number(draft.course_cateid ?? 0);
    if (cateId < 1) {
      setSubcategories([]);
      return;
    }
    void adminApi.courseSubcategories(cateId, Number(draft.course_subcateid ?? 0)).then((res) => {
      setSubcategories((res.data.data as CategoryOption[]) ?? []);
    });
  }, [draft.course_cateid, draft.course_subcateid]);

  const setField = (name: string, value: string) => {
    onDraftChange({ ...draft, [name]: value });
  };

  const onCategoryChange = (value: string) => {
    onDraftChange({
      ...draft,
      course_cateid: value,
      course_subcateid: '',
    });
  };

  const onClangChange = (value: string) => {
    onDraftChange({ ...draft, course_clang: value, course_clang_id: '' });
    const keyword = value.trim();
    if (keyword.length < 1) {
      setClangSuggestions([]);
      setShowClangSuggestions(false);
      return;
    }
    void adminApi.courseLanguageAutocomplete(keyword).then((res) => {
      const items = (res.data.data as ClangSuggestion[]) ?? [];
      setClangSuggestions(items);
      setShowClangSuggestions(items.length > 0);
    });
  };

  return (
    <form className="form" name="srchForm" onSubmit={onSearch}>
      <input type="hidden" name="page" value="1" />
      <input type="hidden" name="course_clang_id" value={draft.course_clang_id ?? ''} />
      <div className="row">
        <AdminLegacySearchField label={lbl('LBL_KEYWORD', 'Keyword')}>
          <input
            className="search-input"
            type="text"
            name="keyword"
            placeholder={lbl('LBL_SEARCH_BY_COURSE_TITLE_OR_TEACHER', 'Search by course title or teacher')}
            value={draft.keyword ?? ''}
            onChange={(e) => setField('keyword', e.target.value)}
          />
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_COURSE_LANGUAGE', 'Course language')}>
          <input
            type="text"
            name="course_clang"
            autoComplete="off"
            value={draft.course_clang ?? ''}
            onChange={(e) => onClangChange(e.target.value)}
            onFocus={() => clangSuggestions.length > 0 && setShowClangSuggestions(true)}
            onBlur={() => window.setTimeout(() => setShowClangSuggestions(false), 150)}
          />
          {showClangSuggestions && clangSuggestions.length > 0 ? (
            <ul
              className="ui-menu ui-widget ui-widget-content ui-autocomplete custom-ui-autocomplete"
              role="listbox"
            >
              {clangSuggestions.map((item) => (
                <li key={item.id} className="ui-menu-item">
                  <div
                    className="ui-menu-item-wrapper"
                    role="option"
                    tabIndex={-1}
                    onMouseDown={(ev) => {
                      ev.preventDefault();
                      onDraftChange({
                        ...draft,
                        course_clang: item.name,
                        course_clang_id: String(item.id),
                      });
                      setShowClangSuggestions(false);
                    }}
                  >
                    {item.name}
                  </div>
                </li>
              ))}
            </ul>
          ) : null}
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_CATEGORY', 'Category')}>
          <select
            name="course_cateid"
            value={draft.course_cateid ?? ''}
            onChange={(e) => onCategoryChange(e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_SUBCATEGORY', 'Subcategory')}>
          <select
            id="subCategories"
            name="course_subcateid"
            value={draft.course_subcateid ?? ''}
            onChange={(e) => setField('course_subcateid', e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            {subcategories.map((subcategory) => (
              <option key={subcategory.id} value={subcategory.id}>
                {subcategory.name}
              </option>
            ))}
          </select>
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_DATE_FROM', 'Date from')}>
          <input
            className="small dateTimeFld field--calender"
            type="date"
            name="course_addedon_from"
            value={draft.course_addedon_from ?? ''}
            onChange={(e) => setField('course_addedon_from', e.target.value)}
          />
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_DATE_TO', 'Date to')}>
          <input
            className="small dateTimeFld field--calender"
            type="date"
            name="course_addedon_till"
            value={draft.course_addedon_till ?? ''}
            onChange={(e) => setField('course_addedon_till', e.target.value)}
          />
        </AdminLegacySearchField>
        <AdminLegacySearchButtons
          col={3}
          searchLabel={lbl('LBL_SEARCH', 'Search')}
          clearLabel={lbl('LBL_CLEAR', 'Clear')}
          onClear={onClear}
        />
      </div>
    </form>
  );
}
