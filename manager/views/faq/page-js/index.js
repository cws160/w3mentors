/* global fcom, langLbl, e */
$(document).ready(function () {
    searchFaq(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchFaq(frm);
    }
    reloadList = function () {
        searchFaq(document.srchFormPaging);
    };
    searchFaq = function (form) {
        fcom.ajax(fcom.makeUrl('Faq', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    addFaqForm = function (id) {
        FaqForm(id);
    };
    FaqForm = function (id) {
        fcom.ajax(fcom.makeUrl('Faq', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    editFaqFormNew = function (faqId) {
        editFaqForm(faqId);
    };
    editFaqForm = function (faqId) {
        fcom.ajax(fcom.makeUrl('Faq', 'form', [faqId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupFaq = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Faq', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                editFaqLangForm(t.faqId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    }
    editFaqLangForm = function (faqId, langId) {
        fcom.ajax(fcom.makeUrl('Faq', 'langForm', [faqId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupLangFaq = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Faq', 'langSetup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                editFaqLangForm(t.faqId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'faqId=' + id;
        fcom.updateWithAjax(fcom.makeUrl('Faq', 'deleteRecord'), data, function (res) {
            reloadList();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var faqId = parseInt(obj.id);
        var data = 'faqId=' + faqId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('Faq', 'changeStatus'), data, function (res) {
            searchFaq(document.srchForm);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var faqId = parseInt(obj.id);
        var data = 'faqId=' + faqId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('Faq', 'changeStatus'), data, function (res) {
            searchFaq(document.srchForm);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchFaq(document.srchForm);
    };
})();
