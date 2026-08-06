/* global fcom */

$(document).ready(function () {
    searchSentEmails(document.sentEmailSrchForm);
});
(function () {
    searchSentEmails = function (frm) {
        var dv = $('#emails-list');
        fcom.ajax(fcom.makeUrl('SentEmails', 'search'), fcom.frmData(frm), function (res) {
            dv.html(res);
        });
    };
    goToSearchPage = function (page) {
        var frm = document.frmSentEmailSearchPaging;
        $(frm.page).val(page);
        searchSentEmails(frm);
    };
    listPage = function (page) {
        searchSentEmails(document.sentEmailSrchForm, page);
    };
    reloadProgramsList = function () {
        document.sentEmailSrchForm.reset();
        setTimeout(function () {
            searchSentEmails(document.sentEmailSrchForm, 1);
        }, 500);
    };
})();