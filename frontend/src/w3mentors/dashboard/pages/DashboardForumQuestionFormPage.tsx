import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, Navigate, useNavigate, useParams } from 'react-router-dom';
import { api } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';
import { ForumSpriteIcon } from '../components/ForumSpriteIcon';
import { ForumTagRequestModal } from '../forum/ForumTagRequestModal';
import { DASHBOARD_MODAL_FORM_OPTS } from '../dashboardModalOptions';

type LanguageOption = { value: number; label: string };
type TagOption = { id: number; name: string };
type SelectedTag = { id: number; name: string };

type FormData = {
  id: number;
  title: string;
  slug: string;
  description: string;
  language_id: number;
  status: number;
  comments_allowed: number;
  tags: SelectedTag[];
  can_edit_status: boolean;
  status_alert: 'resolved' | 'spammed' | null;
};

type FormMeta = {
  languages: LanguageOption[];
  title_min: number;
  title_max: number;
  tags_limit: number;
};

/** Legacy dashboard/views/forum/add-form.php */
export function DashboardForumQuestionFormPage() {
  const role = useDashboardRole();
  const navigate = useNavigate();
  const { lbl } = useSite();
  const { showModal, closeModal } = useModal();
  const { questionId: questionIdParam } = useParams();
  const questionId = questionIdParam ? Number(questionIdParam) : 0;

  const [meta, setMeta] = useState<FormMeta | null>(null);
  const [form, setForm] = useState<FormData | null>(null);
  const [selectedTags, setSelectedTags] = useState<SelectedTag[]>([]);
  const [tagKeyword, setTagKeyword] = useState('');
  const [tagSuggestions, setTagSuggestions] = useState<TagOption[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const suggestTimer = useRef<number | null>(null);
  const tagsWrapRef = useRef<HTMLDivElement>(null);

  const loadForm = useCallback(() => {
    setLoading(true);
    setError('');
    const path = questionId > 0 ? `/dashboard/forum/form/${questionId}` : '/dashboard/forum/form';

    return api
      .get<{ data: FormData; meta: FormMeta }>(path)
      .then((res) => {
        setForm(res.data.data);
        setMeta(res.data.meta);
        setSelectedTags(res.data.data.tags ?? []);
      })
      .catch((err: unknown) => {
        const msg =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_Something_went_wrong', 'Something went wrong.');
        setError(msg);
        setForm(null);
      })
      .finally(() => setLoading(false));
  }, [questionId, lbl]);

  useEffect(() => {
    loadForm();
  }, [loadForm]);

  useEffect(() => {
    if (!showSuggestions || tagKeyword.trim() === '') {
      setTagSuggestions([]);
      return;
    }

    if (suggestTimer.current) {
      window.clearTimeout(suggestTimer.current);
    }

    suggestTimer.current = window.setTimeout(() => {
      api
        .get<{ data: TagOption[] }>('/dashboard/forum-tags/suggest', {
          params: { keyword: tagKeyword.trim() },
        })
        .then((res) => setTagSuggestions(res.data.data))
        .catch(() => setTagSuggestions([]));
    }, 250);

    return () => {
      if (suggestTimer.current) {
        window.clearTimeout(suggestTimer.current);
      }
    };
  }, [tagKeyword, showSuggestions]);

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (tagsWrapRef.current && !tagsWrapRef.current.contains(e.target as Node)) {
        setShowSuggestions(false);
      }
    };
    document.addEventListener('click', onDocClick);
    return () => document.removeEventListener('click', onDocClick);
  }, []);

  if (!role) {
    return <Navigate to="/dashboard" replace />;
  }

  const titleMax = meta?.title_max ?? 150;
  const titleMin = meta?.title_min ?? 10;
  const tagsLimit = meta?.tags_limit ?? 5;
  const titleRemaining = form ? titleMax - form.title.length : titleMax;
  const slugRemaining = form ? titleMax - form.slug.length : titleMax;

  const addTag = (tag: TagOption) => {
    if (selectedTags.some((t) => t.id === tag.id)) {
      return;
    }
    if (selectedTags.length >= tagsLimit) {
      return;
    }
    setSelectedTags((prev) => [...prev, tag]);
    setTagKeyword('');
    setShowSuggestions(false);
  };

  const removeTag = (tagId: number) => {
    setSelectedTags((prev) => prev.filter((t) => t.id !== tagId));
  };

  const formatSlug = async () => {
    if (!form || form.slug.trim() === '') {
      return;
    }
    try {
      const res = await api.post<{ data: { slug: string } }>('/dashboard/forum/slug', {
        slug: form.slug,
      });
      setForm((f) => (f ? { ...f, slug: res.data.data.slug } : f));
    } catch {
      /* keep current slug */
    }
  };

  const openTagRequest = () => {
    showModal(
      <ForumTagRequestModal requestId={0} onClose={closeModal} onSaved={() => undefined} />,
      DASHBOARD_MODAL_FORM_OPTS
    );
  };

  const save = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form) {
      return;
    }

    setSaving(true);
    setError('');
    try {
      await api.post('/dashboard/forum/form', {
        fque_id: form.id,
        fque_title: form.title.trim(),
        fque_slug: form.slug.trim(),
        fque_description: form.description.trim(),
        fque_lang_id: form.language_id,
        fque_status: form.status,
        fque_comments_allowed: form.comments_allowed,
        fque_sel_tags: selectedTags.map((t) => t.id).join(','),
      });
      navigate(dashboardPath(role, 'forum'));
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_Something_went_wrong', 'Something went wrong.');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const published = form?.status === 1;
  const commentsAllowed = form?.comments_allowed === 1;
  const canEditStatus = form?.can_edit_status ?? true;

  return (
    <div className="container container--small">
      <div className="page__head">
        <Link to={dashboardPath(role, 'forum')} className="page-back">
          <DashboardSpriteIcon id="arrow-back" className="icon icon--back me-2" />
          {lbl('LBL_Back_to_questions', 'Back to questions')}
        </Link>
        <div className="row align-items-center justify-content-between">
          <div className="col-sm-6">
            <h1>{lbl('LBL_WHAT_IS_ON_YOUR_MIND?', 'What is on your mind?')}</h1>
          </div>
        </div>
      </div>
      <div className="page__body">
        <div className="page-panel">
          <div className="page-panel__body">
            {loading ? (
              <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
            ) : !form ? (
              <p className="text-danger">{error || lbl('LBL_Something_went_wrong', 'Something went wrong.')}</p>
            ) : (
              <>
                {form.status_alert === 'resolved' && (
                  <div className="alert alert--success">Resolved Marked</div>
                )}
                {form.status_alert === 'spammed' && (
                  <div className="alert alert--danger">Spammed Marked</div>
                )}
                <form className="form" id="addquestion" onSubmit={save}>
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
                          <div
                            className="field_cover field-count"
                            data-length={titleMax}
                            field-count={titleRemaining}
                          >
                            <input
                              type="text"
                              className="form-control field-count__wrap"
                              id="que_title"
                              value={form.title}
                              maxLength={titleMax}
                              onChange={(e) => setForm((f) => (f ? { ...f, title: e.target.value } : f))}
                              required
                            />
                            <small>
                              {lbl(
                                'LBL_Title_Must_Be_Between_{min-length}_And_{max-length}',
                                `Title must be between ${titleMin} and ${titleMax} characters`
                              )}
                            </small>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-8">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_Question_slug', 'Question slug')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div
                            className="field_cover field-count"
                            data-length={titleMax}
                            field-count={slugRemaining}
                          >
                            <input
                              type="text"
                              className="form-control field-count__wrap"
                              id="fque_slug"
                              value={form.slug}
                              maxLength={titleMax}
                              onChange={(e) => setForm((f) => (f ? { ...f, slug: e.target.value } : f))}
                              onBlur={formatSlug}
                              required
                            />
                            <small>
                              {lbl(
                                'LBL_Slug_Must_Be_Between_{min-length}_And_{max-length}',
                                `Slug must be between ${titleMin} and ${titleMax} characters`
                              )}
                            </small>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-4">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">{lbl('LBL_Language', 'Language')}</label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <select
                              className="form-control"
                              value={form.language_id || ''}
                              onChange={(e) =>
                                setForm((f) => (f ? { ...f, language_id: Number(e.target.value) } : f))
                              }
                              required
                            >
                              <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                              {(meta?.languages ?? []).map((opt) => (
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
                  <div className="row">
                    <div className="col-md-12">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">
                            {lbl('LBL_Description', 'Description')}
                            <span className="spn_must_field">*</span>
                          </label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <textarea
                              className="form-control"
                              rows={8}
                              value={form.description}
                              onChange={(e) =>
                                setForm((f) => (f ? { ...f, description: e.target.value } : f))
                              }
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
                          <label className="field_label">{lbl('LBL_Tags', 'Tags')}</label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover d-sm-flex align-items-center" ref={tagsWrapRef}>
                            <input
                              type="text"
                              className="form-control"
                              id="fque_tags"
                              autoComplete="off"
                              value={tagKeyword}
                              onChange={(e) => {
                                setTagKeyword(e.target.value);
                                setShowSuggestions(true);
                              }}
                              onFocus={() => setShowSuggestions(true)}
                              onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                  e.preventDefault();
                                }
                              }}
                            />
                            <button
                              type="button"
                              className="rm_btn rm_btn--small btn btn--bordered color-secondary btn--block-mobile ms-3"
                              title={lbl('LBL_Forum_Request_new_tag', 'Request new tag')}
                              onClick={openTagRequest}
                            >
                              <ForumSpriteIcon
                                id="request-tag"
                                className="icon icon--request-tag icon--small me-2"
                              />
                              {lbl('LBL_Request_new_tag', 'Request new tag')}
                            </button>
                            {showSuggestions && tagSuggestions.length > 0 && (
                              <ul
                                className="dropdown-menu show"
                                style={{ position: 'absolute', zIndex: 10, maxHeight: 200, overflowY: 'auto' }}
                              >
                                {tagSuggestions.map((tag) => (
                                  <li key={tag.id}>
                                    <button
                                      type="button"
                                      className="dropdown-item"
                                      onClick={() => addTag(tag)}
                                    >
                                      {tag.name}
                                    </button>
                                  </li>
                                ))}
                              </ul>
                            )}
                          </div>
                          <div id="question-tags" className="tags pt-2 pb-2">
                            {selectedTags.map((tag) => (
                              <a
                                key={tag.id}
                                className="tags__item badge badge--curve"
                                id={`tag_${tag.id}`}
                                href="#"
                                onClick={(e) => e.preventDefault()}
                              >
                                {tag.name}
                                <button
                                  type="button"
                                  className="btn btn-link p-0 ms-2 border-0 align-baseline"
                                  aria-label={lbl('LBL_REMOVE', 'Remove')}
                                  onClick={() => removeTag(tag.id)}
                                >
                                  <ForumSpriteIcon id="cancel" className="icon icon--cancel icon--small" />
                                </button>
                              </a>
                            ))}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="row">
                    <div className="col-md-6">
                      <div className="field-set">
                        <div className="field-wraper">
                          <div className="field_cover">
                            <label
                              className={`statustab switch-group d-flex align-items-center justify-content-between ${!canEditStatus ? 'disabled-switch' : ''}`}
                            >
                              <span className="switch-group__label question-status-js">
                                {published
                                  ? lbl('LBL_Question_published', 'Question published')
                                  : lbl('LBL_Question_unpublished', 'Question unpublished')}
                              </span>
                              <span className="switch switch--small">
                                <input
                                  className="switch__label"
                                  type="checkbox"
                                  checked={published}
                                  disabled={!canEditStatus}
                                  onChange={(e) =>
                                    setForm((f) =>
                                      f ? { ...f, status: e.target.checked ? 1 : 0 } : f
                                    )
                                  }
                                />
                                <i className="switch__handle bg-green" />
                              </span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="field-set">
                        <div className="field-wraper">
                          <div className="field_cover">
                            <label
                              className={`statustab switch-group d-flex align-items-center justify-content-between ${!canEditStatus ? 'disabled-switch' : ''}`}
                            >
                              <span className="switch-group__label comments-allowed-status-js">
                                {commentsAllowed
                                  ? lbl('LBL_Comments_allowed', 'Comments allowed')
                                  : lbl('LBL_Comments_not_allowed', 'Comments not allowed')}
                              </span>
                              <span className="switch switch--small">
                                <input
                                  className="switch__label"
                                  type="checkbox"
                                  checked={commentsAllowed}
                                  disabled={!canEditStatus}
                                  onChange={(e) =>
                                    setForm((f) =>
                                      f ? { ...f, comments_allowed: e.target.checked ? 1 : 0 } : f
                                    )
                                  }
                                />
                                <i className="switch__handle bg-green" />
                              </span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  {error && <p className="text-danger small">{error}</p>}
                  <div className="row">
                    <div className="col-auto">
                      <div className="field-set">
                        <div className="field-wraper form-buttons-group">
                          <div className="field_cover">
                            <input type="hidden" name="fque_id" value={form.id} />
                            <input
                              type="submit"
                              className="btn btn--primary"
                              name="btn_submit"
                              disabled={saving}
                              value={
                                saving
                                  ? lbl('LBL_LOADING', 'Loading...')
                                  : lbl('LBL_ASK_NOW', 'Ask now')
                              }
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
