/* global fcom, langLbl */
$(document).ready(function () {
    searchPageLangData(document.frmPagesSearch);
    $(document).on('click', 'ul.linksvertical li a.redirect--js', function (event) {
        event.stopPropagation();
    });
});
(function () {
    var dv = '#listing';
    reloadList = function () {
        searchPageLangData(document.frmPagesSearchPaging);
    };
    searchPageLangData = function (form) {
        fcom.ajax(fcom.makeUrl('PageLangData', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    goToSearchPage = function (pageno) {
        var frm = document.frmPagesSearchPaging;
        $(frm.pageno).val(pageno);
        searchPageLangData(frm);
    };
    langForm = function (plangId, langId) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('PageLangData', 'langForm', [plangId, langId]), '', function (t) {
            $.w3mentorsmodal(t);
            $.w3mentorsmodal.setEditorLayout(langId);
        });
    };
    clearSearch = function () {
        document.frmPagesSearch.reset();
        searchPageLangData(document.frmPagesSearch);
    };
    resetToDefaultContent = function () {
        var agree = confirm(langLbl.confirmReplaceCurrentToDefault);
        if (!agree) {
            return false;
        }
        oUtil.obj.putHTML($("#editor_default_content").html());
    };
})();

$(document).on('submit', '#page-lang-data', function (e) {
    e.preventDefault();
    var frm = $(this);
    var validator = $(frm).validation({errordisplay: 3});
    validator.validate();
    if (!validator.isValid()) {
        return;
    }
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(fcom.makeUrl('PageLangData', 'langSetup'), data, function (res) {
        fcom.resetEditorInstance();
        searchPageLangData(document.frmPagesSearchPaging);
        let element = $('.tabs-nav a.active').parent().next('li');
        if (element.length > 0) {
            let langId = element.find('a').attr('data-id');
            langForm(res.plangId, langId);
            return;
        }
        $.w3mentorsmodal.close();
    });
})
