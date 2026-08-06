import { type ChangeEvent, type FormEvent, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { legacyImageUrl } from '../utils/adminMedia';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  slideId: number;
  onClose: () => void;
  onSaved: () => void;
};

type TabKey = 'general' | `lang-${number}`;
type SlideImageState = Record<number, File | null>;

export function AdminSlideModal({ open, slideId, onClose, onSaved }: Props) {
  const { lbl, languages } = useSite();
  const [identifier, setIdentifier] = useState('');
  const [active, setActive] = useState(1);
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [mediaLangId, setMediaLangId] = useState(0);
  const [displayTypes, setDisplayTypes] = useState<Record<number, string>>({});
  const [existingImages, setExistingImages] = useState<Record<number, Record<string, unknown>>>({});
  const [selectedImages, setSelectedImages] = useState<SlideImageState>({});
  const [previewUrls, setPreviewUrls] = useState<Record<number, string>>({});
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const activeLangIndex = useMemo(
    () => (mediaLangId > 0 ? languages.findIndex((lang) => lang.id === mediaLangId) : -1),
    [languages, mediaLangId],
  );
  const isLastLangTab = activeLangIndex >= 0 && activeLangIndex === languages.length - 1;

  useEffect(() => {
    if (!open) return;
    setIdentifier('');
    setActive(1);
    setActiveTab('general');
    setRecordId(slideId);
    setMediaLangId(0);
    setDisplayTypes({});
    setExistingImages({});
    setSelectedImages({});
    setPreviewUrls({});
    setError('');
    setLoading(true);
    void adminApi
      .slideForm(slideId)
      .then((res) => {
        const slide = (res.data.data?.slide ?? {}) as Record<string, unknown>;
        setIdentifier(String(slide.slide_identifier ?? ''));
        setActive(Number(slide.slide_active ?? 1));
        setRecordId(Number(slide.slide_id ?? slideId));
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [lbl, open, slideId]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.slideSetup({
        slide_id: slideId,
        slide_identifier: identifier,
        slide_active: active,
      });
      const savedId = Number(res.data.data?.slide_id ?? slideId);
      setRecordId(savedId);
      onSaved();
      const firstLang = languages[0];
      if (firstLang) {
        await loadMediaTab(savedId, firstLang.id);
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

  const loadMediaTab = async (nextRecordId: number, langId: number) => {
    if (nextRecordId < 1) return;
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.slideMediaForm(nextRecordId, langId);
      const data = res.data.data;
      const nextDisplayTypes: Record<number, string> = {};
      Object.entries(data.display_types ?? {}).forEach(([type, label]) => {
        nextDisplayTypes[Number(type)] = String(label);
      });
      const nextImages: Record<number, Record<string, unknown>> = {};
      Object.entries(data.images ?? {}).forEach(([type, image]) => {
        nextImages[Number(type)] = image;
      });
      setDisplayTypes(nextDisplayTypes);
      setExistingImages(nextImages);
      setSelectedImages({});
      setPreviewUrls({});
      setRecordId(Number(data.slide_id ?? nextRecordId));
      setMediaLangId(Number(data.lang_id ?? langId));
      setActiveTab(`lang-${langId}`);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_INVALID_REQUEST', 'Invalid request'),
      );
    } finally {
      setLoading(false);
    }
  };

  const onImageSelected = (type: number, event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] ?? null;
    setSelectedImages((prev) => ({ ...prev, [type]: file }));
    if (file) {
      setPreviewUrls((prev) => ({ ...prev, [type]: URL.createObjectURL(file) }));
    }
  };

  const submitMedia = async (event: FormEvent) => {
    event.preventDefault();
    if (recordId < 1 || mediaLangId < 1) return;

    const payload = new FormData();
    Object.entries(displayTypes).forEach(([typeKey]) => {
      const type = Number(typeKey);
      const file = selectedImages[type];
      if (file) {
        payload.append(`slide_image_${type}`, file);
      }
    });

    setSaving(true);
    setError('');
    try {
      await adminApi.slideMediaSetup(recordId, mediaLangId, payload);
      onSaved();
      const nextLang = languages[activeLangIndex + 1];
      if (nextLang) {
        await loadMediaTab(recordId, nextLang.id);
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

  const imageSrc = (type: number) => {
    if (previewUrls[type]) {
      return previewUrls[type];
    }
    return `${legacyImageUrl(type, recordId, 0, mediaLangId)}?t=${
      String(existingImages[type]?.file_added ?? '') || Date.now()
    }`;
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_BANNER_SETUP', 'Banner setup')}
      size="md"
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
                  setMediaLangId(0);
                }}
              >
                {lbl('LBL_GENERAL', 'General')}
              </a>
            </li>
            {languages.map((lang) => (
              <li key={lang.id} className={recordId < 1 ? 'is-inactive' : ''}>
                <a
                  href="javascript:void(0)"
                  className={activeTab === `lang-${lang.id}` ? 'active' : ''}
                  onClick={(event) => {
                    event.preventDefault();
                    if (recordId > 0) {
                      void loadMediaTab(recordId, lang.id);
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
          <form className="form form_horizontal" onSubmit={submit}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Banner_Identifier', 'Banner identifier')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={identifier}
                        onChange={(event) => setIdentifier(event.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Status', 'Status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={active}
                        onChange={(event) => setActive(Number(event.target.value))}
                      >
                        <option value={1}>{lbl('LBL_ACTIVE', 'Active')}</option>
                        <option value={0}>{lbl('LBL_INACTIVE', 'Inactive')}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="field-wraper">
                    <div className="field_cover">
                      <button className="btn btn-primary" type="submit" disabled={saving}>
                        {saving
                          ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                          : lbl('LBL_Save_Changes', 'Save changes')}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" onSubmit={submitMedia}>
            <div className="row">
              {Object.entries(displayTypes).map(([typeKey, display]) => {
                const type = Number(typeKey);
                const isRequired = !existingImages[type];
                return (
                  <div className="col-md-12" key={type}>
                    <div className="row">
                      <div className="col-md-6">
                        <div className="form-group">
                          <h6>
                            {lbl('LBL_' + display.toUpperCase(), display)}
                            {isRequired ? <span className="spn_must_field">*</span> : null}
                          </h6>
                          <span className="form-text text-muted">
                            <strong>
                              {lbl('LBL_{device}_IMAGE', '{device} image').replace(
                                '{device}',
                                lbl('LBL_' + display.toUpperCase(), display),
                              )}
                            </strong>
                            <p>
                              {lbl('LBL_ALLOWED_FILE_EXTS_{ext}', 'Allowed file extensions: {ext}').replace(
                                '{ext}',
                                'png, jpg, jpeg, gif, webp',
                              )}
                            </p>
                          </span>
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="form-group">
                          <div className="dropzone mt-3 dropzoneContainerJs">
                            <div className="dropzone-uploaded dropzoneUploadedJs">
                              <img src={imageSrc(type)} alt="" />
                            </div>
                          </div>
                          <input
                            className="form-control mt-3"
                            type="file"
                            accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                            required={isRequired}
                            onChange={(event) => onImageSelected(type, event)}
                          />
                        </div>
                      </div>
                      <div className="col-md-12">
                        <div className="form-group">
                          <div className="separator my-3" />
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="field-wraper">
                    <div className="field_cover">
                      <button className="btn btn-primary" type="submit" disabled={saving}>
                        {saving
                          ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                          : isLastLangTab
                            ? lbl('LBL_UPDATE', 'Update')
                            : lbl('LBL_SAVE_&_NEXT', 'Save & Next')}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}
