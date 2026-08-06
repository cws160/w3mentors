$(document).ready(function () {
  /* COMMON TOGGLES */
  $(document).on("click", ".trigger-js", function (e) {
    e.preventDefault();
    $($(this).attr("href")).toggleClass("is-visible");
    $(this).toggleClass("is-active");
    $("html").toggleClass("is-toggle");
  });

  /* FOR FOOTER */
  if ($(window).width() < 767) {
    /* FOR FOOTER TOGGLES */
    $(".toggle-trigger-js").click(function () {
      if ($(this).hasClass("is-active")) {
        $(this).removeClass("is-active");
        $(this).siblings(".toggle-target-js").slideUp();
        return false;
      }
      $(".toggle-trigger-js").removeClass("is-active");
      $(this).addClass("is-active");
      $(".toggle-target-js").slideUp();
      $(this).siblings(".toggle-target-js").slideDown();
      var obj = $(this).siblings(".toggle-target-js");
      $("html, body").animate(
        {
          scrollTop: $(obj).offset().top,
        },
        800
      );
    });
  }
  $(".settings__trigger-js").click(function () {
    var t = $(this)
      .parents(".toggle-group")
      .children(".settings__target-js")
      .is(":hidden");
    $(".toggle-group .settings__target-js").hide();
    $(".toggle-group .settings__trigger-js").removeClass("is--active");
    if (t) {
      $(this)
        .parents(".toggle-group")
        .children(".settings__target-js")
        .toggle()
        .parents(".toggle-group")
        .children(".settings__trigger-js")
        .addClass("is--active");
    }
  });
  $(".toggle--nav-js").click(function () {
    $(this).toggleClass("is-active");
    $("html").toggleClass("show-nav-js");
    $("html").removeClass("show-dashboard-js");
  });

  $("#STICKY").on("scroll", function () {
    var scrollHeight = $(this).scrollTop() + $(this).outerHeight() + 200;
    if (scrollHeight >= $(this)[0].scrollHeight) {
      $("#STICKY").removeClass("scrolling");
    } else {
      $("#STICKY").addClass("scrolling");
    }
  });
});

/* FUNCTION FOR COMMON DROPDOWN */
jQuery(document).ready(function (e) {
  function t(t) {
    e(t).bind("click", function (t) {
      t.preventDefault();
      e(this).parent().fadeOut();
    });
  }
  e(document).bind("click", function (t) {
    var n = e(t.target);
    if (!n.parents().hasClass("toggle-group"))
      e(".toggle-group .toggle__target-js").hide();
  });
  e(document).bind("click", function (t) {
    var n = e(t.target);
    if (!n.parents().hasClass("toggle-group"))
      e(".toggle-group").removeClass("is-active");
  });
});
$.loader = {
  selector: ".loading-wrapper",
  show: function () {
    $(this.selector).show();
  },
  hide: function () {
    $(this.selector).hide();
  },
};
function updateStickyPosition() {
  if ($(window).width() >= 1200) {
    var headerHeight = $(".header").outerHeight();
    $("#STICKY").css("top", `calc(20px + ${headerHeight}px)`);
  } else {
    $("#STICKY").css("top", "");
  }
}

//After scroller ios keyboard close function start
// let lastScrollTop = 0;
// let ignoreBlur = false;

// // Detect when an input field is focused
// document.addEventListener("focusin", function (event) {
//     if (event.target.tagName === "INPUT" || event.target.tagName === "TEXTAREA") {
//         ignoreBlur = true;
//         setTimeout(() => {
//             ignoreBlur = false;
//         }, 500); // Small delay to prevent instant blur on tap
//     }
// });

// window.addEventListener("scroll", function () {
//     let currentScroll = window.scrollY;

//     if (!ignoreBlur && Math.abs(currentScroll - lastScrollTop) > 10) {
//         document.activeElement.blur();
//     }

//     lastScrollTop = currentScroll;
// });
