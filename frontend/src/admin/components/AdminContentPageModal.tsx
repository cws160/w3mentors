import { type ChangeEvent, type FormEvent, type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { legacyImageUrl } from '../utils/adminMedia';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  pageId: number;
  onClose: () => void;
  onSaved: () => void;
};

type LanguageOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;
const TYPE_CPAGE_BACKGROUND_IMAGE = 27;

export function AdminContentPageModal({ open, pageId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [layouts, setLayouts] = useState<Record<number, string>>({});
  const [identifier, setIdentifier] = useState('');
  const [layout, setLayout] = useState(1);
  const [langId, setLangId] = useState(0);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [imageTitle, setImageTitle] = useState('');
  const [blocks, setBlocks] = useState<Record<number, string>>({ 1: '', 2: '' });
  const [bgImage, setBgImage] = useState<Record<string, unknown> | null>(null);
  const [imageSaving, setImageSaving] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const activeLangIndex = useMemo(
    () => (langId > 0 ? siteLanguages.findIndex((lang) => lang.id === langId) : -1),
    [langId, siteLanguages],
  );
  const isLastLangTab = activeLangIndex >= 0 && activeLangIndex === siteLanguages.length - 1;

  const normalizeLayouts = (raw: Record<string, string> | undefined) => {
    const next: Record<number, string> = {};
    Object.entries(raw ?? {}).forEach(([key, value]) => {
      next[Number(key)] = String(value);
    });
    return next;
  };

  const applyLangData = (
    data: Record<string, unknown>,
    nextPageId: number,
    nextLangId: number,
    fallbackIdentifier = identifier,
    fallbackLayout = layout,
    fallbackLanguages = siteLanguages,
  ) => {
    setRecordId(Number(data.cpage_id ?? nextPageId));
    setLangId(Number(data.lang_id ?? nextLangId));
    setIdentifier(String(data.cpage_identifier ?? fallbackIdentifier));
    setLayout(Number(data.cpage_layout ?? fallbackLayout));
    setLayouts(normalizeLayouts(data.layouts as Record<string, string> | undefined));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? fallbackLanguages);
    setTitle(String(data.cpage_title ?? ''));
    setContent(String(data.cpage_content ?? ''));
    setImageTitle(String(data.cpage_image_title ?? ''));
    setBgImage((data.bg_image as Record<string, unknown> | null | undefined) ?? null);
    const rawBlocks = (data.blocks ?? {}) as Record<string, unknown>;
    setBlocks({
      1: String(rawBlocks[1] ?? rawBlocks['1'] ?? ''),
      2: String(rawBlocks[2] ?? rawBlocks['2'] ?? ''),
    });
    setActiveTab(`lang-${nextLangId}`);
  };

  const reset = useCallback(() => {
    setActiveTab('general');
    setRecordId(0);
    setSiteLanguages(contextLanguages);
    setLayouts({});
    setIdentifier('');
    setLayout(1);
    setLangId(0);
    setTitle('');
    setContent('');
    setImageTitle('');
    setBlocks({ 1: '', 2: '' });
    setBgImage(null);
    setImageSaving(false);
    setError('');
  }, [contextLanguages]);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void (async () => {
      try {
        const res = pageId > 0 ? await adminApi.contentPageShow(pageId) : await adminApi.contentPageCreateForm();
        const data = (res.data.data ?? {}) as Record<string, unknown>;
        const nextRecordId = Number(data.cpage_id ?? pageId);
        const nextIdentifier = String(data.cpage_identifier ?? '');
        const nextLayout = Number(data.cpage_layout ?? 1);
        const nextLanguages = (data.site_languages as LanguageOption[] | undefined) ?? contextLanguages;
        setRecordId(nextRecordId);
        setIdentifier(nextIdentifier);
        setLayout(nextLayout);
        setLayouts(normalizeLayouts(data.layouts as Record<string, string> | undefined));
        setSiteLanguages(nextLanguages);
        const firstLang = nextLanguages[0];
        if (pageId > 0 && nextRecordId > 0 && firstLang) {
          const langRes = await adminApi.contentPageLangForm(nextRecordId, firstLang.id);
          applyLangData(
            (langRes.data.data ?? {}) as Record<string, unknown>,
            nextRecordId,
            firstLang.id,
            nextIdentifier,
            nextLayout,
            nextLanguages,
          );
        }
      } catch (err: unknown) {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      } finally {
        setLoading(false);
      }
    })();
  }, [contextLanguages, lbl, open, pageId, reset]);

  const loadLang = useCallback(
    async (nextPageId: number, nextLangId: number) => {
      if (nextPageId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.contentPageLangForm(nextPageId, nextLangId);
        applyLangData((res.data.data ?? {}) as Record<string, unknown>, nextPageId, nextLangId);
      } catch (err: unknown) {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      } finally {
        setLoading(false);
      }
    },
    [identifier, layout, lbl, siteLanguages],
  );

  const submitGeneral = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload = { cpage_id: recordId, cpage_identifier: identifier.trim(), cpage_layout: layout };
      const res =
        recordId > 0
          ? await adminApi.contentPageUpdate(recordId, payload)
          : await adminApi.contentPageCreate(payload);
      const savedId = Number(res.data.data?.page_id ?? recordId);
      setRecordId(savedId);
      onSaved();
      const nextLangId = Number(res.data.data?.next_lang_id ?? siteLanguages[0]?.id ?? 0);
      if (nextLangId > 0) {
        await loadLang(savedId, nextLangId);
      } else {
        onClose();
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  const submitLang = async (event: FormEvent) => {
    event.preventDefault();
    if (recordId < 1 || langId < 1) return;
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.contentPageLangUpdate(recordId, langId, {
        cpage_title: title.trim(),
        cpage_image_title: imageTitle,
        cpage_content: content,
        blocks,
      });
      onSaved();
      const nextLang = siteLanguages[activeLangIndex + 1];
      const missingLangId = Number(res.data.data?.next_lang_id ?? 0);
      if (nextLang) {
        await loadLang(recordId, nextLang.id);
      } else if (missingLangId > 0) {
        await loadLang(recordId, missingLangId);
      } else {
        onClose();
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  const uploadBackgroundImage = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] ?? null;
    event.target.value = '';
    if (!file || recordId < 1 || langId < 1) return;

    const payload = new FormData();
    payload.append('file', file);
    setImageSaving(true);
    setError('');
    try {
      const res = await adminApi.contentPageBackgroundUpload(recordId, langId, payload);
      setBgImage(res.data.data?.bg_image ?? null);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setImageSaving(false);
    }
  };

  const removeBackgroundImage = async () => {
    if (recordId < 1 || langId < 1 || !window.confirm(lbl('LBL_DO_YOU_WANT_TO_DELETE_IMAGE', 'Do you want to delete image?'))) {
      return;
    }

    setImageSaving(true);
    setError('');
    try {
      await adminApi.contentPageBackgroundDelete(recordId, langId);
      setBgImage(null);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setImageSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_CONTENT_PAGE_SETUP', 'Content Page Setup')}
      size="lg"
      onClose={onClose}
    >
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul>
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={(event) => {
                  event.preventDefault();
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
                  onClick={(event) => {
                    event.preventDefault();
                    if (recordId > 0) {
                      void loadLang(recordId, lang.id);
                    }
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
        {error ? <div className="alert alert-danger m-3">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={submitGeneral}>
            <div className="row">
              <LegacyField label={lbl('LBL_Page_Identifier', 'Page Identifier')} required>
                <input
                  className="form-control"
                  value={identifier}
                  onChange={(event) => setIdentifier(event.target.value)}
                  required
                />
              </LegacyField>
              <LegacyField label={lbl('LBL_Layout_Type', 'Layout Type')} required>
                <select
                  className="form-control"
                  value={layout}
                  onChange={(event) => setLayout(Number(event.target.value))}
                  required
                >
                  {Object.entries(layouts).map(([value, label]) => (
                    <option key={value} value={value}>
                      {label}
                    </option>
                  ))}
                </select>
              </LegacyField>
              <FormButton saving={saving} label={lbl('LBL_SAVE_CHANGES', 'Save changes')} />
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" onSubmit={submitLang}>
            <div className="row">
              <LegacyField label={lbl('LBL_Page_Title', 'Page Title')} required>
                <input
                  className="form-control"
                  value={title}
                  onChange={(event) => setTitle(event.target.value)}
                  required
                />
              </LegacyField>
              {layout === 1 ? (
                <>
                  <LegacyField label={lbl('LBL_Backgroud_Image', 'Backgroud Image')}>
                    <label className="btn btn-primary btn-sm m-0" htmlFor="cpage_bg_image">
                      {imageSaving ? lbl('LBL_PROCESSING', 'Processing') : lbl('LBL_Upload_Image', 'Upload Image')}
                    </label>
                    <input
                      id="cpage_bg_image"
                      className="d-none"
                      type="file"
                      accept="image/png,image/jpeg,image/gif,image/webp"
                      onChange={uploadBackgroundImage}
                      disabled={imageSaving}
                    />
                    <small className="text--small d-block mt-2">
                      {lbl('LBL_This_will_be_displayed_on_your_cms_Page', 'This will be displayed on your CMS Page')}
                    </small>
                    {bgImage ? (
                      <div className="image-div-js">
                        <div className="image-listing row">
                          <div className="col-md-6">
                            <div className="uploaded--image">
                              <img
                                src={`${legacyImageUrl(TYPE_CPAGE_BACKGROUND_IMAGE, recordId, 'LARGE', langId)}?t=${String(bgImage.file_added ?? Date.now())}`}
                                className="bg-image-js"
                                alt=""
                              />
                              <a
                                href="javascript:void(0)"
                                className="remove--img"
                                onClick={(event) => {
                                  event.preventDefault();
                                  void removeBackgroundImage();
                                }}
                              >
                                <i className="ion-close-round" />
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    ) : null}
                  </LegacyField>
                  <LegacyField label={lbl('LBL_Background_Image_Title', 'Background Image Title')}>
                    <input
                      className="form-control"
                      value={imageTitle}
                      onChange={(event) => setImageTitle(event.target.value)}
                    />
                  </LegacyField>
                  <LegacyField label={lbl('LBL_Content_Block_1', 'Content Block 1')}>
                    <textarea
                      className="form-control"
                      rows={6}
                      value={blocks[1] ?? ''}
                      onChange={(event) => setBlocks((prev) => ({ ...prev, 1: event.target.value }))}
                    />
                  </LegacyField>
                  <LegacyField label={lbl('LBL_Content_Block_2', 'Content Block 2')}>
                    <textarea
                      className="form-control"
                      rows={6}
                      value={blocks[2] ?? ''}
                      onChange={(event) => setBlocks((prev) => ({ ...prev, 2: event.target.value }))}
                    />
                  </LegacyField>
                </>
              ) : (
                <LegacyField label={lbl('LBL_Page_Content', 'Page Content')}>
                  <textarea
                    className="form-control"
                    rows={5}
                    value={content}
                    onChange={(event) => setContent(event.target.value)}
                  />
                </LegacyField>
              )}
              <FormButton
                saving={saving}
                label={isLastLangTab ? lbl('LBL_SAVE_CHANGES', 'Save changes') : lbl('LBL_SAVE_&_NEXT', 'Save & next')}
              />
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}

function LegacyField({ label, required = false, children }: { label: string; required?: boolean; children: ReactNode }) {
  return (
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
  );
}

function FormButton({ saving, label }: { saving: boolean; label: string }) {
  return (
    <div className="col-md-12">
      <div className="field-set">
        <div className="field-wraper">
          <div className="field_cover">
            <button className="btn btn-primary" type="submit" disabled={saving}>
              {saving ? 'Processing please wait' : label}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
