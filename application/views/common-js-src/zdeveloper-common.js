/* global fcom, confWebRootUrl, SslUsed, jstz, langLbl, searchfavorites, confWebDashUrl, ALERT_CLOSE_TIME */
function isJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

String.prototype.replaceArray = function (find, replace) {
    var replaceString = this;
    var regex;
    for (var i = 0; i < find.length; i++) {
        regex = new RegExp(find[i], "g");
        replaceString = replaceString.replace(regex, replace[i]);
    }
    return replaceString;
};

function thousandsSeparators(num) {
    var num_parts = num.toString().split(".");
    num_parts[0] = num_parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, langLbl.currencyGroupingSymbol);
    return num_parts.join(langLbl.currencyDecimalSymbol);
}

function formatMoney(price) {
    let format = (0 > price) ? langLbl.currencyNegativeFormat : langLbl.currencyPositiveFormat;
    price = Math.abs(price);
    price = parseFloat(price).toFixed(2);
    price = thousandsSeparators(price);
    return format.replaceArray(['{currency_symbol}', '{currency_number}'], [langLbl.currencSymbol, price]);
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
    getBadgeCount = function () {
        setTimeout(function () {
            fcom.ajax(fcom.makeUrl('Dashboard', 'getBadgeCounts', [], confWebDashUrl), '', function (response) {
                if (response.notifications > 0) {
                    let notifications = (response.notifications > 99) ? '99+' : response.notifications;
                    $('.notification-count-js').addClass('head-count');
                    $('.notification-count-js').text(notifications);
                } else {
                    $('.notification-count-js').removeClass('head-count');
                }
                if (response.messages > 0) {
                    let messages = (response.messages > 99) ? '99+' : response.messages;
                    $('.message-count-js').addClass('head-count');
                    $('.message-count-js').text(messages);
                } else {
                    $('.message-count-js').removeClass('head-count');
                }
            }, { fOutMode: 'json' });
        }, ALERT_CLOSE_TIME * 1000);
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
        let data = 'isPopUp=' + 1;
        if (document.frmSearch) {
            data += '&' + fcom.frmData(document.frmSearch) + '&' + 'pageno=' + document.frmSearchPaging.pageno.value;
        }
        fcom.ajax(fcom.makeUrl('GuestUser', 'loginForm'), data, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-form' });
        });
    };
    signinSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.process();
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'signinSetup'), data, function (res) {
            window.location.reload();
        });
    };
    signupForm = function () {
        fcom.process();
        fcom.ajax(fcom.makeUrl('GuestUser', 'signupForm'), '', function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-form' });
        });
    };
    signupSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.process();
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'signupSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            frm.reset();
            setTimeout(function () {
                (res.redirectUrl) ? window.location.href = res.redirectUrl : window.location.reload();
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
        // langId = lang.value;
        var data = { langId: langId, url: window.location.pathname };
        fcom.updateWithAjax(fcom.makeUrl('CookieConsent', 'setSiteLanguage'), data, function (res) {
            window.location.href = res.url;
        });
    };
    setSiteCurrency = function (currencyId) {
        // currencyId = currency.value;
        fcom.updateWithAjax(fcom.makeUrl('CookieConsent', 'setSiteCurrency', [currencyId]), '', function (res) {
            document.location.reload();
        });
    };
    resendSignupVerifyEmail = function (email) {
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'resendSignupVerifyEmail', [email]), {});
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
    toggleTeacherFavorite = function (teacherId, el, unfavourite = 0) {
        var data = 'teacher_id= ' + teacherId;
        fcom.updateWithAjax(fcom.makeUrl('Learner', 'toggleTeacherFavorite', [unfavourite], confWebDashUrl), data, function (ans) {
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
            $.w3mentorsmodal(res, { 'size': 'modal-lg cookies-popup' });
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
        fcom.process();
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
        fcom.process();
        newsletterAjaxRuning = true;
        var data = fcom.frmData(form);
        fcom.ajax(fcom.makeUrl('Home', 'setUpNewsLetter'), data, function (response) {
            if (response.status == 1) {
                form.reset();
            }
            newsletterAjaxRuning = false;
        }, { fOutMode: 'json', failed: true });
    };
    resetDeviceIframe = function (frame) {
        if ($(window).width() < 576) {
            return;
        }
        resetIframe(frame);
    }
    resetIframe = function (frame) {
        setTimeout(() => {
            var height = $(frame).contents().height();
            $(frame).css({ height: height + 'px' });
            $(frame).parent().css({ height: height + 'px' });
        }, 100);
    };
    resetIframes = function () {
        $('.iframe-content').each(function () {
            var iframe = $(this).children('iframe');

            var height = iframe.contents().height();
            iframe.css({ height: height + 'px' });
            $(this).css('height', height + 'px');

        });
    };

})(jQuery);
function toggleOffers() {
    $('.offers-ui__trigger').toggleClass("is-active");
    $('html').toggleClass("show-offers-js");
    $(".offers-target-js").toggleClass('is-show');
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
function decodeHtmlCharCodes(str) {
    return str.replace(/(&#(\d+);)/g, function (match, capture, charCode) {
        return String.fromCharCode(charCode);
    });
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
        $.w3mentorsmodal('<div class="modal-header"><h5>' + langLbl.infoVideo + '</h5><button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button></div><div class="modal-body"><div class="videowrap"><iframe id="ytplayer" type="text/html" width="100%" height="100%" src="' + $(this).attr('data-src') + '" frameborder="2"></iframe></div></div>', { 'size': 'modal-lg' });
    });

    fatDebounce = function (func, delay) {
        let debounceTimer;
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => func.apply(context, args), delay);
        };
    };

    callAnalyticsEvent = function (event_name, event_data = {}) {
        if (parseInt(GA4) === 1) {
            gtag('event', event_name, event_data);
        }
    };

    /* Bind bootstrap tooltip with ajax elements. */
    $('[data-bs-toggle="tooltip"]').tooltip({
        trigger: 'hover'
    }).on('click', function () {
        setTimeout(() => {
            $(this).tooltip('hide');
        }, 100);
    });

    /* Header Search Bar Functions from courses-home.js */
    function keywordSrch() {
        var keyword = $('#homeSearchFld').val();
        var type = $('input[name="type"]').val();
        
        if (keyword.length >= 3) {
            fcom.ajax(fcom.makeUrl('Home', 'autoComplete'), { keyword, type }, function (resp) {
                if (resp == '') {
                    $('.autoSuggestJs').html(resp).parent().hide();
                } else {
                    $('.autoSuggestJs').html(resp).parent().show();
                }
            });
        } else {
            $('.autoSuggestJs').html('').parent().hide();
        }
    }
    const debounceSearch = fatDebounce(keywordSrch, 500);
    $('body').on('input', '#homeSearchFld', function () {
        if ($('#homeSearchFld').val() != '') {
            $('.srchBtnJs').hide();
            $('.resetBtnJs').show();
        } else {
            $('.srchBtnJs').show();
            $('.resetBtnJs').hide();
        }
        debounceSearch();
    });

    clearKeywordSrch = function () {
        $('#homeSearchFld').val('').trigger('input');
    }

    if( $(window).width() > 576 ){
        $(".expand-trigger-js").click(function () {
            $('.search-dropdown').toggleClass("is-active");
            ($(".search-dropdown").hasClass('is-active')) ? $(".expand-target-js").hide() : $(".expand-target-js").show();
        });
    }

    $('.filterTypeJs .filterLinkJs').click(function () {
        $('.filterTypeJs .filterLinkJs').removeClass('is-active');
        $(this).addClass('is-active');
        $('.selectedFilterJs').text($(this).text());
        $('input[name="type"]').val($(this).data('filter'));
        $(".expand-target-js").hide();
        $('.search-dropdown').toggleClass("is-active");
        $('#homeSearchFld').trigger('input');
    });

    $(document).mouseup(function (e) {
        var container = $(".autoSuggestJs").parent();
        if ((!container.is(e.target) && container.has(e.target).length === 0)) {
            container.hide();
        }
        var container2 = $(".filterTypeJs").parent().parent();
        if (!container2.is(e.target) && container2.has(e.target).length === 0) {
            container2.hide();
        }
    });

    $(document).on('click', '.close-checkout-modal', function () {
        cart.resetCartData();
    });

    setMomentLocale = function() {
        moment.updateLocale('custom', {
            weekdays: dayLongNames,
            weekdaysShort: dayShortNames,
            weekdaysMin: dayShortNames,
            months: monthNames.longName,
            monthsShort: monthNames.shortName,
            meridiem: function (hour, minute, isLower) {
                return hour < 12 ? AM_LABEL : PM_LABEL;
            }
        });
    };

    unsetMomentLocale = function(){
        moment.updateLocale('custom', null);
    };
    
    countFilters = function () {
        $('.filter-js').each(function () {
            var ch = $(this).find('.options-filter-js').find('input[type="checkbox"]:checked').length;
            var rd = $(this).find('.options-filter-js').find('input[type="radio"]:checked').length;
            var tx;
            $(this).find('.text-filter-js').find('input[type="text"]').each(function () {
                if ($(this).val() != '') {
                    tx = 1;
                }
            });
            if (ch > 0) {
                $(this).find('.count-filter-js').html('(' + ch + ')');
            } else if (rd > 0) {
                $(this).find('.count-filter-js').html('(' + rd + ')');
            } else if (tx > 0) {
                $(this).find('.count-filter-js').html('(1)');
            } else {
                $(this).find('.count-filter-js').html('');
            }
        });
    };
});