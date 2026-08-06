import payment1 from '../assets/payment-method/payment-1.svg';
import payment2 from '../assets/payment-method/payment-2.svg';
import payment3 from '../assets/payment-method/payment-3.svg';
import payment4 from '../assets/payment-method/payment-4.svg';

/** Footer payment badges — bundled so they work without the /images API proxy. */
export const FOOTER_PAYMENT_ICONS = [
  { src: payment1, alt: 'PayPal' },
  { src: payment2, alt: 'Visa' },
  { src: payment3, alt: 'Mastercard' },
  { src: payment4, alt: 'American Express' },
] as const;
