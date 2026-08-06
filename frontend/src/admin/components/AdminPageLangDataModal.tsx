import { type FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import {
  getInnovEditorHtml,
  purgeOrphanInnovEditorNodes,
  resetAdminInnovEditors,
  setInnovEditorHtml,
} from '../hooks/adminInnovEditorResources';
import { AdminInnovHtmlEditor } from './AdminInnovHtmlEditor';
import { AdminModal } from './AdminModal';

const HELPING_TEXT_EDITOR_ID = 'plang_helping_text';

function helpingTextEditorId(_langId: number): string {
  return HELPING_TEXT_EDITOR_ID;
}

type LanguageOption = { id: number; name: string };

type LangForm = {
  plang_id: number;
  plang_lang_id: number;
  plang_key: string;
  plang_title: string;
  plang_summary: string;
  plang_warring_msg: string;
  plang_recommendations: string;
  plang_helping_text: string;
  default_helping_text: string;
  layout_direction: string;
  site_languages: LanguageOption[];
  default_lang_id?: number;
  show_auto_translate?: boolean;
};

type Props = {
  open: boolean;
  plangId: number;
  langId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminPageLangDataModal({ open, plangId, langId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const lblRef = useRef(lbl);
  lblRef.current = lbl;
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [activeLangId, setActiveLangId] = useState(langId);
  const [siteLanguages, setSiteLanguages] = useState<LanguageOption[]>([]);
  const [pageKey, setPageKey] = useState('');
  const [title, setTitle] = useState('');
  const [summary, setSummary] = useState('');
  const [warning, setWarning] = useState('');
  const [recommendations, setRecommendations] = useState('');
  const [helpingText, setHelpingText] = useState('');
  const [defaultHelpingText, setDefaultHelpingText] = useState('');
  const [layoutDirection, setLayoutDirection] = useState('ltr');
  const [showAutoTranslate, setShowAutoTranslate] = useState(false);
  const [autoTranslate, setAutoTranslate] = useState(false);
  const [formReady, setFormReady] = useState(false);
  const [recordPlangId, setRecordPlangId] = useState(0);

  const reset = useCallback(() => {
    setError('');
    setPageKey('');
    setTitle('');
    setSummary('');
    setWarning('');
    setRecommendations('');
    setHelpingText('');
    setDefaultHelpingText('');
    setLayoutDirection('ltr');
    setSiteLanguages([]);
    setShowAutoTranslate(false);
    setAutoTranslate(false);
    setFormReady(false);
    setRecordPlangId(0);
    setActiveLangId(1);
    resetAdminInnovEditors();
    purgeOrphanInnovEditorNodes();
  }, []);

  const loadForm = useCallback(
    (recordId: number, nextLangId: number) => {
      if (recordId < 1 || nextLangId < 1) {
        return;
      }
      resetAdminInnovEditors();
      setFormReady(false);
      setLoading(true);
      setError('');
      setAutoTranslate(false);
      void adminApi
        .pageLangDataLangForm(recordId, nextLangId)
        .then((res) => {
          const data = res.data.data as LangForm;
          setRecordPlangId(Number(data.plang_id ?? recordId));
          setActiveLangId(nextLangId);
          setSiteLanguages(data.site_languages ?? []);
          setPageKey(data.plang_key ?? '');
          setTitle(data.plang_title ?? '');
          setSummary(data.plang_summary ?? '');
          setWarning(data.plang_warring_msg ?? '');
          setRecommendations(data.plang_recommendations ?? '');
          setHelpingText(data.plang_helping_text ?? '');
          setDefaultHelpingText(data.default_helping_text ?? '');
          setLayoutDirection(data.layout_direction ?? 'ltr');
          setShowAutoTranslate(Boolean(data.show_auto_translate));
          setFormReady(true);
        })
        .catch(() => {
          setError(lblRef.current('LBL_INVALID_REQUEST', 'Invalid request'));
          setFormReady(false);
        })
        .finally(() => setLoading(false));
    },
    [],
  );

  useEffect(() => {
    setActiveLangId(langId);
    loadForm(plangId, langId);
    return () => {
      reset();
    };
  }, [langId, loadForm, plangId, reset]);

  const onSelectLangTab = (nextLangId: number) => {
    const recordId = recordPlangId > 0 ? recordPlangId : plangId;
    if (recordId < 1) return;
    loadForm(recordId, nextLangId);
  };

  const onResetHelpingText = () => {
    if (
      !window.confirm(
        lbl(
          'LBL_Do_you_want_to_replace_current_content_to_default_content',
          'Do you want to replace current content to default content?',
        ),
      )
    ) {
      return;
    }
    const defaultNode = document.getElementById('editor_default_content');
    const defaultHtml = defaultNode?.innerHTML ?? defaultHelpingText;
    setInnovEditorHtml(helpingTextEditorId(activeLangId), defaultHtml);
    setHelpingText(defaultHtml);
  };

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!title.trim()) {
      setError(lbl('LBL_INVALID_REQUEST', 'Invalid request'));
      return;
    }

    const helpingHtml = getInnovEditorHtml(helpingTextEditorId(activeLangId));

    setSaving(true);
    setError('');
    try {
      const res = await adminApi.pageLangDataLangSetup({
        plang_key: pageKey,
        plang_lang_id: activeLangId,
        plang_title: title.trim(),
        plang_summary: summary,
        plang_warring_msg: warning,
        plang_recommendations: recommendations,
        plang_helping_text: helpingHtml,
        ...(showAutoTranslate && autoTranslate ? { update_langs_data: 1 } : {}),
      });
      onSaved();
      const nextLangId = Number(res.data.data?.next_lang_id ?? 0);
      const savedId = Number(res.data.data?.plang_id ?? recordPlangId ?? plangId);
      if (nextLangId > 0) {
        loadForm(savedId, nextLangId);
        return;
      }
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to save page language data',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_PAGE_LANG_SETUP', 'Page lang setup')}
      size="lg"
      onClose={onClose}
    >
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul className="tabs-nav">
            {siteLanguages.map((language) => (
              <li key={language.id}>
                <a
                  href="javascript:void(0)"
                  className={activeLangId === language.id ? 'active' : ''}
                  data-id={language.id}
                  onClick={(e) => {
                    e.preventDefault();
                    onSelectLangTab(language.id);
                  }}
                >
                  {language.name}
                </a>
              </li>
            ))}
          </ul>
        </nav>
      </div>

      <div className="form-edit-body">
        <div id="editor_default_content" style={{ display: 'none' }} dangerouslySetInnerHTML={{ __html: defaultHelpingText }} />
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : error && !formReady ? (
          <div className="alert alert-danger m-4">{error}</div>
        ) : formReady ? (
          <form
            className={`form layout--${layoutDirection}`}
            dir={layoutDirection}
            id="page-lang-data"
            onSubmit={onSubmit}
          >
            {error ? <div className="alert alert-danger">{error}</div> : null}
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Page_Identifier', 'Page identifier')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input className="form-control" name="plang_key" value={pageKey} disabled readOnly />
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_Page_Title', 'Page title')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        name="plang_title"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Page_Summary', 'Page summary')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        name="plang_summary"
                        value={summary}
                        onChange={(e) => setSummary(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Page_Warning', 'Page warning')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        name="plang_warring_msg"
                        value={warning}
                        onChange={(e) => setWarning(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_Recommendation', 'Recommendation')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        name="plang_recommendations"
                        value={recommendations}
                        onChange={(e) => setRecommendations(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_HELPING_TEXT', 'Helping text')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <br />
                      <a
                        href="javascript:void(0)"
                        className="btn btn-primary btn-outline-brand"
                        onClick={(e) => {
                          e.preventDefault();
                          onResetHelpingText();
                        }}
                      >
                        {lbl('LBL_Reset_Editor_Content_to_default', 'Reset editor content to default')}
                      </a>
                      <AdminInnovHtmlEditor
                        mountKey={`${recordPlangId}-${activeLangId}`}
                        editorId={helpingTextEditorId(activeLangId)}
                        initialHtml={helpingText}
                        layoutDirection={layoutDirection}
                        active={formReady && !loading}
                      />
                    </div>
                  </div>
                </div>
              </div>

              {showAutoTranslate ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <label className="checkbox d-flex">
                      <input
                        type="checkbox"
                        name="update_langs_data"
                        value="1"
                        checked={autoTranslate}
                        onChange={(e) => setAutoTranslate(e.target.checked)}
                      />
                      <span>
                        {lbl('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', 'Auto translate for other languages')}
                      </span>
                    </label>
                  </div>
                </div>
              ) : null}
            </div>

            <div className="form-actions">
              <button type="submit" name="btn_submit" className="btn btn-brand" disabled={saving}>
                {saving
                  ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                  : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        ) : null}
      </div>
    </AdminModal>
  );
}
