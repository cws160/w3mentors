/* global fcom, langLbl, dv */
$(document).ready(function () {
    search(document.frmCouponSearch);
});
$(document).delegate('.language-js', 'change', function () {
    var lang_id = $(this).val();
    var coupon_id = $("input[name='coupon_id']").val();
    couponImages(coupon_id, lang_id);
});
(function () {
    search = function (form) {
        fcom.ajax(fcom.makeUrl('Coupons', 'search'), fcom.frmData(form), function (res) {
            $('#listing').html(res);
        });
    };
    goToSearchPage = function (pageno) {
        var frm = document.frmCouponSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    reloadList = function () {
        search(document.frmCouponSearchPaging);
    }
    form = function (id) {
        fcom.ajax(fcom.makeUrl('Coupons', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('Coupons', 'setup'), data, function (t) {
            reloadList();
            if (t.langId > 0) {
                langForm(t.couponId, t.langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (couponId, langId) {
        fcom.ajax(fcom.makeUrl('Coupons', 'langForm', [couponId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Coupons', 'langSetup'), fcom.frmData(frm), function (res) {
            reloadList();
            if (res.langId > 0) {
                langForm(res.couponId, res.langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    remove = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Coupons', 'remove'), 'id=' + id, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.frmCouponSearch.reset();
        search(document.frmCouponSearch);
    };


    toggleMaxDiscount = function (val) {
        if (val == 2) {
            $("#coupon_max_discount_div").hide();
        } else {
            $("#coupon_max_discount_div").show();
        }
    };
})();
function bindDatetimePicker(selector) {
    var dayShortNames = weekDayNames.shortName.slice(0);
    var lastValue = dayShortNames[6];
    dayShortNames.pop();
    dayShortNames.unshift(lastValue);
    defaultsValue = {
        monthNames: monthNames.longName,
        monthNamesShort: monthNames.shortName,
        dayNamesMin: dayShortNames,
        dayNamesShort: dayShortNames,
        currentText: langLbl.today,
        closeText: langLbl.done,
        prevText: langLbl.prev,
        nextText: langLbl.next,
        isRTL: (layoutDirection == 'rtl')
    };
    $.datepicker.regional[''] = $.extend(true, {}, defaultsValue);
    $.datepicker.setDefaults($.datepicker.regional['']);
    var dayNames = weekDayNames.shortName.slice(0);
    var lastValue = dayNames[6];
    dayNames.pop();
    dayNames.unshift(lastValue);
    $.fn.datetimepicker.defaults.i18n = {'': {months: monthNames.longName, dayOfWeek: dayNames}};
    $(selector).datetimepicker({
        step: 15, lang: '',
        format: 'Y-m-d ' + timeFormat,
        formatDate: 'Y-m-d',
        formatTime: timeFormat,
        minDate: new Date(),
        closeOnDateSelect: false,
        closeOnInputClick: false,
        onChangeDateTime: function (currentDateTime, $input) {
            let selectedDateTime = $input.val();
            let selectedTime = selectedDateTime.split(" ")[1];
            let minutes = parseInt(selectedTime.split(":")[1]);
            let minutesToAdd = 0;
            const validTime = [15, 30, 45, 0];
            if (!validTime.includes(minutes)) {
                if (minutes < 15) {
                    minutesToAdd = 15 - minutes;
                } else if (minutes < 30) {
                    minutesToAdd = 30 - minutes;
                } else if (minutes < 45) {
                    minutesToAdd = 45 - minutes;
                } else if (minutes > 45) {
                    minutesToAdd = 60 - minutes;
                }
            }
            this.setOptions({
                value: moment(selectedDateTime, 'YYYY-MM-DD ' + timeFormatJs).add(minutesToAdd, 'minutes').format('YYYY-MM-DD ' + timeFormatJs)
            });
        },
    });
}