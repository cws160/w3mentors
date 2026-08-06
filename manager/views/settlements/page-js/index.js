/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    search = function (form) {
        fcom.ajax(fcom.makeUrl('Settlements', 'search'), fcom.frmData(form), function (res) {
            $('#listing').html(res);
        });
    };
    goToSearchPage = function (page) {
        var form = document.srchFormPaging;
        $(form.pageno).val(page);
        search(form);
    };
    clearSearch = function () {
        document.srchForm.reset();
        $("input[name='slstat_teacher_id']").val('');
        search(document.srchForm);
    };
    regenerate = function () {
        fcom.updateWithAjax(fcom.makeUrl('SalesReport', 'regenerate'), '', function (res) {
            $("#regendatedtime").text(res.regendatedtime);
            search(document.srchForm);
        });
    };
})();