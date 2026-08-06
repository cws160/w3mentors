/**
 * Slick Carousel expects jQuery on window. Vite bundles jQuery as ESM; expose it globally
 * before loading slick (same as legacy frontend header scripts).
 */
import jQuery from 'jquery';

const $ = jQuery;

if (typeof window !== 'undefined') {
  window.jQuery = $;
  window.$ = $;
}

export { $, jQuery };

declare global {
  interface Window {
    jQuery: typeof jQuery;
    $: typeof jQuery;
  }
}
