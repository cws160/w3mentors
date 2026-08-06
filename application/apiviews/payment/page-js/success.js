/* global fcom, langLbl, confWebDashUrl, SCHEDULED */
(function () {
    scheduleForm = function (lessonId) {
        fcom.process();
        fcom.ajax(fcom.makeUrl('Lessons', 'scheduleForm', '', confWebDashUrl), { lessonId: lessonId }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-xl'} );
        });
    };
    scheduleSetup = function (frm) {
        fcom.process();
        fcom.ajax(fcom.makeUrl('Lessons', 'scheduleSetup', '', confWebDashUrl), fcom.frmData(frm), function (response) {
            window.location.reload();
        });
    };
})();