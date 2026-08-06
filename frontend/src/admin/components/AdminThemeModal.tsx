import { type FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type BorderStyle = { value: number; label: string };

type ThemeRecord = {
  theme_id: number;
  theme_title: string;
  theme_niche?: string;
  theme_primary_color: string;
  theme_primary_inverse_color: string;
  theme_secondary_color: string;
  theme_secondary_inverse_color: string;
  theme_footer_color: string;
  theme_footer_inverse_color: string;
  theme_gradient_primary_color: string;
  theme_gradient_secondary_color: string;
  theme_borders_style: number;
};

type Props = {
  open: boolean;
  themeId: number;
  action: 'update' | 'clone';
  onClose: () => void;
  onSaved: () => void;
};

const JSCOLOR_URL = '/manager/views/js/jscolor.min.js';

let jscolorPromise: Promise<void> | null = null;

function ensureJscolor(): Promise<void> {
  if (typeof window === 'undefined') {
    return Promise.resolve();
  }
  const w = window as Window & { jscolor?: { installByClassName: (name: string) => void } };
  if (w.jscolor) {
    return Promise.resolve();
  }
  if (!jscolorPromise) {
    jscolorPromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = JSCOLOR_URL;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => {
        jscolorPromise = null;
        reject(new Error('Failed to load jscolor'));
      };
      document.body.appendChild(script);
    });
  }
  return jscolorPromise;
}

function installJscolor(): void {
  const w = window as Window & { jscolor?: { installByClassName: (name: string) => void } };
  w.jscolor?.installByClassName('jscolor');
}

const COLOR_FIELDS: Array<{ key: keyof ThemeRecord; labelKey: string; fallback: string }> = [
  { key: 'theme_primary_color', labelKey: 'LBL_Primary_Color', fallback: 'Primary color' },
  { key: 'theme_primary_inverse_color', labelKey: 'LBL_Primary_Inverse_Color', fallback: 'Primary inverse color' },
  { key: 'theme_secondary_color', labelKey: 'LBL_Secondary_Color', fallback: 'Secondary color' },
  { key: 'theme_secondary_inverse_color', labelKey: 'LBL_Secondary_Inverse_Color', fallback: 'Secondary inverse color' },
  { key: 'theme_footer_color', labelKey: 'LBL_Footer_Color', fallback: 'Footer color' },
  { key: 'theme_footer_inverse_color', labelKey: 'LBL_Footer_Inverse_Color', fallback: 'Footer inverse color' },
  { key: 'theme_gradient_primary_color', labelKey: 'LBL_Gradient_Primary_Color', fallback: 'Gradient primary color' },
  { key: 'theme_gradient_secondary_color', labelKey: 'LBL_Gradient_Secondary_Color', fallback: 'Gradient secondary color' },
];

export function AdminThemeModal({ open, themeId, action, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [borderStyles, setBorderStyles] = useState<BorderStyle[]>([]);
  const [form, setForm] = useState<ThemeRecord>({
    theme_id: 0,
    theme_title: '',
    theme_niche: 'onlinetutoring',
    theme_primary_color: '',
    theme_primary_inverse_color: '',
    theme_secondary_color: '',
    theme_secondary_inverse_color: '',
    theme_footer_color: '',
    theme_footer_inverse_color: '',
    theme_gradient_primary_color: '',
    theme_gradient_secondary_color: '',
    theme_borders_style: 1,
  });
  const formBodyRef = useRef<HTMLDivElement>(null);

  const reset = useCallback(() => {
    setError('');
    setBorderStyles([]);
    setForm({
      theme_id: 0,
      theme_title: '',
      theme_niche: 'onlinetutoring',
      theme_primary_color: '',
      theme_primary_inverse_color: '',
      theme_secondary_color: '',
      theme_secondary_inverse_color: '',
      theme_footer_color: '',
      theme_footer_inverse_color: '',
      theme_gradient_primary_color: '',
      theme_gradient_secondary_color: '',
      theme_borders_style: 1,
    });
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }
    if (themeId < 1) {
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .themeForm(themeId, action)
      .then(async (res) => {
        const data = res.data.data;
        const theme = data.theme as ThemeRecord;
        setBorderStyles((data.border_styles as BorderStyle[]) ?? []);
        setForm({
          theme_id: Number(theme.theme_id ?? 0),
          theme_title: String(theme.theme_title ?? ''),
          theme_niche: String(theme.theme_niche ?? 'onlinetutoring'),
          theme_primary_color: String(theme.theme_primary_color ?? ''),
          theme_primary_inverse_color: String(theme.theme_primary_inverse_color ?? ''),
          theme_secondary_color: String(theme.theme_secondary_color ?? ''),
          theme_secondary_inverse_color: String(theme.theme_secondary_inverse_color ?? ''),
          theme_footer_color: String(theme.theme_footer_color ?? ''),
          theme_footer_inverse_color: String(theme.theme_footer_inverse_color ?? ''),
          theme_gradient_primary_color: String(theme.theme_gradient_primary_color ?? ''),
          theme_gradient_secondary_color: String(theme.theme_gradient_secondary_color ?? ''),
          theme_borders_style: Number(theme.theme_borders_style ?? 1),
        });
        await ensureJscolor();
      })
      .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
      .finally(() => setLoading(false));
  }, [action, lbl, open, reset, themeId]);

  useEffect(() => {
    if (!open || loading) {
      return;
    }
    void ensureJscolor().then(() => {
      requestAnimationFrame(() => installJscolor());
    });
  }, [loading, open, form]);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!form.theme_title.trim()) {
      setError(lbl('LBL_TITLE_IS_MANDATORY', 'Title is required'));
      return;
    }

    setSaving(true);
    setError('');
    try {
      await adminApi.themeSetup({ ...form });
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

  const setField = <K extends keyof ThemeRecord>(key: K, value: ThemeRecord[K]) => {
    setForm((prev) => ({ ...prev, [key]: value }));
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_Theme_Setup', 'Theme setup')}
      size="md"
      onClose={onClose}
    >
      <div className="form-edit-body" ref={formBodyRef}>
        {loading ? (
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          <form className="form form_horizontal" onSubmit={onSubmit}>
            {error ? <div className="alert alert--danger">{error}</div> : null}
            <input type="hidden" name="theme_id" value={form.theme_id} />
            <input type="hidden" name="theme_niche" value={form.theme_niche ?? 'onlinetutoring'} />
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Title', 'Title')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="text"
                        className="form-control"
                        value={form.theme_title}
                        onChange={(e) => setField('theme_title', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              {COLOR_FIELDS.map((field) => (
                <div className="col-md-12" key={field.key}>
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl(field.labelKey, field.fallback)}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          type="text"
                          className="form-control jscolor"
                          value={form[field.key] as string}
                          onChange={(e) => setField(field.key, e.target.value as ThemeRecord[typeof field.key])}
                        />
                      </div>
                    </div>
                  </div>
                </div>
              ))}
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Borders_Style', 'Borders style')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={form.theme_borders_style}
                        onChange={(e) => setField('theme_borders_style', Number(e.target.value))}
                      >
                        {borderStyles.map((style) => (
                          <option key={style.value} value={style.value}>
                            {style.label}
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
                      <button type="submit" className="btn btn-primary" disabled={saving}>
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
