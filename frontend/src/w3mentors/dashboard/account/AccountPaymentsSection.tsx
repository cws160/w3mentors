import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

type PaymentMeta = {
  enabled: boolean;
  has_bank: boolean;
  has_paypal: boolean;
  default_tab: 'bank' | 'paypal';
  bank: {
    bank_name: string;
    account_holder_name: string;
    account_number: string;
    ifsc_swift_code: string;
    bank_address: string;
  };
  paypal_email: string;
};

type PayoutTab = 'bank' | 'paypal';

/** Legacy: dashboard/views/account/bank-info-form.php & paypal-email-address-form.php */
export function AccountPaymentsSection() {
  const { lbl } = useSite();
  const [meta, setMeta] = useState<PaymentMeta | null>(null);
  const [tab, setTab] = useState<PayoutTab>('bank');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const [bankName, setBankName] = useState('');
  const [accountHolder, setAccountHolder] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [ifscCode, setIfscCode] = useState('');
  const [bankAddress, setBankAddress] = useState('');
  const [paypalEmail, setPaypalEmail] = useState('');

  const applyMeta = (data: PaymentMeta) => {
    setMeta({ ...data, enabled: data.enabled ?? true });
    setTab(data.default_tab);
    setBankName(data.bank.bank_name);
    setAccountHolder(data.bank.account_holder_name);
    setAccountNumber(data.bank.account_number);
    setIfscCode(data.bank.ifsc_swift_code);
    setBankAddress(data.bank.bank_address);
    setPaypalEmail(data.paypal_email);
  };

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    api
      .get<{ data: PaymentMeta }>('/users/me/payments')
      .then((res) => applyMeta(res.data.data))
      .catch((err: { response?: { status?: number; data?: { message?: string } } }) => {
        setMeta(null);
        setError(
          err.response?.data?.message ??
            lbl('LBL_PAYMENT_METHOD_NOT_ACTIVE_YET', 'Payment method is not active yet.')
        );
      })
      .finally(() => setLoading(false));
  }, [lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const switchTab = (next: PayoutTab) => {
    setTab(next);
    setError('');
  };

  const setUpBankInfo = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await api.put<{ data: PaymentMeta }>('/users/me/payments/bank', {
        bank_name: bankName,
        account_holder_name: accountHolder,
        account_number: accountNumber,
        ifsc_swift_code: ifscCode,
        bank_address: bankAddress,
      });
      applyMeta(res.data.data);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')
      );
    } finally {
      setSaving(false);
    }
  };

  const setupPaypalInfo = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await api.put<{ data: PaymentMeta }>(
        '/users/me/payments/paypal',
        { paypal_email: paypalEmail }
      );
      applyMeta(res.data.data);
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')
      );
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <p className="padding-6 color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  if (!meta?.enabled) {
    return (
      <div className="content-panel__body">
        <p className="padding-6 color-secondary">{error}</p>
      </div>
    );
  }

  const isBank = tab === 'bank';
  const showBankTab = meta.has_bank;
  const showPaypalTab = meta.has_paypal;

  return (
    <>
      <div className="content-panel__head">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>
              {isBank
                ? lbl('LBL_Manage_Payments', 'Manage payments')
                : lbl('LBL_MANAGE_PAYMENTS', 'Manage payments')}
            </h5>
          </div>
          {isBank ? (
            <div>
              <p className="color-secondary mb-0">{lbl('LBL_MANAGE_PAYMENT_INFO_TEXT', '')}</p>
            </div>
          ) : (
            <div />
          )}
        </div>
      </div>
      <div className="content-panel__body">
        <div className="form">
          <form
            id="bankInfoFrm"
            className="form"
            onSubmit={isBank ? setUpBankInfo : setupPaypalInfo}
          >
            <div className="form__body p-0">
              <nav className="tabs tabs--line ps-4 pe-4">
                <ul>
                  {showBankTab && (
                    <li className={isBank ? 'is-active' : ''}>
                      <a
                        href="javascript:void(0);"
                        onClick={(e) => {
                          e.preventDefault();
                          switchTab('bank');
                        }}
                      >
                        {lbl('LBL_BANK_ACCOUNT', 'Bank account')}
                      </a>
                    </li>
                  )}
                  {showPaypalTab && (
                    <li className={!isBank ? 'is-active' : ''}>
                      <a
                        href="javascript:void(0);"
                        onClick={(e) => {
                          e.preventDefault();
                          switchTab('paypal');
                        }}
                      >
                        {lbl('LBL_PAYPAL_EMAIL', 'PayPal email')}
                      </a>
                    </li>
                  )}
                </ul>
              </nav>
              <div className="tabs-data">
                {isBank && showBankTab ? (
                  <div className="padding-6 pb-0" id="paymentInfoDiv">
                    <div className="row">
                      <div className="col-md-6">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('M_BANK_NAME', 'Bank name')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="text"
                                name="ub_bank_name"
                                className="form-control"
                                value={bankName}
                                onChange={(e) => setBankName(e.target.value)}
                                required
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('M_BENEFICIARY/ACCOUNT_HOLDER_NAME', 'Beneficiary/Account Holder Name')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="text"
                                name="ub_account_holder_name"
                                className="form-control"
                                value={accountHolder}
                                onChange={(e) => setAccountHolder(e.target.value)}
                                required
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="row">
                      <div className="col-md-6">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('M_BANK_ACCOUNT_NUMBER', 'Bank account number')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="text"
                                name="ub_account_number"
                                className="form-control"
                                value={accountNumber}
                                onChange={(e) => setAccountNumber(e.target.value)}
                                required
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('M_IFSC_CODE/SWIFT_CODE', 'IFSC Code/Swift Code')}
                              <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="text"
                                name="ub_ifsc_swift_code"
                                className="form-control"
                                value={ifscCode}
                                onChange={(e) => setIfscCode(e.target.value)}
                                required
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="row">
                      <div className="col-md-12">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">{lbl('M_BANK_ADDRESS', 'Bank address')}</label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <textarea
                                name="ub_bank_address"
                                className="form-control"
                                value={bankAddress}
                                onChange={(e) => setBankAddress(e.target.value)}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ) : (
                  showPaypalTab && (
                    <div className="padding-6 pb-0">
                      <div className="row">
                        <div className="col-md-8">
                          <div className="field-set">
                            <div className="caption-wraper">
                              <label className="field_label">
                                {lbl('M_PAYPAL_EMAIL_ADDRESS', 'PayPal email address')}
                                <span className="spn_must_field">*</span>
                              </label>
                            </div>
                            <div className="field-wraper">
                              <div className="field_cover">
                                <input
                                  type="email"
                                  name="ub_paypal_email_address"
                                  className="form-control"
                                  value={paypalEmail}
                                  onChange={(e) => setPaypalEmail(e.target.value)}
                                  required
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  )
                )}
              </div>
            </div>
            {error ? <p className="padding-6 pt-0 color-primary mb-0">{error}</p> : null}
            <div className="form__actions">
              <div className={isBank ? 'd-flex' : 'd-flex align-items-center gap-1'}>
                <input
                  type="submit"
                  name="btn_submit"
                  className="btn btn--primary"
                  value={saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
                  disabled={saving}
                />
              </div>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
