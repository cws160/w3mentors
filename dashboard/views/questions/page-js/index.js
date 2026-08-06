/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */
$(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Questions', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.frmQuesSearch.reset();
        search(document.frmQuesSearch);
        getSubcategories(0, '#subCategories');
    };
    remove = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Questions', 'remove'), {quesId: id}, function (res) {
            search(document.frmSearchPaging);
        });
    };
    updateStatus = function (id, obj) {
        var checked = $(obj).is(':checked');
        if (!confirm(langLbl.confirmUpdateStatus)) {
            $(obj).prop('checked', (checked == false) ? true : false);
            return;
        }
        var status = $(obj).val();
        fcom.updateWithAjax(fcom.makeUrl('Questions', 'updateStatus'), { id, status }, function (res) {
            search(document.frmSearchPaging);
            return;
        });
        $(obj).prop('checked', (checked == false) ? true : false);
    }
    search(document.frmQuesSearch);
});
