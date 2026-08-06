/* global siteConstants */
(function ($) {
    $.appalert = function (data, type) {
        if ($('#app-alert').hasClass('animated')) {
            $('#app-alert').removeClass('animated fadeInDown');
            setTimeout(function () {
                $.appalert.fill(data, type);
            }, 10);
        } else {
            $.appalert.fill(data, type);
        }
    };
    $.extend($.appalert, {
        fill: function (data, type) {
            $('#app-alert .alert').removeClass('alert--process alert--success alert--warning alert--danger');
            $('#app-alert .alert').addClass('alert--' + type);
            $('#app-alert .alert__message > p').html(data);
            $('#app-alert').addClass('animated fadeInDown');
            if ($.appalert.timer) {
                clearTimeout($.appalert.timer);
            }
            if (type == 'success') {
                $.appalert.startTimer();
            }
        },
        close: function () {
            if ($.appalert.timer) {
                clearTimeout($.appalert.timer);
            }
            $('#app-alert').removeClass('animated fadeInDown');
        },
        startTimer: function () {
            if ($.appalert.timer) {
                clearTimeout($.appalert.timer);
            }
            $.appalert.timer = setTimeout(function () {
                $('#app-alert').removeClass('animated fadeInDown');
            }, ALERT_CLOSE_TIME * 1000);
        }
    });
})(jQuery);