import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import type { ProfileLanguageForm, ProfileLanguageResponse } from './teacherProfileTypes';

type Props = {
  langId: number;
  onNextLang?: () => void;
  onGoToTeachLang?: () => void;
};

/** Legacy: dashboard/views/account/user-lang-form.php */
export function TeacherProfileLangSection({ langId, onNextLang, onGoToTeachLang }: Props) {
  const { lbl } = useSite();
  const [form, setForm] = useState<ProfileLanguageForm | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await api.get<ProfileLanguageResponse>(
        `/users/me/profile/languages/${langId}`
      );
      setForm(res.data.data);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')
      );
    } finally {
      setLoading(false);
    }
  }, [langId, lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const save = async (goNext: boolean, goTeachLang: boolean) => {
    if (!form) return false;
    setSaving(true);
    setError('');
    try {
      await api.put(`/users/me/profile/languages/${langId}`, {
        biography: form.values.biography,
      });
      if (goTeachLang && onGoToTeachLang) {
        onGoToTeachLang();
      } else if (goNext && onNextLang) {
        onNextLang();
      } else {
        await load();
      }
      return true;
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')
      );
      return false;
    } finally {
      setSaving(false);
    }
  };

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await save(false, false);
  };

  if (loading || !form) {
    return (
      <div className="padding-6">
        <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
      </div>
    );
  }

  const direction = form.meta.direction === 'rtl' ? 'rtl' : 'ltr';

  return (
    <div className="padding-6">
      <div className="max-width-80">
        <form
          id="profileLangInfoFrm"
          className={`form form--${direction}`}
          onSubmit={onSubmit}
          autoComplete="off"
        >
          <input type="hidden" name="userlang_lang_id" value={form.values.lang_id} />
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">
                    {lbl('LBL_BIOGRAPHY', 'Biography')}
                    <span className="spn_must_field">*</span>
                  </label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <textarea
                      name="user_biography"
                      className="form-control"
                      rows={8}
                      value={form.values.biography}
                      onChange={(e) =>
                        setForm((prev) =>
                          prev
                            ? {
                                ...prev,
                                values: { ...prev.values, biography: e.target.value },
                              }
                            : prev
                        )
                      }
                      required
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
          {error ? <p className="color-danger mb-3">{error}</p> : null}
          <div className="row submit-row submit-row-lang">
            <div className="col-sm-12">
              <div className="field-set">
                <div className="field-wraper">
                  <div className="field_cover">
                    <input
                      type="submit"
                      className="btn btn--primary"
                      value={saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
                      disabled={saving}
                    />
                    {(form.meta.is_teacher || !form.meta.is_last_language) && (
                      <input
                        type="button"
                        className="btn btn--secondary ms-2"
                        value={lbl('LBL_NEXT', 'Next')}
                        disabled={saving}
                        onClick={async () => {
                          if (form.meta.is_last_language) {
                            await save(false, true);
                          } else {
                            await save(true, false);
                          }
                        }}
                      />
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}
