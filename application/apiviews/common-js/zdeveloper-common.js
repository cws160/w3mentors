/* global fcom, confWebRootUrl, SslUsed, jstz, langLbl, searchfavorites, confWebDashUrl, ALERT_CLOSE_TIME */
function isJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
var newsletterAjaxRuning = false;
$(document).ready(function () {
    setUpJsTabs();
    setUpGoToTop();
    toggleHeaderCurrencyLanguageForDevices();
    toggleFooterCurrencyLanguage();
    if ($.datepicker) {
        var old_goToToday = $.datepicker._gotoToday
        $.datepicker._gotoToday = function (id) {
            old_goToToday.call(this, id);
            this._selectDate(id);
            $(id).blur();
            return;
        }
    }
});
(function ($) {
    setUpJsTabs = function () {
        $(".tabs-content-js").hide();
        $(".tabs-js li:first").addClass("is-active").show();
        $(".tabs-content-js:first").show();
    };
    setUpGoToTop = function () {
        $(window).scroll(function () {
            if ($(this).scrollTop() > 100) {
                $('.scroll-top-js').addClass("isvisible");
            } else {
                $('.scroll-top-js').removeClass("isvisible");
            }
        });
        $(".scroll-top-js").click(function () {
            $('body,html').animate({
                scrollTop: 0
            }, 800);
            return false;
        });
    };
    setUpStickyHeader = function () {
        if ($(window).width() > 767) {
            $(window).scroll(function () {
                body_height = $(".body").position();
                scroll_position = $(window).scrollTop();
                if (body_height.top < scroll_position) {
                    $(".header").addClass("is-fixed");
                } else {
                    $(".header").removeClass("is-fixed");
                }
            });
        }
    };
    jQuery(document).ready(function (e) {
        function t(t) {
            e(t).bind("click", function (t) {
                t.preventDefault();
                e(this).parent().fadeOut()
            })
        }
        $(".tabs-content-js").hide();
        $(".tabs-js li:first").addClass("is-active").show();
        $(".tabs-content-js:first").show();
        $(".tabs-js li").click(function () {
            $(".tabs-js li").removeClass("is-active");
            $(this).addClass("is-active");
            $(".tabs-content-js").hide();
            var activeTab = $(this).data("href");
            $(activeTab).fadeIn();
            return true;
        });
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
    signinForm = function () {
        fcom.process();
        fcom.ajax(fcom.makeUrl('GuestUser', 'loginForm'), 'isPopUp=' + 1, function (response) {
            $.w3mentorsmodal(response,{'size':'modal-lg'});
        });
    };
    signinSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.process();
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'signinSetup'), fcom.frmData(frm), function (res) {
            if (res.redirectUrl) {
                setTimeout(() => {
                    window.location.href = res.redirectUrl;
                }, 1000);
                return;
            }
        });
    };
    signupForm = function () {
        fcom.process();
        fcom.ajax(fcom.makeUrl('GuestUser', 'signupForm'), '', function (response) {
            $.w3mentorsmodal(response,{'size':'modal-lg'});
        });
    };
    signupSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'signupSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            frm.reset();
            setTimeout(function () {
                (res.redirectUrl) ?  window.location.href = res.redirectUrl :  window.location.reload();  
            }, ALERT_CLOSE_TIME * 1000);
        });
    };
    toggleHeaderCurrencyLanguageForDevices = function () {
        $('.nav__item-settings-js').click(function () {
            $(this).toggleClass("is-active");
            $('html').toggleClass("show-setting-js");
        });
    };
    toggleFooterCurrencyLanguage = function () {
        $(".toggle-footer-lang-currency-js").click(function () {
            var clickedSectionClass = $(this).siblings(".listing-div-js").attr("div-for");
            $(".toggle-footer-lang-currency-js").each(function () {
                if ($(this).siblings(".listing-div-js").attr("div-for") != clickedSectionClass) {
                    $(this).siblings(".listing-div-js").hide();
                }
            });
            $(this).siblings(".listing-div-js").slideToggle();
        });
    };
    setSiteLanguage = function (langId) {
        var data = {langId: langId, url: window.location.pathname};
        fcom.updateWithAjax(fcom.makeUrl('CookieConsent', 'setSiteLanguage'), data, function (res) {
            window.location.href = res.url;
        });
    };
    setSiteCurrency = function (currencyId) {
        fcom.updateWithAjax(fcom.makeUrl('CookieConsent', 'setSiteCurrency', [currencyId]), '', function (res) {
            document.location.reload();
        });
    };
    resendSignupVerifyEmail = function (email) {
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'resendSignupVerifyEmail'), {email: email});
    };
    togglePassword = function (e) {
        var passType = $("input[name='user_password']").attr("type");
        if (passType == "text") {
            $("input[name='user_password']").attr("type", "password");
            $(e).html($(e).attr("data-show-caption"));
        } else {
            $("input[name='user_password']").attr("type", "text");
            $(e).html($(e).attr("data-hide-caption"));
        }
    };
    toggleLoginPassword = function (e) {
        var passType = $("input[name='password']").attr("type");
        if (passType == "text") {
            $("input[name='password']").attr("type", "password");
            $(e).html($(e).attr("data-show-caption"));
        } else {
            $("input[name='password']").attr("type", "text");
            $(e).html($(e).attr("data-hide-caption"));
        }
    };
    toggleTeacherFavorite = function (teacherId, el) {
        var data = 'teacher_id= ' + teacherId;
        fcom.updateWithAjax(fcom.makeUrl('Learner', 'toggleTeacherFavorite', [], confWebDashUrl), data, function (ans) {
            if (ans.action == 'A') {
                $(el).addClass("is--active");
            } else if (ans.action == 'R') {
                $(el).removeClass("is--active");
            }
            if (typeof searchfavorites != 'undefined') {
                searchfavorites(document.frmFavSrch);
            }
        });
        $(el).blur();
    };
    closeNavigation = function () {
        $('.subheader .nav__dropdown a').removeClass('is-active');
        $('.subheader .nav__dropdown-target').fadeOut();
    };
    acceptAllCookies = function () {
        fcom.updateWithAjax(fcom.makeUrl('CookieConsent', 'acceptAll'), '', function (res) {
            $(".cookie-alert").remove();
            $(".cookie-alert").hide('slow');
        });
    };
    cookieConsentForm = function () {
        fcom.process();
        fcom.ajax(fcom.makeUrl('CookieConsent', 'form'), '', function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg', 'addClass': 'cookies-popup' });
        });
    };
    cookieConsentSetup = function (form) {
        if (!$(form).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('CookieConsent', 'setup'), fcom.frmData(form), function (res) {
            $('.cookie-alert').remove();
            $.w3mentorsmodal.close();
        });
    };
    teacherSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('TeacherRequest', 'teacherSetup'), fcom.frmData(frm), function (res) {
            setTimeout(() => {
                window.location.href = res.redirectUrl;
            }, 1000)
        });
    };
    resendVerificationLink = function (username) {
        if (username == "undefined" || typeof username === "undefined") {
            username = '';
        }
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'resendVerificationLink', [username]));
    };

    submitNewsletterForm = function (form) {
        if (newsletterAjaxRuning) {
            return false
        }
        if (!$(form).validate()) {
            return;
        }
        newsletterAjaxRuning = true;
        $.loader.show();
        var data = fcom.frmData(form);
        fcom.ajax(fcom.makeUrl('Home', 'setUpNewsLetter'), data, function (response) {
            if (response.status == 1) {
                form.reset();
            }
            $.loader.hide();
            newsletterAjaxRuning = false;
        }, {fOutMode: 'json', failed: true});
    }

})(jQuery);

