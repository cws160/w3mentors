import { type FormEvent, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

const ALLOWED_TAG_SPECIAL_CHARS = '.+#-';

type LanguageOption = { id: number; name: string };

type TagDetail = {
  ftag_id: number;
  ftag_name: string;
  ftag_language_id: number;
  ftag_active: number;
};

type Props = {
  open: boolean;
  tagId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminForumTagModal({ open, tagId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [languages, setLanguages] = useState<LanguageOption[]>([]);
  const [name, setName] = useState('');
  const [languageId, setLanguageId] = useState('');

  const tagNameHint = lbl(
    'LBL_DO_NOT_INCLUDE_SPECIAL_SYMBOLS_EXCEPT_{ALLOWED-SPECIAL-CHARS}',
    'Do not include special symbols except {allowed-special-chars}',
  ).replace('{allowed-special-chars}', ALLOWED_TAG_SPECIAL_CHARS);

  useEffect(() => {
    if (!open) {
      setError('');
      setName('');
      setLanguageId('');
      return;
    }

    setLoading(true);
    void adminApi
      .courseLanguageCreateForm()
      .then((res) => setLanguages(res.data.data?.site_languages ?? []))
      .finally(() => setLoading(false));

    if (tagId > 0) {
      void adminApi.forumTagShow(tagId).then((res) => {
        const data = res.data.data as TagDetail;
        setName(data.ftag_name ?? '');
        setLanguageId(String(data.ftag_language_id ?? ''));
      });
    }
  }, [open, tagId]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    const payload = {
      ftag_name: name.trim(),
      ftag_language_id: Number(languageId),
      ftag_active: 1,
    };
    try {
      if (tagId > 0) {
        await adminApi.updateForumTag(tagId, payload);
      } else {
        await adminApi.createForumTag(payload);
      }
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save tag',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      onClose={onClose}
      title={lbl('LBL_Forum_Tag_Setup', 'Forum tag setup')}
      size="md"
    >
      {loading ? (
        <div className="table-processing loaderJs">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : (
        <div className="form-edit-body">
          {error ? <div className="alert alert-danger">{error}</div> : null}
          <form className="form form_horizontal" name="frmForumTags" onSubmit={onSubmit}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Tag_Name', 'Tag name')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        type="text"
                        name="ftag_name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        required
                        minLength={2}
                        maxLength={50}
                      />
                      <small className="text--small">{tagNameHint}</small>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Language', 'Language')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        name="ftag_language_id"
                        value={languageId}
                        onChange={(e) => setLanguageId(e.target.value)}
                        required
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {languages.map((lang) => (
                          <option key={lang.id} value={lang.id}>
                            {lang.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" name="btn_submit" className="btn btn-primary" disabled={saving}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        </div>
      )}
    </AdminModal>
  );
}
