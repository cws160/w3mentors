import { type ChangeEvent, type FormEvent, type ReactNode, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type LanguageOption = { id: number; code: string; name: string };

type Props = {
  labelId: number;
  importOpen: boolean;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminLanguageLabelModal({ labelId, importOpen, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const open = labelId > 0 || importOpen;
  const [labelKey, setLabelKey] = useState('');
  const [languages, setLanguages] = useState<LanguageOption[]>([]);
  const [captions, setCaptions] = useState<Record<string, string>>({});
  const [importFile, setImportFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!open) {
      setLabelKey('');
      setLanguages([]);
      setCaptions({});
      setImportFile(null);
      setError('');
      return;
    }
    if (importOpen) {
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .languageLabelShow(labelId)
      .then((res) => {
        const data = (res.data.data ?? {}) as Record<string, unknown>;
        setLabelKey(String(data.label_key ?? ''));
        setLanguages((data.site_languages as LanguageOption[] | undefined) ?? []);
        const nextCaptions: Record<string, string> = {};
        const rawCaptions = (data.captions ?? {}) as Record<string, unknown>;
        Object.keys(rawCaptions).forEach((key) => {
          nextCaptions[key] = String(rawCaptions[key] ?? '');
        });
        setCaptions(nextCaptions);
      })
      .catch((err: unknown) => {
        setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_INVALID_REQUEST', 'Invalid request'));
      })
      .finally(() => setLoading(false));
  }, [importOpen, labelId, lbl, open]);

  const submitLabel = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      await adminApi.languageLabelUpdate(labelId, { captions });
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  const submitImport = async (event: FormEvent) => {
    event.preventDefault();
    if (!importFile) {
      setError(lbl('LBL_Please_Select_A_CSV_File', 'Please select a CSV file'));
      return;
    }
    setSaving(true);
    setError('');
    try {
      await adminApi.importLanguageLabels(importFile);
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal open={open} title={importOpen ? lbl('LBL_IMPORT_LABELS', 'Import Labels') : lbl('LBL_Manage_Labels', 'Manage Labels')} size="md" onClose={onClose}>
      <div className="form-edit-body">
        {error ? <div className="alert alert-danger m-3">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : importOpen ? (
          <form className="form form_horizontal" onSubmit={submitImport}>
            <div className="row">
              <LegacyField label={lbl('LBL_File_to_be_uploaded:', 'File to be uploaded:')} required>
                <input className="form-control" type="file" accept=".csv,text/csv" onChange={(event: ChangeEvent<HTMLInputElement>) => setImportFile(event.target.files?.[0] ?? null)} required />
                <small>{lbl('LBL_Import_Labels_Instructions', 'Upload a CSV file with Key and language code columns.')}</small>
              </LegacyField>
              <FormButton saving={saving} label={lbl('LBL_IMPORT', 'Import')} />
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" onSubmit={submitLabel}>
            <div className="row">
              <LegacyField label={lbl('LBL_Key', 'Key')}>
                <input className="form-control" value={labelKey} disabled readOnly />
              </LegacyField>
              {languages.map((language) => (
                <LegacyField key={language.id} label={language.name} required>
                  <textarea
                    className="form-control"
                    value={captions[String(language.id)] ?? ''}
                    onChange={(event) => setCaptions((prev) => ({ ...prev, [String(language.id)]: event.target.value }))}
                    required
                  />
                </LegacyField>
              ))}
              <FormButton saving={saving} label={lbl('LBL_SAVE_CHANGES', 'Save changes')} />
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
