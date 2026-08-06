import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  teachersApi,
  type TeacherBookingOptions,
  type TeacherPricingLanguage,
} from '../../api/client';
import { useSite } from '../context/SiteContext';
import { useModal } from '../context/ModalContext';
import { useAuthModals } from '../hooks/useAuthModals';
import { formatMoney } from '../utils/assets';
import { TeacherBookCalendarStep } from './TeacherBookCalendarStep';

const LESSON_TYPE_REGULAR = 2;
const LESSON_TYPE_SUBSCRIPTION = 3;

type Props = {
  slugOrId: string;
  initialTlangId?: number;
  initialDuration?: number;
};

export function TeacherBookModalContent({
  slugOrId,
  initialTlangId,
  initialDuration,
}: Props) {
  const { lbl, languages } = useSite();
  const { closeModal } = useModal();
  const { openLoginModal } = useAuthModals();
  const [options, setOptions] = useState<TeacherBookingOptions | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [tlangId, setTlangId] = useState(0);
  const [duration, setDuration] = useState(0);
  const [quantity, setQuantity] = useState(1);
  const [ordlesType, setOrdlesType] = useState(LESSON_TYPE_REGULAR);
  const [ordlesOffline, setOrdlesOffline] = useState(0);
  const [ordlesAddressId, setOrdlesAddressId] = useState(0);
  const [step, setStep] = useState<'lang' | 'calendar'>('lang');

  const loadOptions = useCallback(() => {
    setLoading(true);
    setError('');
    const params: Record<string, number> = {
      lang_id: languages[0]?.id ?? 1,
    };
    if (initialTlangId) params.ordles_tlang_id = initialTlangId;
    if (initialDuration) params.ordles_duration = initialDuration;
    if (tlangId) params.ordles_tlang_id = tlangId;
    if (duration) params.ordles_duration = duration;

    teachersApi
      .bookingOptions(slugOrId, params)
      .then((res) => {
        const data = res.data.data;
        setOptions(data);
        setTlangId(data.defaults.ordles_tlang_id);
        setDuration(data.defaults.ordles_duration);
        setQuantity(data.defaults.ordles_quantity);
        setOrdlesType(data.defaults.ordles_type);
        setOrdlesOffline(data.defaults.ordles_offline);
        setOrdlesAddressId(data.defaults.ordles_address_id);
      })
      .catch((err: { response?: { status?: number } }) => {
        if (err.response?.status === 401) {
          closeModal();
          openLoginModal();
          return;
        }
        setError(lbl('LBL_Something_went_wrong', 'Unable to load booking options.'));
      })
      .finally(() => setLoading(false));
  }, [
    slugOrId,
    initialTlangId,
    initialDuration,
    tlangId,
    duration,
    lbl,
    languages,
    closeModal,
    openLoginModal,
  ]);

  useEffect(() => {
    loadOptions();
  }, []);

  const activeLang: TeacherPricingLanguage | undefined = useMemo(
    () => options?.languages.find((l) => l.id === tlangId),
    [options, tlangId]
  );

  const durationOptions = useMemo(() => {
    if (!activeLang) return [];
    return activeLang.slots;
  }, [activeLang]);

  const totalPrice = useMemo(() => {
    if (!activeLang || !duration) return 0;
    const unit = activeLang.prices[duration] ?? 0;
    return unit * quantity;
  }, [activeLang, duration, quantity]);

  const onLanguageChange = (id: number) => {
    setTlangId(id);
    const lang = options?.languages.find((l) => l.id === id);
    if (lang?.slots.length) {
      setDuration(lang.slots[0]);
    }
  };

  const goToCalendar = () => setStep('calendar');

  if (loading) {
    return (
      <div className="modal-body p-4 text-center">
        {lbl('LBL_Loading', 'Loading...')}
      </div>
    );
  }

  if (error || !options) {
    return (
      <div className="modal-body p-4">
        <p>{error || lbl('LBL_INVALID_REQUEST', 'Invalid request')}</p>
        <button type="button" className="btn btn--secondary" onClick={closeModal}>
          {lbl('LBL_Close', 'Close')}
        </button>
      </div>
    );
  }

  if (step === 'calendar') {
    return (
      <TeacherBookCalendarStep
        slugOrId={slugOrId}
        quantity={quantity}
        duration={duration}
        ordlesType={ordlesType}
        ordlesOffline={ordlesOffline}
        ordlesAddressId={ordlesAddressId}
        totalPrice={totalPrice}
        onBack={() => setStep('lang')}
      />
    );
  }

  const minQty = 1;
  const maxQty = 99;
  const subWeeks = options.subscription_weeks;
  const repeatOnText = `${lbl('LBL_REPEAT_ON', 'Repeat on')} ${lbl('LBL_EVERY_{NUMBER}_WEEKS', 'Every {number} weeks').replace('{number}', String(subWeeks))}`;

  const updateQuantity = (delta: '-' | '+') => {
    setQuantity((q) => {
      const next = delta === '+' ? q + 1 : q - 1;
      return Math.min(maxQty, Math.max(minQty, next));
    });
  };

  return (
    <>
      <div className="modal-header modal-header--checkout">
        <h4 className="flex-1 text-center">
          {lbl('LBL_SELECT_LANGUAGE_AND_DURATION', 'Select subject and duration')}
        </h4>
        <button
          type="button"
          className="btn-close w3mentorsmodalJs close-checkout-modal"
          data-bs-dismiss="modal"
          aria-label=""
          onClick={closeModal}
        />
      </div>
      <div className="modal-body p-0">
        <div className="chekout-form">
          <form className="form form--checkout" action="javascript:void(0);" onSubmit={(e) => e.preventDefault()}>
            <div className="row">
              <div className="col-md-6">
                <div className="form-group">
                  <select
                    className="form-control"
                    id="ordles_tlang_id"
                    name="ordles_tlang_id"
                    value={tlangId}
                    onChange={(e) => onLanguageChange(Number(e.target.value))}
                  >
                    {options.languages.map((lang) => (
                      <option key={lang.id} value={lang.id}>
                        {lang.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
              <div className="col-md-6">
                <div className="form-group">
                  <select
                    className="form-control"
                    id="ordles_duration"
                    name="ordles_duration"
                    value={duration}
                    onChange={(e) => setDuration(Number(e.target.value))}
                  >
                    {durationOptions.map((slot) => (
                      <option key={slot} value={slot}>
                        {lbl('LBL_{slot}_MINUTE_LESSON', '{slot} minute lesson').replace(
                          '{slot}',
                          String(slot)
                        )}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>
            <div className="row justify-content-center g-4">
              <div className="col-md-4">
                <div className="form-group">
                  <div className="cart-qty form-control">
                    <button
                      type="button"
                      className="cart-qty__update decrease"
                      onClick={() => updateQuantity('-')}
                    />
                    <input
                      className="cart-qty__value"
                      type="text"
                      name="ordles_quantity"
                      min={minQty}
                      max={maxQty}
                      value={quantity}
                      readOnly
                    />
                    <button
                      type="button"
                      className="cart-qty__update increase"
                      onClick={() => updateQuantity('+')}
                    />
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="form-group">
                  <label className="selector-switch__control">
                    <span className="selector-switch__label">
                      {lbl('LBL_RECURRING_BUY', 'Recurring buy')}
                    </span>
                    <span className="selector-switch__action">
                      <span className="switch switch--small">
                        <input
                          className="switch__label"
                          type="checkbox"
                          name="ordles_type"
                          value={LESSON_TYPE_SUBSCRIPTION}
                          checked={ordlesType === LESSON_TYPE_SUBSCRIPTION}
                          onChange={(e) =>
                            setOrdlesType(
                              e.target.checked
                                ? LESSON_TYPE_SUBSCRIPTION
                                : LESSON_TYPE_REGULAR
                            )
                          }
                        />
                        <i className="switch__handle bg-green" />
                      </span>
                    </span>
                  </label>
                  <span className="selector-switch__info">
                    {repeatOnText}
                    <span className="selector-switch__info-media is-hover">
                      <svg
                        className="icon icon--small"
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 16 16"
                      >
                        <path d="M8,15a7,7,0,1,1,7-7,7,7,0,0,1-7,7m0,1A8,8,0,1,0,0,8a8,8,0,0,0,8,8" />
                        <path d="M8.93,6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738,3.468c-.194.9.105,1.319.808,1.319A2.071,2.071,0,0,0,8.831,12l.088-.416a1.108,1.108,0,0,1-.686.246c-.275,0-.375-.193-.3-.533ZM9,4.5a1,1,0,1,1-1-1,1,1,0,0,1,1,1" />
                      </svg>
                      <div className="tooltip tooltip--top bg-black">
                        {lbl(
                          'LBL_SUBSCRIPTION_HELP_TEXT',
                          'Upon enabling, the lessons you are selecting will be auto booked/Scheduled every 4 weeks. Payment will be auto debited from the wallet balance.'
                        )}
                      </div>
                    </span>
                  </span>
                </div>
              </div>
              {options.offline_sessions_enabled && (
                <div className="col-md-4">
                  <div className="form-group">
                    <label className="selector-switch__control">
                      <span className="selector-switch__label">
                        {lbl('LBL_OFFLINE_LESSON', 'Offline lesson')}
                      </span>
                      <span className="selector-switch__action">
                        <span className="switch switch--small">
                          <input
                            className="switch__label"
                            type="checkbox"
                            name="ordles_offline"
                            value={1}
                            checked={ordlesOffline === 1}
                            onChange={(e) => {
                              const checked = e.target.checked;
                              setOrdlesOffline(checked ? 1 : 0);
                              if (checked && options.default_address?.id) {
                                setOrdlesAddressId(options.default_address.id);
                              } else if (!checked) {
                                setOrdlesAddressId(0);
                              }
                            }}
                          />
                          <i className="switch__handle bg-green" />
                        </span>
                      </span>
                    </label>
                    <span className="selector-switch__info">
                      {lbl('LBL_SEE_ADDRESS_INFO', 'See address info')}
                      <span className="selector-switch__info-media is-hover">
                        <svg
                          className="icon icon--small"
                          xmlns="http://www.w3.org/2000/svg"
                          width="16"
                          height="16"
                          viewBox="0 0 16 16"
                        >
                          <path d="M8,15a7,7,0,1,1,7-7,7,7,0,0,1-7,7m0,1A8,8,0,1,0,0,8a8,8,0,0,0,8,8" />
                          <path d="M8.93,6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738,3.468c-.194.9.105,1.319.808,1.319A2.071,2.071,0,0,0,8.831,12l.088-.416a1.108,1.108,0,0,1-.686.246c-.275,0-.375-.193-.3-.533ZM9,4.5a1,1,0,1,1-1-1,1,1,0,0,1,1,1" />
                        </svg>
                        <div className="tooltip tooltip--top bg-black">
                          {options.default_address?.formatted ?? ''}
                        </div>
                      </span>
                    </span>
                  </div>
                </div>
              )}
            </div>
          </form>
        </div>
      </div>
      <div className="modal-footer">
        <div className="row justify-content-center align-items-center gap-md-5">
          <div className="col-auto">
            <div className="cart-price">
              <span className="cart-price__label">
                {lbl('LBL_TOTAL_PRICE', 'Total price')} :
              </span>
              <span className="cart-price__value" id="price-js">
                {formatMoney(totalPrice)}
              </span>
            </div>
          </div>
          <div className="col-auto">
            <button
              type="button"
              className="btn btn--primary color-white"
              onClick={goToCalendar}
            >
              {lbl('LBL_NEXT', 'Next')}
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
