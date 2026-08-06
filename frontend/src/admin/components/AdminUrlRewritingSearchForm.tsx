import { type FormEvent, useEffect, useState } from 'react';
import { adminApi } from '../api/adminClient';
import { AdminLegacySearchButtons, AdminLegacySearchField } from './AdminLegacySearchField';

type LanguageOption = { id: number; name: string };

type Props = {
  draft: Record<string, string>;
  lbl: (key: string, fallback: string) => string;
  onDraftChange: (next: Record<string, string>) => void;
  onSearch: (e: FormEvent) => void;
  onClear: () => void;
};

export function AdminUrlRewritingSearchForm({
  draft,
  lbl,
  onDraftChange,
  onSearch,
  onClear,
}: Props) {
  const [languages, setLanguages] = useState<LanguageOption[]>([]);

  useEffect(() => {
    void adminApi.urlRewritingForm(0).then((res) => {
      const langs = (res.data.data?.languages ?? []) as LanguageOption[];
      setLanguages(langs);
    });
  }, []);

  const setField = (name: string, value: string) => {
    onDraftChange({ ...draft, [name]: value });
  };

  return (
    <form className="form" name="srchForm" onSubmit={onSearch}>
      <input type="hidden" name="page" value="1" />
      <div className="row">
        <AdminLegacySearchField label={lbl('LBL_Keyword', 'Keyword')}>
          <input
            type="text"
            name="keyword"
            value={draft.keyword ?? ''}
            onChange={(e) => setField('keyword', e.target.value)}
          />
        </AdminLegacySearchField>
        <AdminLegacySearchField label={lbl('LBL_Language', 'Language')}>
          <select
            name="seourl_lang_id"
            value={draft.seourl_lang_id ?? ''}
            onChange={(e) => setField('seourl_lang_id', e.target.value)}
          >
            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
            {languages.map((lang) => (
              <option key={lang.id} value={String(lang.id)}>
                {lang.name}
              </option>
            ))}
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
