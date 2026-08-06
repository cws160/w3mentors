$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    search = function (form) {
        fcom.ajax(fcom.makeUrl('TeacherRequests', 'searchQualifications'), fcom.frmData(form), function (res) {
            $("#listing").html(res);
        });
    };
     goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        search(frm);
    };
} )();