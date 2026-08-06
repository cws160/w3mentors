import { type FormEvent, useEffect, useState } from 'react';
import { adminApi } from '../api/adminClient';
import { AdminLegacySearchButtons, AdminLegacySearchField } from './AdminLegacySearchField';

type CategoryOption = { id: number; name: string };

type Props = {
  draft: Record<string, string>;
  lbl: (key: string, fallback: string) => string;
  onDraftChange: (next: Record<string, string>) => void;
  onSearch: (e: FormEvent) => void;
  onClear: () => void;
};

export function AdminQuestionsSearchForm({ draft, lbl, onDraftChange, onSearch, onClear }: Props) {
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [subcategories, setSubcategories] = useState<CategoryOption[]>([]);

  useEffect(() => {
    void adminApi.questionQuizCategories(0).then((res) => {
      setCategories((res.data.data as CategoryOption[]) ?? []);
    }).catch(() => setCategories([]));
  }, []);

  useEffect(() => {
    const cateId = Number(draft.ques_cate_id ?? 0);
    if (cateId < 1) {
      setSubcategories([]);
      return;
    }
    void adminApi.questionQuizCategories(cateId).then((res) => {
      setSubcategories((res.data.data as CategoryOption[]) ?? []);
    }).catch(() => setSubcategories([]));
  }, [draft.ques_cate_id]);

  const setField = (name: string, value: string) => {
    onDraftChange({ ...draft, [name]: value });
  };

  const onCategoryChange = (value: string) => {
    onDraftChange({
      ...draft,
      ques_cate_id: value,
      ques_subcate_id: '',
    });
  };

  return (
    <form className="form" name="srchForm" onSubmit={onSearch}>
      <input type="hidden" name="page" value="1" />
      <div className="row">
        <AdminLegacySearchField label={lbl('LBL_TITLE', 'Title')}>
          <input
            className="search-input"
            type="text"
            name="keyword"
            autoComplete="off"
            value={draft.keyword ?? ''}
            onChange={(e) => setField('keyword', e.target.value)}
          />
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_CATEGORY', 'Category')}>
          <select
            className="search-input"
            name="ques_cate_id"
            value={draft.ques_cate_id ?? ''}
            onChange={(e) => onCategoryChange(e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            {categories.map((item) => (
              <option key={item.id} value={String(item.id)}>
                {item.name}
              </option>
            ))}
          </select>
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_SUBCATEGORY', 'Subcategory')}>
          <select
            className="search-input"
            name="ques_subcate_id"
            value={draft.ques_subcate_id ?? ''}
            onChange={(e) => setField('ques_subcate_id', e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            {subcategories.map((item) => (
              <option key={item.id} value={String(item.id)}>
                {item.name}
              </option>
            ))}
          </select>
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_TEACHER', 'Teacher')}>
          <input
            className="search-input"
            type="text"
            name="teacher"
            autoComplete="off"
            value={draft.teacher ?? ''}
            onChange={(e) => setField('teacher', e.target.value)}
          />
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_TYPE', 'Type')}>
          <select
            className="search-input"
            name="ques_type"
            value={draft.ques_type ?? ''}
            onChange={(e) => setField('ques_type', e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            <option value="1">{lbl('LBL_SINGLE_CHOICE', 'Single choice')}</option>
            <option value="2">{lbl('LBL_MULTIPLE_CHOICE', 'Multiple choice')}</option>
            <option value="3">{lbl('LBL_TEXT', 'Text')}</option>
          </select>
        </AdminLegacySearchField>
        <AdminLegacySearchButtons
          col={6}
          searchLabel={lbl('LBL_SEARCH', 'Search')}
          clearLabel={lbl('LBL_CLEAR', 'Clear')}
          onClear={onClear}
        />
      </div>
    </form>
  );
}
