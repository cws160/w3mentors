/* global fcom */
$(document).ready(function () {
    searchLessons(document.frmSrch);
});
dv = '#listItemsLessons';
function searchLessons(frm) {
    fcom.ajax(fcom.makeUrl('Lessons', 'search'), fcom.frmData(frm), function (t) {
        $(dv).html(t);
    });
}
function viewCalendar(frm) {
    fcom.ajax(fcom.makeUrl('LearnerScheduledLessons', 'viewCalendar'), fcom.frmData(frm), function (t) {
        $(dv).html(t);
    });
}
