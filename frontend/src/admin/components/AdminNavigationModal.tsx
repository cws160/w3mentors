import { type FormEvent, type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  navigationId: number;
  onClose: () => void;
  onSaved: () => void;
};

type LanguageOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;

export function AdminNavigationModal({ open, navigationId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [active, setActive] = useState(1);
  const [langId, setLangId] = useState(0);
  const [title, setTitle] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const activeLangIndex = useMemo(
    () => (langId > 0 ? siteLanguages.findIndex((lang) => lang.id === langId) : -1),
    [langId, siteLanguages],
  );
  const isLastLangTab = activeLangIndex >= 0 && activeLangIndex === siteLanguages.length - 1;

  const reset = useCallback(() => {
    setActiveTab('general');
    setRecordId(0);
    setSiteLanguages(contextLanguages);
    setIdentifier('');
    setActive(1);
    setLangId(0);
    setTitle('');
    setError('');
  }, [contextLanguages]);

  const applyLangData = (
    data: Record<string, unknown>,
    nextNavigationId: number,
    nextLangId: number,
    fallbackLanguages = siteLanguages,
  ) => {
    setRecordId(Number(data.nav_id ?? nextNavigationId));
    setIdentifier(String(data.nav_identifier ?? identifier));
    setActive(Number(data.nav_active ?? active));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? fallbackLanguages);
    setLangId(Number(data.lang_id ?? nextLangId));
    setTitle(String(data.nav_name ?? ''));
    setActiveTab(`lang-${nextLangId}`);
  };

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void (async () => {
      try {
        const res = await adminApi.navigationShow(navigationId);
        const data = (res.data.data ?? {}) as Record<string, unknown>;
        const nextLanguages = (data.site_languages as LanguageOption[] | undefined) ?? contextLanguages;
        setRecordId(Number(data.nav_id ?? navigationId));
        setIdentifier(String(data.nav_identifier ?? ''));
        setActive(Number(data.nav_active ?? 1));
        setSiteLanguages(nextLanguages);
        const firstLang = nextLanguages[0];
        if (firstLang) {
          const langRes = await adminApi.navigationLangForm(navigationId, firstLang.id);
          applyLangData((langRes.data.data ?? {}) as Record<string, unknown>, navigationId, firstLang.id, nextLanguages);
        }
      } catch (err: unknown) {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      } finally {
        setLoading(false);
      }
    })();
  }, [contextLanguages, lbl, navigationId, open, reset]);

  const loadLang = useCallback(
    async (nextNavigationId: number, nextLangId: number) => {
      if (nextNavigationId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.navigationLangForm(nextNavigationId, nextLangId);
        applyLangData((res.data.data ?? {}) as Record<string, unknown>, nextNavigationId, nextLangId);
      } catch (err: unknown) {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      } finally {
        setLoading(false);
      }
    },
    [active, identifier, lbl, siteLanguages],
  );

  const submitGeneral = async (event: FormEvent) => {
    event.preventDefault();
    if (recordId < 1) return;
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.navigationUpdate(recordId, {
        nav_identifier: identifier.trim(),
        nav_active: active,
      });
      onSaved();
      const nextLangId = Number(res.data.data?.next_lang_id ?? siteLanguages[0]?.id ?? 0);
      if (nextLangId > 0) {
        await loadLang(recordId, nextLangId);
      } else {
        onClose();
      }
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'),
      );
    } finally {
      setSaving(false);
    }
  };

  const submitLang = async (event: FormEvent) => {
    event.preventDefault();
    if (recordId < 1 || langId < 1) return;
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.navigationLangUpdate(recordId, langId, {
        nav_name: title.trim(),
      });
      onSaved();
      const nextLang = siteLanguages[activeLangIndex + 1];
      const missingLangId = Number(res.data.data?.next_lang_id ?? 0);
      if (nextLang) {
        await loadLang(recordId, nextLang.id);
      } else if (missingLangId > 0) {
        await loadLang(recordId, missingLangId);
      } else {
        onClose();
      }
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
      title={lbl('LBL_NAVIGATION_SETUP', 'Navigation Setup')}
      size="lg"
      onClose={onClose}
    >
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul>
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={(event) => {
                  event.preventDefault();
                  setActiveTab('general');
                  setLangId(0);
                }}
              >
                {lbl('LBL_GENERAL', 'General')}
              </a>
            </li>
            {siteLanguages.map((lang) => (
              <li key={lang.id}>
                <a
                  href="javascript:void(0)"
                  className={activeTab === `lang-${lang.id}` ? 'active' : ''}
                  onClick={(event) => {
                    event.preventDefault();
                    void loadLang(recordId, lang.id);
                  }}
                >
                  {lang.name}
                </a>
              </li>
            ))}
          </ul>
        </nav>
      </div>
      <div className="form-edit-body">
        {error ? <div className="alert alert-danger m-3">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : activeTab === 'general' ? (
          <form className="form form_horizontal" onSubmit={submitGeneral}>
            <div className="row">
              <LegacyField label={lbl('LBL_IDENTIFIER', 'Identifier')} required>
                <input
                  className="form-control"
                  value={identifier}
                  onChange={(event) => setIdentifier(event.target.value)}
                  required
                />
              </LegacyField>
              <LegacyField label={lbl('LBL_STATUS', 'Status')}>
                <select
                  className="form-control"
                  value={active}
                  onChange={(event) => setActive(Number(event.target.value))}
                >
                  <option value={1}>{lbl('LBL_ACTIVE', 'Active')}</option>
                  <option value={0}>{lbl('LBL_INACTIVE', 'Inactive')}</option>
                </select>
              </LegacyField>
              <FormButton saving={saving} label={lbl('LBL_SAVE_CHANGES', 'Save changes')} />
            </div>
          </form>
        ) : (
          <form className="form form_horizontal" onSubmit={submitLang}>
            <div className="row">
              <LegacyField label={lbl('LBL_TITLE', 'Title')} required>
                <input
                  className="form-control"
                  value={title}
                  onChange={(event) => setTitle(event.target.value)}
                  required
                />
              </LegacyField>
              <FormButton
                saving={saving}
                label={isLastLangTab ? lbl('LBL_SAVE_CHANGES', 'Save changes') : lbl('LBL_SAVE_&_NEXT', 'Save & next')}
              />
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
