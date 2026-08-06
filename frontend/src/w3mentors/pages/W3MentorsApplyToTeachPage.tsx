import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  APPLY_TO_TEACH_BLOCKS,
  contentApi,
  type ApplyToTeachBlock,
  type ApplyToTeachContent,
} from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useSite } from '../context/SiteContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { useW3MentorsAos } from '../hooks/useW3MentorsSliders';
import { bindApplyToTeachPage } from '../lib/w3mentors-ui';
import { AFILE, imageUrl } from '../utils/assets';
import { cmsPagePath } from '../utils/cms';
import { normalizeLegacyHtml } from '../utils/legacyHtml';

function renderContentBlock(block: ApplyToTeachBlock) {
  const html = normalizeLegacyHtml(block.html);
  if (!html.trim()) return null;

  switch (block.block_type) {
    case APPLY_TO_TEACH_BLOCKS.BENEFITS:
      return (
        <section className="section" key={block.block_type}>
          <div dangerouslySetInnerHTML={{ __html: html }} />
        </section>
      );
    case APPLY_TO_TEACH_BLOCKS.FEATURES:
      return (
        <section className="section" id="how-it-works" data-aos="fade-up" data-aos-duration="1000" key={block.block_type}>
          <div dangerouslySetInnerHTML={{ __html: html }} />
        </section>
      );
    case APPLY_TO_TEACH_BLOCKS.STATIC_BANNER:
      return (
        <section className="section section--cta-block" key={block.block_type}>
          <div dangerouslySetInnerHTML={{ __html: html }} />
        </section>
      );
    case APPLY_TO_TEACH_BLOCKS.BECOME_A_TUTOR:
      return (
        <section className="section section--tutor-steps bg-gradiant" key={block.block_type}>
          <div dangerouslySetInnerHTML={{ __html: html }} />
        </section>
      );
    default:
      return (
        <section className="section" key={block.block_type}>
          <div dangerouslySetInnerHTML={{ __html: html }} />
        </section>
      );
  }
}

