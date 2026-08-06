
/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */

/**
 * global fcom, layoutDirection, langLbl, weekDayNames, weekDayNames, monthNames
 * please not change dayName var or not use the direct weekDayNames
 */
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
$(document).ready(function () {
    searchOrders(document.frmOrderSrch);
});
var dv = '#listItems';
searchOrders = function (frm) {
    var data = fcom.frmData(frm);
    fcom.ajax(fcom.makeUrl('Learner', 'getOrders'), data, function (t) {
        $(dv).html(t);
    });
};
clearSearch = function () {
    document.frmOrderSrch.reset();
    searchOrders(document.frmOrderSrch);
};
goToSearchPage = function (page) {
    if (typeof page == undefined || page == null) {
        page = 1;
    }
    var frm = document.frmOrderSearchPaging;
    $(frm.page).val(page);
    searchOrders(frm);
};