function toggleOffers(element) {
    $(element).toggleClass("is-active");
    $('html').toggleClass("show-offers-js");
    $(".offers-target-js").toggle();
}

function setCookie(key, value) {
    var expires = new Date();
    expires.setTime(expires.getTime() + 604800);
    secure = (SslUsed == 1) ? ' secure;' : '';
    samesite = "";
    if (secure) {
        samesite = " samesite=none;";
    }
    document.cookie = key + '=' + value + '; ' + secure + samesite + ' expires=' + expires.toUTCString() + ';  domain=.' + window.location.hostname + '; path=' + confWebRootUrl;
}
function getCookie(key) {
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
}

$(document).ready(function () {
    var userTimezone = getCookie('CONF_SITE_TIMEZONE');
    var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    var timezone = tz || langLbl.defaultTimeZone;
    if (userTimezone == '' || userTimezone == null) {
        setCookie('CONF_SITE_TIMEZONE', timezone);
    }



    /* FOR BACK TO TOP */
    $(function () {
        $(window).scroll(function () {
            if ($(this).scrollTop() > 500) {
                $('.gototop').addClass("isvisible");
            } else {
                $('.gototop').removeClass("isvisible");
            }
        });

        // scroll body to 0px on click
        $('.gototop').click(function () {
            $('body,html').animate({
                scrollTop: 0
            }, 800);
            return false;
        });
    });

    $(document).on('click', '.play-video', function () {
        $.w3mentorsmodal('<div class="modal-header"><h5>' + langLbl.infoVideo + '</h5><button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button></div><div class="modal-body"><iframe id="ytplayer" type="text/html" width="1000" height="460" src="' + $(this).attr('data-src') + '" frameborder="2"></iframe></div>');
    });


});

