/* eslint-disable @typescript-eslint/no-explicit-any */
import './setup-jquery';
import { $ } from './setup-jquery';

let bound = false;

/** Mobile nav, footer accordions, and trigger panels (legacy common_ui_functions.js). */
export function bindW3MentorsUiHandlers(): void {
  if (bound || typeof window === 'undefined') return;
  bound = true;

  $(document).on('click', '.trigger-js', function (e) {
    if ($(this).closest('.site').length && $('body').is('.dashboard-teacher, .dashboard-learner')) {
      return;
    }
    e.preventDefault();
    const href = $(this).attr('href');
    if (href) $(href).toggleClass('is-visible');
    $(this).toggleClass('is-active');
    $('html').toggleClass('is-toggle');
  });

  $(document).on('click', '.toggle--nav-js', function () {
    $(this).toggleClass('is-active');
    $('html').toggleClass('show-nav-js');
    $('html').removeClass('show-dashboard-js');
  });

  $(document).on('click', '.toggle-trigger-js', function () {
    if (window.innerWidth >= 767) return;
    const $trigger = $(this);
    if ($trigger.hasClass('is-active')) {
      $trigger.removeClass('is-active');
      $trigger.siblings('.toggle-target-js').slideUp();
      return;
    }
    $('.toggle-trigger-js').removeClass('is-active');
    $trigger.addClass('is-active');
    $('.toggle-target-js').slideUp();
    $trigger.siblings('.toggle-target-js').slideDown();
  });
}

/** Course detail tabs (legacy application/views/courses/page-js/view.js). */
export function bindCourseDetailPage(): () => void {
  const updateStickyPosition = () => {
    if ($(window).width()! >= 576) {
      const headerHeight = $('.header').outerHeight() ?? 0;
      $('#TAB-STICKY').css('top', `${headerHeight}px`);
    } else {
      $('#TAB-STICKY').css('top', '');
    }
  };

  const checkActiveSection = () => {
    const fromTop = $(window).scrollTop() ?? 0;
    $('.panels-container-js .panel-content-js').each(function (this: HTMLElement) {
      const offsetTop = $(this).offset()?.top ?? 0;
      if (offsetTop - 120 <= fromTop) {
        $('.page-nav-js li').removeClass('is-active');
        $('.page-nav-js li[data-id="' + $(this).data('id') + '"]').addClass('is-active');
      }
    });
  };

  const onNavClick = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    const idSectionGoto = $(this).closest('li').data('id');
    const $target = $('.panels-container-js .panel-content-js[data-id="' + idSectionGoto + '"]');
    if ($target.length) {
      $('html, body').stop().animate({ scrollTop: ($target.offset()?.top ?? 0) - 100 }, 300);
    }
    $('.page-nav-js li').removeClass('is-active');
    $('.page-nav-js li[data-id="' + idSectionGoto + '"]').addClass('is-active');
    e.preventDefault();
  };

  const onPanelTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $panel = $(this).parents('.panel-content-js');
    if ($panel.hasClass('is-active')) {
      $(this).siblings('.panel-target-js').slideUp();
      $('.panel-content-js').removeClass('is-active');
    } else {
      $('.panel-content-js').removeClass('is-active');
      $panel.addClass('is-active');
      $('.panel-target-js').slideUp();
      $(this).siblings('.panel-target-js').slideDown();
    }
  };

  $(document).on('click', '.page-nav-js li a', onNavClick);
  $(window).on('scroll.courseDetail', checkActiveSection);
  $(window).on('resize.courseDetail', updateStickyPosition);

  if ($(window).width()! < 576) {
    $('.panel-target-js').hide();
    $(document).on('click', '.panel-trigger-js', onPanelTrigger);
  }

  updateStickyPosition();
  checkActiveSection();

  return () => {
    $(document).off('click', '.page-nav-js li a', onNavClick);
    $(document).off('click', '.panel-trigger-js', onPanelTrigger);
    $(window).off('scroll.courseDetail', checkActiveSection);
    $(window).off('resize.courseDetail', updateStickyPosition);
    $('#TAB-STICKY').css('top', '');
  };
}

