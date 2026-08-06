/* global fcom, langLbl, search */
(function () {
    cancelForm = function (classId) {
        fcom.ajax(fcom.makeUrl('Classes', 'cancelForm'), { classId: classId }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-md' });
        });
    };
    cancelSetup = function (form) {
        if (!$(form).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Classes', 'cancelSetup'), fcom.frmData(form), function (res) {
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
    feedbackForm = function (classId) {
        fcom.ajax(fcom.makeUrl('Classes', 'feedbackForm'), { classId: classId }, function (response) {
            $.w3mentorsmodal(response);
        });
    };
    feedbackSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.ajax(fcom.makeUrl('Classes', 'feedbackSetup'), fcom.frmData(frm), function (response) {
            reloadPage(3000);
        });
    };

    endMeeting = function (classId) {
        if (confirm(endClassConfirmMsg)) {
            if (typeof statusInterval !== 'undefined') {
                clearInterval(statusInterval);
            }
            var action = fcom.makeUrl('Classes', 'endMeeting');
            fcom.ajax(action, { classId: classId }, function (response) {
                reloadPage(3000);
            });
        }
    };
})();