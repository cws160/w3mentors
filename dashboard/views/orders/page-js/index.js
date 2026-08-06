/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */
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
$(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Orders', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.orderSearchFrm.reset();
        search(document.orderSearchFrm);
    };
    view = function (orderId, orderType) {
        $('body').removeClass('is-order-open');
        if ($('.target-data-' + orderId).hasClass('is-active')) {
            $('.target-data-' + orderId).hide().removeClass('is-active').removeClass('is-expanded');
            return;
        } else {
            $('.action-trigger-js').removeClass('is-active');
            $('.target-data-js').removeClass('is-active').removeClass('is-expanded').hide();
            fcom.ajax(fcom.makeUrl('Orders', 'view'), {orderId: orderId, orderType: orderType}, function (response) {
                $('.target-data-' + orderId).html(response).addClass('is-active').addClass('is-expanded').show();
                $('body').addClass('is-order-open');
                $('html, body').animate({scrollTop: $('.target-data-' + orderId).offset().top - 170}, );
            });
        }
    };
    search(document.orderSearchFrm);
});
