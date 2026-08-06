/* global fcom, langLbl */

searchLessons = null;
var isLessonCancelAjaxRun = false;
requestReschedule = function (id) {
    fcom.ajax(fcom.makeUrl('Lessons', 'requestReschedule', [id]), '', function (t) {
        $.w3mentorsmodal(t, { 'size': 'modal-lg' });
    });
};

requestRescheduleSetup = function (frm) {
    if (!$(frm).validate())
        return;
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(fcom.makeUrl('Lessons', 'requestRescheduleSetup'), data, function (t) {
        $.w3mentorsmodal.close();
        location.reload();
    });
};

cancelLesson = function (id) {
    fcom.ajax(fcom.makeUrl('Lessons', 'cancelLesson', [id]), '', function (t) {
        $.w3mentorsmodal(t, { 'size': 'modal-lg' });
    });
};

cancelLessonSetup = function (frm) {
    if (isLessonCancelAjaxRun) {
        return false;
    }
    isLessonCancelAjaxRun = true;
    if (!$(frm).validate())
        return;
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(fcom.makeUrl('Lessons', 'cancelLessonSetup'), data, function (t) {
        $.w3mentorsmodal.close();
        location.reload();
    });
};

viewBookingCalendar = function (id) {
    fcom.ajax(fcom.makeUrl('Lessons', 'viewBookingCalendar', [id]), '', function (t) {
        $.w3mentorsmodal(t, { 'size': 'modal-lg' });
    });
};

goToPlanSearchPage = function (pageno) {
    var frm = document.planSearchFrm;
    $(frm.pageno).val(pageno);
    fcom.ajax(fcom.makeUrl('Plans', 'search'), fcom.frmData(document.planSearchFrm), function (res) {
        $(".plan-listing#listing").html(res);
    });
};

scheduleLessonSetup = function (lessonId, startTime, endTime, date) {
    fcom.ajax(fcom.makeUrl('Lessons', 'scheduleLessonSetup'), 'lessonId=' + lessonId + '&startTime=' + startTime + '&endTime=' + endTime + '&date=' + date, function (doc) {
        $.w3mentorsmodal.close();
        location.reload();
    });
};


$(document).ready(function () {
    $(document).on("click", '.iss_accordion', function () {
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.display === "block") {
            panel.style.display = "none";
        } else {
            panel.style.display = "block";
        }
    });
});