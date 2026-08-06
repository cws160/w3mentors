import { type FormEvent } from 'react';
import { AdminLegacySearchButtons, AdminLegacySearchField } from './AdminLegacySearchField';

type Props = {
  draft: Record<string, string>;
  lbl: (key: string, fallback: string) => string;
  onDraftChange: (next: Record<string, string>) => void;
  onSearch: (e: FormEvent) => void;
  onClear: () => void;
};

export function AdminQuizzesSearchForm({ draft, lbl, onDraftChange, onSearch, onClear }: Props) {
  const setField = (name: string, value: string) => {
    onDraftChange({ ...draft, [name]: value });
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
            name="quiz_type"
            value={draft.quiz_type ?? ''}
            onChange={(e) => setField('quiz_type', e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            <option value="1">{lbl('LBL_AUTO_GRADED', 'Auto graded')}</option>
            <option value="2">{lbl('LBL_NON_GRADED', 'Non graded')}</option>
          </select>
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_STATUS', 'Status')}>
          <select
            className="search-input"
            name="quiz_status"
            value={draft.quiz_status ?? ''}
            onChange={(e) => setField('quiz_status', e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            <option value="1">{lbl('LBL_DRAFTED', 'Drafted')}</option>
            <option value="2">{lbl('LBL_PUBLISHED', 'Published')}</option>
          </select>
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_ACTIVE', 'Active')}>
          <select
            className="search-input"
            name="quiz_active"
            value={draft.quiz_active ?? ''}
            onChange={(e) => setField('quiz_active', e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            <option value="1">{lbl('LBL_YES', 'Yes')}</option>
            <option value="0">{lbl('LBL_NO', 'No')}</option>
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
