/* global fcom, langLbl */
$(document).ready(function () {
    searchBlogContributions(document.srchForm);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchBlogContributions(frm);
    }
    reloadList = function () {
        searchBlogContributions(document.srchFormPaging);
    }
    view = function (id) {
        fcom.ajax(fcom.makeUrl('BlogContributions', 'view', [id]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    updateStatus = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogContributions', 'updateStatus'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            reloadList();
        });
    };
    searchBlogContributions = function (form) {
        fcom.ajax(fcom.makeUrl('BlogContributions', 'search'), fcom.frmData(form), function (res) {
            $("#listing").html(res);
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogContributions', 'deleteRecord'), {id: id}, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchBlogContributions(document.srchForm);
    };
})();