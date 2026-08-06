/* global fcom */

$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    search = function (form) {
        fcom.ajax(fcom.makeUrl('LessonStats', 'searchLog'), fcom.frmData(form), function (response) {
            $("#listing").html(response);
        });
    };

    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.page).val(pageno);
        search(frm);
    };

    exportReport = function () {
        document.srchFormPaging.action = fcom.makeUrl('LessonStats', 'exportReport');
        document.srchFormPaging.method = "post";
        document.srchFormPaging.submit();
    };
})();