export function W3MentorsApplyToTeachPage() {
  const { lbl, legalPages } = useSite();
  const { user, login } = useAuth();
  const navigate = useNavigate();
  const [data, setData] = useState<ApplyToTeachContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [formError, setFormError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    contentApi
      .applyToTeach()
      .then((res) => setData(res.data))
      .catch(() => setData(null))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => bindApplyToTeachPage(), []);

  const hasHowItWorks = useMemo(
    () => (data?.blocks ?? []).some((b) => b.block_type === APPLY_TO_TEACH_BLOCKS.FEATURES && b.html.trim()),
    [data?.blocks]
  );
  const hasFaqs = (data?.faqs.length ?? 0) > 0;
  const scrollColSize = hasFaqs && hasHowItWorks ? 6 : 12;

  useW3MentorsAos([data?.blocks.length, data?.faqs.length]);

  async function onGuestSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setFormError('');
    const form = e.currentTarget;
    const email = (form.elements.namedItem('user_email') as HTMLInputElement).value.trim();
    const password = (form.elements.namedItem('user_password') as HTMLInputElement).value;
    if (!email || !password) {
      setFormError(lbl('LBL_IS_MANDATORY', 'This field is mandatory'));
      return;
    }
    setSubmitting(true);
    try {
      await login(email, password);
      navigate('/teacher-request/form');
    } catch {
      setFormError(
        lbl(
          'LBL_INVALID_CREDENTIALS_OR_SIGN_UP',
          'Invalid email or password. Sign up if you do not have an account.'
        )
      );
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  if (!data) return <W3MentorsPageMessage message={lbl('LBL_Something_went_wrong', 'Unable to load page.')} error />;

  const bannerUrl = imageUrl(AFILE.APPLY_TO_TEACH_BANNER, 0, 'LARGE');
  const termsLink = data.terms_page_id ? (
    <Link
      to={legalPages?.terms_url ?? cmsPagePath(data.terms_page_id, legalPages ?? undefined)}
      className="link"
      target="_blank"
      rel="noreferrer"
    >
      {lbl('LBL_Terms_&_Conditions', 'Terms & Conditions')}
    </Link>
  ) : (
    <span className="link">{lbl('LBL_Terms_&_Conditions', 'Terms & Conditions')}</span>
  );
  const privacyLink = data.privacy_page_id ? (
    <Link
      to={legalPages?.privacy_url ?? cmsPagePath(data.privacy_page_id, legalPages ?? undefined)}
      className="link"
      target="_blank"
      rel="noreferrer"
    >
      {lbl('LBL_Privacy_Policy', 'Privacy Policy')}
    </Link>
  ) : (
    <span className="link">{lbl('LBL_Privacy_Policy', 'Privacy Policy')}</span>
  );

  return (
    <>
      <section
        className="section section--hero"
        style={{ backgroundImage: `url(${bannerUrl})` }}
      >
        <div className="container container--xxl">
          <div className="hero-panel">
            <div className="hero-panel__content" data-aos="fade-up" data-aos-duration="1000">
              <div className="hero-form">
                <div className="hero-form__head">
                  <h1>{lbl('LBL_APPLY_TO_TEACH', 'Apply to Teach')}</h1>
                  <p>
                    {lbl(
                      'LBL_APPLY_TO_TEACH_DESCRITPION',
                      'Create a profile, share your expertise, and start teaching students worldwide.'
                    )}
                  </p>
                </div>
                <div className="hero-form__body">
                  {user ? (
                    <>
                      {!user.is_teacher && (
                        <Link
                          to="/teacher-request/form"
                          className="btn btn--secondary btn--large btn--block mb-4"
                        >
                          {lbl('LBL_BECOME_A_TUTOR', 'Become a Tutor')}
                        </Link>
                      )}
                      {(hasFaqs || hasHowItWorks) && (
                        <div className="row g-5">
                          {hasFaqs && (
                            <div className={`col-${scrollColSize}`}>
                              <a href="#faq-area" className="btn btn--block btn--primary scroll scroll-section-js">
                                {lbl('LBL_FAQS', 'FAQs')}
                              </a>
                            </div>
                          )}
                          {hasHowItWorks && (
                            <div className={`col-${scrollColSize}`}>
                              <a href="#how-it-works" className="btn btn--block btn--primary scroll scroll-section-js">
                                {lbl('LBL_HOW_IT_WORKS', 'How it works')}
                              </a>
                            </div>
                          )}
                        </div>
                      )}
                    </>
                  ) : (
                    <div className="form-register">
                      <form className="form form--custom" onSubmit={onGuestSubmit}>
                        <div className="row">
                          <div className="col-sm-12">
                            <div className="form-group">
                              <input
                                type="email"
                                name="user_email"
                                className="form-control"
                                placeholder={lbl('LBL_EMAIL', 'Email')}
                                required
                                autoComplete="email"
                              />
                            </div>
                          </div>
                          <div className="col-sm-12">
                            <div className="form-group">
                              <div className="field-password">
                                <input
                                  type="password"
                                  name="user_password"
                                  className="form-control"
                                  placeholder={lbl('LBL_PASSWORD', 'Password')}
                                  required
                                  autoComplete="new-password"
                                />
                                <a href="#password" className="password-toggle">
                                  <span className="icon" id="hide-password">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16.2" height="17.134" viewBox="0 0 16.2 17.134">
                                      <path
                                        d="M13.685,15.853a7.764,7.764,0,0,1-4.4,1.375,8.437,8.437,0,0,1-8.1-7.269,9.083,9.083,0,0,1,2.5-4.9L1.339,2.536,2.4,1.393,17.222,17.384l-1.059,1.142-2.478-2.673ZM4.74,6.2A7.383,7.383,0,0,0,2.71,9.96a7.171,7.171,0,0,0,3.846,5.031,6.307,6.307,0,0,0,6.038-.316l-1.518-1.638A3.187,3.187,0,0,1,6.9,12.532a3.852,3.852,0,0,1-.468-4.507ZM9.965,11.84,7.538,9.222a2.136,2.136,0,0,0,.419,2.166,1.774,1.774,0,0,0,2.008.452Zm5.909,1.829L14.8,12.514A7.509,7.509,0,0,0,15.852,9.96,7.262,7.262,0,0,0,12.72,5.324a6.315,6.315,0,0,0-5.272-.745L6.267,3.3a7.7,7.7,0,0,1,3.014-.614,8.437,8.437,0,0,1,8.1,7.269,9.2,9.2,0,0,1-1.506,3.709Zm-6.8-7.337a3.236,3.236,0,0,1,2.59,1.058,3.8,3.8,0,0,1,.98,2.794L9.073,6.332Z"
                                        transform="translate(-1.181 -1.393)"
                                        fill="#a2a2a2"
                                      />
                                    </svg>
                                  </span>
                                  <span className="icon" id="show-password" style={{ display: 'none' }}>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16.2" height="14.538" viewBox="0 0 16.2 14.538">
                                      <path
                                        d="M9.281,3a8.437,8.437,0,0,1,8.1,7.269,8.436,8.436,0,0,1-8.1,7.269,8.437,8.437,0,0,1-8.1-7.269A8.436,8.436,0,0,1,9.281,3Zm0,12.922a6.873,6.873,0,0,0,6.571-5.652,6.873,6.873,0,0,0-6.57-5.647A6.873,6.873,0,0,0,2.71,10.27a6.874,6.874,0,0,0,6.571,5.653Zm0-2.019a3.509,3.509,0,0,1-3.369-3.634A3.509,3.509,0,0,1,9.281,6.634a3.509,3.509,0,0,1,3.369,3.634A3.509,3.509,0,0,1,9.281,13.9Zm0-1.615a1.95,1.95,0,0,0,1.872-2.019A1.95,1.95,0,0,0,9.281,8.25a1.95,1.95,0,0,0-1.872,2.019A1.95,1.95,0,0,0,9.281,12.288Z"
                                        transform="translate(-1.181 -3)"
                                        fill="#a2a2a2"
                                      />
                                    </svg>
                                  </span>
                                </a>
                              </div>
                            </div>
                          </div>
                        </div>
                        {formError && (
                          <p className="color-secondary mb-2" role="alert">
                            {formError}
                          </p>
                        )}
                        <button
                          type="submit"
                          className="btn btn--primary btn--block"
                          disabled={submitting}
                        >
                          {lbl('LBL_Submit', 'Submit')}
                        </button>
                        <p className="mt-3">
                          <Link to="/register" className="link">
                            {lbl('LBL_Sign_Up', 'Sign up')}
                          </Link>
                        </p>
                      </form>
                      <div className="hero-form__footer">
                        <p>
                          {(() => {
                            const raw = lbl(
                              'LBL_BY_SIGNING_UP_YOU_AGREE_TO_TERMS',
                              'By signing up you agree to %s and %s'
                            );
                            if (!raw.includes('%s')) {
                              return (
                                <>
                                  {raw} {termsLink} {privacyLink}
                                </>
                              );
                            }
                            const [before, middle, after] = raw.split('%s');
                            return (
                              <>
                                {before}
                                {termsLink}
                                {middle}
                                {privacyLink}
                                {after}
                              </>
                            );
                          })()}
                        </p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {data.blocks.map((block) => renderContentBlock(block))}

      {hasFaqs && (
        <section className="section section--faq" id="faq-area" data-aos="fade-up" data-aos-duration="1000">
          <div className="container container--narrow">
            <div className="section__head mb-5">
              <h2>{lbl('LBL_faq_title_second', 'Frequently asked questions')}</h2>
            </div>
            <div className="section__body">
              <div className="faq-container" id="faqParentJs">
                {data.faqs.map((faq) => (
                  <div className="faq-row faq-group-js" key={faq.id}>
                    <a
                      href={`#description${faq.id}`}
                      data-bs-toggle="collapse"
                      data-bs-target={`#description${faq.id}`}
                      className="faq-title faq__trigger collapsed"
                    >
                      <h5>{faq.title}</h5>
                    </a>
                    <div
                      className="faq__target faq__target-js collapse"
                      data-bs-parent="#faqParentJs"
                      id={`description${faq.id}`}
                    >
                      <div className="faq-answer">
                        <div
                          className="editor-content"
                          dangerouslySetInnerHTML={{
                            __html: normalizeLegacyHtml(faq.description.replace(/\n/g, '<br />')),
                          }}
                        />
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>
      )}

      {data.contact_html.trim() && (
        <section className="section bg-gradiant section--contact-cta" data-aos="fade-up" data-aos-duration="1000">
          <div className="container container--narrow">
            <div dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(data.contact_html) }} />
          </div>
        </section>
      )}
    </>
  );
}
