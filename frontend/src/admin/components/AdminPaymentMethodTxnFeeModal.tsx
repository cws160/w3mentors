import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type FeeTypeOption = { value: number; label: string };

type Props = {
  open: boolean;
  methodId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminPaymentMethodTxnFeeModal({ open, methodId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [feeType, setFeeType] = useState(1);
  const [fee, setFee] = useState('');
  const [feeTypeOptions, setFeeTypeOptions] = useState<FeeTypeOption[]>([]);

  const reset = useCallback(() => {
    setError('');
    setFeeType(1);
    setFee('');
    setFeeTypeOptions([]);
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
      .paymentMethodTxnFeeForm(methodId)
      .then((res) => {
        const data = res.data.data ?? {};
        setFeeType(Number(data.type ?? 1));
        setFee(String(data.fee ?? 0));
        setFeeTypeOptions((data.fee_type_options as FeeTypeOption[]) ?? []);
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
      await adminApi.paymentMethodTxnFeeSetup({
        pmethod_id: methodId,
        type: feeType,
        fee: Number(fee),
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
    <AdminModal
      open={open}
      title={lbl('LBL_Method_Fee_Setups', 'Method fee setups')}
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
          <form className="form form_horizontal" id="gatewayFeeForm" onSubmit={onSubmit}>
              <div className="row">
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_FEE_TYPE', 'Fee type')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <select
                          className="form-control"
                          value={feeType}
                          onChange={(e) => setFeeType(Number(e.target.value))}
                          required
                        >
                          <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                          {feeTypeOptions.map((opt) => (
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
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_TXN_FEE', 'Txn fee')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          type="number"
                          min={0}
                          max={feeType === 1 ? 100 : undefined}
                          step="any"
                          value={fee}
                          onChange={(e) => setFee(e.target.value)}
                          required
                        />
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