/** Pricing table duration toggle (legacy teachers/page-js/view.js `changeSlot`). */
export function changeTeacherPricingSlot(): void {
  const slot = $('select[name="selected_slot"]').val();
  $('.trigger-checkout').css('display', 'none');
  $(`.slot-change-${slot}`).css('display', 'block');
}

/** Teacher profile panels + qualification tabs (legacy teachers/page-js/view.js). */
export function bindTeacherDetailPage(): () => void {
  const onPanelTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $head = $(this);
    const $section = $head.closest('.section--profile');
    if ($head.hasClass('is-active')) {
      $head.removeClass('is-active');
      $head.siblings('.panel__body-target-js').slideUp();
      return;
    }
    $section.find('.panel__head-trigger-js').removeClass('is-active');
    $head.addClass('is-active');
    $section.find('.panel__body-target-js').slideUp();
    $head.siblings('.panel__body-target-js').slideDown();
    const $slider = $head.siblings('.panel__body-target-js').find('.slick-slider');
    if ($slider.length > 0) {
      try {
        ($slider as any).slick('refresh');
      } catch {
        // not initialized yet
      }
    }
  };

  const onQualTabClick = function (this: HTMLAnchorElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $clicked = $(this);
    const $tab = $clicked.closest('.js--tabs');
    const $triggers = $tab.find('a');
    const href = $clicked.attr('href') ?? '';
    $triggers.removeClass('current');
    $tab.closest('.box-panel').find('.row--resume').removeClass('visible');
    $clicked.addClass('current');
    $(href).addClass('visible');
  };

  $(document).on('click.teacherQualTab', '.js--tabs a', onQualTabClick);

  (window as Window & { changeSlot?: () => void }).changeSlot = changeTeacherPricingSlot;
  $(document).on('change.teacherPricingSlot', '#selected_slot', changeTeacherPricingSlot);
  if ($('#selected_slot').length) {
    changeTeacherPricingSlot();
  }

  const $profilePanels = $('.section--profile .panel__body-target-js');
  if ($(window).width()! < 767) {
    $profilePanels.hide();
    $('.section--profile .panel-cover__head.panel__head-trigger-js.is-active')
      .siblings('.panel__body-target-js')
      .show();
    $(document).on('click', '.section--profile .panel__head-trigger-js', onPanelTrigger);
  } else {
    $profilePanels.show();
  }

  return () => {
    $(document).off('click', '.section--profile .panel__head-trigger-js', onPanelTrigger);
    $(document).off('click.teacherQualTab', '.js--tabs a', onQualTabClick);
    $(document).off('change.teacherPricingSlot', '#selected_slot', changeTeacherPricingSlot);
    delete (window as Window & { changeSlot?: () => void }).changeSlot;
    $('.section--profile .panel__body-target-js').show();
  };
}

/** Apply to teach hero form (legacy teacher-request/page-js/index.js). */
export function bindApplyToTeachPage(): () => void {
  const onScrollSection = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const target = $(this).attr('href') || $(this).data('href');
    if (!target) return;
    const $el = $(target);
    if (!$el.length) return;
    $('html, body').animate({ scrollTop: ($el.offset()?.top ?? 0) - 150 }, 500);
  };

  const onPasswordToggle = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $fld = $('input[name="user_password"]');
    if ($fld.attr('type') === 'password') {
      $fld.attr('type', 'text');
      $('#show-password').show();
      $('#hide-password').hide();
    } else {
      $fld.attr('type', 'password');
      $('#hide-password').show();
      $('#show-password').hide();
    }
  };

  $(document).on('click.applyTeachScroll', '.scroll-section-js', onScrollSection);
  $(document).on('click.applyTeachPwd', '#show-password, #hide-password', onPasswordToggle);

  return () => {
    $(document).off('click.applyTeachScroll', '.scroll-section-js', onScrollSection);
    $(document).off('click.applyTeachPwd', '#show-password, #hide-password', onPasswordToggle);
  };
}

