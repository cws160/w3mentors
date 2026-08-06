import {
  type ChangeEvent,
  type FormEvent,
  useCallback,
  useEffect,
  useRef,
  useState,
} from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { legacyImageUrl } from '../utils/adminMedia';
import { AdminModal } from './AdminModal';
import { AdminSpriteIcon } from './AdminSpriteIcon';

type LanguageOption = { id: number; name: string };
type ParentOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}` | 'media';

const TYPE_CATEGORY_IMAGE = 64;

type Props = {
  open: boolean;
  cateId: number;
  cateType: number;
  defaultParentId?: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminCategoryModal({
  open,
  cateId,
  cateType,
  defaultParentId = 0,
  onClose,
  onSaved,
}: Props) {
  const { lbl } = useSite();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [parentCategories, setParentCategories] = useState<ParentOption[]>([]);
  const [showFeatured, setShowFeatured] = useState(false);
  const [identifier, setIdentifier] = useState('');
  const [parentId, setParentId] = useState('0');
  const [featured, setFeatured] = useState(false);
  const [status, setStatus] = useState('1');
  const [langName, setLangName] = useState('');
  const [langDetails, setLangDetails] = useState('');
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [layoutDirection, setLayoutDirection] = useState('ltr');
  const [hasImage, setHasImage] = useState(false);
  const [imageCacheBust, setImageCacheBust] = useState(0);

  const activeLangId = activeTab.startsWith('lang-') ? Number(activeTab.replace('lang-', '')) : 0;
  const activeLangIndex = siteLanguages.findIndex((lang) => lang.id === activeLangId);
  const activeLanguage = activeLangIndex >= 0 ? siteLanguages[activeLangIndex] : null;
  const isArabicLang =
    activeTab.startsWith('lang-') &&
    (activeLanguage?.name.toLowerCase() === 'arabic' || activeLanguage?.id === 2);
  const isSpanishLang = activeTab.startsWith('lang-') && activeLanguage?.name.toLowerCase() === 'spanish';
  const isDefaultLang = activeLangId > 0 && activeLangId === siteLanguages[0]?.id;
  const showAutoTranslate = siteLanguages.length > 1 && activeLangId > 0;
  const showAutoTranslateCheckbox = showAutoTranslate && isDefaultLang;
  const showAutoTranslateButton = showAutoTranslate && !isDefaultLang;
  const isLastLangTab = activeLangIndex >= 0 && activeLangIndex === siteLanguages.length - 1;

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIdentifier('');
    setParentId(String(defaultParentId));
    setFeatured(false);
    setStatus('1');
    setLangName('');
    setLangDetails('');
    setAutoTranslate(false);
    setLayoutDirection('ltr');
    setHasImage(false);
    setImageCacheBust(0);
  }, [defaultParentId]);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .categoryCreateForm(cateType, defaultParentId)
      .then((res) => {
        const data = res.data.data ?? {};
        setSiteLanguages((data.site_languages as LanguageOption[]) ?? []);
        setParentCategories((data.parent_categories as ParentOption[]) ?? []);
        setShowFeatured(Boolean(data.show_featured));
        setParentId(String(data.default_parent_id ?? defaultParentId));
      })
      .finally(() => setLoading(false));

    if (cateId > 0) {
      void adminApi.categoryShow(cateId, cateType).then((res) => {
        const data = res.data.data ?? {};
        setRecordId(cateId);
        setIdentifier(String(data.cate_identifier ?? ''));
        setParentId(String(data.cate_parent ?? 0));
        setFeatured(Number(data.cate_featured) === 1);
        setStatus(String(data.cate_status ?? 1));
        setShowFeatured(Boolean(data.show_featured));
      });
    }
  }, [cateId, cateType, defaultParentId, open, reset]);

  const loadLangTab = useCallback(
    async (langId: number, categoryId = recordId, showLoader = false) => {
      if (categoryId < 1) return;
      if (showLoader) setLoading(true);
      setError('');
      try {
        const res = await adminApi.categoryLangForm(categoryId, langId, cateType);
        const data = res.data.data ?? {};
        setLangName(String(data.cate_name ?? ''));
        setLangDetails(String(data.cate_details ?? ''));
        setAutoTranslate(false);
        setLayoutDirection(String(data.layout_direction ?? 'ltr'));
        setActiveTab(`lang-${langId}`);
      } catch (err: unknown) {
        const message =
          err && typeof err === 'object' && 'response' in err
            ? String(
                (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                  'Unable to load language form',
              )
            : 'Unable to load language form';
        setError(message);
      } finally {
        if (showLoader) setLoading(false);
      }
    },
    [cateType, recordId],
  );

  const loadMediaTab = useCallback(
    async (id: number) => {
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.categoryMediaForm(id, cateType);
        setHasImage(Boolean(res.data.data?.has_image));
        setImageCacheBust(Date.now());
        setActiveTab('media');
      } catch (err: unknown) {
        const message =
          err && typeof err === 'object' && 'response' in err
            ? String(
                (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                  'Unable to load media form',
              )
            : 'Unable to load media form';
        setError(message);
      } finally {
        setLoading(false);
      }
    },
    [cateType],
  );

  const onTabClick = (tab: TabKey) => {
    if (tab === 'general') {
      setActiveTab('general');
      return;
    }
    if (recordId < 1) return;
    if (tab === 'media') {
      void loadMediaTab(recordId);
      return;
    }
    if (tab.startsWith('lang-')) {
      const langId = Number(tab.replace('lang-', ''));
      void loadLangTab(langId, recordId, false);
    }
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload = {
        cate_id: recordId,
        cate_type: cateType,
        cate_identifier: identifier.trim(),
        cate_parent: Number(parentId),
        cate_featured: featured ? 1 : 0,
        cate_status: Number(status),
      };
      const res =
        recordId > 0
          ? await adminApi.updateCategory(recordId, payload)
          : await adminApi.createCategory(payload);
      const savedId = Number(res.data.id ?? recordId);
      setRecordId(savedId);
      onSaved();
      const firstLang = siteLanguages[0];
      if (firstLang) {
        await loadLangTab(firstLang.id, savedId, false);
      }
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to save')
          : 'Unable to save';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  const onSaveLang = async (e: FormEvent) => {
    e.preventDefault();
    if (recordId < 1 || activeLangId < 1) return;
    const submitter = (e.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
    const shouldAutoTranslate = submitter?.name === 'update_langs_data' || autoTranslate;
    setSaving(true);
    setError('');
    try {
      await adminApi.storeCategoryLang(recordId, activeLangId, {
        cate_type: cateType,
        cate_name: langName.trim(),
        cate_details: langDetails.trim(),
        update_langs_data: shouldAutoTranslate ? 1 : 0,
      });
      onSaved();
      if (isLastLangTab) {
        await loadMediaTab(recordId);
        return;
      }
      const nextLang = siteLanguages[activeLangIndex + 1];
      if (nextLang) {
        await loadLangTab(nextLang.id, recordId, false);
      }
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to save')
          : 'Unable to save';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  const onPickImage = () => {
    if (recordId < 1 || saving) return;
    fileInputRef.current?.click();
  };

  const onImageSelected = async (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file || recordId < 1) return;

    setSaving(true);
    setError('');
    try {
      await adminApi.uploadCategoryImage(recordId, file, cateType);
      setHasImage(true);
      setImageCacheBust(Date.now());
      onSaved();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String(
              (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                'Unable to upload image',
            )
          : 'Unable to upload image';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  const onRemoveImage = async () => {
    if (recordId < 1 || !hasImage) return;
    if (!window.confirm(lbl('LBL_CONFIRM_DELETE_IMAGE', 'Are you sure you want to delete this image?'))) {
      return;
    }

    setSaving(true);
    setError('');
    try {
      await adminApi.removeCategoryImage(recordId, cateType);
      setHasImage(false);
      setImageCacheBust(Date.now());
      onSaved();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String(
              (err as { response?: { data?: { message?: string } } }).response?.data?.message ??
                'Unable to delete image',
            )
          : 'Unable to delete image';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  const categoryImageUrl = `${legacyImageUrl(TYPE_CATEGORY_IMAGE, hasImage ? recordId : 0, 'LARGE')}?t=${imageCacheBust}`;

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_CATEGORY_SETUP', 'Category setup')}
      size="md"
      onClose={onClose}
    >
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul className="tabs-nav">
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={(e) => {
                  e.preventDefault();
                  onTabClick('general');
                }}
              >
                {lbl('LBL_GENERAL', 'General')}
              </a>
            </li>
            {siteLanguages.map((lang) => (
              <li key={lang.id} className={recordId < 1 ? 'is-inactive' : ''}>
                <a
                  href="javascript:void(0)"
                  className={activeTab === `lang-${lang.id}` ? 'active' : ''}
                  onClick={(e) => {
                    e.preventDefault();
                    onTabClick(`lang-${lang.id}`);
                  }}
                >
                  {lang.name}
                </a>
              </li>
            ))}
            <li className={`mediaTab${recordId < 1 ? ' is-inactive' : ''}`}>
              <a
                href="javascript:void(0)"
                className={activeTab === 'media' ? 'active' : ''}
                onClick={(e) => {
                  e.preventDefault();
                  onTabClick('media');
                }}
              >
                {lbl('LBL_MEDIA', 'Media')}
              </a>
            </li>
          </ul>
        </nav>
      </div>
      <div className="form-edit-body">
        {error ? <div className="alert alert-danger">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : null}

        {!loading && activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={onSaveGeneral}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_IDENTIFIER', 'Identifier')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <input
                      className="form-control"
                      value={identifier}
                      onChange={(e) => setIdentifier(e.target.value)}
                      required
                    />
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_PARENT', 'Parent')}</label>
                  </div>
                  <div className="field-wraper">
                    <select
                      className="form-control"
                      value={parentId}
                      onChange={(e) => {
                        setParentId(e.target.value);
                        setShowFeatured(cateType === 1 && Number(e.target.value) === 0);
                        if (Number(e.target.value) > 0) setFeatured(false);
                      }}
                    >
                      <option value="0">{lbl('LBL_ROOT_CATEGORY', 'Root category')}</option>
                      {parentCategories.map((parent) => (
                        <option key={parent.id} value={parent.id}>
                          {parent.name}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>
              {showFeatured ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="field-wraper">
                      <label className="checkbox d-flex">
                        <input
                          type="checkbox"
                          checked={featured}
                          onChange={(e) => setFeatured(e.target.checked)}
                        />
                        {lbl('LBL_FEATURED', 'Featured')}
                      </label>
                    </div>
                  </div>
                </div>
              ) : null}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_STATUS', 'Status')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <select className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                      <option value="1">{lbl('LBL_ACTIVE', 'Active')}</option>
                      <option value="0">{lbl('LBL_INACTIVE', 'Inactive')}</option>
                    </select>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="field-wraper form-buttons-group">
                    <button type="submit" className="btn btn-brand" disabled={saving}>
                      {saving
                        ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                        : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        ) : null}

        {!loading && activeTab.startsWith('lang-') ? (
          <form className="form form_horizontal" dir={layoutDirection} onSubmit={onSaveLang}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {isArabicLang ? 'اسم' : isSpanishLang ? 'Nombre' : lbl('LBL_NAME', 'Name')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <input
                      className="form-control"
                      value={langName}
                      onChange={(e) => setLangName(e.target.value)}
                      required
                    />
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {isArabicLang ? 'وصف' : isSpanishLang ? 'Descripción' : lbl('LBL_DESCRIPTION', 'Description')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <textarea
                      className="form-control"
                      rows={5}
                      value={langDetails}
                      onChange={(e) => setLangDetails(e.target.value)}
                      required
                    />
                  </div>
                </div>
              </div>
              {showAutoTranslateCheckbox ? (
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
              ) : null}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="field-wraper form-buttons-group">
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
                      <button type="submit" className="btn btn-brand" disabled={saving}>
                        {saving
                          ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                          : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </form>
        ) : null}

        {!loading && activeTab === 'media' ? (
          <div className="form form_horizontal">
            <input
              ref={fileInputRef}
              type="file"
              className="hide"
              accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
              onChange={onImageSelected}
            />
            <div className="row">
              <div className="col-md-6">
                <div className="form-group">
                  <h6>{lbl('LBL_IMAGE', 'Image')}</h6>
                  <span className="form-text text-muted">
                    <strong>{lbl('LBL_IMAGE_DISCLAIMER', 'Image disclaimer')} : </strong>
                    {lbl('LBL_Dimensions_%s', 'Dimensions %s').replace('%s', '100*100')}
                  </span>
                </div>
              </div>
              <div className="col-md-6">
                <div className="form-group">
                  <div className="dropzone mt-3 dropzoneContainerJs">
                    <div className="dropzone-uploaded dropzoneUploadedJs">
                      <img src={categoryImageUrl} alt="" title="" />
                      <div className="dropzone-uploaded-action">
                        <ul className="actions">
                          <li>
                            <a
                              href="javascript:void(0)"
                              className="categoryFile-Js"
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
        ) : null}
      </div>
    </AdminModal>
  );
}
