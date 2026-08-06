import { type FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { legacyImageUrl } from '../utils/adminMedia';
import { AdminModal } from './AdminModal';
import { AdminSpriteIcon } from './AdminSpriteIcon';
import { AdminTabbedModalLayout, type AdminTabbedModalTab } from './AdminTabbedModalLayout';

const TYPE_TEACHING_LANGUAGES = 42;

type LanguageOption = { id: number; name: string };
type TabKey = AdminTabbedModalTab;

type TeachLanguageDetail = {
  tlang_id: number;
  tlang_identifier: string;
  tlang_slug: string;
  tlang_parent: number;
  tlang_featured: number;
  tlang_active: number;
  tlang_subcategories: number;
  tlang_min_price: number;
  tlang_max_price: number;
  tlang_hourly_price: number;
  tlang_name: string;
  tlang_description: string;
};

type Props = {
  open: boolean;
  tlangId: number;
  parentId: number;
  managePrices: boolean;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminTeachLanguageModal({
  open,
  tlangId,
  parentId,
  managePrices,
  onClose,
  onSaved,
}: Props) {
  const { lbl, langId } = useSite();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [slug, setSlug] = useState('');
  const [selectedParent, setSelectedParent] = useState('0');
  const [parentOptions, setParentOptions] = useState<{ id: number; name: string }[]>([]);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [featured, setFeatured] = useState(false);
  const [status, setStatus] = useState('1');
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [hourlyPrice, setHourlyPrice] = useState('');
  const [hasSubcategories, setHasSubcategories] = useState(false);
  const [hasImage, setHasImage] = useState(false);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [mediaDimensions, setMediaDimensions] = useState('60x60');
  const [mediaExtensions, setMediaExtensions] = useState('png, jpg, jpeg');
  const [imageCacheBust, setImageCacheBust] = useState(0);

  const activeLangId = String(activeTab).startsWith('lang-')
    ? Number(String(activeTab).replace('lang-', ''))
    : 0;
  const activeLanguage = siteLanguages.find((lang) => lang.id === activeLangId);
  const activeLanguageName = activeLanguage?.name.toLowerCase() ?? '';
  const isArabicLang = activeLanguageName === 'arabic' || activeLangId === 2;
  const isSpanishLang = activeLanguageName === 'spanish';
  const layoutDirection = isArabicLang ? 'rtl' : undefined;
  const showAutoTranslate = siteLanguages.length > 1 && activeLangId === siteLanguages[0]?.id;
  const showAutoTranslateButton = isArabicLang || isSpanishLang;
  const selectedParentId = Number(selectedParent) || 0;
  const showMediaTab = selectedParentId < 1;
  const showFeatured = selectedParentId < 1;
  const showPrices = !hasSubcategories;

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIdentifier('');
    setSlug('');
    setSelectedParent(String(parentId));
    setParentOptions([]);
    setName('');
    setDescription('');
    setFeatured(false);
    setStatus('1');
    setMinPrice('');
    setMaxPrice('');
    setHourlyPrice('');
    setHasSubcategories(false);
    setHasImage(false);
    setAutoTranslate(false);
    setMediaDimensions('60x60');
    setMediaExtensions('png, jpg, jpeg');
    setImageCacheBust(0);
  }, [parentId]);

  const loadParentOptions = useCallback(
    (excludeId: number) => {
      void adminApi.teachLanguageContext(parentId, langId, excludeId).then((res) => {
        setParentOptions((res.data.data?.parent_options as { id: number; name: string }[]) ?? []);
      });
    },
    [langId, parentId],
  );

  const loadGeneral = useCallback((id: number) => {
    setLoading(true);
    setError('');
    void adminApi
      .teachLanguageShow(id, 1)
      .then((res) => {
        const data = res.data.data as TeachLanguageDetail;
        setIdentifier(data.tlang_identifier ?? '');
        setSlug(data.tlang_slug ?? '');
        setSelectedParent(String(data.tlang_parent ?? parentId));
        setFeatured(Number(data.tlang_featured ?? 0) === 1);
        setStatus(String(data.tlang_active ?? 1));
        setMinPrice(String(data.tlang_min_price ?? ''));
        setMaxPrice(String(data.tlang_max_price ?? ''));
        setHourlyPrice(String(data.tlang_hourly_price ?? ''));
        setHasSubcategories(Number(data.tlang_subcategories ?? 0) > 0);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load teaching subject',
        );
      })
      .finally(() => setLoading(false));
  }, [parentId]);

  const onParentChange = (value: string) => {
    setSelectedParent(value);
    if (Number(value) > 0) {
      setFeatured(false);
    }
  };

  const loadLangTab = useCallback((id: number, langId: number) => {
    setLoading(true);
    setError('');
    void adminApi
      .teachLanguageShow(id, langId)
      .then((res) => {
        const data = res.data.data as TeachLanguageDetail;
        setName(data.tlang_name ?? '');
        setDescription(data.tlang_description ?? '');
        setAutoTranslate(false);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load teaching subject',
        );
      })
      .finally(() => setLoading(false));
  }, []);

  const loadMediaTab = useCallback((id: number) => {
    setLoading(true);
    setError('');
    void adminApi
      .teachLanguageMediaForm(id)
      .then((res) => {
        const data = res.data.data ?? {};
        setHasImage(Boolean(data.has_image));
        setMediaDimensions(String(data.dimensions ?? '60x60'));
        setMediaExtensions(String(data.allowed_extensions ?? 'png, jpg, jpeg'));
        setImageCacheBust(Date.now());
        setActiveTab('media');
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load media form',
        );
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .courseLanguageCreateForm()
      .then((res) => {
        setSiteLanguages(res.data.data?.site_languages ?? []);
      })
      .finally(() => setLoading(false));

    loadParentOptions(tlangId);

    if (tlangId > 0) {
      setRecordId(tlangId);
      loadGeneral(tlangId);
    } else {
      reset();
      setSelectedParent(String(parentId));
    }
  }, [loadGeneral, loadParentOptions, open, parentId, reset, tlangId]);

  const onSelectLangTab = (langId: number) => {
    if (recordId < 1) {
      return;
    }
    setActiveTab(`lang-${langId}`);
    loadLangTab(recordId, langId);
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload: Record<string, unknown> = {
        tlang_identifier: identifier.trim(),
        tlang_slug: slug.trim(),
        tlang_parent: selectedParentId,
        tlang_featured: featured ? 1 : 0,
        tlang_active: Number(status),
        lang_id: 1,
      };
      if (showPrices) {
        if (managePrices) {
          payload.tlang_hourly_price = Number(hourlyPrice || 0);
        } else {
          payload.tlang_min_price = Number(minPrice || 0);
          payload.tlang_max_price = Number(maxPrice || 0);
        }
      }
      const res =
        recordId > 0
          ? await adminApi.updateTeachLanguage(recordId, payload)
          : await adminApi.createTeachLanguage(payload);
      const savedId = Number(res.data?.data?.tlang_id ?? recordId);
      setRecordId(savedId);
      onSaved();
      const firstLang = siteLanguages[0];
      if (firstLang) {
        setActiveTab(`lang-${firstLang.id}`);
        loadLangTab(savedId, firstLang.id);
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save teaching subject',
      );
    } finally {
      setSaving(false);
    }
  };

  const onSaveLang = async (e: FormEvent) => {
    e.preventDefault();
    if (recordId < 1) {
      return;
    }
    const langId = Number(String(activeTab).replace('lang-', ''));
    const submitter = (e.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
    const shouldAutoTranslate = submitter?.name === 'update_langs_data' || autoTranslate;
    setSaving(true);
    setError('');
    try {
      await adminApi.updateTeachLanguage(recordId, {
        tlang_identifier: identifier.trim(),
        tlang_slug: slug.trim(),
        tlang_name: name.trim(),
        tlang_description: description.trim(),
        tlang_parent: selectedParentId,
        tlang_featured: featured ? 1 : 0,
        tlang_active: Number(status),
        lang_id: langId,
        update_langs_data: shouldAutoTranslate ? 1 : 0,
      });
      onSaved();
      const currentIndex = siteLanguages.findIndex((lang) => lang.id === langId);
      const nextLang = siteLanguages[currentIndex + 1];
      if (nextLang) {
        setActiveTab(`lang-${nextLang.id}`);
        loadLangTab(recordId, nextLang.id);
        return;
      }
      if (showMediaTab) {
        loadMediaTab(recordId);
        return;
      }
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save teaching subject',
      );
    } finally {
      setSaving(false);
    }
  };

  const onPickImage = () => {
    fileInputRef.current?.click();
  };

  const onImageSelected = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file || recordId < 1) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      await adminApi.uploadTeachLanguageImage(recordId, file);
      setHasImage(true);
      setImageCacheBust(Date.now());
      onSaved();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to upload image',
      );
    } finally {
      setSaving(false);
    }
  };

  const onRemoveImage = async () => {
    if (recordId < 1 || !hasImage) {
      return;
    }
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'))) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      await adminApi.removeTeachLanguageImage(recordId);
      setHasImage(false);
      setImageCacheBust(Date.now());
      onSaved();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to remove image',
      );
    } finally {
      setSaving(false);
    }
  };

  const modalTitle =
    activeTab === 'media'
      ? lbl('LBL_LANGUAGE_IMAGE', 'Language image')
      : lbl('LBL_TEACHING_LANGUAGE_SETUP', 'Teaching subject setup');

  const imageUrl = `${legacyImageUrl(TYPE_TEACHING_LANGUAGES, hasImage ? recordId : 0, 'LARGE')}?t=${imageCacheBust}`;

  return (
    <AdminModal open={open} title={modalTitle} size="md" onClose={onClose}>
      <AdminTabbedModalLayout
        activeTab={activeTab}
        recordId={recordId}
        siteLanguages={siteLanguages}
        generalBodyClass="form-edit-body"
        langBodyClass="card-body"
        mediaBodyClass="form-edit-body"
        showMediaTab={showMediaTab}
        loading={loading}
        error={error}
        lbl={lbl}
        onSelectGeneral={() => {
          setActiveTab('general');
          if (recordId > 0) {
            loadGeneral(recordId);
          }
        }}
        onSelectLang={onSelectLangTab}
        onSelectMedia={() => {
          if (recordId > 0) {
            loadMediaTab(recordId);
          }
        }}
      >
        {activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={onSaveGeneral}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_LANGUAGE_IDENTIFIER', 'Identifier')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={identifier}
                        onChange={(e) => setIdentifier(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_LANGUAGE_SLUG', 'Language slug')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={slug}
                        onChange={(e) => setSlug(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_PARENT', 'Parent')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={selectedParent}
                        onChange={(e) => onParentChange(e.target.value)}
                        required
                      >
                        <option value="0">{lbl('LBL_ROOT_LANGUAGE', 'Root language')}</option>
                        {parentOptions.map((option) => (
                          <option key={option.id} value={option.id}>
                            {option.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              {showFeatured ? (
                <div className="col-md-12">
                  <div className="field-set fldFeaturedJs">
                    <div className="field-wraper">
                      <div className="field_cover">
                        <label className="checkbox d-flex">
                          <input
                            type="checkbox"
                            checked={featured}
                            onChange={(e) => setFeatured(e.target.checked)}
                          />
                          <span>{lbl('LBL_FEATURED', 'Featured')}</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
              {showPrices ? (
                managePrices ? (
                  <div className="col-md-12">
                    <div className="field-set">
                      <div className="caption-wraper">
                        <label className="field_label">
                          {lbl('LBL_HOURLY_PRICE', 'Hourly price')}
                          <span className="spn_must_field">*</span>
                        </label>
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <input
                            className="form-control"
                            type="number"
                            min="1"
                            step="0.01"
                            value={hourlyPrice}
                            onChange={(e) => setHourlyPrice(e.target.value)}
                            required
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                ) : (
                  <>
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_HOURLY_MIN_PRICE', 'Hourly min price')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              className="form-control"
                              type="number"
                              min="1"
                              step="0.01"
                              value={minPrice}
                              onChange={(e) => setMinPrice(e.target.value)}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_HOURLY_MAX_PRICE', 'Hourly max price')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
                              className="form-control"
                              type="number"
                              min="1"
                              step="0.01"
                              value={maxPrice}
                              onChange={(e) => setMaxPrice(e.target.value)}
                              required
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </>
                )
              ) : null}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_STATUS', 'Status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                      >
                        <option value="1">{lbl('LBL_ACTIVE', 'Active')}</option>
                        <option value="0">{lbl('LBL_INACTIVE', 'Inactive')}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : activeTab === 'media' ? (
          <div className="form form_horizontal">
            <input
              ref={fileInputRef}
              type="file"
              className="hide tlang_image_file"
              accept="image/png,image/jpeg,image/jpg"
              onChange={(e) => void onImageSelected(e)}
            />
            <div className="row">
              <div className="col-md-6">
                <div className="form-group">
                  <h6>{lbl('LBL_LANGUAGE_IMAGE', 'Language image')}</h6>
                  <span className="form-text text-muted">
                    <strong>{lbl('LBL_IMAGE_DISCLAIMER', 'Image disclaimer')} : </strong>
                    {lbl('LBL_PREFERRED_DIMENSIONS_ARE_{dimensions}', 'Preferred dimensions are {dimensions}')
                      .replace('{dimensions}', mediaDimensions)}{' '}
                    {lbl('LBL_ALLOWED_FILE_EXTS_{extension}', 'Allowed file extensions {extension}').replace(
                      '{extension}',
                      mediaExtensions,
                    )}
                  </span>
                </div>
              </div>
              <div className="col-md-6">
                <div className="form-group">
                  <div className="dropzone mt-3 dropzoneContainerJs">
                    <div className="dropzone-uploaded dropzoneUploadedJs">
                      <img src={imageUrl} alt="" title="" />
                      <div className="dropzone-uploaded-action">
                        <ul className="actions">
                          <li>
                            <a
                              href="javascript:void(0)"
                              className="tlanguageFile-Js"
                              title={lbl('LBL_CLICK_HERE_TO_EDIT', 'Click here to edit')}
                              onClick={(e) => {
                                e.preventDefault();
                                onPickImage();
                              }}
                            >
                              <AdminSpriteIcon icon="edit" />
                            </a>
                          </li>
                          {hasImage ? (
                            <li>
                              <a
                                href="javascript:void(0)"
                                title={lbl('LBL_CLICK_HERE_TO_REMOVE', 'Click here to remove')}
                                onClick={(e) => {
                                  e.preventDefault();
                                  void onRemoveImage();
                                }}
                              >
                                <AdminSpriteIcon icon="delete" />
                              </a>
                            </li>
                          ) : null}
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="form-group">
                  <div className="separator my-3" />
                </div>
              </div>
            </div>
          </div>
        ) : (
          <form className="form form_horizontal" dir={layoutDirection} onSubmit={onSaveLang}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {isArabicLang
                        ? 'اسم'
                        : isSpanishLang
                          ? 'Nombre'
                          : lbl('LBL_Teach_Language_name', 'Name')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {isArabicLang
                        ? 'وصف اللغة'
                        : isSpanishLang
                          ? 'Descripción del idioma'
                          : lbl('LBL_LANGUAGE_DESCRIPTION', 'Language description')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        className="form-control"
                        rows={6}
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {showAutoTranslate ? (
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="field-wraper">
                      <div className="field_cover">
                        <label className="checkbox d-flex">
                          <input
                            type="checkbox"
                            name="update_langs_data"
                            value="1"
                            checked={autoTranslate}
                            onChange={(event) => setAutoTranslate(event.target.checked)}
                          />
                          <span className="input-helper" />
                          <span>
                            {lbl(
                              'LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES',
                              'Auto translate for other languages',
                            )}
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}
            <div className="form-actions form-buttons-group">
              {showAutoTranslateButton ? (
                <>
                  {!isArabicLang ? (
                    <button type="submit" className="btn btn-brand" disabled={saving}>
                      {saving
                        ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                        : isSpanishLang
                          ? 'Guardar cambios'
                          : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                    </button>
                  ) : null}
                  <button
                    type="submit"
                    name="update_langs_data"
                    value="1"
                    className="btn btn-secondary"
                    disabled={saving}
                  >
                    {isArabicLang
                      ? 'الملء التلقائي لبيانات اللغة'
                      : isSpanishLang
                        ? 'Autocompletar datos de idioma'
                        : lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                  </button>
                  {isArabicLang ? (
                    <button type="submit" className="btn btn-brand" disabled={saving}>
                      {saving ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait') : 'حفظ التغييرات'}
                    </button>
                  ) : null}
                </>
              ) : (
                <button type="submit" className="btn btn-primary" disabled={saving}>
                  {lbl('LBL_SAVE_CHANGES', 'Save changes')}
                </button>
              )}
            </div>
          </form>
        )}
      </AdminTabbedModalLayout>
    </AdminModal>
  );
}
