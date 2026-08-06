/* global fcom, langLbl */
$(document).ready(function () {
    searchLanguage(document.frmLanguageSearch);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.frmLanguageSearchPaging;
        $(frm.page).val(page);
        searchLanguage(frm);
    }
    reloadList = function () {
        var frm = document.frmLanguageSearchPaging;
        searchLanguage(frm);
    };
    searchLanguage = function (form) {
        fcom.ajax(fcom.makeUrl('Languages', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    languageForm = function (id) {
        fcom.ajax(fcom.makeUrl('Languages', 'form', [id]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    setupLanguage = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Languages', 'setup'), fcom.frmData(frm), function (t) {
            $.w3mentorsmodal.close();
            reloadList();
        });
    }
    editLanguageForm = function (languageId) {
        fcom.ajax(fcom.makeUrl('Languages', 'form', [languageId]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    toggleStatus = function (e, obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var languageId = parseInt(obj.id);
        var data = 'languageId=' + languageId;
        fcom.ajax(fcom.makeUrl('Languages', 'changeStatus'), data, function (res) {
            $(obj).toggleClass("active");
        });
    };
    clearSearch = function () {
        document.frmSearch.reset();
        searchLanguage(document.frmSearch);
    };
})();