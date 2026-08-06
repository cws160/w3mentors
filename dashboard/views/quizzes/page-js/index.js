/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */
$(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Quizzes', 'search'), fcom.frmData(frm), function (res) {
            $("#quiz-listing").html(res);
        });
    };
    updateStatus = function (id, obj) {
        var checked = $(obj).is(':checked');
        if (!confirm(langLbl.confirmUpdateStatus)) {
            $(obj).prop('checked', (checked == false) ? true : false);
            return;
        }
        var status = $(obj).val();
        fcom.updateWithAjax(fcom.makeUrl('Quizzes', 'updateStatus'), { id, status }, function (res) {
            search(document.frmPaging);
            return;
        });
        $(obj).prop('checked', (checked == false) ? true : false);
    }
    remove = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Quizzes', 'delete'), { id }, function (res) {
            search(document.frmPaging);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
    search(document.srchForm);
});
