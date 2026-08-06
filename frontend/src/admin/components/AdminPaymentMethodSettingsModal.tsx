import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import {
  AdminLegacySetupFormBody,
  AdminLegacySetupInfo,
  legacySetupFieldLabel,
  legacySetupFieldRequired,
  type LegacySetupField,
} from './adminLegacySetupForm';
import { AdminModal } from './AdminModal';

type SettingField = LegacySetupField & { key: string };

type Props = {
  open: boolean;
  methodId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminPaymentMethodSettingsModal({ open, methodId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [code, setCode] = useState('');
  const [info, setInfo] = useState('');
  const [fields, setFields] = useState<SettingField[]>([]);
  const [values, setValues] = useState<Record<string, string | number | boolean>>({});
  const [types, setTypes] = useState<Record<string, string>>({});

  const reset = useCallback(() => {
    setError('');
    setCode('');
    setInfo('');
    setFields([]);
    setValues({});
    setTypes({});
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }
    if (methodId < 1) {
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .paymentMethodSettingsForm(methodId)
      .then((res) => {
        const data = res.data.data ?? {};
        const nextFields = ((data.fields as SettingField[]) ?? []).map((field) => ({
          ...field,
          name: field.key,
        }));
        setCode(String(data.pmethod_code ?? ''));
        setInfo(String(data.pmethod_info ?? ''));
        setFields(nextFields);
        const nextValues: Record<string, string | number | boolean> = {};
        const nextTypes: Record<string, string> = {};
        nextFields.forEach((field) => {
          nextValues[field.key] = field.value ?? '';
          nextTypes[field.key] = field.type;
        });
        setValues(nextValues);
        setTypes(nextTypes);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [lbl, methodId, open, reset]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      await adminApi.paymentMethodSettingsSetup({
        pmethod_id: methodId,
        pmethod_settings: values,
        pmethod_type: types,
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

  const title = code
    ? `${lbl(`LBL_${code}`, code)} ${lbl('LBL_Settings', 'Settings')}`
    : lbl('LBL_Settings', 'Settings');

  return (
    <AdminModal open={open} title={title} size="md" onClose={onClose}>
      <AdminLegacySetupFormBody loading={loading}>
        <form className="form" onSubmit={onSubmit}>
          {error ? <div className="alert alert-danger">{error}</div> : null}
          <div className="row">
            {fields.map((field) => (
              <div key={field.key} className="col-md-12">
                <div className="field-set">
                  <label className="field_label">
                    {legacySetupFieldLabel(field, lbl)}
                    {legacySetupFieldRequired(field) ? <span className="spn_must_field">*</span> : null}
                  </label>
                  <div className="field_cover">
                    {field.type === 'textarea' ? (
                      <textarea
                        className="form-control"
                        value={String(values[field.key] ?? '')}
                        onChange={(e) =>
                          setValues((prev) => ({ ...prev, [field.key]: e.target.value }))
                        }
                        required
                      />
                    ) : field.type === 'checkbox' ? (
                      <label className="checkbox">
                        <input
                          type="checkbox"
                          checked={Boolean(values[field.key])}
                          onChange={(e) =>
                            setValues((prev) => ({ ...prev, [field.key]: e.target.checked ? 1 : 0 }))
                          }
                        />
                      </label>
                    ) : (
                      <input
                        className="form-control"
                        type="text"
                        value={String(values[field.key] ?? '')}
                        onChange={(e) =>
                          setValues((prev) => ({ ...prev, [field.key]: e.target.value }))
                        }
                        required
                      />
                    )}
                  </div>
                </div>
              </div>
            ))}
            <div className="col-md-12">
              <AdminLegacySetupInfo info={info} />
            </div>
            <div className="col-md-12">
              <button type="submit" className="btn btn-brand" disabled={saving || fields.length === 0}>
                {lbl('LBL_Save_Changes', 'Save changes')}
              </button>
            </div>
          </div>
        </form>
      </AdminLegacySetupFormBody>
    </AdminModal>
  );
}
