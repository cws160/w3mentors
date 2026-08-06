import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { adminApi } from '../api/adminClient';
import type { AdminModuleConfig } from '../config/adminModuleTypes';
import { AdminLegacySearchButtons, AdminLegacySearchField } from './AdminLegacySearchField';

type TeacherSuggestion = {
  id: number;
  full_name: string;
  email: string;
};

type LanguageSuggestion = {
  id: number;
  name: string;
};

type SiteLanguageOption = {
  id: number;
  name: string;
};

type Props = {
  config: AdminModuleConfig;
  draft: Record<string, string>;
  lbl: (key: string, fallback: string) => string;
  onDraftChange: (next: Record<string, string>) => void;
  onSearch: (e: FormEvent) => void;
  onClear: () => void;
};

export function AdminModuleSearchForm({ config, draft, lbl, onDraftChange, onSearch, onClear }: Props) {
  const [teacherSuggestions, setTeacherSuggestions] = useState<TeacherSuggestion[]>([]);
  const [showTeacherSuggestions, setShowTeacherSuggestions] = useState(false);
  const [languageSuggestions, setLanguageSuggestions] = useState<LanguageSuggestion[]>([]);
  const [showLanguageSuggestions, setShowLanguageSuggestions] = useState(false);
  const [siteLanguageOptions, setSiteLanguageOptions] = useState<SiteLanguageOption[]>([]);
  const teacherCoverRef = useRef<HTMLDivElement>(null);
  const languageCoverRef = useRef<HTMLDivElement>(null);
  const teacherDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const languageDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const isGroupClassSearch = config.module === 'group-classes' || config.module === 'package-classes';
  const isLessonsSearch = config.module === 'lessons';
  const isClassLanguageOrderSearch = config.module === 'classes' || config.module === 'packages';
  const languageFieldName = isClassLanguageOrderSearch ? 'ordcls_tlang' : 'ordles_tlang';
  const languageFieldIdName = isClassLanguageOrderSearch ? 'ordcls_tlang_id' : 'ordles_tlang_id';

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (teacherCoverRef.current && !teacherCoverRef.current.contains(e.target as Node)) {
        setShowTeacherSuggestions(false);
      }
      if (languageCoverRef.current && !languageCoverRef.current.contains(e.target as Node)) {
        setShowLanguageSuggestions(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  useEffect(() => {
    const needsSiteLanguages = (config.searchFields ?? []).some((field) => field.type === 'language');
    if (!needsSiteLanguages) {
      return;
    }

    let ignore = false;
    void adminApi
      .courseLanguageCreateForm()
      .then((res) => {
        if (!ignore) {
          setSiteLanguageOptions(res.data.data.site_languages ?? []);
        }
      })
      .catch(() => {
        if (!ignore) {
          setSiteLanguageOptions([]);
        }
      });

    return () => {
      ignore = true;
    };
  }, [config.searchFields]);

  const setField = (name: string, value: string) => {
    onDraftChange({ ...draft, [name]: value });
  };

  const onTeacherChange = (value: string) => {
    onDraftChange({ ...draft, teacher: value, teacher_id: '' });
    if (teacherDebounceRef.current) clearTimeout(teacherDebounceRef.current);
    if (value.trim().length < 2) {
      setTeacherSuggestions([]);
      return;
    }
    teacherDebounceRef.current = setTimeout(() => {
      void adminApi.groupClassTeacherAutocomplete(value.trim()).then((res) => {
        setTeacherSuggestions(res.data.data ?? []);
        setShowTeacherSuggestions(true);
      });
    }, 250);
  };

  const pickTeacher = (item: TeacherSuggestion) => {
    onDraftChange({ ...draft, teacher: item.full_name, teacher_id: String(item.id) });
    setShowTeacherSuggestions(false);
    setTeacherSuggestions([]);
  };

  const onLanguageChange = (value: string) => {
    onDraftChange({ ...draft, [languageFieldName]: value, [languageFieldIdName]: '' });
    if (languageDebounceRef.current) clearTimeout(languageDebounceRef.current);
    if (value.trim().length < 1) {
      setLanguageSuggestions([]);
      setShowLanguageSuggestions(false);
      return;
    }
    languageDebounceRef.current = setTimeout(() => {
      void adminApi.teachLanguageAutocomplete(value.trim()).then((res) => {
        const items = res.data.data ?? [];
        setLanguageSuggestions(items);
        setShowLanguageSuggestions(items.length > 0);
      });
    }, 250);
  };

  const pickLanguage = (item: LanguageSuggestion) => {
    onDraftChange({ ...draft, [languageFieldName]: item.name, [languageFieldIdName]: String(item.id) });
    setShowLanguageSuggestions(false);
    setLanguageSuggestions([]);
  };

  const dateInputClass = config.searchDateInputClass ?? 'field--calender';
  const submitCol = config.searchSubmitCol ?? 3;
  const formId =
    config.module === 'email-templates'
      ? 'frmEtplsSearch'
      : config.module === 'states' || config.module === 'countries'
        ? 'frmSearch'
        : undefined;

  return (
    <form
      className="form"
      id={formId}
      name="srchForm"
      onSubmit={onSearch}
    >
      <input type="hidden" name="page" value="1" />
      {isGroupClassSearch ? (
        <>
          <input
            type="hidden"
            name="grpcls_parent"
            value={draft.grpcls_parent ?? (config.module === 'group-classes' ? '0' : '')}
          />
          <input type="hidden" name="teacher_id" value={draft.teacher_id ?? ''} />
        </>
      ) : null}
      {isLessonsSearch || isClassLanguageOrderSearch ? (
        <input type="hidden" name={languageFieldIdName} value={draft[languageFieldIdName] ?? ''} />
      ) : null}
      <div className="row">
        {(config.searchFields ?? []).map((field) => {
          if (isGroupClassSearch && field.name === 'teacher') {
            return (
              <AdminLegacySearchField
                key={field.name}
                col={field.col}
                label={lbl(field.labelKey, field.labelFallback)}
                fieldCoverRef={teacherCoverRef}
              >
                <input
                  type="text"
                  name={field.name}
                  autoComplete="off"
                  value={draft[field.name] ?? ''}
                  onChange={(e) => onTeacherChange(e.target.value)}
                  onFocus={() => teacherSuggestions.length > 0 && setShowTeacherSuggestions(true)}
                />
                {showTeacherSuggestions && teacherSuggestions.length > 0 ? (
                  <ul
                    className="ui-menu ui-widget ui-widget-content ui-autocomplete custom-ui-autocomplete"
                    role="listbox"
                  >
                    {teacherSuggestions.map((item) => (
                      <li key={item.id} className="ui-menu-item">
                        <div
                          className="ui-menu-item-wrapper"
                          role="option"
                          tabIndex={-1}
                          onMouseDown={(e) => {
                            e.preventDefault();
                            pickTeacher(item);
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

          if ((isLessonsSearch || isClassLanguageOrderSearch) && field.name === languageFieldName) {
            return (
              <AdminLegacySearchField
                key={field.name}
                col={field.col}
                label={lbl(field.labelKey, field.labelFallback)}
                fieldCoverRef={languageCoverRef}
              >
                <input
                  id={languageFieldName}
                  type="text"
                  name={field.name}
                  autoComplete="off"
                  value={draft[field.name] ?? ''}
                  onChange={(e) => onLanguageChange(e.target.value)}
                  onFocus={() => languageSuggestions.length > 0 && setShowLanguageSuggestions(true)}
                />
                {showLanguageSuggestions && languageSuggestions.length > 0 ? (
                  <ul
                    className="ui-menu ui-widget ui-widget-content ui-autocomplete custom-ui-autocomplete"
                    role="listbox"
                  >
                    {languageSuggestions.map((item) => (
                      <li key={item.id} className="ui-menu-item">
                        <div
                          className="ui-menu-item-wrapper"
                          role="option"
                          tabIndex={-1}
                          onMouseDown={(e) => {
                            e.preventDefault();
                            pickLanguage(item);
                          }}
                        >
                          {item.name}
                        </div>
                      </li>
                    ))}
                  </ul>
                ) : null}
              </AdminLegacySearchField>
            );
          }

          return (
            <AdminLegacySearchField
              key={field.name}
              col={field.col}
              label={lbl(field.labelKey, field.labelFallback)}
            >
              {field.type === 'select' || field.type === 'language' ? (
                <select
                  name={field.name}
                  value={draft[field.name] ?? ''}
                  onChange={(e) => setField(field.name, e.target.value)}
                >
                  {field.type === 'language' ? (
                    <>
                      <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                      {siteLanguageOptions.map((language) => (
                        <option key={language.id} value={String(language.id)}>
                          {language.name}
                        </option>
                      ))}
                    </>
                  ) : (
                    (field.options ?? []).map((opt) => (
                      <option key={opt.value} value={opt.value}>
                        {lbl(opt.labelKey, opt.labelFallback)}
                      </option>
                    ))
                  )}
                </select>
              ) : (
                <input
                  className={field.inputClass ?? (field.type === 'date' ? dateInputClass : undefined)}
                  type={field.type === 'date' ? 'date' : 'text'}
                  name={field.name}
                  placeholder={
                    field.placeholderKey
                      ? lbl(field.placeholderKey, field.placeholderFallback ?? '')
                      : undefined
                  }
                  value={draft[field.name] ?? ''}
                  onChange={(e) => setField(field.name, e.target.value)}
                />
              )}
            </AdminLegacySearchField>
          );
        })}
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
