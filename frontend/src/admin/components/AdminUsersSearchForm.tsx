import { useEffect, useRef, useState } from 'react';
import type { AdminModuleConfig } from '../config/adminModuleTypes';
import { adminApi } from '../api/adminClient';
import { AdminLegacySearchButtons, AdminLegacySearchField } from './AdminLegacySearchField';

type Suggestion = {
  id: number;
  full_name: string;
  email: string;
};

type Props = {
  config: AdminModuleConfig;
  draft: Record<string, string>;
  lbl: (key: string, fallback: string) => string;
  onDraftChange: (next: Record<string, string>) => void;
  onSearch: (e: React.FormEvent) => void;
  onClear: () => void;
};

export function AdminUsersSearchForm({ config, draft, lbl, onDraftChange, onSearch, onClear }: Props) {
  const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const keywordCoverRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (keywordCoverRef.current && !keywordCoverRef.current.contains(e.target as Node)) {
        setShowSuggestions(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  const setField = (name: string, value: string) => {
    onDraftChange({ ...draft, [name]: value });
  };

  const onKeywordChange = (value: string) => {
    onDraftChange({ ...draft, keyword: value, user_id: '' });

    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (value.trim().length < 2) {
      setSuggestions([]);
      return;
    }

    debounceRef.current = setTimeout(() => {
      void adminApi.userAutocomplete(value.trim()).then((res) => {
        setSuggestions(res.data.data ?? []);
        setShowSuggestions(true);
      });
    }, 250);
  };

  const pickSuggestion = (item: Suggestion) => {
    onDraftChange({ ...draft, keyword: item.full_name, user_id: String(item.id) });
    setShowSuggestions(false);
    setSuggestions([]);
  };

  const dateInputClass = config.searchDateInputClass ?? 'field--calender';
  const submitCol = config.searchSubmitCol ?? 3;

  const renderField = (field: NonNullable<AdminModuleConfig['searchFields']>[number]) => {
    if (field.name === 'keyword') {
      return (
        <AdminLegacySearchField
          key={field.name}
          label={lbl(field.labelKey, field.labelFallback)}
          fieldCoverRef={keywordCoverRef}
        >
          <input
            id="keyword"
            name="keyword"
            type="text"
            autoComplete="off"
            value={draft.keyword ?? ''}
            onChange={(e) => onKeywordChange(e.target.value)}
            onFocus={() => suggestions.length > 0 && setShowSuggestions(true)}
          />
          {showSuggestions && suggestions.length > 0 ? (
            <ul
              className="ui-menu ui-widget ui-widget-content ui-autocomplete custom-ui-autocomplete"
              role="listbox"
            >
              {suggestions.map((item) => (
                <li key={item.id} className="ui-menu-item">
                  <div
                    className="ui-menu-item-wrapper"
                    role="option"
                    tabIndex={-1}
                    onMouseDown={(e) => {
                      e.preventDefault();
                      pickSuggestion(item);
                    }}
                  >
                    {item.full_name} ({item.email})
                  </div>
                </li>
              ))}
            </ul>
          ) : null}
        </AdminLegacySearchField>
      );
    }

    if (field.type === 'date') {
      return (
        <AdminLegacySearchField key={field.name} label={lbl(field.labelKey, field.labelFallback)}>
          <input
            className={dateInputClass}
            type="date"
            name={field.name}
            value={draft[field.name] ?? ''}
            onChange={(e) => setField(field.name, e.target.value)}
          />
        </AdminLegacySearchField>
      );
    }

    if (field.type === 'select') {
      return (
        <AdminLegacySearchField key={field.name} label={lbl(field.labelKey, field.labelFallback)}>
          <select
            name={field.name}
            value={draft[field.name] ?? ''}
            onChange={(e) => setField(field.name, e.target.value)}
          >
            {(field.options ?? []).map((opt) => (
              <option key={opt.value} value={opt.value}>
                {lbl(opt.labelKey, opt.labelFallback)}
              </option>
            ))}
          </select>
        </AdminLegacySearchField>
      );
    }

    return (
      <AdminLegacySearchField key={field.name} label={lbl(field.labelKey, field.labelFallback)}>
        <input
          type="text"
          name={field.name}
          value={draft[field.name] ?? ''}
          onChange={(e) => setField(field.name, e.target.value)}
        />
      </AdminLegacySearchField>
    );
  };

  return (
    <form className="form" name="srchForm" onSubmit={onSearch}>
      <input type="hidden" name="user_id" value={draft.user_id ?? ''} />
      <input type="hidden" name="page" value="1" />
      <div className="row">
        {(config.searchFields ?? []).map((field) => renderField(field))}
        <AdminLegacySearchButtons
          col={submitCol}
          searchLabel={lbl('LBL_SEARCH', 'Search')}
          clearLabel={lbl('LBL_CLEAR', 'Clear')}
          onClear={onClear}
        />
      </div>
    </form>
  );
}
