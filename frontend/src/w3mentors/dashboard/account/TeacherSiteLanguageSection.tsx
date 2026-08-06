import { useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

export function TeacherSiteLanguageSection() {
  const { lbl, languages, langId, setLangId } = useSite();
  const [selected, setSelected] = useState(langId);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage('');
    try {
      await api.patch('/users/me', { lang_id: selected });
      setLangId(selected);
      setMessage(lbl('LBL_SETUP_SUCCESSFUL', 'Saved successfully'));
    } catch {
      setMessage(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <div className="content-panel__head">
        <h5>{lbl('LBL_LANGUAGE', 'Language')}</h5>
      </div>
      <div className="content-panel__body">
        <form className="form padding-6" onSubmit={onSubmit}>
          <div className="field-set">
            <div className="caption-wraper">
              <label className="field_label">{lbl('LBL_DASHBOARD_LANGUAGE', 'Dashboard language')}</label>
            </div>
            <div className="field-wraper">
              <select
                className="form-control"
                value={selected}
                onChange={(e) => setSelected(Number(e.target.value))}
              >
                {languages.map((lang) => (
                  <option key={lang.id} value={lang.id}>
                    {lang.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
          {message && <p className="color-primary mt-3">{message}</p>}
          <button type="submit" className="btn btn--primary mt-3" disabled={saving}>
            {saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
          </button>
        </form>
      </div>
    </>
  );
}
