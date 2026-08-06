/* global fcom */

$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    search = function (form) {
        fcom.ajax(fcom.makeUrl('TeacherRequests', 'search'), fcom.frmData(form), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
    view = function (utrequestId) {
        fcom.ajax(fcom.makeUrl('TeacherRequests', 'view', [utrequestId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    changeStatusForm = function (utrequestId) {
        fcom.ajax(fcom.makeUrl('TeacherRequests', 'changeStatusForm', [utrequestId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    showHideCommentBox = function (val) {
        if (val == STATUS_CANCELLED) {
            $('#comments').parents('.row').removeClass('hide');
        } else {
            $('textarea[name="tereq_comments"]').val('');
            $('#comments').parents('.row').addClass('hide');
        }
    };
    updateStatus = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('TeacherRequests', 'updateStatus'), fcom.frmData(frm), function (res) {
            search(document.srchFormPaging);
            $.w3mentorsmodal.close();
        });
    };
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        search(frm);
    };
})();