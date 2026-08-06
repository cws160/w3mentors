import axios from 'axios';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { contentApi } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { useSite } from '../context/SiteContext';
import { normalizeLegacyHtml, unwrapBootstrapColumn } from '../utils/legacyHtml';

export function W3MentorsContactPage() {
  const { lbl } = useSite();
  const { user } = useAuth();
  const [bannerHtml, setBannerHtml] = useState('');
  const [leftHtml, setLeftHtml] = useState('');
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const leftColumn = useMemo(() => unwrapBootstrapColumn(leftHtml), [leftHtml]);

  useEffect(() => {
    contentApi
      .contact()
      .then((res) => {
        setBannerHtml(res.data.banner_html ?? '');
        setLeftHtml(res.data.left_html ?? '');
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!user) {
      return;
    }
    setName((prev) => prev || user.full_name || `${user.first_name} ${user.last_name ?? ''}`.trim());
    setEmail((prev) => prev || user.email);
  }, [user]);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError('');
    setSuccess('');
    setSubmitting(true);

    try {
      const res = await contentApi.contactSubmit({
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim(),
        message: message.trim(),
      });
      setSuccess(
        res.data.message ??
          lbl('MSG_YOUR_MESSAGE_SENT_SUCCESSFULLY', 'Your message has been sent successfully.')
      );
      setName(user?.full_name || '');
      setPhone('');
      setEmail(user?.email || '');
      setMessage('');
    } catch (err) {
      if (axios.isAxiosError(err)) {
        const errors = err.response?.data?.errors as Record<string, string[]> | undefined;
        const apiMessage = err.response?.data?.message as string | undefined;
        const firstFieldError =
          errors?.name?.[0] ?? errors?.email?.[0] ?? errors?.phone?.[0] ?? errors?.message?.[0];
        setError(firstFieldError ?? apiMessage ?? lbl('LBL_Something_went_wrong', 'Something went wrong.'));
      } else {
        setError(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
      }
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) {
    return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  }

  return (
    <section className="section bg-grey">
      <div className="container container--fixed">
        {bannerHtml ? (
          <div dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(bannerHtml) }} />
        ) : null}
        <div className="contact-wrapper">
          <div className="row g-5 justify-content-around">
            {leftColumn.inner ? (
              <div className={leftColumn.columnClass}>
                <div dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(leftColumn.inner) }} />
              </div>
            ) : null}
            <div className="col-md-7 col-lg-6">
              <div className="contact-form">
                {success ? (
                  <p className="text-center color-primary">{success}</p>
                ) : (
                  <form className="form form--normal" id="frmContact" name="frmContact" onSubmit={onSubmit}>
                    <div className="row">
                      <div className="col-md-6">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl('LBL_Name', 'Name')} <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="text"
                                className="form-control"
                                name="name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
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
                              {lbl('LBL_Phone_no', 'Phone no.')} <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="text"
                                className="form-control"
                                name="phone"
                                value={phone}
                                onChange={(e) => setPhone(e.target.value)}
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
                            <label className="field_label">
                              {lbl('LBL_Email', 'Email')} <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="email"
                                className="form-control"
                                name="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
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
                            <label className="field_label">
                              {lbl('LBL_Message', 'Message')} <span className="spn_must_field">*</span>
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <textarea
                                className="form-control"
                                name="message"
                                rows={5}
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                required
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    {error ? (
                      <div className="row">
                        <div className="col-md-12">
                          <p className="color-red">{error}</p>
                        </div>
                      </div>
                    ) : null}
                    <div className="row">
                      <div className="col-md-12">
                        <div className="field-set">
                          <div className="field-wraper">
                            <div className="field_cover">
                              <input
                                type="submit"
                                name="btn_submit"
                                className="btn btn--primary"
                                value={
                                  submitting
                                    ? lbl('LBL_Loading', 'Loading...')
                                    : lbl('BTN_SUBMIT', 'Submit')
                                }
                                disabled={submitting}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </form>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
