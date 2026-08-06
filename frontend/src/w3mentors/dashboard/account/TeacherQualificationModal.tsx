import { useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import type { ExperienceType, TeacherAccountProfile, TeacherQualification } from './teacherAccountTypes';

const SPRITE = '/dashboard/images/sprite.svg';

export type QualificationFormState = {
  experience_type: number;
  title: string;
  institute_name: string;
  institute_address: string;
  description: string;
  start_year: number | '';
  end_year: number;
};

export function emptyQualificationForm(): QualificationFormState {
  return {
    experience_type: 1,
    title: '',
    institute_name: '',
    institute_address: '',
    description: '',
    start_year: '',
    end_year: new Date().getFullYear(),
  };
}

export function qualificationToForm(q: TeacherQualification): QualificationFormState {
  return {
    experience_type: q.experience_type,
    title: q.title,
    institute_name: q.institute_name,
    institute_address: q.institute_address,
    description: q.description,
    start_year: q.start_year,
    end_year: q.end_year,
  };
}

function yearOptions(from: number, to: number): number[] {
  const years: number[] = [];
  for (let y = from; y >= to; y -= 1) {
    years.push(y);
  }
  return years;
}

function QualificationField({
  label,
  required,
  children,
}: {
  label: string;
  required?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div className="field-set">
      <div className="caption-wraper">
        <label className="field_label">
          {label}
          {required && <span className="spn_must_field">*</span>}
        </label>
      </div>
      <div className="field-wraper">
        <div className="field_cover">{children}</div>
      </div>
    </div>
  );
}

type ModalProps = {
  editingId: number | null;
  initialForm: QualificationFormState;
  experienceTypes: ExperienceType[];
  onClose: () => void;
  onSaved: (data: TeacherAccountProfile) => void;
};

export function TeacherQualificationModal({
  editingId,
  initialForm,
  experienceTypes,
  onClose,
  onSaved,
}: ModalProps) {
  const { lbl, langId } = useSite();
  const [form, setForm] = useState(initialForm);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const years = yearOptions(new Date().getFullYear(), 1970);
  const endYears = yearOptions(new Date().getFullYear() + 10, 1970);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (form.start_year === '') {
      setError(lbl('LBL_START_YEAR_IS_REQUIRED', 'Start year is required'));
      return;
    }
    setSaving(true);
    setError('');
    try {
      const payload = { ...form, start_year: Number(form.start_year) };
      const url = editingId
        ? `/account/teacher/qualifications/${editingId}`
        : '/account/teacher/qualifications';
      const method = editingId ? 'put' : 'post';
      const res = await api[method]<{ data: TeacherAccountProfile }>(url, payload, {
        params: { lang_id: langId },
      });
      onSaved(res.data.data);
      onClose();
    } catch {
      setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <div className="modal-header">
        <h5>{lbl('LBL_SETUP_RESUME', 'Setup resume')}</h5>
        <button type="button" className="btn-close w3mentorsmodalJs" aria-label="Close" onClick={onClose} />
      </div>
      <div className="modal-body">
        <form id="experienceFrm" className="form" onSubmit={onSubmit}>
          <div className="row">
            <div className="col-md-6">
              <QualificationField label={lbl('LBL_Experience_Type', 'Experience type')} required>
                <select
                  className="form-control"
                  value={form.experience_type}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, experience_type: Number(e.target.value) }))
                  }
                  required
                >
                  {experienceTypes.map((t) => (
                    <option key={t.id} value={t.id}>
                      {lbl(t.key, t.key.replace('LBL_', '').replace(/_/g, ' '))}
                    </option>
                  ))}
                </select>
              </QualificationField>
            </div>
            <div className="col-md-6">
              <QualificationField label={lbl('LBL_Title', 'Title')} required>
                <input
                  className="form-control"
                  placeholder={lbl('LBL_Eg:_B.A._English', 'Eg: B.A. English')}
                  value={form.title}
                  onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                  required
                />
              </QualificationField>
            </div>
          </div>
          <div className="row">
            <div className="col-md-6">
              <QualificationField label={lbl('LBL_Institution', 'Institution')} required>
                <input
                  className="form-control"
                  placeholder={lbl('LBL_Eg:_Oxford_University', 'Eg: Oxford University')}
                  value={form.institute_name}
                  onChange={(e) => setForm((f) => ({ ...f, institute_name: e.target.value }))}
                  required
                />
              </QualificationField>
            </div>
            <div className="col-md-6">
              <QualificationField label={lbl('LBL_Location', 'Location')} required>
                <input
                  className="form-control"
                  placeholder={lbl('LBL_Eg:_London', 'Eg: London')}
                  value={form.institute_address}
                  onChange={(e) => setForm((f) => ({ ...f, institute_address: e.target.value }))}
                  required
                />
              </QualificationField>
            </div>
          </div>
          <div className="row">
            <div className="col-md-12">
              <QualificationField label={lbl('LBL_Description', 'Description')}>
                <textarea
                  className="form-control"
                  rows={4}
                  placeholder={lbl(
                    'LBL_Eg._Focus_in_Humanist_Literature',
                    'Eg. Focus in Humanist Literature'
                  )}
                  value={form.description}
                  onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                />
              </QualificationField>
            </div>
          </div>
          <div className="row">
            <div className="col-md-6">
              <QualificationField label={lbl('LBL_Start_Year', 'Start year')} required>
                <select
                  className="form-control"
                  value={form.start_year}
                  onChange={(e) =>
                    setForm((f) => ({
                      ...f,
                      start_year: e.target.value === '' ? '' : Number(e.target.value),
                    }))
                  }
                  required
                >
                  <option value="">{lbl('LBL_Select', 'Select')}</option>
                  {years.map((y) => (
                    <option key={y} value={y}>
                      {y}
                    </option>
                  ))}
                </select>
              </QualificationField>
            </div>
            <div className="col-md-6">
              <QualificationField label={lbl('LBL_End_Year', 'End year')} required>
                <select
                  className="form-control"
                  value={form.end_year}
                  onChange={(e) => setForm((f) => ({ ...f, end_year: Number(e.target.value) }))}
                  required
                >
                  {endYears.map((y) => (
                    <option key={y} value={y}>
                      {y}
                    </option>
                  ))}
                </select>
              </QualificationField>
            </div>
          </div>
          {error && <p className="color-red mt-2">{error}</p>}
          <div className="row text-right">
            <div className="col-sm-12">
              <div className="field-set mb-0">
                <div className="field-wraper form-buttons-group">
                  <div className="field_cover">
                    <button type="submit" className="btn btn--primary" disabled={saving}>
                      {saving
                        ? lbl('LBL_SAVING', 'Saving...')
                        : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </>
  );
}

export function QualificationActionButton({
  title,
  icon,
  onClick,
}: {
  title: string;
  icon: 'edit' | 'trash';
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
      onClick={onClick}
      title={title}
    >
      <svg className="icon icon--issue icon--small">
        <use xlinkHref={`${SPRITE}#${icon}`} />
      </svg>
      <div className="tooltip tooltip--top bg-black">{title}</div>
    </button>
  );
}
