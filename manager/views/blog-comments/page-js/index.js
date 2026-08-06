/* global fcom, langLbl */
$(document).ready(function () {
    searchBlogComments(document.srchForm);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchBlogComments(frm);
    }
    reloadList = function () {
        searchBlogComments(document.srchFormPaging);
    }
    view = function (id) {
        fcom.ajax(fcom.makeUrl('BlogComments', 'view', [id]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    updateStatus = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogComments', 'updateStatus'), fcom.frmData(frm), function (res) {
            reloadList();
            $.w3mentorsmodal.close();
        });
    };
    searchBlogComments = function (form) {
        fcom.ajax(fcom.makeUrl('BlogComments', 'search'), fcom.frmData(form), function (res) {
            $("#listing").html(res);
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogComments', 'deleteRecord'), {id: id}, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchBlogComments(document.srchForm);
    };
})();