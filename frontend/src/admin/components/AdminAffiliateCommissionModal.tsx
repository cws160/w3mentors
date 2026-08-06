import { type FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type AffiliateSuggestion = {
  id: number;
  full_name: string;
  email: string;
};

type CommissionForm = {
  afcomm_id: number;
  afcomm_user_id: number;
  afcomm_commission: number | string;
  user_name: string;
  is_global: boolean;
};

type Props = {
  open: boolean;
  commissionId: number;
  onClose: () => void;
  onSaved: () => void;
};

function formatCommissionInput(value: number | string): string {
  const num = Number(value);
  if (Number.isNaN(num)) {
    return '';
  }
  if (Number.isInteger(num)) {
    return String(num);
  }
  return String(parseFloat(num.toFixed(2)));
}

export function AdminAffiliateCommissionModal({ open, commissionId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [recordId, setRecordId] = useState(0);
  const [userId, setUserId] = useState(0);
  const [userName, setUserName] = useState('');
  const [commission, setCommission] = useState('');
  const [isGlobal, setIsGlobal] = useState(false);
  const [userLocked, setUserLocked] = useState(false);
  const [suggestions, setSuggestions] = useState<AffiliateSuggestion[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const coverRef = useRef<HTMLDivElement>(null);

  const reset = useCallback(() => {
    setError('');
    setRecordId(0);
    setUserId(0);
    setUserName('');
    setCommission('');
    setIsGlobal(false);
    setUserLocked(false);
    setSuggestions([]);
    setShowSuggestions(false);
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .affiliateCommissionShow(commissionId)
      .then((res) => {
        const data = res.data.data as CommissionForm;
        const global = Boolean(data.is_global);
        setRecordId(data.afcomm_id ?? 0);
        setUserId(data.afcomm_user_id ?? 0);
        setUserName(
          global
            ? lbl('LBL_GLOBAL_COMMISSION', 'Global commission')
            : (data.user_name ?? ''),
        );
        setCommission(
          data.afcomm_commission !== '' && data.afcomm_commission !== undefined
            ? formatCommissionInput(data.afcomm_commission)
            : '',
        );
        setIsGlobal(global);
        setUserLocked((data.afcomm_id ?? 0) > 0);
      })
      .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
      .finally(() => setLoading(false));
  }, [commissionId, lbl, open, reset]);

  useEffect(() => {
    if (!open || userLocked) {
      return;
    }
    const keyword = userName.trim();
    if (keyword.length < 1) {
      setSuggestions([]);
      return;
    }
    const timer = window.setTimeout(() => {
      void adminApi.affiliateCommissionAutocomplete(keyword).then((res) => {
        setSuggestions((res.data.data ?? []) as AffiliateSuggestion[]);
        setShowSuggestions(true);
      });
    }, 250);
    return () => window.clearTimeout(timer);
  }, [open, userLocked, userName]);

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (coverRef.current && !coverRef.current.contains(e.target as Node)) {
        setShowSuggestions(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  const pickAffiliate = (item: AffiliateSuggestion) => {
    setUserId(item.id);
    setUserName(item.full_name);
    setShowSuggestions(false);
  };

  const onUserNameChange = (value: string) => {
    setUserName(value);
    setUserId(0);
  };

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    const rate = Number(commission);
    if (Number.isNaN(rate) || rate < 1 || rate > 100) {
      setError(lbl('LBL_INVALID_REQUEST', 'Commission must be between 1 and 100'));
      return;
    }
    if (recordId < 1 && userId < 1) {
      setError(lbl('LBL_INVALID_REQUEST', 'Please select an affiliate'));
      return;
    }

    setSaving(true);
    setError('');
    try {
      await adminApi.setupAffiliateCommission({
        afcomm_id: recordId,
        afcomm_user_id: isGlobal ? 0 : userId,
        afcomm_commission: rate,
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to save commission',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_AFFILIATE_COMMISSION_SETUP', 'Affiliate commission setup')}
      size="sm"
      onClose={onClose}
    >
      <div className="form-edit-body">
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          <form className="form form_horizontal" onSubmit={onSubmit}>
            {error ? <div className="alert alert-danger">{error}</div> : null}
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_USER_NAME', 'User name')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover" ref={coverRef}>
                      <input
                        className="form-control"
                        type="text"
                        name="user_name"
                        autoComplete="off"
                        value={userName}
                        disabled={userLocked}
                        onChange={(e) => onUserNameChange(e.target.value)}
                        onFocus={() => !userLocked && suggestions.length > 0 && setShowSuggestions(true)}
                      />
                      {!userLocked && showSuggestions && suggestions.length > 0 ? (
                        <ul
                          className="ui-menu ui-widget ui-widget-content ui-autocomplete custom-ui-autocomplete"
                          role="listbox"
                        >
                          {suggestions.map((item) => (
                            <li key={item.id} className="ui-menu-item">
                              <div
                                className="ui-menu-item-wrapper"
                                role="option"
                                tabIndex={-1}
                                onMouseDown={(ev) => {
                                  ev.preventDefault();
                                  pickAffiliate(item);
                                }}
                              >
                                {item.full_name} ({item.email})
                              </div>
                            </li>
                          ))}
                        </ul>
                      ) : null}
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_COMMISSION_[%]', 'Commission [%]')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        type="text"
                        name="afcomm_commission"
                        inputMode="decimal"
                        value={commission}
                        onChange={(e) => setCommission(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="form-actions">
              <button type="submit" name="btn_submit" className="btn btn-brand" disabled={saving}>
                {saving
                  ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                  : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}
