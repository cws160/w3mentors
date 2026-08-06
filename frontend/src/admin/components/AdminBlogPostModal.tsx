import { type FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type LanguageOption = { id: number; name: string };
type CategoryOption = { id: number; name: string };
type ImageRow = {
  file_id: number;
  file_name: string;
  file_lang_id: number;
  language_label: string;
  image_url: string;
};
type TabKey = 'general' | `lang-${number}` | 'images';

type Props = {
  open: boolean;
  postId: number;
  onClose: () => void;
  onSaved: () => void;
};

function slugify(value: string) {
  return value
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}

export function AdminBlogPostModal({ open, postId, onClose, onSaved }: Props) {
  const { lbl, langId } = useSite();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [frontendBaseUrl, setFrontendBaseUrl] = useState('');
  const [identifier, setIdentifier] = useState('');
  const [seoUrl, setSeoUrl] = useState('');
  const [published, setPublished] = useState('0');
  const [commentOpened, setCommentOpened] = useState(false);
  const [selectedCategories, setSelectedCategories] = useState<number[]>([]);
  const [title, setTitle] = useState('');
  const [authorName, setAuthorName] = useState('');
  const [description, setDescription] = useState('');
  const [layoutDirection, setLayoutDirection] = useState('ltr');
  const [imageLangId, setImageLangId] = useState('0');
  const [imageLanguageOptions, setImageLanguageOptions] = useState<LanguageOption[]>([]);
  const [images, setImages] = useState<ImageRow[]>([]);
  const [imageCacheBust, setImageCacheBust] = useState(0);

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIdentifier('');
    setSeoUrl('');
    setPublished('0');
    setCommentOpened(false);
    setSelectedCategories([]);
    setTitle('');
    setAuthorName('');
    setDescription('');
    setLayoutDirection('ltr');
    setImageLangId('0');
    setImageLanguageOptions([]);
    setImages([]);
    setImageCacheBust(0);
    setFrontendBaseUrl('');
  }, []);

  const loadImages = useCallback(
    (nextLangId = Number(imageLangId)) => {
      if (recordId < 1) return;
      setLoading(true);
      void adminApi
        .blogPostImages(recordId, nextLangId)
        .then((res) => {
          setImages(res.data.data ?? []);
          setImageCacheBust(Date.now());
        })
        .catch(() => setImages([]))
        .finally(() => setLoading(false));
    },
    [imageLangId, recordId],
  );

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .blogPostCreateForm(postId, langId)
      .then((res) => {
        const data = res.data.data ?? {};
        setSiteLanguages(data.site_languages ?? []);
        setCategories(data.categories ?? []);
        setFrontendBaseUrl(String(data.frontend_base_url ?? window.location.origin));
        if (postId < 1 && Array.isArray(data.selected_categories)) {
          setSelectedCategories(data.selected_categories as number[]);
        }
      })
      .finally(() => setLoading(false));

    if (postId > 0) {
      void adminApi.blogPostShow(postId).then((res) => {
        const data = res.data.data ?? {};
        setRecordId(postId);
        setIdentifier(String(data.post_identifier ?? ''));
        setSeoUrl(String(data.seourl_custom ?? ''));
        setPublished(String(data.post_published ?? 0));
        setCommentOpened(Number(data.post_comment_opened ?? 0) === 1);
        setSelectedCategories((data.categories as number[]) ?? []);
      });
    }
  }, [langId, open, postId, reset]);

  const loadLangTab = useCallback((nextLangId: number) => {
    if (recordId < 1) return;
    setLoading(true);
    void adminApi
      .blogPostLangForm(recordId, nextLangId)
      .then((res) => {
        const data = res.data.data ?? {};
        setTitle(String(data.post_title ?? ''));
        setAuthorName(String(data.post_author_name ?? ''));
        setDescription(String(data.post_description ?? ''));
        setLayoutDirection(String(data.layout_direction ?? 'ltr'));
      })
      .finally(() => setLoading(false));
  }, [recordId]);

  const loadImagesTab = useCallback(() => {
    if (recordId < 1) return;
    setLoading(true);
    void adminApi
      .blogPostImagesForm(recordId)
      .then((res) => {
        const data = res.data.data ?? {};
        const options = (data.language_options ?? []).map((option) => ({
          id: option.id,
          name:
            option.id === 0
              ? lbl('LBL_All_Languages', 'All Languages')
              : option.name,
        }));
        setImageLanguageOptions(options);
        setImageLangId('0');
        return adminApi.blogPostImages(recordId, 0);
      })
      .then((res) => {
        if (!res) return;
        setImages(res.data.data ?? []);
        setImageCacheBust(Date.now());
      })
      .catch(() => setImages([]))
      .finally(() => setLoading(false));
  }, [lbl, recordId]);

  const onSelectLangTab = (nextLangId: number) => {
    if (recordId < 1) return;
    setActiveTab(`lang-${nextLangId}`);
    loadLangTab(nextLangId);
  };

  const onSelectImagesTab = () => {
    if (recordId < 1) return;
    setActiveTab('images');
    loadImagesTab();
  };

  const toggleCategory = (categoryId: number) => {
    setSelectedCategories((current) =>
      current.includes(categoryId) ? current.filter((id) => id !== categoryId) : [...current, categoryId],
    );
  };

  const advanceAfterLangSave = (savedLangId: number) => {
    const currentIndex = siteLanguages.findIndex((lang) => lang.id === savedLangId);
    const nextLang = siteLanguages[currentIndex + 1];
    if (nextLang) {
      onSelectLangTab(nextLang.id);
      return;
    }
    onSelectImagesTab();
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload = {
        post_id: recordId,
        post_identifier: identifier.trim(),
        seourl_custom: seoUrl.trim(),
        post_published: Number(published),
        post_comment_opened: commentOpened ? 1 : 0,
        categories: selectedCategories,
        lang_id: langId,
      };
      const res =
        recordId > 0
          ? await adminApi.updateBlogPost(recordId, payload)
          : await adminApi.createBlogPost(payload);
      const savedId = Number(res.data.id ?? recordId);
      setRecordId(savedId);
      const firstLang = siteLanguages[0];
      if (firstLang) {
        setActiveTab(`lang-${firstLang.id}`);
        loadLangTab(firstLang.id);
      }
      onSaved();
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
    if (recordId < 1) return;
    const nextLangId = Number(String(activeTab).replace('lang-', ''));
    setSaving(true);
    setError('');
    try {
      await adminApi.storeBlogPostLang(recordId, nextLangId, {
        post_title: title.trim(),
        post_author_name: authorName.trim(),
        post_description: description.trim(),
      });
      onSaved();
      advanceAfterLangSave(nextLangId);
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
    fileInputRef.current?.click();
  };

  const onImageSelected = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file || recordId < 1) return;
    setSaving(true);
    setError('');
    try {
      await adminApi.uploadBlogPostImage(recordId, Number(imageLangId), file);
      loadImages(Number(imageLangId));
      onSaved();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to upload')
          : 'Unable to upload';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  const onDeleteImage = async (fileId: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    setSaving(true);
    try {
      await adminApi.deleteBlogPostImage(recordId, fileId);
      loadImages(Number(imageLangId));
      onSaved();
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Unable to delete')
          : 'Unable to delete';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  const seoPreview =
    seoUrl.trim() && frontendBaseUrl
      ? `${frontendBaseUrl.replace(/\/$/, '')}/${seoUrl.trim().replace(/^\//, '')}`
      : '';

  const bodyClass = activeTab === 'images' ? 'card-body' : 'form-edit-body';

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_BLOG_POST_SETUP', 'Blog post setup')}
      size="lg"
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
                  setActiveTab('general');
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
                    onSelectLangTab(lang.id);
                  }}
                >
                  {lang.name}
                </a>
              </li>
            ))}
            <li className={recordId < 1 ? 'is-inactive' : ''}>
              <a
                href="javascript:void(0)"
                className={activeTab === 'images' ? 'active' : ''}
                onClick={(e) => {
                  e.preventDefault();
                  onSelectImagesTab();
                }}
              >
                {lbl('LBL_Post_Images', 'Post images')}
              </a>
            </li>
          </ul>
        </nav>
      </div>
      <div className={bodyClass}>
        {error ? <div className="alert alert-danger">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={onSaveGeneral}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Post_Identifier', 'Post identifier')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={identifier}
                        onChange={(e) => {
                          const next = e.target.value;
                          setIdentifier(next);
                          setSeoUrl(slugify(next));
                        }}
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
                      {lbl('LBL_SEO_Friendly_URL', 'SEO-Friendly URL')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        id="seourl_custom"
                        className="form-control"
                        value={seoUrl}
                        onChange={(e) => setSeoUrl(e.target.value)}
                        required
                      />
                      {seoPreview ? (
                        <small className="text--small">{seoPreview}</small>
                      ) : null}
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Post_Status', 'Post status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={published} onChange={(e) => setPublished(e.target.value)}>
                        <option value="0">{lbl('LBL_IN_DRAFT', 'In draft')}</option>
                        <option value="1">{lbl('LBL_PUBLISHED', 'Published')}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_CATEGORIES', 'Categories')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <ul className="list">
                        {categories.map((category) => (
                          <li key={category.id}>
                            <label className="checkbox d-flex">
                              <input
                                type="checkbox"
                                name="categories[]"
                                value={category.id}
                                checked={selectedCategories.includes(category.id)}
                                onChange={() => toggleCategory(category.id)}
                              />
                              <span>{category.name}</span>
                            </label>
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <label className="checkbox d-flex">
                    <input
                      type="checkbox"
                      name="post_comment_opened"
                      checked={commentOpened}
                      onChange={(e) => setCommentOpened(e.target.checked)}
                    />
                    <span>{lbl('LBL_ALLOW_COMMENTS', 'Allow comments')}</span>
                  </label>
                </div>
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" name="btn_submit" className="btn btn-brand" disabled={saving}>
                {saving ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait') : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : activeTab === 'images' ? (
          <div className="form form_horizontal">
            <input
              ref={fileInputRef}
              type="file"
              className="hide"
              accept="image/png,image/jpeg,image/jpg"
              onChange={(e) => void onImageSelected(e)}
            />
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_LANGUAGE', 'Language')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control language-js"
                        value={imageLangId}
                        onChange={(e) => {
                          setImageLangId(e.target.value);
                          loadImages(Number(e.target.value));
                        }}
                      >
                        {(imageLanguageOptions.length > 0 ? imageLanguageOptions : [{ id: 0, name: lbl('LBL_All_Languages', 'All Languages') }]).map(
                          (option) => (
                            <option key={option.id} value={option.id}>
                              {option.name}
                            </option>
                          ),
                        )}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Photo(s)', 'Photo(s)')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <button
                        type="button"
                        className="btn btn--primary btn--sm blogFile-Js"
                        onClick={onPickImage}
                        disabled={saving}
                      >
                        {lbl('LBL_Upload_Image', 'Upload Image')}
                      </button>
                      <small className="text--small">
                        {lbl('LBL_Preferred_Dimensions_%s', 'Preferred Dimensions %s').replace('%s', '945*710')}
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div id="image-listing">
              {images.length > 0 ? (
                <div className="row g-4">
                  {images.map((image) => (
                    <div className="col-md-4" key={image.file_id} id={String(image.file_id)}>
                      <div className="logoWrap">
                        <div className="logothumb">
                          <img
                            src={`${image.image_url}?${imageCacheBust}`}
                            title={image.file_name}
                            alt={image.file_name}
                          />
                          <a
                            className="deleteLink white"
                            href="javascript:void(0)"
                            title={lbl('LBL_DELETE', 'Delete')}
                            onClick={(e) => {
                              e.preventDefault();
                              void onDeleteImage(image.file_id);
                            }}
                          >
                            <i className="ion-close-round icon" />
                          </a>
                        </div>
                        <small>
                          <strong>{lbl('LBL_LANGUAGE', 'Language')}: </strong>
                          {image.language_label}
                        </small>
                      </div>
                    </div>
                  ))}
                </div>
              ) : null}
            </div>
          </div>
        ) : (
          <form className={`form form_horizontal layout--${layoutDirection}`} dir={layoutDirection} onSubmit={onSaveLang}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_TITLE', 'Title')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input className="form-control" value={title} onChange={(e) => setTitle(e.target.value)} required />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_POST_AUTHOR_NAME', 'Post author name')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={authorName}
                        onChange={(e) => setAuthorName(e.target.value)}
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
                      {lbl('LBL_DESCRIPTION', 'Description')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        className="form-control"
                        name="post_description"
                        rows={10}
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" name="btn_submit" className="btn btn-brand" disabled={saving}>
                {saving ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait') : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}
