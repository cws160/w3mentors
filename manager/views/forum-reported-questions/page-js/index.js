/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    var dv = '#listing';
    search = function (form) {
        fcom.ajax(fcom.makeUrl('ForumReportedQuestions', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };

    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };

    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };

    view = function (reportId) {
        fcom.ajax(fcom.makeUrl('ForumReportedQuestions', 'view', [reportId]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };

    actionForm = function (reportId) {
        fcom.ajax(fcom.makeUrl('ForumReportedQuestions', 'actionForm', [reportId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };

    setupAction = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ForumReportedQuestions', 'setupAction'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            search(document.srchFormPaging);
        });
    };


})();
