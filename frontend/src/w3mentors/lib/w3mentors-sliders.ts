/* eslint-disable @typescript-eslint/no-explicit-any */
import './setup-jquery';
import 'slick-carousel';
import { $ } from './setup-jquery';

const SLIDER_SELECTOR =
  '.slider-onefifth-js, .slider-oneforth-js, .slider-onethird-js, .slider-onehalf-js, .slider-single-js, .js--testimonials, .js--testimonials-main, .js--testimonials-thumb';

function slickReady(): boolean {
  return typeof ($ as any).fn?.slick === 'function';
}

function hasSlides($el: JQuery): boolean {
  return $el.children().length > 0;
}

export function destroyW3MentorsSliders(root: ParentNode = document): void {
  if (!slickReady()) return;
  const $root = $(root as unknown as HTMLElement);
  $root.find('.slick-initialized').each(function destroy(this: HTMLElement) {
    try {
      ($(this) as any).slick('unslick');
    } catch {
      // already torn down
    }
  });
}

export function initW3MentorsSliders(root: ParentNode = document): void {
  if (!slickReady()) {
    console.warn('[w3mentors] Slick is not available on jQuery - sliders disabled.');
    return;
  }

  const $root = $(root as unknown as HTMLElement);

  const initIfNeeded = (selector: string, options: Record<string, unknown>) => {
    $root.find(`${selector}:not(.slick-initialized)`).each(function init(this: HTMLElement) {
      const $el = $(this);
      if (!hasSlides($el)) return;
      ($el as any).slick(options);
    });
  };

  initIfNeeded('.slider-onefifth-js', {
    centerPadding: '0px',
    slidesToShow: 5,
    slidesToScroll: 1,
    infinite: false,
    dots: false,
    responsive: [
      { breakpoint: 1200, settings: { slidesToShow: 4, arrows: false, dots: true } },
      { breakpoint: 992, settings: { slidesToShow: 3, arrows: false, dots: true } },
      { breakpoint: 768, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 480, settings: { slidesToShow: 1, arrows: false, dots: true } },
    ],
  });

  initIfNeeded('.slider-oneforth-js', {
    centerPadding: '0px',
    slidesToShow: 4,
    slidesToScroll: 1,
    dots: false,
    responsive: [
      { breakpoint: 1200, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 768, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 480, settings: { slidesToShow: 1, arrows: false, dots: true } },
    ],
  });

  initIfNeeded('.slider-onethird-js', {
    centerPadding: '0px',
    slidesToShow: 3,
    slidesToScroll: 1,
    dots: false,
    responsive: [
      { breakpoint: 1200, settings: { slidesToShow: 3, arrows: false, dots: true } },
      { breakpoint: 768, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 480, settings: { slidesToShow: 1, arrows: false, dots: true } },
    ],
  });

  initIfNeeded('.slider-onehalf-js', {
    centerPadding: '0px',
    slidesToShow: 2,
    slidesToScroll: 1,
    dots: false,
    responsive: [
      { breakpoint: 1200, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 768, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 580, settings: { slidesToShow: 1, arrows: false, dots: true } },
    ],
  });

  initIfNeeded('.slider-single-js', {
    dots: false,
    arrows: true,
    autoplay: true,
    adaptiveHeight: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    prevArrow: '<button type="button" class="slick-prev" aria-label="previous"></button>',
    nextArrow: '<button type="button" class="slick-next" aria-label="next"></button>',
  });

  initIfNeeded('.js--testimonials', {
    centerPadding: '0',
    centerMode: true,
    slidesToShow: 3,
    slidesToScroll: 1,
    dots: false,
    arrows: false,
    infinite: true,
    responsive: [
      { breakpoint: 1200, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 992, settings: { slidesToShow: 2, arrows: false, dots: true } },
      { breakpoint: 768, settings: { slidesToShow: 1, arrows: false, dots: true } },
      { breakpoint: 480, settings: { slidesToShow: 1, arrows: false, dots: true } },
    ],
  });

  const $thumb = $root.find('.js--testimonials-thumb');
  const testiLen = $thumb.find('.testimonial').length;
  if (testiLen > 0) {
    initIfNeeded('.js--testimonials-main', {
      slidesToShow: 1,
      slidesToScroll: 1,
      infinite: testiLen > 1,
      arrows: true,
      fade: true,
      asNavFor: '.js--testimonials-thumb',
      responsive: [{ breakpoint: 1200, settings: { arrows: false, dots: true } }],
    });
    initIfNeeded('.js--testimonials-thumb', {
      slidesToShow: Math.max(testiLen - 1, 1),
      asNavFor: '.js--testimonials-main',
      arrows: false,
      dots: false,
      infinite: testiLen > 1,
      centerMode: true,
      variableWidth: true,
      focusOnSelect: true,
      draggable: false,
      responsive: [{ breakpoint: 1200, settings: { slidesToShow: 1, variableWidth: false } }],
    });
  }

  // Sliders inside hidden category tabs need a layout pass when shown
  $root.find(`${SLIDER_SELECTOR}.slick-initialized`).each(function refresh(this: HTMLElement) {
    try {
      ($(this) as any).slick('setPosition');
    } catch {
      // ignore
    }
  });
}

export function refreshW3MentorsSliders(root: ParentNode = document): void {
  destroyW3MentorsSliders(root);
  initW3MentorsSliders(root);
}
