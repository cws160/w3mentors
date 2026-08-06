import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type LanguageOption = { id: number; name: string };
type ParentOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;

type Props = {
  open: boolean;
  categoryId: number;
  defaultParentId?: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminBlogPostCategoryModal({
  open,
  categoryId,
  defaultParentId = 0,
  onClose,
  onSaved,
}: Props) {
  const { lbl, langId } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [parentCategories, setParentCategories] = useState<ParentOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [parentId, setParentId] = useState('0');
  const [status, setStatus] = useState('1');
  const [langName, setLangName] = useState('');
  const [layoutDirection, setLayoutDirection] = useState('ltr');
  const [autoTranslate, setAutoTranslate] = useState(false);

  const activeLangId = activeTab.startsWith('lang-') ? Number(activeTab.replace('lang-', '')) : 0;
  const activeLanguage = siteLanguages.find((lang) => lang.id === activeLangId);
  const isEnglishLang = activeLanguage?.name.toLowerCase() === 'english';
  const isDefaultLang = activeLangId > 0 && activeLangId === siteLanguages[0]?.id;
  const showAutoTranslate = siteLanguages.length > 1 && (isDefaultLang || isEnglishLang);

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIdentifier('');
    setParentId(String(defaultParentId));
    setStatus('1');
    setLangName('');
    setLayoutDirection('ltr');
    setAutoTranslate(false);
  }, [defaultParentId]);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .blogPostCategoryCreateForm(defaultParentId, categoryId, langId)
      .then((res) => {
        const data = res.data.data ?? {};
        setSiteLanguages(data.site_languages ?? []);
        setParentCategories(data.parent_categories ?? []);
        setParentId(String(data.default_parent_id ?? defaultParentId));
      })
      .finally(() => setLoading(false));

    if (categoryId > 0) {
      void adminApi.blogPostCategoryShow(categoryId).then((res) => {
        const data = res.data.data ?? {};
        setRecordId(categoryId);
        setIdentifier(String(data.bpcategory_identifier ?? ''));
        setParentId(String(data.bpcategory_parent ?? defaultParentId));
        setStatus(String(data.bpcategory_active ?? 1));
      });
    }
  }, [categoryId, defaultParentId, langId, open, reset]);

  const loadLangTab = useCallback((nextLangId: number) => {
    if (recordId < 1) return;
    setLoading(true);
    void adminApi
      .blogPostCategoryLangForm(recordId, nextLangId)
      .then((res) => {
        const data = res.data.data ?? {};
        setLangName(String(data.bpcategory_name ?? ''));
        setLayoutDirection(String(data.layout_direction ?? 'ltr'));
        setAutoTranslate(false);
      })
      .finally(() => setLoading(false));
  }, [recordId]);

  const onSelectLangTab = (nextLangId: number) => {
    if (recordId < 1) return;
    setActiveTab(`lang-${nextLangId}`);
    setAutoTranslate(false);
    loadLangTab(nextLangId);
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload = {
        bpcategory_id: recordId,
        bpcategory_identifier: identifier.trim(),
        bpcategory_parent: Number(parentId),
        bpcategory_active: Number(status),
      };
      const res =
        recordId > 0
          ? await adminApi.updateBlogPostCategory(recordId, payload)
          : await adminApi.createBlogPostCategory(payload);
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
      await adminApi.storeBlogPostCategoryLang(recordId, nextLangId, {
        bpcategory_name: langName.trim(),
        update_langs_data: showAutoTranslate && autoTranslate ? 1 : 0,
      });
      onSaved();
      onClose();
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

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_BLOG_POST_CATEGORY_SETUP', 'Blog post category setup')}
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
          </ul>
        </nav>
      </div>
      <div className="form-edit-body">
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
                    <label className="field_label">{lbl('LBL_CATEGORY_IDENTIFIER', 'Category identifier')}</label>
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
                    <label className="field_label">{lbl('LBL_CATEGORY_PARENT', 'Category parent')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={parentId} onChange={(e) => setParentId(e.target.value)}>
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
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_CATEGORY_STATUS', 'Category status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
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
                {saving ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait') : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" dir={layoutDirection} onSubmit={onSaveLang}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_CATEGORY_NAME', 'Category name')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={langName}
                        onChange={(e) => setLangName(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              {showAutoTranslate ? (
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
                            {lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait') : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}