/** Blog listing + detail (legacy blog/index.php, post-detail.php). */
export function bindBlogPage(): () => void {
  const onBlogToggle = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    $(this).toggleClass('is-active');
    $('html').toggleClass('show-categories-js');
  };

  const onCateTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $trigger = $(this);
    if ($trigger.hasClass('is-active')) {
      $trigger.removeClass('is-active');
      $trigger.siblings('.cate-target-js').slideUp();
      return;
    }
    $('.cate-trigger-js').removeClass('is-active');
    $trigger.addClass('is-active');
    $('.cate-target-js').slideUp();
    $trigger.siblings('.cate-target-js').slideDown();
  };

  const onNavDropdownTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $trigger = $(this);
    if ($trigger.hasClass('is-active')) {
      $trigger.removeClass('is-active');
      $trigger.siblings('.nav-dropdown-target-js').slideUp();
      return;
    }
    $('.nav-dropdown-trigger-js').removeClass('is-active');
    $trigger.addClass('is-active');
    $('.nav-dropdown-target-js').slideUp();
    $trigger.siblings('.nav-dropdown-target-js').slideDown();
  };

  const onGotoComments = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const target = $(this).attr('data-target');
    if (!target) return;
    const $el = $(target);
    if ($el.length) {
      $('html, body').animate({ scrollTop: ($el.offset()?.top ?? 0) - 100 }, 1000);
    }
  };

  $(document).on('click.blogToggle', '.blog-toggle-js', onBlogToggle);
  $(document).on('click.blogCate', '.cate-trigger-js', onCateTrigger);
  $(document).on('click.blogNavDrop', '.nav-dropdown-trigger-js', onNavDropdownTrigger);
  $(document).on('click.blogGotoComments', '.goto-comments-js', onGotoComments);

  return () => {
    $(document).off('click.blogToggle', '.blog-toggle-js', onBlogToggle);
    $(document).off('click.blogCate', '.cate-trigger-js', onCateTrigger);
    $(document).off('click.blogNavDrop', '.nav-dropdown-trigger-js', onNavDropdownTrigger);
    $(document).off('click.blogGotoComments', '.goto-comments-js', onGotoComments);
    $('html').removeClass('show-categories-js');
  };
}

/** About Us / CMS layout 1 (legacy cms/view.php inline scripts). */
export function bindAboutPage(): () => void {
  const onTabClick = function (this: HTMLAnchorElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $clicked = $(this);
    const $tab = $clicked.closest('.js-tab');
    const href = $clicked.attr('href') ?? '';
    $tab.find('a').removeClass('current');
    $tab.closest('.section--HowItWorks, .section').find('.tab-container').removeClass('visible');
    $clicked.addClass('current');
    $(href).addClass('visible');
  };

  const onTestiPrev = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $slider = $(this).closest('.section').find('.js--testimonials.slick-initialized');
    if ($slider.length) {
      try {
        ($slider as any).slick('slickPrev');
      } catch {
        // not ready
      }
    }
  };

  const onTestiNext = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $slider = $(this).closest('.section').find('.js--testimonials.slick-initialized');
    if ($slider.length) {
      try {
        ($slider as any).slick('slickNext');
      } catch {
        // not ready
      }
    }
  };

  const onPlayVideo = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const src = $(this).attr('data-src');
    if (!src) return;
    const html = `<div class="modal-header"><h5>Video</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="videowrap ratio ratio-16x9"><iframe width="100%" height="100%" src="${src}" title="Video" allow="autoplay; encrypted-media" allowfullscreen></iframe></div></div>`;
    const $modal = $(`<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">${html}</div></div></div>`);
    $('body').append($modal);
    const el = $modal[0];
    if (el && typeof (window as any).bootstrap?.Modal === 'function') {
      const instance = new (window as any).bootstrap.Modal(el);
      $modal.on('hidden.bs.modal', () => {
        instance.dispose();
        $modal.remove();
      });
      instance.show();
    } else {
      window.open(src, '_blank', 'noopener');
      $modal.remove();
    }
  };

  $(document).on('click.aboutTab', '.js-tab a', onTabClick);
  $(document).on('click.aboutTestiPrev', '.slider-nav .prev-slide', onTestiPrev);
  $(document).on('click.aboutTestiNext', '.slider-nav .next-slide', onTestiNext);
  $(document).on('click.aboutVideo', '.play-video', onPlayVideo);

  return () => {
    $(document).off('click.aboutTab', '.js-tab a', onTabClick);
    $(document).off('click.aboutTestiPrev', '.slider-nav .prev-slide', onTestiPrev);
    $(document).off('click.aboutTestiNext', '.slider-nav .next-slide', onTestiNext);
    $(document).off('click.aboutVideo', '.play-video', onPlayVideo);
  };
}

