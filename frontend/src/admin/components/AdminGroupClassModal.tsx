import { type FormEvent, type ReactNode, useCallback, useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type LanguageOption = { id: number; name: string };
type TeacherSuggestion = { id: number; full_name: string; email: string };
type TabKey = 'general' | `lang-${number}` | 'media';
type PackageClassItem = { title: string; startAt: string };

type Props = {
  open: boolean;
  classType?: 1 | 2;
  onClose: () => void;
  onSaved: () => void;
};

const API_URL = import.meta.env.VITE_API_URL || '/api/v1';
const BANNER_FILE_TYPE = 55;

function LegacyField({
  label,
  required,
  children,
}: {
  label: string;
  required?: boolean;
  children: ReactNode;
}) {
  return (
    <div className="row">
      <div className="col-md-12">
        <div className="field-set">
          <div className="caption-wraper">
            <label className="field_label">
              {label}
              {required ? <span className="spn_must_field">*</span> : null}
            </label>
          </div>
          <div className="field-wraper">
            <div className="field_cover">{children}</div>
          </div>
        </div>
      </div>
    </div>
  );
}

function fromDatetimeLocal(value: string): string {
  return value.replace('T', ' ');
}

function formatSlug(value: string): string {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}

export function AdminGroupClassModal({ open, classType = 1, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [classId, setClassId] = useState(0);
  const [teachLanguages, setTeachLanguages] = useState<LanguageOption[]>([]);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [durations, setDurations] = useState<Record<string, string>>({});
  const [maxLearners, setMaxLearners] = useState(9999);
  const [offlineEnabled, setOfflineEnabled] = useState(false);
  const [teacherName, setTeacherName] = useState('');
  const [teacherId, setTeacherId] = useState('');
  const [teacherSuggestions, setTeacherSuggestions] = useState<TeacherSuggestion[]>([]);
  const [showTeacherSuggestions, setShowTeacherSuggestions] = useState(false);
  const [slug, setSlug] = useState('');
  const [tlangId, setTlangId] = useState('');
  const [offline, setOffline] = useState('0');
  const [startAt, setStartAt] = useState('');
  const [duration, setDuration] = useState('');
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [entryFee, setEntryFee] = useState('');
  const [currencyCode, setCurrencyCode] = useState('USD');
  const [totalSeats, setTotalSeats] = useState('10');
  const [packageClasses, setPackageClasses] = useState<PackageClassItem[]>([
    { title: '', startAt: '' },
    { title: '', startAt: '' },
  ]);
  const [langTitle, setLangTitle] = useState('');
  const [langDescription, setLangDescription] = useState('');
  const [langPackageTitles, setLangPackageTitles] = useState<string[]>([]);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [hasBanner, setHasBanner] = useState(false);
  const [bannerFile, setBannerFile] = useState<File | null>(null);
  const [bannerPreviewUrl, setBannerPreviewUrl] = useState('');
  const [bannerVersion, setBannerVersion] = useState(0);
  const teacherDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const teacherCoverRef = useRef<HTMLDivElement>(null);

  const resetForm = useCallback(() => {
    setActiveTab('general');
    setClassId(0);
    setTeacherName('');
    setTeacherId('');
    setTeacherSuggestions([]);
    setShowTeacherSuggestions(false);
    setSlug('');
    setTlangId('');
    setOffline('0');
    setStartAt('');
    setDuration('');
    setTitle('');
    setDescription('');
    setEntryFee('');
    setCurrencyCode('USD');
    setTotalSeats('10');
    setPackageClasses([
      { title: '', startAt: '' },
      { title: '', startAt: '' },
    ]);
    setLangTitle('');
    setLangDescription('');
    setLangPackageTitles([]);
    setAutoTranslate(false);
    setHasBanner(false);
    setBannerFile(null);
    setBannerPreviewUrl((value) => {
      if (value) URL.revokeObjectURL(value);
      return '';
    });
    setBannerVersion(0);
    setError('');
  }, []);

  const applyFormDefaults = useCallback((data: Record<string, unknown>) => {
    setTeachLanguages((data.teach_languages as LanguageOption[]) ?? []);
    setSiteLanguages((data.site_languages as LanguageOption[]) ?? []);
    setDurations((data.durations as Record<string, string>) ?? {});
    setMaxLearners(Number(data.max_learners ?? 9999));
    setOfflineEnabled(Boolean(data.offline_enabled));
    setCurrencyCode(String(data.currency_code ?? 'USD'));
    const defaultDuration = String(data.default_duration ?? '');
    setDuration(defaultDuration);
    setTotalSeats('10');
  }, []);

  useEffect(() => {
    if (!open) return;
    // eslint-disable-next-line react-hooks/set-state-in-effect
    resetForm();
    setLoading(true);
    void adminApi
      .groupClassCreateForm()
      .then((res) => applyFormDefaults(res.data.data ?? {}))
      .catch(() => setError('Failed to load form'))
      .finally(() => setLoading(false));
  }, [open, resetForm, applyFormDefaults]);

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (teacherCoverRef.current && !teacherCoverRef.current.contains(e.target as Node)) {
        setShowTeacherSuggestions(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  useEffect(() => {
    return () => {
      if (bannerPreviewUrl) URL.revokeObjectURL(bannerPreviewUrl);
    };
  }, [bannerPreviewUrl]);

  const handleClose = () => {
    resetForm();
    onClose();
  };

  const onTeacherChange = (value: string) => {
    setTeacherName(value);
    setTeacherId('');
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
    setTeacherName(item.full_name);
    setTeacherId(String(item.id));
    setShowTeacherSuggestions(false);
    setTeacherSuggestions([]);
  };

  const setPackageClassField = (index: number, field: keyof PackageClassItem, value: string) => {
    setPackageClasses((items) => items.map((item, i) => (i === index ? { ...item, [field]: value } : item)));
  };

  const addPackageClass = () => {
    setPackageClasses((items) => [...items, { title: '', startAt: '' }]);
  };

  const removePackageClass = (index: number) => {
    setPackageClasses((items) => (items.length > 1 ? items.filter((_, i) => i !== index) : items));
  };

  const setLangPackageTitle = (index: number, value: string) => {
    setLangPackageTitles((items) => {
      const next = [...items];
      next[index] = value;
      return next;
    });
  };

  const activeLangId = activeTab.startsWith('lang-') ? Number(activeTab.replace('lang-', '')) : 0;
  const activeLangIndex = siteLanguages.findIndex((lang) => lang.id === activeLangId);
  const isLastLangTab = activeLangIndex >= 0 && activeLangIndex === siteLanguages.length - 1;
  const activeLanguage = activeLangIndex >= 0 ? siteLanguages[activeLangIndex] : null;
  const isArabicLang =
    activeTab.startsWith('lang-') &&
    (activeLanguage?.name.toLowerCase() === 'arabic' || activeLanguage?.id === 2);
  const langLabel = (english: string, arabic: string) => (isArabicLang ? arabic : english);

  const loadLangTab = useCallback(async (id: number, langId: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.groupClassLangForm(id, langId);
      const data = res.data.data ?? {};
      setLangTitle(String(data.title ?? ''));
      setLangDescription(String(data.description ?? ''));
      setLangPackageTitles(
        ((data.package_classes as Array<{ title?: string }> | undefined) ?? []).map((item) =>
          String(item.title ?? ''),
        ),
      );
      setAutoTranslate(false);
      setActiveTab(`lang-${langId}`);
    } catch (e: unknown) {
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load language form',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  const loadMediaTab = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.groupClassMediaForm(id);
      setHasBanner(Boolean(res.data.data?.has_banner));
      setBannerFile(null);
      setBannerVersion((value) => value + 1);
      setActiveTab('media');
    } catch (e: unknown) {
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load media form',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  const goToNextAfterGeneral = (savedId: number) => {
    setClassId(savedId);
    const firstLang = siteLanguages[0];
    if (firstLang) {
      void loadLangTab(savedId, firstLang.id);
      return;
    }
    void loadMediaTab(savedId);
  };

  const submitGeneral = (e: FormEvent) => {
    e.preventDefault();
    if (!teacherId) {
      setError(lbl('LBL_INVALID_TEACHER', 'Please select a valid teacher.'));
      return;
    }
    const cleanPackageClasses = packageClasses
      .map((item) => ({ title: item.title.trim(), start_at: fromDatetimeLocal(item.startAt) }))
      .filter((item) => item.title !== '' || item.start_at !== '');
    if (classType === 2 && cleanPackageClasses.some((item) => item.title === '' || item.start_at === '')) {
      setError(lbl('LBL_PLEASE_FILL_ALL_REQUIRED_FIELDS', 'Please fill all required fields.'));
      return;
    }
    if (classType === 2 && cleanPackageClasses.length === 0) {
      setError(lbl('LBL_PLEASE_ADD_A_CLASS', 'Please add a class.'));
      return;
    }
    setSaving(true);
    setError('');
    const payload = {
      teacher_id: teacherId,
      slug,
      tlang_id: tlangId,
      offline,
      start_at: classType === 2 ? cleanPackageClasses[0]?.start_at ?? '' : fromDatetimeLocal(startAt),
      duration,
      title,
      description,
      entry_fee: entryFee,
      total_seats: totalSeats,
      class_type: classType,
      package_classes: classType === 2 ? cleanPackageClasses : undefined,
    };
    const request =
      classId > 0
        ? adminApi.updateGroupClass(classId, payload)
        : adminApi.createGroupClass(payload);
    void request
      .then((res) => {
        const savedId = Number(res.data.id ?? classId);
        goToNextAfterGeneral(savedId);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to save class',
        );
      })
      .finally(() => setSaving(false));
  };

  const submitLang = (e: FormEvent) => {
    e.preventDefault();
    if (classId <= 0 || activeLangId <= 0) return;
    setSaving(true);
    setError('');
    void adminApi
      .saveGroupClassLang(classId, activeLangId, {
        title: langTitle,
        description: langDescription,
        auto_translate: autoTranslate ? '1' : '0',
        package_classes: JSON.stringify(langPackageTitles.map((title) => ({ title }))),
      })
      .then(() => {
        if (isLastLangTab) {
          void loadMediaTab(classId);
          return;
        }
        const nextLang = siteLanguages[activeLangIndex + 1];
        if (nextLang) {
          void loadLangTab(classId, nextLang.id);
        }
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to save language data',
        );
      })
      .finally(() => setSaving(false));
  };

  const uploadBannerFile = (file: File, closeAfterUpload = false) => {
    if (classId <= 0) return;
    setSaving(true);
    setError('');
    void adminApi
      .uploadGroupClassBanner(classId, file)
      .then(() => {
        setHasBanner(true);
        setBannerVersion((value) => value + 1);
        onSaved();
        if (closeAfterUpload) handleClose();
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to upload banner',
        );
      })
      .finally(() => setSaving(false));
  };

  const submitMedia = (e: FormEvent) => {
    e.preventDefault();
    if (classId <= 0) return;
    if (!bannerFile && !hasBanner) {
      setError(lbl('LBL_PLEASE_SELECT_FILE', 'Please select a file.'));
      return;
    }
    if (!bannerFile) {
      onSaved();
      handleClose();
      return;
    }
    uploadBannerFile(bannerFile, true);
  };

  const onBannerChange = (file: File | null) => {
    if (!file) return;
    setBannerFile(file);
    setBannerPreviewUrl((value) => {
      if (value) URL.revokeObjectURL(value);
      return URL.createObjectURL(file);
    });
    uploadBannerFile(file);
  };

  const onTabClick = (tab: TabKey) => {
    if (tab === 'general') {
      setActiveTab('general');
      return;
    }
    if (classId <= 0) return;
    if (tab === 'media') {
      void loadMediaTab(classId);
      return;
    }
    if (tab.startsWith('lang-')) {
      const langId = Number(tab.replace('lang-', ''));
      void loadLangTab(classId, langId);
    }
  };

  const bannerUrl =
    classId > 0 && hasBanner
      ? `${API_URL}/image/show/${BANNER_FILE_TYPE}/${classId}/MEDIUM?t=${bannerVersion}`
      : '';
  const mediaPreviewUrl = bannerPreviewUrl || bannerUrl;

  return (
    <AdminModal
      open={open}
      title={
        activeTab === 'media'
          ? classType === 2
            ? lbl('LBL_GROUP_CLASS_PACKAGE_IMAGE', 'Group Class Package Image')
            : lbl('LBL_GROUP_CLASS_IMAGE', 'Group Class Image')
          : classType === 2
          ? lbl('LBL_GROUP_CLASS_PACKAGE_SETUP', 'Group Class Package Setup')
          : lbl('LBL_GROUP_CLASS_SETUP', 'Group Class Setup')
      }
      size="lg"
      onClose={handleClose}
    >
      <div className="card">
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul className="lang-list">
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={() => onTabClick('general')}
              >
                {lbl('LBL_General', 'General')}
              </a>
            </li>
            {siteLanguages.map((lang) => (
              <li key={lang.id} className={`lang-li${classId === 0 ? ' is-inactive' : ''}`}>
                <a
                  href="javascript:void(0)"
                  data-id={lang.id}
                  className={activeTab === `lang-${lang.id}` ? 'active' : ''}
                  onClick={() => onTabClick(`lang-${lang.id}`)}
                >
                  {lang.name}
                </a>
              </li>
            ))}
            <li className={classId === 0 ? 'is-inactive' : ''}>
              <a
                href="javascript:void(0)"
                className={activeTab === 'media' ? 'active' : ''}
                onClick={() => onTabClick('media')}
              >
                {lbl('LBL_MEDIA', 'Media')}
              </a>
            </li>
          </ul>
        </nav>
      </div>

      <div className="card-body">
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : null}
        {error ? <div className="alert alert-danger m-3">{error}</div> : null}

        {!loading && activeTab === 'general' ? (
          <form name="packageForm" id="groupClassesFrm" className="form form_horizontal" onSubmit={submitGeneral}>
              <LegacyField label={lbl('LBL_ASSIGN_TEACHER', 'Assign teacher')} required>
                <div ref={teacherCoverRef}>
                  <input
                    type="text"
                    name="teacher_keyword"
                    autoComplete="off"
                    value={teacherName}
                    onChange={(e) => onTeacherChange(e.target.value)}
                    onFocus={() => teacherSuggestions.length > 0 && setShowTeacherSuggestions(true)}
                    required
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
                            onMouseDown={(ev) => {
                              ev.preventDefault();
                              pickTeacher(item);
                            }}
                          >
                            {item.full_name} ({item.email})
                          </div>
                        </li>
                      ))}
                    </ul>
                  ) : null}
                  <input type="hidden" name="grpcls_teacher_id" value={teacherId} />
                </div>
              </LegacyField>

              <LegacyField label={lbl('LBL_TEACH_LANGUAGE', 'Teach language')} required>
                <select
                  name="grpcls_tlang_id"
                  value={tlangId}
                  onChange={(e) => setTlangId(e.target.value)}
                  required
                >
                  <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                  {teachLanguages.map((lang) => (
                    <option key={lang.id} value={lang.id}>
                      {lang.name}
                    </option>
                  ))}
                </select>
              </LegacyField>

              <LegacyField label={lbl('LBL_SERVICE_TYPE', 'Service type')} required>
                <select
                  name="grpcls_offline"
                  value={offline}
                  onChange={(e) => setOffline(e.target.value)}
                  required
                >
                  <option value="0">{lbl('LBL_ONLINE', 'Online')}</option>
                  {offlineEnabled ? (
                    <option value="1">{lbl('LBL_OFFLINE', 'Offline')}</option>
                  ) : null}
                </select>
              </LegacyField>

              <div className="row">
                <div className="col-md-12" style={{ display: 'none' }}>
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_ADDRESSES', 'Addresses')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <select name="grpcls_address_id">
                          <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <LegacyField label={lbl('LBL_SLUG', 'Slug')} required>
                <input
                  name="grpcls_slug"
                  value={slug}
                  onChange={(e) => setSlug(e.target.value)}
                  onBlur={(e) => setSlug(formatSlug(e.target.value))}
                  required
                />
              </LegacyField>

              {classType === 1 ? (
                <>
                  <LegacyField label={lbl('LBL_START_TIME', 'Start time')} required>
                    <input
                      type="datetime-local"
                      name="grpcls_start_datetime"
                      value={startAt}
                      onChange={(e) => setStartAt(e.target.value)}
                      required
                    />
                  </LegacyField>

                  <LegacyField label={lbl('LBL_DURATION', 'Duration')} required>
                    <select
                      name="grpcls_duration"
                      value={duration}
                      onChange={(e) => setDuration(e.target.value)}
                      required
                    >
                      <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                      {Object.entries(durations).map(([value, label]) => (
                        <option key={value} value={value}>
                          {label}
                        </option>
                      ))}
                    </select>
                  </LegacyField>
                </>
              ) : null}

              <LegacyField
                label={classType === 2 ? lbl('LBL_PACKAGE_TITLE', 'Package Title') : lbl('LBL_TITLE', 'Title')}
                required
              >
                <input
                  name="grpcls_title"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  required
                />
              </LegacyField>

              <LegacyField
                label={
                  classType === 2
                    ? lbl('LBL_PACKAGE_DESCRIPTION', 'Package Description')
                    : lbl('LBL_DESCRIPTION', 'Description')
                }
                required
              >
                <textarea
                  name="grpcls_description"
                  rows={4}
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  required
                />
              </LegacyField>

              <LegacyField label={lbl('LBL_MAX_LEARNERS', 'Max learners')} required>
                <input
                  id="grpcls_total_seats"
                  type="text"
                  name="grpcls_total_seats"
                  min={1}
                  max={maxLearners}
                  value={totalSeats}
                  onChange={(e) => setTotalSeats(e.target.value)}
                  required
                />
              </LegacyField>

              <LegacyField
                label={lbl('LBL_ENTRY_FEE_[{currency}]', 'Entry fee [{currency}]').replace(
                  '{currency}',
                  currencyCode,
                )}
                required
              >
                <input
                  id="grpcls_entry_fee"
                  type="text"
                  name="grpcls_entry_fee"
                  min={1}
                  step="0.01"
                  value={entryFee}
                  onChange={(e) => setEntryFee(e.target.value)}
                  required
                />
              </LegacyField>

              {classType === 2 ? (
                <LegacyField label={lbl('LBL_EACH_CLASS_(MINUTES)', 'Each class (minutes)')} required>
                  <select
                    name="grpcls_duration"
                    value={duration}
                    onChange={(e) => setDuration(e.target.value)}
                    required
                  >
                    <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                    {Object.entries(durations).map(([value, label]) => (
                      <option key={value} value={value}>
                        {label}
                      </option>
                    ))}
                  </select>
                </LegacyField>
              ) : null}

              {classType === 2 ? (
                <>
                  <div className="more-container-js">
                    {packageClasses.map((item, index) => (
                      <div className="row" key={index}>
                        <div className="col-md-6">
                          <div className="field-set">
                            <div className="caption-wraper">
                              <label className="field_label">
                                {lbl('LBL_CLASS_TITLE', 'Class title')}-{index + 1}
                                <span className="spn_must_field">*</span>
                                {index > 0 ? (
                                  <a
                                    href={`javascript:removeClassRow(${index + 1})`}
                                    className="color-secondary"
                                    onClick={(e) => {
                                      e.preventDefault();
                                      removePackageClass(index);
                                    }}
                                  >
                                    {' '}
                                    {lbl('LBL_REMOVE_CLASS', 'Remove class')}
                                  </a>
                                ) : null}
                              </label>
                            </div>
                            <div className="field-wraper">
                              <div className="field_cover">
                                <input
                                  name="title[]"
                                  value={item.title}
                                  onChange={(e) => setPackageClassField(index, 'title', e.target.value)}
                                  required
                                />
                              </div>
                            </div>
                          </div>
                        </div>

                        <div className="col-md-6">
                          <div className="field-set">
                            <div className="caption-wraper">
                              <label className="field_label">
                                {lbl('LBL_START_TIME', 'Start time')}
                                <span className="spn_must_field">*</span>
                              </label>
                            </div>
                            <div className="field-wraper">
                              <div className="field_cover">
                                <input
                                  className="datetime"
                                  autoComplete="off"
                                  readOnly
                                  type="text"
                                  name="starttime[]"
                                  value={item.startAt}
                                  onFocus={(e) => {
                                    e.currentTarget.readOnly = false;
                                    e.currentTarget.type = 'datetime-local';
                                  }}
                                  onBlur={(e) => {
                                    e.currentTarget.readOnly = true;
                                    e.currentTarget.type = 'text';
                                  }}
                                  onChange={(e) => setPackageClassField(index, 'startAt', e.target.value)}
                                  required
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                  <div className="row">
                    <div className="col-md-9" />
                    <div className="col-md-3" style={{ textAlign: 'right' }}>
                      <div className="caption-wraper">
                        <label className="field_label">
                          <a href="javascript:void(0)" className="color-secondary" onClick={addPackageClass}>
                            +{lbl('LBL_ADD_MORE', 'Add more')}
                          </a>
                        </label>
                      </div>
                    </div>
                  </div>
                </>
              ) : null}

              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label" />
                    </div>
                    <div className="field-wraper form-buttons-group">
                      <button type="submit" className="btn btn-brand" disabled={saving}>
                        {saving
                          ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                          : lbl('LBL_SAVE_&_NEXT', 'Save & Next')}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <input type="hidden" name="grpcls_id" value={classId || ''} />
          </form>
        ) : null}

        {!loading && activeTab.startsWith('lang-') ? (
          <form className="form form_horizontal" dir={isArabicLang ? 'rtl' : undefined} onSubmit={submitLang}>
              <LegacyField label={langLabel(lbl('LBL_TITLE', 'Title'), 'عنوان')} required>
                <input
                  value={langTitle}
                  onChange={(e) => setLangTitle(e.target.value)}
                  required
                />
              </LegacyField>
              <LegacyField label={langLabel(lbl('LBL_DESCRIPTION', 'Description'), 'وصف')} required>
                <textarea
                  rows={4}
                  value={langDescription}
                  onChange={(e) => setLangDescription(e.target.value)}
                  required
                />
              </LegacyField>
              {classType === 2
                ? langPackageTitles.map((item, index) => (
                    <LegacyField
                      key={index}
                      label={
                        isArabicLang
                          ? `${index + 1}-عنوان الفصل`
                          : `${lbl('LBL_CLASS_TITLE', 'Class title')}-${index + 1}`
                      }
                      required
                    >
                      <input
                        value={item}
                        onChange={(e) => setLangPackageTitle(index, e.target.value)}
                        required
                      />
                    </LegacyField>
                  ))
                : null}
              {!isArabicLang ? (
                <div className="row">
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label" />
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <label className="checkbox d-flex">
                            <input
                              type="checkbox"
                              name="auto_translate"
                              checked={autoTranslate}
                              onChange={(e) => setAutoTranslate(e.target.checked)}
                            />
                            <span className="input-helper" />{' '}
                            {lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label" />
                    </div>
                    <div className="field-wraper form-buttons-group">
                      {isArabicLang ? (
                        <button
                          type="button"
                          className="btn btn--secondary"
                          disabled={saving}
                          onClick={() => setAutoTranslate(true)}
                        >
                          ملء تلقائيًا للغات الباقية
                        </button>
                      ) : null}
                      <button type="submit" className="btn btn-brand" disabled={saving}>
                        {saving
                          ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                          : isArabicLang
                            ? 'حفظ التغييرات'
                            : isLastLangTab
                            ? lbl('LBL_SAVE_CHANGES', 'Save changes')
                            : lbl('LBL_SAVE_&_NEXT', 'Save & Next')}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
          </form>
        ) : null}

        {!loading && activeTab === 'media' ? (
          <form className="form form_horizontal" onSubmit={submitMedia}>
              <div className="row">
                <div className="col-md-6">
                  <p className="color-dark">
                    <strong>{lbl('LBL_IMAGE_DISCLAIMER', 'Image disclaimer')} :</strong>{' '}
                    {lbl('LBL_PREFERRED_DIMENSIONS_ARE', 'Preferred dimensions are')}
                    <br />
                    1000x563 {lbl('LBL_AND_ALLOWED_FILE_EXTS', 'And Allowed file Exts')} png, jpg,
                    jpeg
                  </p>
                </div>
                <div className="col-md-6">
                  <div className="text-right">
                    <label
                      aria-label={lbl('LBL_CLASS_BANNER', 'Class banner')}
                      style={{
                        alignItems: 'center',
                        border: '1px dashed #cfd5db',
                        borderRadius: '8px',
                        cursor: saving ? 'wait' : 'pointer',
                        display: 'inline-flex',
                        height: '190px',
                        justifyContent: 'center',
                        maxWidth: '100%',
                        padding: '14px',
                        width: '270px',
                      }}
                    >
                      <input
                        type="file"
                        accept="image/png,image/jpeg,image/jpg"
                        disabled={saving}
                        style={{ display: 'none' }}
                        onChange={(e) => onBannerChange(e.target.files?.[0] ?? null)}
                      />
                      {mediaPreviewUrl ? (
                        <img
                          src={mediaPreviewUrl}
                          alt={title}
                          style={{ height: '100%', objectFit: 'cover', width: '100%' }}
                        />
                      ) : (
                        <span
                          style={{
                            alignItems: 'center',
                            background: '#dedede',
                            color: '#747474',
                            display: 'inline-flex',
                            height: '146px',
                            justifyContent: 'center',
                            width: '236px',
                          }}
                        >
                          <svg
                            aria-hidden="true"
                            width="38"
                            height="38"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.8"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                          >
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <circle cx="8.5" cy="10" r="1.5" />
                            <path d="m21 15-5-5L5 19" />
                          </svg>
                        </span>
                      )}
                    </label>
                  </div>
                </div>
              </div>
              <div className="row">
                <div className="col-md-12">
                  <hr />
                </div>
              </div>
          </form>
        ) : null}
      </div>
      </div>
    </AdminModal>
  );
}
