$(document).ready(function () {
    /* COMMON TOGGLES */
    var _body = $('html');
    var _toggle = $('.trigger-js');
    _toggle.each(function () {
        var _this = $(this), _target = $(_this.attr('href'));
        _this.on('click', function (e) {
            e.preventDefault();
            _target.toggleClass('is-visible');
            _this.toggleClass('is-active');
            _body.toggleClass('is-toggle');
        });
    });
    /* FOR FOOTER */
    if ($(window).width() < 767) {
        /* FOR FOOTER TOGGLES */
        $('.toggle-trigger-js').click(function () {
            if ($(this).hasClass('is-active')) {
                $(this).removeClass('is-active');
                $(this).siblings('.toggle-target-js').slideUp();
                return false;
            }
            $('.toggle-trigger-js').removeClass('is-active');
            $(this).addClass("is-active");
            $('.toggle-target-js').slideUp();
            $(this).siblings('.toggle-target-js').slideDown();
        });
    }
  
    $(".settings__trigger-js").click(function () {
        var t = $(this).parents(".toggle-group").children(".settings__target-js").is(":hidden");
        $(".toggle-group .settings__target-js").hide();
        $(".toggle-group .settings__trigger-js").removeClass("is--active");
        if (t) {
            $(this).parents(".toggle-group").children(".settings__target-js").toggle().parents(".toggle-group").children(".settings__trigger-js").addClass("is--active")
        }
    });
    $(".toggle--nav-js").click(function () {
        $(this).toggleClass("is-active");
        $('html').toggleClass("show-nav-js");
        $('html').removeClass("show-dashboard-js");
    });

});

/* FUNCTION FOR COMMON DROPDOWN */
jQuery(document).ready(function (e) {
    function t(t) {
        e(t).bind("click", function (t) {
            t.preventDefault();
            e(this).parent().fadeOut()
        })
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
    })
});
$.loader = {
    selector: '.loading-wrapper',
    show: function () {
        $(this.selector).show();
    },
    hide: function () {
        $(this.selector).hide();
    }
};