/** Forum question detail (legacy forum/page-js/view.js). */
export function bindForumDetailPage(): () => void {
  const onGotoComments = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const target = $(this).attr('data-target');
    if (!target) return;
    const $el = $(target);
    if ($el.length) {
      $('html, body').animate({ scrollTop: ($el.offset()?.top ?? 0) - 100 }, 300);
    }
  };

  const onCommentTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const $trigger = $(this);
    if ($trigger.hasClass('is-active')) {
      $trigger.removeClass('is-active');
      $trigger.siblings('.comment-target-js').hide();
      return;
    }
    $('.comment-trigger-js').removeClass('is-active');
    $trigger.addClass('is-active');
    $('.comment-target-js').hide();
    $trigger.siblings('.comment-target-js').show();
    $trigger.siblings('.comment-target-js').find('textarea').trigger('focus');
  };

  const onSortingTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    $(this).siblings('.sorting-target-js').toggle();
  };

  const onShareTrigger = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const href = $(this).attr('href');
    if (href) $(href).toggle();
  };

  $(document).on('click.forumGoto', '.goto-comments-js', onGotoComments);
  $(document).on('click.forumComment', '.comment-trigger-js', onCommentTrigger);
  $(document).on('click.forumSort', '.sorting-trigger-js', onSortingTrigger);
  $(document).on('click.forumShare', '.share__trigger.trigger-js', onShareTrigger);

  $('.comment-target-js').hide();

  return () => {
    $(document).off('click.forumGoto', '.goto-comments-js', onGotoComments);
    $(document).off('click.forumComment', '.comment-trigger-js', onCommentTrigger);
    $(document).off('click.forumSort', '.sorting-trigger-js', onSortingTrigger);
    $(document).off('click.forumShare', '.share__trigger.trigger-js', onShareTrigger);
  };
}

/** Dashboard sidebar height + toggle handlers (legacy dashboard/views/js/common_ui_functions.js). */
export function bindDashboardUiHandlers(): (() => void) | undefined {
  if (typeof window === 'undefined') return undefined;

  const $scope = $('.site');

  const resizeSidebar = () => {
    const headH = $scope.find('.sidebar__head').innerHeight() ?? 0;
    $scope.find('.sidebar__body').css('height', `calc(100% - ${headH}px)`);
  };

  resizeSidebar();
  $(window).on('resize.dashboardSidebar', resizeSidebar);

  const onTriggerClick = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const href = $(this).attr('href');
    if (!href) return;
    const $target = $scope.find(href);
    $target.toggleClass('is-visible');
    $(this).toggleClass('is-active');
    $('html').toggleClass('is-toggle');
  };

  const onFullviewClick = function (this: HTMLElement, e: JQuery.TriggeredEvent) {
    e.preventDefault();
    const href = $(this).attr('href');
    if (!href) return;
    const $target = $scope.find(href);
    $target.toggleClass('is-visible');
    $(this).toggleClass('is-active');
    $('html').toggleClass('is-fullview');
  };

  $scope.on('click.dashboardTrigger', '.trigger-js', onTriggerClick);
  $scope.on('click.dashboardFullview', '.fullview-js', onFullviewClick);

  return () => {
    $(window).off('resize.dashboardSidebar', resizeSidebar);
    $scope.off('click.dashboardTrigger', '.trigger-js', onTriggerClick);
    $scope.off('click.dashboardFullview', '.fullview-js', onFullviewClick);
  };
}
