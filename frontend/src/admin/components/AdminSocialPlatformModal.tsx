import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type PlatformRecord = {
  splatform_id: number;
  splatform_identifier: string;
  splatform_url: string;
  splatform_active: number;
};

type SelectOption = { value: number; label: string };

type Props = {
  open: boolean;
  platformId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminSocialPlatformModal({ open, platformId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [statusOptions, setStatusOptions] = useState<SelectOption[]>([]);
  const [form, setForm] = useState<PlatformRecord>({
    splatform_id: 0,
    splatform_identifier: '',
    splatform_url: '',
    splatform_active: 1,
  });

  const reset = useCallback(() => {
    setError('');
    setStatusOptions([]);
    setForm({
      splatform_id: 0,
      splatform_identifier: '',
      splatform_url: '',
      splatform_active: 1,
    });
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }
    if (platformId < 1) {
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .socialPlatformForm(platformId)
      .then((res) => {
        const data = res.data.data ?? {};
        const platform = data.platform as PlatformRecord;
        setForm({
          splatform_id: Number(platform.splatform_id ?? 0),
          splatform_identifier: String(platform.splatform_identifier ?? ''),
          splatform_url: String(platform.splatform_url ?? ''),
          splatform_active: Number(platform.splatform_active ?? 1),
        });
        setStatusOptions((data.status_options as SelectOption[]) ?? []);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      })
      .finally(() => setLoading(false));
  }, [lbl, open, platformId, reset]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      await adminApi.socialPlatformSetup(form);
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
    <AdminModal
      open={open}
      title={lbl('LBL_Social_Platform_Setup', 'Social platform setup')}
      size="md"
      onClose={onClose}
    >
      <div className="form-edit-body">
        {error ? <div className="alert alert-danger m-3">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          <form className="form form_horizontal" onSubmit={onSubmit}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Identifier', 'Identifier')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        type="text"
                        value={form.splatform_identifier}
                        readOnly
                        disabled
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Link', 'Link')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        type="text"
                        value={form.splatform_url}
                        onChange={(e) => setForm((prev) => ({ ...prev, splatform_url: e.target.value }))}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Status', 'Status')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.splatform_active}
                        onChange={(e) =>
                          setForm((prev) => ({ ...prev, splatform_active: Number(e.target.value) }))
                        }
                        required
                      >
                        {statusOptions.map((opt) => (
                          <option key={opt.value} value={opt.value}>
                            {opt.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="field-wraper">
                    <div className="field_cover">
                      <button type="submit" className="btn btn-brand" disabled={saving}>
                        {lbl('LBL_Save_Changes', 'Save changes')}
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
