/* global fcom, langLbl, VIEW_LISTING, VIEW_CALENDAR, monthNames, weekDayNames */
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
(function () {
    upcoming = function () {
        fcom.ajax(fcom.makeUrl('Lessons', 'upcoming'), { pagesize: 1 }, function (t) {
            $('#upcomingLesson').html(t);
        });
    };
    search = function (form) {
        view = (form && form.view.value) ? parseInt(form.view.value) : VIEW_LISTING;
        switch (view) {
            case VIEW_CALENDAR:
                getCalendarView();
                break;
            case VIEW_LISTING:
            default:
                searchListing(form);
                break;
        }
    };
    searchListing = function (frm) {
        fcom.ajax(fcom.makeUrl('Lessons', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    getCalendarView = function () {
        fcom.ajax(fcom.makeUrl('Lessons', 'calendarView'), '', function (response) {
            $("#listing").html(response);
        });
    };
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        searchListing(frm);
    };
    clearSearch = function () {
        $('.list-inline li').removeClass('is-active');
        document.frmLessonSearch.reset();
        search(document.frmLessonSearch);
    };
})();
