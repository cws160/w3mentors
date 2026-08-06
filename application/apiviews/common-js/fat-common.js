/* global siteConstants, confWebRootUrl, x, langLbl */
siteConstants.userWebRoot = (siteConstants.rewritingEnabled) ? siteConstants.webroot : siteConstants.webroot_traditional;
var pageReloading = false;
var fcom = {
    process: function () {
        $.appalert(langLbl.processing, 'process');
    },
    success: function (msg) {
        $.appalert(msg, 'success');
    },
    warning: function (msg) {
        $.appalert(msg, 'warning');
    },
    error: function (msg) {
        $.appalert(msg, 'danger');
    },
    ajaxRequestLog: [],
    logAjaxRequest: function (url, data, res, ajaxLoopHandler) {
        var d = (new Date()).getTime();
        var last = d - 20000;
        var obj = {
            url: url,
            data: (typeof data == "object") ? JSON.stringify(data) : data,
            res: (typeof res == "object") ? JSON.stringify(res) : res,
            t: d
        };
        var repeatCount = 0;
        for (var i = fcom.ajaxRequestLog.length - 1; i >= 0; i--) {
            var oldObj = fcom.ajaxRequestLog[i];
            if (oldObj.t < last) {
                fcom.ajaxRequestLog.splice(i, 1);
                continue;
            }
            if (oldObj.url == obj.url && oldObj.data == obj.data && oldObj.res == obj.res) {
                repeatCount++;
            }
        }
        fcom.ajaxRequestLog.push(obj);
        if (repeatCount >= 5000 && !pageReloading) {
            if (confirm('This page seems to be stuck with some ajax call loop.\nDo you want to reload the page?')) {
                pageReloading = true;
                location.reload();
            }
        }
        if (ajaxLoopHandler && repeatCount >= 2) {
            console.log('Executing ajaxLoopHandler for url: ' + url + ', data: ' + obj.data + ', res: ' + obj.res);
            ajaxLoopHandler();
        }
        return repeatCount;
    },
    ajax: function (url, data, callback, options) {
        var o = $.extend(true, {fOutMode: 'html', timeout: null, ajaxLoopHandler: null, failed: false}, options);
        if ("string" == $.type(data)) {
            data += '&fOutMode=' + o.fOutMode + '&fIsAjax=1';
        }
        if ("object" == $.type(data)) {
            var data = $.extend(true, {}, data);
            data.fOutMode = o.fOutMode;
            data.fIsAjax = 1;
        }
        o.fOutMode = o.fOutMode.toLowerCase();
        $.ajax({
            method: "POST",
            url: url,
            data: data,
            timeout: o.timeout,
            dataType: o.fOutMode,
            async: (options && options.async === false) ? false : true,
            success: function (res) {
                if (o.fOutMode == "json" || isJson(res)) {
                    var response = (o.fOutMode == "json") ? res : JSON.parse(res);
                    if (response.status == 1) {
                        if (response.msg && response.msg != '') {
                            fcom.success(response.msg);
                        } else {
                            $.appalert.close();
                        }
                        return callback(res);
                    } else {
                        if (response.msg && response.msg != '') {
                            fcom.error(response.msg);
                        } else {
                            $.appalert.close();
                        }
                        return (options && options.failed) ? callback(res) : false;
                    }
                } else {
                    callback(res);
                    $.appalert.close();
                }
            },
            error: function (jqXHR, textStatus, error) {
                if (textStatus == "parsererror" && jqXHR.statusText == "OK") {
                    alert('Seems some json error.' + jqXHR.responseText);
                    return;
                }
                switch (jqXHR.status) {
                    case 401:
                        return signinForm();
                    default:
                        console.log("Http Error: " + jqXHR.status + ' ' + error);
                }
            }
        });
    },
    ajaxMultipart: function (url, data, callback, options) {
        var o = $.extend(true, {fOutMode: 'html', timeout: 300000}, options);
        o.fOutMode = o.fOutMode.toLowerCase();
        data.append('fOutMode', o.fOutMode);
        data.append('timeout', o.timeout);
        data.append('fIsAjax', 1);
        $.ajax({
            method: "POST",
            enctype: 'multipart/form-data',
            url: url,
            data: data,
            cache: false,
            processData: false,
            contentType: false,
            timeout: o.timeout,
            dataType: o.fOutMode,
            success: function (res) {
                if (o.fOutMode == "json" || isJson(res)) {
                    var response = (o.fOutMode == "json") ? res : JSON.parse(res);
                    if (response.status == 1) {
                        if (response.msg && response.msg != '') {
                            fcom.success(response.msg);
                        } else {
                            $.appalert.close();
                        }
                        return callback(res);
                    } else {
                        if (response.msg && response.msg != '') {
                            fcom.error(response.msg);
                        } else {
                            $.appalert.close();
                        }
                        return (options && options.failed) ? callback(res) : false;
                    }
                } else {
                    callback(res);
                    $.appalert.close();
                }
            },
            error: function (jqXHR, textStatus, error) {
                if (textStatus == "parsererror" && jqXHR.statusText == "OK") {
                    alert('Seems some json error.' + jqXHR.responseText);
                    return;
                }
                switch (jqXHR.status) {
                    case 401:
                        return signinForm();
                    default:
                        console.log("Http Error: " + jqXHR.status + ' ' + error);
                }
            }
        });
    },
    updateWithAjax: function (url, data, callback, options) {
        var o = $.extend(true, {fOutMode: 'json'}, options);
        this.ajax(url, data, function (res) {
            callback(res);
        }, o);
    },
    camel2dashed: function (str) {
        return str.replace(/([a-zA-Z])(?=[A-Z])/g, '$1-').toLowerCase();
    },
    breakUrl: function (url) {
        url = url.substring(siteConstants.userWebRoot.length);
        var arr = url.split('/');
        var obj = {controller: arr[0], action: '', others: []};
        arr.shift();
        if (!arr.length)
            return obj;
        obj.action = arr[0];
        arr.shift();
        obj.others = arr;
        return obj;
    },
    makeUrl: function (controller, action, others, use_root_url, urlRewritingEnabled) {
        if (typeof urlRewritingEnabled === 'undefined') {
            urlRewritingEnabled = (siteConstants.rewritingEnabled == 1);
        }
        if (!use_root_url) {
            use_root_url = (urlRewritingEnabled) ? siteConstants.webroot : siteConstants.webroot_traditional;
        }
        var url;
        if (!controller)
            controller = '';
        if (!action)
            action = '';
        controller = this.camel2dashed(controller);
        action = this.camel2dashed(action);
        if (!others)
            others = [];
        if ('' == action && others.length)
            action = 'index';
        url = use_root_url + controller;
        if ('' != action)
            url += '/' + action;
        if (others.length) {
            for (x in others)
                others[x] = encodeURIComponent(others[x]);
            url += '/' + others.join('/');
        }
        return url;
    },
    frmData: function (frm) {
        var disabled = $(frm).find(':input:disabled').removeAttr('disabled');
        var out = $(frm).serialize();
        disabled.attr('disabled', 'disabled');
        return out;
    },
    qStringToObject: function (q) {
        var args = new Object();
        var pairs = q.split("&");
        for (var i = 0; i < pairs.length; i++) {
            var pos = pairs[i].indexOf('=');
            if (pos == -1)
                continue;
            var argname = pairs[i].substring(0, pos);
            var value = pairs[i].substring(pos + 1);
            args[argname] = unescape(value);
        }
        return args;
    },
    urlWrittenQueryObject: function () {
        var url = location.pathname;
        url = url.substring(siteConstants.userWebRoot.length);
        var arr = url.split('/');
        if (arr.length <= 2)
            return {};
        arr.shift();
        arr.shift();
        var obj = {};
        for (var i = 0; i < arr.length; i += 2) {
            obj[arr[i]] = arr[i + 1];
        }
        return obj;
    }
};
$.fn.selectRange = function (start, end) {
    if (!end) {
        end = start;
    }
    return this.each(function () {
        if (this.setSelectionRange) {
            if (!$(this).is(':visible')) {
                return;
            }
            this.focus();
            this.setSelectionRange(start, end);
        } else if (this.createTextRange) {
            var range = this.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    });
};
