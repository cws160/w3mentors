import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { TeachLanguageTree } from './TeachLanguageTree';
import {
  emptyQualificationForm,
  qualificationToForm,
  QualificationActionButton,
  TeacherQualificationModal,
} from './TeacherQualificationModal';
import type {
  ExperienceType,
  TeacherAccountProfile,
  TeacherAccountResponse,
  TeacherQualification,
} from './teacherAccountTypes';

const PREF_TYPE_LABELS: Record<number, string> = {
  1: 'LBL_ACCENTS',
  2: 'LBL_TEACHES_LEVEL',
  3: 'LBL_LEARNER_AGES',
  4: 'LBL_LESSON_INCLUDES',
  6: 'LBL_TEST_PREPARATIONS',
};

const PREF_TYPE_FALLBACKS: Record<number, string> = {
  1: 'Accents',
  2: 'Teaches level',
  3: 'Learner ages',
  4: 'Lesson includes',
  6: 'Test preparations',
};

type SectionProps = {
  profile: TeacherAccountProfile | null;
  experienceTypes: ExperienceType[];
  loading: boolean;
  onSaved: (data: TeacherAccountProfile) => void;
  onNextTab?: () => void;
};

function AccountFormActions({
  saving,
  message,
  showNext,
  onNextTab,
  lbl,
}: {
  saving: boolean;
  message?: string;
  showNext?: boolean;
  onNextTab?: () => void;
  lbl: (key: string, fallback: string) => string;
}) {
  return (
    <div className="form__actions">
      <div className="d-flex align-items-center gap-1">
        {message && <p className="color-primary m-0 me-3">{message}</p>}
        <button type="submit" className="btn btn--primary" disabled={saving}>
          {saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
        </button>
        {showNext && onNextTab && (
          <button type="button" className="btn btn--secondary" disabled={saving} onClick={onNextTab}>
            {lbl('LBL_Next', 'Next')}
          </button>
        )}
      </div>
    </div>
  );
}

export function TeacherLanguagesSection({ profile, loading, onSaved, onNextTab }: SectionProps) {
  const { lbl, langId } = useSite();
  const [teachIds, setTeachIds] = useState<number[]>([]);
  const [speak, setSpeak] = useState<Record<number, { checked: boolean; proficiency: number }>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!profile) return;
    setTeachIds(profile.languages.selected_teach_lang_ids);
    const map: Record<number, { checked: boolean; proficiency: number }> = {};
    for (const opt of profile.languages.speak_languages) {
      const selected = profile.languages.selected_speak.find((s) => s.slang_id === opt.id);
      map[opt.id] = {
        checked: Boolean(selected),
        proficiency: selected?.proficiency ?? profile.languages.proficiency_levels[0]?.id ?? 0,
      };
    }
    setSpeak(map);
  }, [profile]);

  const profRequired = (profile?.languages.proficiency_levels.length ?? 0) > 0;

  const saveLanguages = async (goNext: boolean) => {
    if (!profile) return false;
    setSaving(true);
    setMessage('');
    const speakLanguages = Object.entries(speak)
      .filter(([, v]) => v.checked && (!profRequired || v.proficiency > 0))
      .map(([id, v]) => ({ slang_id: Number(id), proficiency: v.proficiency }));
    try {
      const res = await api.put<{ data: TeacherAccountProfile }>(
        '/account/teacher/languages',
        { teach_lang_ids: teachIds, speak_languages: speakLanguages },
        { params: { lang_id: langId } }
      );
      onSaved(res.data.data);
      setMessage(lbl('LBL_SETUP_SUCCESSFUL', 'Saved successfully'));
      if (goNext && onNextTab) {
        onNextTab();
      }
      return true;
    } catch {
      setMessage(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
      return false;
    } finally {
      setSaving(false);
    }
  };

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await saveLanguages(false);
  };

  const onSaveAndNext = async () => {
    await saveLanguages(true);
  };

  const profLabel = (langId: number) => {
    const row = speak[langId];
    if (!row?.checked || !profRequired) return null;
    const lvl = profile?.languages.proficiency_levels.find((p) => p.id === row.proficiency);
    return lvl ? (
      <span className="badge color-secondary badge-js badge--round badge--small m-0">{lvl.name}</span>
    ) : null;
  };

  const onSpeakCheck = (langId: number, checked: boolean) => {
    const defaultProf = profile?.languages.proficiency_levels[0]?.id ?? 0;
    setSpeak((prev) => ({
      ...prev,
      [langId]: {
        checked,
        proficiency: checked ? prev[langId]?.proficiency || defaultProf : 0,
      },
    }));
  };

  const onProficiencyChange = (langId: number, proficiency: number) => {
    setSpeak((prev) => ({
      ...prev,
      [langId]: { checked: proficiency > 0, proficiency },
    }));
  };

  if (loading || !profile) {
    return <p className="padding-6 color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  return (
    <>
      <div className="content-panel__head">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_MANAGE_LANGUAGES', 'Manage subjects')}</h5>
          </div>
          <div />
        </div>
      </div>
      <div className="content-panel__body">
        <div className="form">
          <form onSubmit={onSubmit}>
            <div className="form__body">
              <div className="colum-layout">
                <div className="colum-layout__cell">
                  <div className="colum-layout__head">
                    <span className="bold-600">{lbl('LBL_LANGUAGE_TO_TEACH', 'Subjects')}</span>
                    <span className="spn_must_field">*</span>
                  </div>
                  <div className="colum-layout__body">
                    <div className="colum-layout__scroll scrollbar">
                      <TeachLanguageTree
                        nodes={profile.languages.teach_language_tree}
                        selectedIds={teachIds}
                        onChange={setTeachIds}
                      />
                    </div>
                  </div>
                </div>
                <div className="colum-layout__cell">
                  <div className="colum-layout__head">
                    <span className="bold-600">{lbl('LBL_LANGUAGE_I_SPEAK', 'Language I speak')}</span>
                    <span className="spn_must_field">*</span>
                  </div>
                  <div className="colum-layout__body">
                    <div className="colum-layout__scroll scrollbar">
                      {profile.languages.speak_languages.map((lang) => {
                        const row = speak[lang.id] ?? { checked: false, proficiency: 0 };
                        if (profRequired) {
                          return (
                            <div
                              key={lang.id}
                              className={`selection selection--select slanguage-${lang.id}${
                                row.checked ? ' is-selected' : ''
                              }`}
                            >
                              <label className="selection__trigger">
                                <input
                                  type="checkbox"
                                  value={lang.id}
                                  className={`slanguage-checkbox-js slanguage-checkbox-${lang.id}`}
                                  checked={row.checked}
                                  onChange={(e) => onSpeakCheck(lang.id, e.target.checked)}
                                />
                                <span className="selection__trigger-action">
                                  <span className="selection__trigger-label">
                                    {lang.name}
                                    {profLabel(lang.id)}
                                  </span>
                                  <span className="selection__trigger-icon" />
                                </span>
                              </label>
                              {row.checked && (
                                <div className="selection__target">
                                  <select
                                    className="form-control uslang_proficiency select__dropdown"
                                    value={row.proficiency || ''}
                                    onChange={(e) =>
                                      onProficiencyChange(lang.id, Number(e.target.value))
                                    }
                                  >
                                    <option value="">
                                      {lbl('LBL_I_DO_SPEAK_THIS_LANGUAGE', 'I speak this language')}
                                    </option>
                                    {profile.languages.proficiency_levels.map((lvl) => (
                                      <option key={lvl.id} value={lvl.id}>
                                        {lvl.name}
                                      </option>
                                    ))}
                                  </select>
                                </div>
                              )}
                            </div>
                          );
                        }
                        return (
                          <div key={lang.id} className="selection">
                            <label className="selection__trigger">
                              <input
                                name={`uslang_slang_id[${lang.id}]`}
                                value={lang.id}
                                checked={row.checked}
                                className="selection__trigger-input"
                                type="checkbox"
                                onChange={(e) => onSpeakCheck(lang.id, e.target.checked)}
                              />
                              <span className="selection__trigger-action">
                                <span className="selection__trigger-label">{lang.name}</span>
                                <span className="selection__trigger-icon" />
                              </span>
                            </label>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <AccountFormActions
              saving={saving}
              message={message}
              showNext
              onNextTab={onSaveAndNext}
              lbl={lbl}
            />
          </form>
        </div>
      </div>
    </>
  );
}

export function TeacherPriceSection({ profile, loading, onSaved, onNextTab }: SectionProps) {
  const { lbl, langId } = useSite();
  const [prices, setPrices] = useState<Record<number, string>>({});
  const [slots, setSlots] = useState<number[]>([]);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!profile) return;
    const map: Record<number, string> = {};
    for (const lang of profile.prices.user_languages) {
      map[lang.utlang_id] = lang.price != null ? String(lang.price) : '';
    }
    setPrices(map);
    setSlots(profile.prices.selected_slots);
  }, [profile]);

  const savePrices = async (goNext: boolean) => {
    if (!profile) return false;
    setSaving(true);
    setMessage('');
    const payload: Record<number, number> = {};
    for (const [id, val] of Object.entries(prices)) {
      if (val !== '') payload[Number(id)] = Number(val);
    }
    try {
      const res = await api.put<{ data: TeacherAccountProfile }>(
        '/account/teacher/prices',
        { prices: payload, slots },
        { params: { lang_id: langId } }
      );
      onSaved(res.data.data);
      setMessage(lbl('LBL_SETUP_SUCCESSFUL', 'Saved successfully'));
      if (goNext && onNextTab) onNextTab();
      return true;
    } catch {
      setMessage(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
      return false;
    } finally {
      setSaving(false);
    }
  };

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await savePrices(false);
  };

  if (loading || !profile) {
    return <p className="padding-6 color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  if (profile.prices.user_languages.length === 0) {
    return (
      <div className="padding-6">
        <div className="content-panel__head">
          <h5>{lbl('LBL_Manage_Prices', 'Manage prices')}</h5>
        </div>
        <div className="content-panel__body">
          <p className="color-secondary">
            {lbl('LBL_SAVE_TEACH_LANGUAGES_FIRST', 'Save the subjects you teach first, then set prices here.')}
          </p>
        </div>
      </div>
    );
  }

  const slotLabel = (slot: number) =>
    lbl('LBL_{minute}_MINUTES', '{minute} minutes').replace('{minute}', String(slot));

  return (
    <>
      <div className="content-panel__head">
        <h5>{lbl('LBL_Manage_Prices', 'Manage prices')}</h5>
        <p className="mt-1 mb-0 style-italic">
          {profile.prices.manage_prices
            ? lbl(
                'LBL_NOTE:_PRICES_ARE_MANAGED_BY_ADMIN_AND_IN_BASE_CURRENCY',
                'Note: prices are managed by admin and in base currency'
              )
            : lbl(
                'LBL_NOTE:_ENTER_ALL_PRICES_IN_BASE_CURRENCY',
                'Note: enter all prices in base currency'
              )}{' '}
          [{profile.prices.currency_code}]
        </p>
      </div>
      <div className="content-panel__body">
        <form className="form" onSubmit={onSubmit}>
          <div className="form__body p-0">
            <div className="pricing-panel">
              <div className="table-controls">
                <table className="table-sticky-scroll">
                  <thead>
                    <tr className="table-controls__row">
                      <th className="table-controls__colum first-child">
                        {lbl('LBL_Subjects', 'Subjects')}
                      </th>
                      <th className="table-controls__colum color1">
                        <label className="position-relative">{lbl('LBL_HOURLY_PRICE', 'Hourly price')}</label>
                      </th>
                      {profile.prices.slot_options.map((slot) => (
                        <th
                          key={slot}
                          className={`table-controls__colum duration_${slot} ${
                            slots.includes(slot) ? 'is-selected' : 'color1'
                          }`}
                        >
                          <label className="position-relative">
                            <span className="checkbox">
                              <input
                                type="checkbox"
                                name="slots[]"
                                value={slot}
                                checked={slots.includes(slot)}
                                onChange={(e) =>
                                  setSlots((prev) =>
                                    e.target.checked
                                      ? [...prev, slot]
                                      : prev.filter((s) => s !== slot)
                                  )
                                }
                              />
                              <i className="input-helper" />
                            </span>
                            {slotLabel(slot)}
                          </label>
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {profile.prices.user_languages.map((lang) => (
                      <tr key={lang.utlang_id} className="table-controls__row has-price">
                        <td className="table-controls__colum first-child">{lang.name}</td>
                        <td className="table-controls__colum color1">
                          <div className="small-field">
                            {profile.prices.manage_prices ? (
                              <span>{lang.price ?? '—'}</span>
                            ) : (
                              <input
                                type="number"
                                className="form-control"
                                min={lang.min_price}
                                max={lang.max_price}
                                step="0.01"
                                value={prices[lang.utlang_id] ?? ''}
                                onChange={(e) =>
                                  setPrices((prev) => ({ ...prev, [lang.utlang_id]: e.target.value }))
                                }
                                required
                              />
                            )}
                          </div>
                        </td>
                        {profile.prices.slot_options.map((slot) => (
                          <td
                            key={slot}
                            className={`table-controls__colum duration_${slot} ${
                              slots.includes(slot) ? 'is-selected' : 'color1'
                            }`}
                          >
                            <div className="small-field">
                              {slots.includes(slot)
                                ? (
                                    (Number(prices[lang.utlang_id] || lang.price || 0) / 60) *
                                    slot
                                  ).toFixed(2)
                                : '—'}
                            </div>
                          </td>
                        ))}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <AccountFormActions
            saving={saving}
            message={message}
            showNext
            onNextTab={() => savePrices(true)}
            lbl={lbl}
          />
        </form>
      </div>
    </>
  );
}

export function TeacherExperienceSection({
  profile,
  experienceTypes,
  loading,
  onSaved,
  onNextTab,
}: SectionProps) {
  const { lbl, langId } = useSite();
  const { showModal, closeModal } = useModal();

  const openForm = (q: TeacherQualification | null) => {
    const editingId = q?.id ?? null;
    showModal(
      <TeacherQualificationModal
        editingId={editingId}
        initialForm={q ? qualificationToForm(q) : emptyQualificationForm()}
        experienceTypes={experienceTypes}
        onClose={closeModal}
        onSaved={onSaved}
      />,
      { size: 'modal-lg' }
    );
  };

  const onDelete = async (id: number) => {
    if (!window.confirm(lbl('LBL_CONFIRM_REMOVE', 'Are you sure you want to remove?'))) return;
    try {
      const res = await api.delete<{ data: TeacherAccountProfile }>(
        `/account/teacher/qualifications/${id}`,
        { params: { lang_id: langId } }
      );
      onSaved(res.data.data);
    } catch {
      window.alert(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    }
  };

  if (loading || !profile) {
    return <p className="padding-6 color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  const lblRI = lbl('LBL_RESUME_INFORMATION', 'Resume information');
  const lblSE = lbl('LBL_START/END', 'Start/End');
  const lblAttach = lbl('LBL_ATTACHMENT', 'Attachment');
  const lblAction = lbl('LBL_ACTIONS', 'Actions');

  return (
    <>
      <div className="content-panel__head">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_MANAGE_EXPERINCE', 'Manage experience')}</h5>
          </div>
          <div>
            <button
              type="button"
              className="btn btn--small btn--bordered color-secondary"
              onClick={() => openForm(null)}
            >
              {lbl('LBL_ADD_NEW', 'Add new')}
            </button>
          </div>
        </div>
      </div>
      <div className="content-panel__body">
        <div className="form">
          <div className="form__body p-0">
            <div className="table-scroll">
              <table className="table table--bordered table--responsive">
                <tbody>
                  <tr className="title-row">
                    <th>{lblRI}</th>
                    <th>{lblSE}</th>
                    <th>{lblAttach}</th>
                    <th>{lblAction}</th>
                  </tr>
                  {profile.qualifications.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="text-center color-secondary">
                        {lbl('LBL_NO_RECORD_FOUND', 'No record found')}
                      </td>
                    </tr>
                  ) : (
                    profile.qualifications.map((q) => (
                      <tr key={q.id} id={`qualification-${q.id}`}>
                        <td>
                          <div className="flex-cell">
                            <div className="flex-cell__label">{lblRI}</div>
                            <div className="flex-cell__content">
                              <div className="data-group">
                                <span className="bold-600">{q.title}</span>
                                <br />
                                <span>
                                  {lbl('LBL_LOCATION', 'Location')} - {q.institute_address}
                                </span>
                                <br />
                                <span>
                                  {lbl('LBL_INSTITUTION', 'Institution')} - {q.institute_name}
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div className="flex-cell">
                            <div className="flex-cell__label">{lblSE}</div>
                            <div className="flex-cell__content">
                              {q.start_year} - {q.end_year}
                            </div>
                          </div>
                        </td>
                        <td>
                          <div className="flex-cell">
                            <div className="flex-cell__label">{lblAttach}</div>
                            <div className="flex-cell__content">
                              {q.file_name ? (
                                <span className="attachment-file color-primary">{q.file_name}</span>
                              ) : (
                                lbl('LBL_NA', 'N/A')
                              )}
                            </div>
                          </div>
                        </td>
                        <td>
                          <div className="flex-cell">
                            <div className="flex-cell__label">{lblAction}</div>
                            <div className="flex-cell__content">
                              <div className="actions-group">
                                <QualificationActionButton
                                  title={lbl('LBL_EDIT', 'Edit')}
                                  icon="edit"
                                  onClick={() => openForm(q)}
                                />
                                <QualificationActionButton
                                  title={lbl('LBL_DELETE', 'Delete')}
                                  icon="trash"
                                  onClick={() => onDelete(q.id)}
                                />
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
          {onNextTab && (
            <div className="form__actions">
              <div className="d-flex align-items-center gap-1">
                <button type="button" className="btn btn--secondary" onClick={onNextTab}>
                  {lbl('LBL_Next', 'Next')}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}

export function TeacherSkillsSection({ profile, loading, onSaved }: SectionProps) {
  const { lbl, langId } = useSite();
  const [selected, setSelected] = useState<Record<number, boolean>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!profile) return;
    const map: Record<number, boolean> = {};
    for (const group of profile.preferences) {
      for (const opt of group.options) {
        map[opt.id] = group.selected_ids.includes(opt.id);
      }
    }
    setSelected(map);
  }, [profile]);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!profile) return;
    setSaving(true);
    setMessage('');
    const preferenceIds = Object.entries(selected)
      .filter(([, on]) => on)
      .map(([id]) => Number(id));
    try {
      const res = await api.put<{ data: TeacherAccountProfile }>(
        '/account/teacher/preferences',
        { preference_ids: preferenceIds },
        { params: { lang_id: langId } }
      );
      onSaved(res.data.data);
      setMessage(lbl('LBL_PREFERENCES_UPDATED_SUCCESSFULLY', 'Preferences updated successfully'));
    } catch {
      setMessage(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  if (loading || !profile) {
    return <p className="padding-6 color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  return (
    <>
      <div className="content-panel__head content-panel__head--divider">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_MANAGE_SKILLS', 'Manage skills')}</h5>
          </div>
          <div />
        </div>
      </div>
      <div className="content-panel__body">
        <form id="teacherPreferencesFrm" className="form" onSubmit={onSubmit}>
          <div className="form__body">
            {profile.preferences.map((group) => (
              <div key={group.type} className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl(
                          PREF_TYPE_LABELS[group.type] ?? 'LBL_SKILLS',
                          PREF_TYPE_FALLBACKS[group.type] ?? 'Skills'
                        )}
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <ul className="list-onethird list-onethird--bg">
                          {group.options.map((opt) => (
                            <li key={opt.id}>
                              <label>
                                <span className="checkbox">
                                  <input
                                    type="checkbox"
                                    name={`pref_${group.type}[]`}
                                    value={opt.id}
                                    checked={Boolean(selected[opt.id])}
                                    onChange={(e) =>
                                      setSelected((prev) => ({
                                        ...prev,
                                        [opt.id]: e.target.checked,
                                      }))
                                    }
                                  />
                                  <i className="input-helper" />
                                </span>
                                {opt.title}
                              </label>
                            </li>
                          ))}
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
          <AccountFormActions saving={saving} message={message} lbl={lbl} />
        </form>
      </div>
    </>
  );
}

export function useTeacherAccountProfile(enabled: boolean) {
  const { langId } = useSite();
  const [profile, setProfile] = useState<TeacherAccountProfile | null>(null);
  const [experienceTypes, setExperienceTypes] = useState<ExperienceType[]>([]);
  const [loading, setLoading] = useState(false);

  const load = useCallback(async () => {
    if (!enabled) return;
    setLoading(true);
    try {
      const res = await api.get<TeacherAccountResponse>('/account/teacher', {
        params: { lang_id: langId },
      });
      setProfile(res.data.data);
      setExperienceTypes(res.data.experience_types ?? []);
    } catch {
      setProfile(null);
    } finally {
      setLoading(false);
    }
  }, [enabled, langId]);

  useEffect(() => {
    load();
  }, [load]);

  return { profile, experienceTypes, loading, reload: load, setProfile };
}
