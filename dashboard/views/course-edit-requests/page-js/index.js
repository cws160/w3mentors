/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */
$(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('CourseEditRequests', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
    view = function (reqId) {
        fcom.ajax(fcom.makeUrl("CourseEditRequests", "view", [reqId]), {}, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg' });
        });
    }
    search(document.srchForm);
});
