/* global fcom */
(function () {
    checkPassword = function (str) {
        var re = /^(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z])(?=.*).{8,}$/;
        return re.test(str);
    };
    reset_password = function (frm, v) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl("adminGuest", "setupResetPassword"), fcom.frmData(frm), function (response) {
            setTimeout(function () {
                location.href = fcom.makeUrl('adminGuest', 'loginForm');
            }, 3000);
        });
        return false;
    };
    /* for sliding effect */
    if ($(window).width() > 1000)
        $('#moveleft').click(function () {
            $('.panels').animate({ 'marginLeft': "0" });
            $('.innerpanel').animate({ 'marginLeft': "100%" });
        });
    if ($(window).width() > 1000)
        $('#moveright').click(function () {
            $('.panels').animate({ 'marginLeft': "50%" });
            $('.innerpanel').animate({ 'marginLeft': "0" });
        });
    /* for mobile view slide */
    if ($(window).width() < 1000)
        $('.linkslide').click(function () {
            $(this).toggleClass("active");
            var el = $("body");
            if (el.hasClass('active-left')) {
                el.removeClass("active-left");
            } else {
                el.addClass('active-left');
            }
        });
    /* for forms elements */
    function floatLabel(inputType) {
        $(inputType).each(function () {
            var $this = $(this);
            var text_value = $(this).val();
            // on focus add class "active" to label
            $this.focus(function () {
                $this.closest('.field_control').addClass("active");
            });
            // on blur check field and remove class if needed
            $this.blur(function () {
                if ($this.val() === '' || $this.val() === 'blank') {
                    $this.closest('.field_control').removeClass('active');
                }
            });
            // Check input values on postback and add class "active" if value exists
            if (text_value != '') {
                $this.closest('.field_control').addClass("active");
            }
        });
    }
    // Add a class of "floatLabel" to the input field
    floatLabel(".form input[type='text'], .form input[type='password'], .form input[type='email'], .form select, .form textarea, .form input[type='file']");
    /* wave ripple effect */
    var parent, ink, d, x, y;
    $(".themebtn, .leftmenu > li > a, .actions > li > a, .leftlinks > li > a,.profilecover .profileinfo,.pagination li a, .circlebutton").click(function (e) {
        parent = $(this);
        //create .ink element if it doesn't exist
        if (parent.find(".ink").length == 0) {
            parent.prepend("<span class='ink'></span>");
        }
        ink = parent.find(".ink");
        //incase of quick double clicks stop the previous animation
        ink.removeClass("animate");
        //set size of .ink
        if (!ink.height() && !ink.width()) {
            //use parent's width or height whichever is larger for the diameter to make a circle which can cover the entire element.
            d = Math.max(parent.outerWidth(), parent.outerHeight());
            ink.css({ height: d, width: d });
        }
        //get click coordinates
        //logic = click coordinates relative to page - parent's position relative to page - half of self height/width to make it controllable from the center;
        x = e.pageX - parent.offset().left - ink.width() / 2;
        y = e.pageY - parent.offset().top - ink.height() / 2;
        //set the position and add class .animate
        ink.css({ top: y + 'px', left: x + 'px' }).addClass("animate");
    });
})(jQuery);