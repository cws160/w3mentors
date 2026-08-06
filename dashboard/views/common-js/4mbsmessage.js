/* global siteConstants, ALERT_CLOSE_TIME */
(function ($) {
    $.appalert = function () { };
    $.extend($.appalert, {
        open: function (message, type) {
            const alertId = Math.random() * 1000000000000000000000;
            const alertEl = document.createElement("alert");
            alertEl.setAttribute("id", alertId);
            alertEl.setAttribute("role", 'alert');
            alertEl.setAttribute("style", 'margin-bottom:10px;');
            alertEl.setAttribute("class", 'alert alert--' + type);
            const iconEl = document.createElement("alert-icon");
            iconEl.setAttribute("class", 'alert__icon');
            const closeEl = document.createElement("alert-close");
            closeEl.setAttribute("class", 'alert__close');
            closeEl.setAttribute("onclick", '$.appalert.close(\'' + alertId + '\')');
            const messageEl = document.createElement("alert-message");
            messageEl.setAttribute("class", 'alert__message');
            const paraEl = document.createElement("p");
            paraEl.innerHTML = message;
            messageEl.appendChild(paraEl);
            alertEl.appendChild(iconEl);
            alertEl.appendChild(messageEl);
            alertEl.appendChild(closeEl);
            const appAlertEl = document.getElementById('app-alert');
            appAlertEl.prepend(alertEl);
            if (type !== 'process') {
                alertEl.timer = setTimeout(function () {
                    $.appalert.close(alertId);
                }, ALERT_CLOSE_TIME * 1000);
            }
        },
        close: function (alertId) {
            if (alertId == undefined) {
                $('.alert--process').css({ display: 'none' });
            } else {
                $('#' + alertId).css({ display: 'none' });
                var appAlertEl = document.getElementById('app-alert');
                if (appAlertEl.childNodes.length == 0) {
                    appAlertEl.classList.remove('fadeInDown');
                    appAlertEl.classList.remove('animated');
                }
            }
        }
    });
})(jQuery);