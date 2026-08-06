/* global fcom */
$(document).ready(function () {
    searchUrls(document.srchForm);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        searchUrls(frm);
    };
    reloadList = function () {
        searchUrls(document.srchFormPaging);
    };
    searchUrls = function (form) {
        fcom.ajax(fcom.makeUrl('UrlRewriting', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    urlForm = function (seourlId) {
        fcom.ajax(fcom.makeUrl('UrlRewriting', 'form'), {seourlId: seourlId}, function (t) {
            $.w3mentorsmodal(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('UrlRewriting', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'id=' + id;
        fcom.updateWithAjax(fcom.makeUrl('UrlRewriting', 'deleteRecord'), data, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchUrls(document.srchForm);
    };
})();	