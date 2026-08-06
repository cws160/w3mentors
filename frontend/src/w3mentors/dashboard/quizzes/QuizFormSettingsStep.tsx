import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

export type QuizSettingsData = {
  id: number;
  duration: number;
  attempts: number;
  pass_percent: number;
  validity: number;
  certificate: number;
  fail_message: string;
  pass_message: string;
  offer_certificate: boolean;
};

export type QuizFormSettingsStepHandle = {
  save: () => Promise<boolean>;
};

type Props = {
  quizId: number;
  onSaved?: () => void;
};

/** Legacy dashboard/views/quizzes/setting.php */
export const QuizFormSettingsStep = forwardRef<QuizFormSettingsStepHandle, Props>(function QuizFormSettingsStep(
  { quizId, onSaved },
  ref
) {
  const { lbl } = useSite();
  const [form, setForm] = useState({
    duration: '',
    attempts: '',
    pass_percent: '',
    validity: '',
    certificate: '0',
    fail_message: '',
    pass_message: '',
  });
  const [offerCertificate, setOfferCertificate] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<{ data: QuizSettingsData }>(`/dashboard/quizzes/${quizId}/settings`)
      .then((res) => {
        const data = res.data.data;
        setOfferCertificate(data.offer_certificate);
        setForm({
          duration: data.duration ? String(data.duration) : '',
          attempts: data.attempts ? String(data.attempts) : '',
          pass_percent: data.pass_percent ? String(data.pass_percent) : '',
          validity: data.validity ? String(data.validity) : '',
          certificate: String(data.certificate ?? 0),
          fail_message: data.fail_message,
          pass_message: data.pass_message,
        });
      })
      .catch(() => setError(lbl('LBL_Something_went_wrong', 'Something went wrong.')))
      .finally(() => setLoading(false));
  }, [quizId, lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const save = useCallback(async (): Promise<boolean> => {
    setSaving(true);
    setError('');
    try {
      await api.post(`/dashboard/quizzes/${quizId}/settings`, {
        duration: form.duration ? Number(form.duration) : 0,
        attempts: Number(form.attempts),
        pass_percent: Number(form.pass_percent),
        validity: Number(form.validity),
        certificate: Number(form.certificate),
        fail_message: form.fail_message.trim(),
        pass_message: form.pass_message.trim(),
      });
      onSaved?.();
      return true;
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_Something_went_wrong', 'Something went wrong.');
      setError(msg);
      return false;
    } finally {
      setSaving(false);
    }
  }, [form, quizId, onSaved, lbl]);

  useImperativeHandle(ref, () => ({ save }), [save]);

  if (loading) {
    return <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  return (
    <div className="box-panel">
      <div className="box-panel__head border-bottom">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h4>{lbl('LBL_SETUP_QUIZ', 'Setup quiz')}</h4>
          </div>
        </div>
      </div>
      <div className="box-panel__body">
        <div className="box-panel__container">
          <div className="row">
            <div className="col-md-3">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">{lbl('LBL_DURATION_(IN_MINS)', 'Duration (in mins)')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <input
                      type="number"
                      className="form-control"
                      min={0}
                      value={form.duration}
                      onChange={(e) => setForm((f) => ({ ...f, duration: e.target.value }))}
                    />
                    <small>{lbl('LBL_LEAVE_EMPTY_IN_CASE_OF_NO_TIME_LIMIT', 'Leave empty for no time limit')}</small>
                  </div>
                </div>
              </div>
            </div>
            <div className="col-md-3">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">
                    {lbl('LBL_NO_OF_ATTEMPTS_ALLOWED', 'No. of attempts allowed')}
                  </label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <input
                      type="number"
                      className="form-control"
                      min={1}
                      max={10}
                      value={form.attempts}
                      onChange={(e) => setForm((f) => ({ ...f, attempts: e.target.value }))}
                    />
                  </div>
                </div>
              </div>
            </div>
            <div className="col-md-3">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">{lbl('LBL_PASS_PERCENTAGE', 'Pass percentage')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <input
                      type="number"
                      className="form-control"
                      min={1}
                      max={100}
                      value={form.pass_percent}
                      onChange={(e) => setForm((f) => ({ ...f, pass_percent: e.target.value }))}
                    />
                  </div>
                </div>
              </div>
            </div>
            <div className="col-md-3">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">{lbl('LBL_VALIDITY_(IN_HOURS)', 'Validity (in hours)')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <input
                      type="number"
                      className="form-control"
                      min={1}
                      value={form.validity}
                      onChange={(e) => setForm((f) => ({ ...f, validity: e.target.value }))}
                    />
                    <small>{lbl('LBL_QUIZ_VALIDITY_INSTRUCTIONS', 'Quiz validity instructions')}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">{lbl('LBL_FAIL_MESSAGE', 'Fail message')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <textarea
                      className="form-control"
                      rows={3}
                      value={form.fail_message}
                      onChange={(e) => setForm((f) => ({ ...f, fail_message: e.target.value }))}
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
                  <label className="field_label">{lbl('LBL_PASS_MESSAGE', 'Pass message')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <textarea
                      className="form-control"
                      rows={3}
                      value={form.pass_message}
                      onChange={(e) => setForm((f) => ({ ...f, pass_message: e.target.value }))}
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
                  <label className="field_label">{lbl('LBL_OFFER_CERTIFICATE', 'Offer certificate')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <ul className="list-inline">
                      {offerCertificate ? (
                        <>
                          <li>
                            <label>
                              <span className="radio">
                                <input
                                  type="radio"
                                  name="quiz_certificate"
                                  value="1"
                                  checked={form.certificate === '1'}
                                  onChange={() => setForm((f) => ({ ...f, certificate: '1' }))}
                                />
                                <i className="input-helper" />
                              </span>
                              {lbl('LBL_YES', 'Yes')}
                            </label>
                          </li>
                          <li>
                            <label>
                              <span className="radio">
                                <input
                                  type="radio"
                                  name="quiz_certificate"
                                  value="0"
                                  checked={form.certificate === '0'}
                                  onChange={() => setForm((f) => ({ ...f, certificate: '0' }))}
                                />
                                <i className="input-helper" />
                              </span>
                              {lbl('LBL_NO', 'No')}
                            </label>
                          </li>
                        </>
                      ) : (
                        <li>
                          <span className="color-secondary">{lbl('LBL_NO', 'No')}</span>
                        </li>
                      )}
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="row">
            <div className="col-md-12">
              <p className="color-third style-italic bold-600 font-small">
                {lbl('LBL_QUIZ_SETTINGS_NOTE', 'Quiz settings note')}
              </p>
            </div>
          </div>
          {error && <p className="text-danger small">{error}</p>}
          {saving && <p className="color-secondary small">{lbl('LBL_LOADING', 'Loading...')}</p>}
        </div>
      </div>
    </div>
  );
});
