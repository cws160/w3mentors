import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import {
  AdminLegacySetupFormBody,
  AdminLegacySetupInfo,
  legacySetupFieldLabel,
  legacySetupFieldRequired,
  legacySetupSelectOptions,
  type LegacySetupField,
} from './adminLegacySetupForm';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  toolId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminMeetingToolModal({ open, toolId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [code, setCode] = useState('');
  const [info, setInfo] = useState('');
  const [fields, setFields] = useState<LegacySetupField[]>([]);
  const [values, setValues] = useState<Record<string, string | number | boolean>>({});

  const reset = useCallback(() => {
    setError('');
    setCode('');
    setInfo('');
    setFields([]);
    setValues({});
  }, []);

  const modalTitle = lbl('LBL_MEETING_TOOL_SETUP', 'Meeting tool setup');

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }
    if (toolId < 1) {
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .meetingToolForm(toolId)
      .then((res) => {
        const data = res.data.data ?? {};
        const nextFields = (data.fields as LegacySetupField[]) ?? [];
        setCode(String(data.metool_code ?? ''));
        setInfo(String(data.metool_info ?? ''));
        setFields(nextFields);
        const nextValues: Record<string, string | number | boolean> = {};
        nextFields.forEach((field) => {
          if (field.type === 'hidden') {
            return;
          }
          nextValues[field.name] = field.value ?? '';
        });
        setValues(nextValues);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [lbl, open, reset, toolId]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const payload: Record<string, string | number | boolean> = { ...values };
      fields.forEach((field) => {
        if (field.type === 'hidden') {
          payload[field.name] = field.value ?? '';
        }
      });
      await adminApi.meetingToolSetup({
        metool_id: toolId,
        metool_settings: payload,
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal open={open} title={modalTitle} size="md" onClose={onClose}>
      <AdminLegacySetupFormBody loading={loading}>
        <form className="form" onSubmit={onSubmit}>
          {error ? <div className="alert alert-danger">{error}</div> : null}
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <label className="field_label">
                  {lbl('LBL_Code', 'Code')}
                  <span className="spn_must_field">*</span>
                </label>
                <div className="field_cover">
                  <input className="form-control" type="text" value={code} readOnly disabled />
                </div>
              </div>
            </div>
            {fields
              .filter((field) => field.type !== 'hidden')
              .map((field) => (
                <div key={field.name} className="col-md-12">
                  <div className="field-set">
                    <label className="field_label">
                      {legacySetupFieldLabel(field, lbl)}
                      {legacySetupFieldRequired(field) ? <span className="spn_must_field">*</span> : null}
                    </label>
                    <div className="field_cover">
                      {field.type === 'textarea' ? (
                        <textarea
                          className="form-control"
                          placeholder={field.placeholder}
                          value={String(values[field.name] ?? '')}
                          onChange={(e) =>
                            setValues((prev) => ({ ...prev, [field.name]: e.target.value }))
                          }
                          required
                        />
                      ) : field.type === 'select' ? (
                        <select
                          className="form-control"
                          value={String(values[field.name] ?? '')}
                          onChange={(e) =>
                            setValues((prev) => ({ ...prev, [field.name]: e.target.value }))
                          }
                        >
                          {legacySetupSelectOptions(field).map((opt) => (
                            <option key={opt.value} value={opt.value}>
                              {opt.label}
                            </option>
                          ))}
                        </select>
                      ) : field.type === 'checkbox' ? (
                        <label className="checkbox">
                          <input
                            type="checkbox"
                            checked={Boolean(values[field.name])}
                            onChange={(e) =>
                              setValues((prev) => ({
                                ...prev,
                                [field.name]: e.target.checked ? 1 : 0,
                              }))
                            }
                          />
                        </label>
                      ) : (
                        <input
                          className="form-control"
                          type="text"
                          placeholder={field.placeholder}
                          value={String(values[field.name] ?? '')}
                          onChange={(e) =>
                            setValues((prev) => ({ ...prev, [field.name]: e.target.value }))
                          }
                          required
                        />
                      )}
                      {field.helptext ? <small>{field.helptext}</small> : null}
                    </div>
                  </div>
                </div>
              ))}
            <div className="col-md-12">
              <AdminLegacySetupInfo info={info} />
            </div>
            <div className="col-md-12">
              <button type="submit" className="btn btn-brand" disabled={saving || fields.length === 0}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </div>
        </form>
      </AdminLegacySetupFormBody>
    </AdminModal>
  );
}
