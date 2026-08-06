import { type FormEvent, type ReactNode, useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import { AdminTabbedModalLayout, type AdminTabbedModalTab } from './AdminTabbedModalLayout';

const DISCOUNT_PERCENTAGE = 1;
const DISCOUNT_FLAT = 2;

type LanguageOption = { id: number; name: string };
type SelectOption = { value: number; label: string };

type CouponRecord = {
  coupon_id: number;
  coupon_identifier: string;
  coupon_code: string;
  coupon_discount_type: number;
  coupon_discount_value: number | string;
  coupon_max_discount: number | string;
  coupon_min_order: number | string;
  coupon_max_uses: number;
  coupon_user_uses: number;
  coupon_start_date: string;
  coupon_end_date: string;
  coupon_active: number;
};

type Props = {
  open: boolean;
  couponId: number;
  onClose: () => void;
  onSaved: () => void;
};

function Field({
  label,
  required,
  children,
}: {
  label: string;
  required?: boolean;
  children: ReactNode;
}) {
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

export function AdminCouponModal({ open, couponId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<AdminTabbedModalTab>('general');
  const [recordId, setRecordId] = useState(0);
  const [isExpired, setIsExpired] = useState(false);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [discountTypes, setDiscountTypes] = useState<SelectOption[]>([]);
  const [statusOptions, setStatusOptions] = useState<SelectOption[]>([]);
  const [form, setForm] = useState<CouponRecord>({
    coupon_id: 0,
    coupon_identifier: '',
    coupon_code: '',
    coupon_discount_type: DISCOUNT_FLAT,
    coupon_discount_value: '',
    coupon_max_discount: '',
    coupon_min_order: '',
    coupon_max_uses: 1,
    coupon_user_uses: 1,
    coupon_start_date: '',
    coupon_end_date: '',
    coupon_active: 1,
  });
  const [couponTitle, setCouponTitle] = useState('');
  const [couponDescription, setCouponDescription] = useState('');

  const reset = useCallback(() => {
    setError('');
    setActiveTab('general');
    setRecordId(0);
    setIsExpired(false);
    setSiteLanguages([]);
    setDiscountTypes([]);
    setStatusOptions([]);
    setCouponTitle('');
    setCouponDescription('');
    setForm({
      coupon_id: 0,
      coupon_identifier: '',
      coupon_code: '',
      coupon_discount_type: DISCOUNT_FLAT,
      coupon_discount_value: '',
      coupon_max_discount: '',
      coupon_min_order: '',
      coupon_max_uses: 1,
      coupon_user_uses: 1,
      coupon_start_date: '',
      coupon_end_date: '',
      coupon_active: 1,
    });
  }, []);

  const loadForm = useCallback(
    (id: number) => {
      setLoading(true);
      setError('');
      void adminApi
        .couponForm(id)
        .then((res) => {
          const data = res.data.data ?? {};
          const coupon = data.coupon as CouponRecord;
          setRecordId(Number(coupon.coupon_id ?? 0));
          setIsExpired(Boolean(data.is_expired));
          setSiteLanguages((data.site_languages as LanguageOption[]) ?? []);
          const options = data.options as {
            discount_types?: SelectOption[];
            status_options?: SelectOption[];
          };
          setDiscountTypes(options?.discount_types ?? []);
          setStatusOptions(options?.status_options ?? []);
          setForm({
            coupon_id: Number(coupon.coupon_id ?? 0),
            coupon_identifier: String(coupon.coupon_identifier ?? ''),
            coupon_code: String(coupon.coupon_code ?? ''),
            coupon_discount_type: Number(coupon.coupon_discount_type ?? DISCOUNT_FLAT),
            coupon_discount_value: coupon.coupon_discount_value ?? '',
            coupon_max_discount: coupon.coupon_max_discount ?? '',
            coupon_min_order: coupon.coupon_min_order ?? '',
            coupon_max_uses: Number(coupon.coupon_max_uses ?? 1),
            coupon_user_uses: Number(coupon.coupon_user_uses ?? 1),
            coupon_start_date: String(coupon.coupon_start_date ?? ''),
            coupon_end_date: String(coupon.coupon_end_date ?? ''),
            coupon_active: Number(coupon.coupon_active ?? 1),
          });
        })
        .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
        .finally(() => setLoading(false));
    },
    [lbl],
  );

  const loadLangTab = useCallback((id: number, langId: number) => {
    setLoading(true);
    setError('');
    void adminApi
      .couponLangForm(id, langId)
      .then((res) => {
        const data = res.data.data ?? {};
        setCouponTitle(String(data.coupon_title ?? ''));
        setCouponDescription(String(data.coupon_description ?? ''));
        setSiteLanguages((data.site_languages as LanguageOption[]) ?? []);
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load coupon language',
        );
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }
    loadForm(couponId);
  }, [couponId, loadForm, open, reset]);

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
      const res = await adminApi.couponSetup({ ...form, coupon_id: recordId });
      const savedId = Number(res.data.data?.coupon_id ?? recordId);
      setRecordId(savedId);
      setForm((prev) => ({ ...prev, coupon_id: savedId }));
      onSaved();
      const firstLang = siteLanguages[0];
      if (firstLang) {
        setActiveTab(`lang-${firstLang.id}`);
        loadLangTab(savedId, firstLang.id);
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to save coupon',
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
      await adminApi.couponLangSetup({
        couponlang_coupon_id: recordId,
        couponlang_lang_id: langId,
        coupon_title: couponTitle.trim(),
        coupon_description: couponDescription.trim(),
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
          'Failed to save coupon language',
      );
    } finally {
      setSaving(false);
    }
  };

  const showMaxDiscount = Number(form.coupon_discount_type) === DISCOUNT_PERCENTAGE;

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_COUPON_SETUP', 'Coupon setup')}
      size="lg"
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
              <Field label={lbl('LBL_Coupon_Identifier', 'Coupon identifier')} required>
                <input
                  className="form-control"
                  value={form.coupon_identifier}
                  onChange={(e) => setForm((prev) => ({ ...prev, coupon_identifier: e.target.value }))}
                  required
                />
              </Field>

              <Field label={lbl('LBL_Coupon_Code', 'Coupon code')} required>
                <input
                  className="form-control"
                  value={form.coupon_code}
                  onChange={(e) => setForm((prev) => ({ ...prev, coupon_code: e.target.value }))}
                  required
                />
              </Field>

              <Field label={lbl('LBL_DISCOUNT_TYPE', 'Discount type')} required>
                <select
                  className="form-control"
                  value={form.coupon_discount_type}
                  onChange={(e) =>
                    setForm((prev) => ({ ...prev, coupon_discount_type: Number(e.target.value) }))
                  }
                  required
                >
                  {discountTypes.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </Field>

              <Field label={lbl('LBL_DISCOUNT_VALUE', 'Discount value')} required>
                <input
                  className="form-control"
                  type="text"
                  inputMode="decimal"
                  value={String(form.coupon_discount_value)}
                  onChange={(e) =>
                    setForm((prev) => ({ ...prev, coupon_discount_value: e.target.value }))
                  }
                  required
                />
              </Field>

              {showMaxDiscount ? (
                <Field label={lbl('LBL_MAX_DISCOUNT', 'Max discount')} required>
                  <input
                    className="form-control"
                    type="text"
                    inputMode="decimal"
                    value={String(form.coupon_max_discount)}
                    onChange={(e) =>
                      setForm((prev) => ({ ...prev, coupon_max_discount: e.target.value }))
                    }
                    required
                  />
                </Field>
              ) : null}

              <Field label={lbl('LBL_MIN_ORDER', 'Min order')}>
                <input
                  className="form-control"
                  type="text"
                  inputMode="decimal"
                  value={String(form.coupon_min_order)}
                  onChange={(e) => setForm((prev) => ({ ...prev, coupon_min_order: e.target.value }))}
                />
              </Field>

              <Field label={lbl('LBL_Max_uses', 'Max uses')} required>
                <input
                  className="form-control"
                  type="number"
                  min={1}
                  max={9999}
                  value={form.coupon_max_uses}
                  onChange={(e) =>
                    setForm((prev) => ({ ...prev, coupon_max_uses: Number(e.target.value) }))
                  }
                  required
                />
              </Field>

              <Field label={lbl('LBL_Uses/User', 'Uses per user')} required>
                <input
                  className="form-control"
                  type="number"
                  min={1}
                  max={9999}
                  value={form.coupon_user_uses}
                  onChange={(e) =>
                    setForm((prev) => ({ ...prev, coupon_user_uses: Number(e.target.value) }))
                  }
                  required
                />
              </Field>

              <Field label={lbl('LBL_Date_From', 'Date from')} required>
                <input
                  className="form-control"
                  type="datetime-local"
                  value={form.coupon_start_date}
                  onChange={(e) =>
                    setForm((prev) => ({ ...prev, coupon_start_date: e.target.value }))
                  }
                  required
                />
              </Field>

              <Field label={lbl('LBL_Date_Till', 'Date till')} required>
                <input
                  className="form-control"
                  type="datetime-local"
                  value={form.coupon_end_date}
                  onChange={(e) => setForm((prev) => ({ ...prev, coupon_end_date: e.target.value }))}
                  required
                />
              </Field>

              <Field label={lbl('LBL_Status', 'Status')} required>
                <select
                  className="form-control"
                  value={form.coupon_active}
                  onChange={(e) =>
                    setForm((prev) => ({ ...prev, coupon_active: Number(e.target.value) }))
                  }
                  disabled={isExpired}
                  required
                >
                  {statusOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </Field>
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-brand" disabled={saving}>
                {saving
                  ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                  : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" onSubmit={onSaveLang}>
            <div className="row">
              <Field label={lbl('LBL_Coupon_title', 'Coupon title')} required>
                <input
                  className="form-control"
                  value={couponTitle}
                  onChange={(e) => setCouponTitle(e.target.value)}
                  required
                />
              </Field>

              <Field label={lbl('LBL_Description', 'Description')}>
                <textarea
                  className="form-control"
                  rows={4}
                  maxLength={250}
                  value={couponDescription}
                  onChange={(e) => setCouponDescription(e.target.value)}
                />
              </Field>
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-brand" disabled={saving}>
                {saving
                  ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                  : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        )}
      </AdminTabbedModalLayout>
    </AdminModal>
  );
}
