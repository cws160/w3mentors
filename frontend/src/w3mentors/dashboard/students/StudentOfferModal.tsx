import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

type OfferFormData = {
  offpri_id: number;
  learner_id: number;
  learner_name: string;
  lesson_slots: number[];
  class_slots: number[];
  lesson_prices: Record<string, number>;
  class_prices: Record<string, number>;
  package_price: number | null;
};

type Props = {
  learnerId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function StudentOfferModal({ learnerId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [form, setForm] = useState<OfferFormData | null>(null);
  const [lessonPrices, setLessonPrices] = useState<Record<string, string>>({});
  const [classPrices, setClassPrices] = useState<Record<string, string>>({});
  const [packagePrice, setPackagePrice] = useState('');

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    api
      .get<{ data: OfferFormData }>(`/dashboard/students/${learnerId}/offer`)
      .then((res) => {
        const data = res.data.data;
        setForm(data);
        setLessonPrices(
          Object.fromEntries(
            data.lesson_slots.map((slot) => [String(slot), String(data.lesson_prices[String(slot)] ?? '')])
          )
        );
        setClassPrices(
          Object.fromEntries(
            data.class_slots.map((slot) => [String(slot), String(data.class_prices[String(slot)] ?? '')])
          )
        );
        setPackagePrice(data.package_price != null ? String(data.package_price) : '');
      })
      .catch(() => setError(lbl('LBL_Something_went_wrong', 'Something went wrong.')))
      .finally(() => setLoading(false));
  }, [learnerId, lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form) return;
    setSaving(true);
    setError('');
    try {
      await api.put(`/dashboard/students/${learnerId}/offer`, {
        lesson_prices: lessonPrices,
        class_prices: classPrices,
        package_price: packagePrice || null,
      });
      onSaved();
      onClose();
    } catch {
      setError(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    } finally {
      setSaving(false);
    }
  };

  const lessonSlotLabel = (slot: number) =>
    lbl('LBL_LESSON_{slot}_SLOT_OFFER(%)', `Lesson ${slot} slot offer(%)`).replace('{slot}', String(slot));

  const classSlotLabel = (slot: number) =>
    lbl('LBL_CLASS_{slot}_SLOT_OFFER(%)', `Class ${slot} slot offer(%)`).replace('{slot}', String(slot));

  return (
    <>
      <div className="modal-header">
        <h5 className="page-heading">
          {form
            ? lbl('LBL_OFFER_PERCENTAGE_FOR_%s', 'Offer percentage for %s').replace(
                '%s',
                form.learner_name
              )
            : lbl('LBL_OFFER_PRICE', 'Offer price')}
        </h5>
        <button type="button" className="btn-close w3mentorsmodalJs" aria-label="Close" onClick={onClose} />
      </div>
      <div className="modal-body">
        {loading ? (
          <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : !form ? (
          <p className="color-secondary">{error || lbl('LBL_INVALID_REQUEST', 'Invalid request')}</p>
        ) : (
          <form className="form" onSubmit={submit}>
            <fieldset className="fieldset-box">
              <legend>{lbl('LBL_LESSON_OFFER', 'Lesson offer')}</legend>
              <table className="table-pricing">
                <tbody>
                  {form.lesson_slots.map((slot) => (
                    <tr key={`lesson-${slot}`}>
                      <td width="70%">{lessonSlotLabel(slot)}</td>
                      <td>
                        <input
                          type="text"
                          className="form-control"
                          value={lessonPrices[String(slot)] ?? ''}
                          onChange={(e) =>
                            setLessonPrices((prev) => ({ ...prev, [String(slot)]: e.target.value }))
                          }
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </fieldset>
            <fieldset className="fieldset-box">
              <legend>{lbl('LBL_CLASS_OFFER', 'Class offer')}</legend>
              <table className="table-pricing">
                <tbody>
                  {form.class_slots.map((slot) => (
                    <tr key={`class-${slot}`}>
                      <td width="70%">{classSlotLabel(slot)}</td>
                      <td>
                        <input
                          type="text"
                          className="form-control"
                          value={classPrices[String(slot)] ?? ''}
                          onChange={(e) =>
                            setClassPrices((prev) => ({ ...prev, [String(slot)]: e.target.value }))
                          }
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </fieldset>
            <fieldset className="fieldset-box">
              <legend>{lbl('LBL_CLASS_PACKAGE_OFFER', 'Class package offer')}</legend>
              <table className="table-pricing">
                <tbody>
                  <tr>
                    <td width="70%">{lbl('LBL_CLASS_PACKAGES_OFFER(%)', 'Class packages offer(%)')}</td>
                    <td>
                      <input
                        type="text"
                        className="form-control"
                        value={packagePrice}
                        onChange={(e) => setPackagePrice(e.target.value)}
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </fieldset>
            {error && <p className="text-danger small">{error}</p>}
            <div className="row">
              <div className="fld_wrapper-js col-md-12">
                <div className="field-set mb-0">
                  <div className="field-wraper form-buttons-group">
                    <div className="field_cover">
                      <input
                        type="submit"
                        className="btn btn--primary"
                        disabled={saving}
                        value={lbl('LBL_SAVE', 'Save')}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        )}
      </div>
    </>
  );
}
