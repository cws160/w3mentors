import { type FormEvent, type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type Props = {
  open: boolean;
  blockId: number;
  onClose: () => void;
  onSaved: () => void;
};

type LanguageOption = { id: number; name: string };
type TabKey = 'general' | `lang-${number}`;

export function AdminContentBlockModal({ open, blockId, onClose, onSaved }: Props) {
  const { lbl, languages: contextLanguages } = useSite();
  const [activeTab, setActiveTab] = useState<TabKey>('general');
  const [recordId, setRecordId] = useState(0);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [identifier, setIdentifier] = useState('');
  const [active, setActive] = useState(1);
  const [editable, setEditable] = useState(1);
  const [langId, setLangId] = useState(0);
  const [label, setLabel] = useState('');
  const [content, setContent] = useState('');
  const [defaultContent, setDefaultContent] = useState('');
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
    setEditable(1);
    setLangId(0);
    setLabel('');
    setContent('');
    setDefaultContent('');
    setError('');
  }, [contextLanguages]);

  const applyLangData = (
    data: Record<string, unknown>,
    nextBlockId: number,
    nextLangId: number,
    fallbackLanguages = siteLanguages,
  ) => {
    setRecordId(Number(data.epage_id ?? nextBlockId));
    setIdentifier(String(data.epage_identifier ?? identifier));
    setActive(Number(data.epage_active ?? active));
    setEditable(Number(data.epage_editable ?? editable));
    setSiteLanguages((data.site_languages as LanguageOption[] | undefined) ?? fallbackLanguages);
    setLangId(Number(data.lang_id ?? nextLangId));
    setLabel(String(data.epage_label ?? ''));
    setContent(String(data.epage_content ?? ''));
    setDefaultContent(String(data.epage_default_content ?? ''));
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
        const res = await adminApi.contentBlockShow(blockId);
        const data = (res.data.data ?? {}) as Record<string, unknown>;
        const nextLanguages = (data.site_languages as LanguageOption[] | undefined) ?? contextLanguages;
        setRecordId(Number(data.epage_id ?? blockId));
        setIdentifier(String(data.epage_identifier ?? ''));
        setActive(Number(data.epage_active ?? 1));
        setEditable(Number(data.epage_editable ?? 1));
        setSiteLanguages(nextLanguages);
        const firstLang = nextLanguages[0];
        if (firstLang) {
          const langRes = await adminApi.contentBlockLangForm(blockId, firstLang.id);
          applyLangData((langRes.data.data ?? {}) as Record<string, unknown>, blockId, firstLang.id, nextLanguages);
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
  }, [blockId, contextLanguages, lbl, open, reset]);

  const loadLang = useCallback(
    async (nextBlockId: number, nextLangId: number) => {
      if (nextBlockId < 1) return;
      setLoading(true);
      setError('');
      try {
        const res = await adminApi.contentBlockLangForm(nextBlockId, nextLangId);
        applyLangData((res.data.data ?? {}) as Record<string, unknown>, nextBlockId, nextLangId);
      } catch (err: unknown) {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            lbl('LBL_INVALID_REQUEST', 'Invalid request'),
        );
      } finally {
        setLoading(false);
      }
    },
    [active, editable, identifier, lbl, siteLanguages],
  );

  const submitGeneral = async (event: FormEvent) => {
    event.preventDefault();
    if (recordId < 1) return;
    setSaving(true);
    setError('');
    try {
      const res = await adminApi.contentBlockUpdate(recordId, {
        epage_identifier: identifier.trim(),
        epage_active: active,
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
      const res = await adminApi.contentBlockLangUpdate(recordId, langId, {
        epage_label: label.trim(),
        epage_content: content,
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
      title={lbl('LBL_CONTENT_BLOCK_SETUP', 'Content Block Setup')}
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
                  data-id={lang.id}
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
              <LegacyField label={lbl('LBL_Page_Identifier', 'Page Identifier')} required>
                <input
                  className="form-control"
                  value={identifier}
                  onChange={(event) => setIdentifier(event.target.value)}
                  required
                />
              </LegacyField>
              <LegacyField label={lbl('LBL_Status', 'Status')}>
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
              <LegacyField label={lbl('LBL_Page_Title', 'Page Title')} required>
                <input
                  className="form-control"
                  value={label}
                  onChange={(event) => setLabel(event.target.value)}
                  required
                />
              </LegacyField>
              {editable === 1 ? (
                <LegacyField label={lbl('LBL_Page_Content', 'Page Content')}>
                  {defaultContent ? (
                    <a
                      className="btn btn-primary btn-outline-brand mb-3"
                      href="javascript:void(0)"
                      onClick={(event) => {
                        event.preventDefault();
                        setContent(defaultContent);
                      }}
                    >
                      {lbl('LBL_Reset_Editor_Content_to_default', 'Reset Editor Content to default')}
                    </a>
                  ) : null}
                  <textarea
                    className="form-control"
                    rows={10}
                    value={content}
                    onChange={(event) => setContent(event.target.value)}
                  />
                </LegacyField>
              ) : null}
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
