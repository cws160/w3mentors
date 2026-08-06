/* global fcom, langLbl */
(function () {
    scheduleForm = function (lessonId) {
        fcom.ajax(fcom.makeUrl('Lessons', 'scheduleForm'), {lessonId: lessonId}, function (response) {
            $.w3mentorsmodal(response,{ 'size': 'modal-lg checkout--modal'});
        });
    };
    scheduleSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.ajax(fcom.makeUrl('Lessons', 'scheduleSetup'), fcom.frmData(frm), function (response) {
            var res = JSON.parse(response);
            if (res.status == 1) {
                if (typeof search !== 'undefined') {
                    search(document.frmSearchPaging);
                } else {
                    reloadPage(0);
                }
                $.w3mentorsmodal.close();
            }
        });
    };
    rescheduleForm = function (lessonId) {
        fcom.ajax(fcom.makeUrl('Lessons', 'rescheduleForm'), {lessonId: lessonId}, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal' });
        });
    };
    rescheduleSetup = function (frm) {
        var rescheduleReason = $('#reschedule-reason-js').val();
        if ($.trim(rescheduleReason) == "") {
            $('.scheduled-lesson-popup').parent().animate({scrollTop: 0}, 500);
            return false;
        }
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Lessons', 'rescheduleSetup'), fcom.frmData(frm), function (res) {
            if (res.status == 1) {
                if (typeof search !== 'undefined') {
                    search(document.frmSearchPaging);
                } else {
                    reloadPage(1000);
                }
                $.w3mentorsmodal.close();
            }
        });
    };
    cancelForm = function (lessonId) {
        fcom.ajax(fcom.makeUrl('Lessons', 'cancelForm'), {lessonId: lessonId}, function (response) {
            $.w3mentorsmodal(response,{'size':'modal-md'});
        });
    };
    cancelSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Lessons', 'cancelSetup'), fcom.frmData(frm), function (res) {
            if (res.status == 1) {
                if (typeof search !== 'undefined') {
                    search(document.frmSearchPaging);
                } else {
                    reloadPage(1000);
                }
                $.w3mentorsmodal.close();
            }
        });
    };
    feedbackForm = function (lessonId) {
        fcom.ajax(fcom.makeUrl('Lessons', 'feedbackForm'), {lessonId: lessonId}, function (response) {
            $.w3mentorsmodal(response,{'size':'modal-md'});
        });
    };
    feedbackSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.ajax(fcom.makeUrl('Lessons', 'feedbackSetup'), fcom.frmData(frm), function (response) {
            reloadPage(300);
        });
    };
    planListing = function (attachedPlanId) {
        fcom.ajax(fcom.makeUrl('Plans', 'search'), {attachedPlanId: attachedPlanId, listing_type: 1}, function (response) {
            $.w3mentorsmodal(response,{ 'size': 'modal-lg' });
        });
    };

    endLesson = function (lessonId) {
        if (confirm(endLessonConfirmMsg)) {
            if (typeof statusInterval !== 'undefined') {
                clearInterval(statusInterval);
            }
            var action = fcom.makeUrl('Lessons', 'endMeeting');
            fcom.ajax(action, { lessonId: lessonId }, function (response) {
                if (typeof search !== 'undefined') {
                    search(document.frmSearchPaging);
                } else {
                    reloadPage(1000);
                }
            });
        }
    };
})();