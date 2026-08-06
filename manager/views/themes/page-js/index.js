/* global fcom */

$(document).ready(function () {
    search(document.frmSearch);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (page) {
        if (typeof page == undefined || page == null) {
            page = 1;
        }
        var frm = document.frmThemeSearchPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (form) {
        var data = '';
        if (form) {
            data = fcom.frmData(form);
        }
        $(dv).html(fcom.getLoader());
        fcom.ajax(fcom.makeUrl('Themes', 'search'), data, function (res) {
            $(dv).html(res);
        });
    };
    clearSearch = function () {
        document.frmSearch.reset();
        search(document.frmSearch);
    };
    activate = function (themeId) {
        if (confirm(langLbl.confirmActivate)) {
            fcom.updateWithAjax(fcom.makeUrl('Themes', 'activate'), {themeId: themeId}, function (res) {
                search(document.frmThemeSearchPaging);
            });
        }
    };
    edit = function (themeId, action) {
        fcom.ajax(fcom.makeUrl('Themes', 'edit'), {themeId: themeId, action: action}, function (res) {
            $.w3mentorsmodal(res);
            jscolor.installByClassName('jscolor');
        });
    };
    setup = function (frm) {
        if (!$(frm).validate())
            return;
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('Themes', 'setup'), data, function (t) {
            search(document.frmThemeSearchPaging);
            $.w3mentorsmodal.close();
        });
    };
    deleteTheme = function (themeId) {
        if (confirm(langLbl.confirmDelete)) {
            fcom.updateWithAjax(fcom.makeUrl('Themes', 'delete'), {themeId: themeId}, function (res) {
                search(document.frmThemeSearchPaging);
            });
        }
    };
})();
