import { type FormEvent, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import { AdminTabbedModalLayout, type AdminTabbedModalTab } from './AdminTabbedModalLayout';

type LanguageOption = { id: number; name: string };
type TabKey = AdminTabbedModalTab;
type SelectOption = { value: string; label: string };
type StatusOption = { value: number; label: string };

type FormOptions = {
  currency_codes: Record<string, string>;
  positive_formats: SelectOption[];
  negative_formats: SelectOption[];
  decimal_symbols: Record<string, string>;
  grouping_symbols: Record<string, string>;
  status_options: StatusOption[];
};

type CurrencyRecord = {
  currency_id: number;
  currency_code: string;
  currency_symbol: string;
  currency_positive_format: string;
  currency_negative_format: string;
  currency_decimal_symbol: string;
  currency_grouping_symbol: string;
  currency_value: number | string;
  currency_active: number;
};

type Props = {
  open: boolean;
  currencyId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminCurrencyModal({ open, currencyId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [isDefault, setIsDefault] = useState(false);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [options, setOptions] = useState<FormOptions | null>(null);
  const [form, setForm] = useState<CurrencyRecord>({
    currency_id: 0,
    currency_code: '',
    currency_symbol: '',
    currency_positive_format: '{currency_symbol}{currency_number}',
    currency_negative_format: '-{currency_symbol}{currency_number}',
    currency_decimal_symbol: '.',
    currency_grouping_symbol: ',',
    currency_value: 1,
    currency_active: 1,
  });
  const [currencyName, setCurrencyName] = useState('');

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIsDefault(false);
    setOptions(null);
    setCurrencyName('');
    setForm({
      currency_id: 0,
      currency_code: '',
      currency_symbol: '',
      currency_positive_format: '{currency_symbol}{currency_number}',
      currency_negative_format: '-{currency_symbol}{currency_number}',
      currency_decimal_symbol: '.',
      currency_grouping_symbol: ',',
      currency_value: 1,
      currency_active: 1,
    });
  }, []);

  const loadForm = useCallback(
    (id: number) => {
      setLoading(true);
      setError('');
      void adminApi
        .currencyForm(id)
        .then((res) => {
          const data = res.data.data ?? {};
          const currency = data.currency as CurrencyRecord;
          setOptions(data.options as FormOptions);
          setSiteLanguages((data.site_languages as LanguageOption[]) ?? []);
          setIsDefault(Boolean(data.is_default));
          setRecordId(Number(currency.currency_id ?? 0));
          setForm({
            currency_id: Number(currency.currency_id ?? 0),
            currency_code: String(currency.currency_code ?? ''),
            currency_symbol: String(currency.currency_symbol ?? ''),
            currency_positive_format: String(currency.currency_positive_format ?? ''),
            currency_negative_format: String(currency.currency_negative_format ?? ''),
            currency_decimal_symbol: String(currency.currency_decimal_symbol ?? '.'),
            currency_grouping_symbol: String(currency.currency_grouping_symbol ?? ','),
            currency_value: currency.currency_value ?? 1,
            currency_active: Number(currency.currency_active ?? 1),
          });
        })
        .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
        .finally(() => setLoading(false));
    },
    [lbl],
  );

  const loadLangTab = useCallback(
    (id: number, langId: number) => {
      setLoading(true);
      setError('');
      void adminApi
        .currencyLangForm(id, langId)
        .then((res) => {
          const data = res.data.data ?? {};
          setCurrencyName(String(data.currency_name ?? ''));
          setSiteLanguages((data.site_languages as LanguageOption[]) ?? siteLanguages);
        })
        .catch((err: unknown) => {
          setError(
            (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
              'Failed to load currency language',
          );
        })
        .finally(() => setLoading(false));
    },
    [siteLanguages],
  );

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }
    loadForm(currencyId);
  }, [currencyId, loadForm, open, reset]);

  const onSelectLangTab = (langId: number) => {
    if (recordId < 1) {
      return;
    }
    setActiveTab(`lang-${langId}`);
    loadLangTab(recordId, langId);
  };

  const onSaveGeneral = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.currencySetup({ ...form, currency_id: recordId });
      const savedId = Number(res.data.data?.currency_id ?? recordId);
      setRecordId(savedId);
      setForm((prev) => ({ ...prev, currency_id: savedId }));
      onSaved();
      const firstLang = siteLanguages[0];
      if (firstLang) {
        setActiveTab(`lang-${firstLang.id}`);
        loadLangTab(savedId, firstLang.id);
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save currency',
      );
    } finally {
      setSaving(false);
    }
  };

  const onSaveLang = async (e: FormEvent) => {
    e.preventDefault();
    if (recordId < 1) {
      return;
    }
    const langId = Number(String(activeTab).replace('lang-', ''));
    setSaving(true);
    setError('');
    try {
      await adminApi.currencyLangSetup({
        currency_id: recordId,
        lang_id: langId,
        currency_name: currencyName.trim(),
      });
      onSaved();
      const currentIndex = siteLanguages.findIndex((lang) => lang.id === langId);
      const nextLang = siteLanguages[currentIndex + 1];
      if (nextLang) {
        setActiveTab(`lang-${nextLang.id}`);
        loadLangTab(recordId, nextLang.id);
        return;
      }
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save currency language',
      );
    } finally {
      setSaving(false);
    }
  };

  const setField = <K extends keyof CurrencyRecord>(key: K, value: CurrencyRecord[K]) => {
    setForm((prev) => ({ ...prev, [key]: value }));
  };

  const codeOptions = options ? Object.entries(options.currency_codes) : [];

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_Currency_Setup', 'Currency setup')}
      size="md"
      onClose={onClose}
    >
      <AdminTabbedModalLayout
        activeTab={activeTab}
        recordId={recordId}
        siteLanguages={siteLanguages}
        loading={loading}
        error={error}
        lbl={lbl}
        onSelectGeneral={() => {
          setActiveTab('general');
          loadForm(recordId);
        }}
        onSelectLang={onSelectLangTab}
      >
        {activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={onSaveGeneral}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Currency_code', 'Currency code')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.currency_code}
                        disabled={isDefault}
                        onChange={(e) => setField('currency_code', e.target.value)}
                        required
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {codeOptions.map(([value, label]) => (
                          <option key={value} value={value}>
                            {label}
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
                      {lbl('LBL_CURRENCY_SYMBOL', 'Currency symbol')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={form.currency_symbol}
                        onChange={(e) => setField('currency_symbol', e.target.value)}
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
                      {lbl('LBL_POSTIVE_FORMAT', 'Positive format')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.currency_positive_format}
                        onChange={(e) => setField('currency_positive_format', e.target.value)}
                        required
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {(options?.positive_formats ?? []).map((opt) => (
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
                      {lbl('LBL_NEGATIVE_FORMAT', 'Negative format')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.currency_negative_format}
                        onChange={(e) => setField('currency_negative_format', e.target.value)}
                        required
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {(options?.negative_formats ?? []).map((opt) => (
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
                      {lbl('LBL_DECIMAL_SYMBOL', 'Decimal symbol')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.currency_decimal_symbol}
                        onChange={(e) => setField('currency_decimal_symbol', e.target.value)}
                        required
                      >
                        {Object.entries(options?.decimal_symbols ?? { '.': '.' }).map(([value, label]) => (
                          <option key={value} value={value}>
                            {label}
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
                      {lbl('LBL_GROUPING_SYMBOL', 'Grouping symbol')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.currency_grouping_symbol}
                        onChange={(e) => setField('currency_grouping_symbol', e.target.value)}
                        required
                      >
                        {Object.entries(options?.grouping_symbols ?? { ',': ',' }).map(([value, label]) => (
                          <option key={value} value={value}>
                            {label}
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
                      {lbl('LBL_CURRENCY_CONVERSION_VALUE', 'Currency conversion value')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        type="number"
                        step="any"
                        value={form.currency_value}
                        disabled={isDefault}
                        onChange={(e) => setField('currency_value', e.target.value)}
                        required
                      />
                      {isDefault ? (
                        <small>{lbl('LBL_THIS_IS_YOUR_DEFAULT_CURRENCY', 'This is your default currency')}</small>
                      ) : null}
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_STATUS', 'Status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.currency_active}
                        disabled={isDefault}
                        onChange={(e) => setField('currency_active', Number(e.target.value))}
                      >
                        {(options?.status_options ?? []).map((opt) => (
                          <option key={opt.value} value={opt.value}>
                            {opt.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" onSubmit={onSaveLang}>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Currency_Name', 'Currency name')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        value={currencyName}
                        onChange={(e) => setCurrencyName(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        )}
      </AdminTabbedModalLayout>
    </AdminModal>
  );
}
