/* global SITE_ROOT_URL, fcom, langLbl */
$(document).ready(function () {
    searchFaqCategories(document.srchForm);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchFaqCategories(frm);
    };
    redirectUrl = function (redirecrt) {
        window.location = SITE_ROOT_URL + '' + redirecrt;
    }
    reloadList = function () {
        searchFaqCategories(document.srchFormPaging);
    };
    searchFaqCategories = function (form) {
        fcom.ajax(fcom.makeUrl('FaqCategories', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    faqToCmsForm = function () {
        fcom.ajax(fcom.makeUrl('FaqCategories', 'faqToCmsForm'), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    setupFaqToCms = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('FaqCategories', 'setupFaqToCms'), fcom.frmData(frm), function (t) {
            $.w3mentorsmodal.close();
        });
    };
    addFaqCatForm = function (id) {
        faqCatForm(id);
    };
    faqCatForm = function (id) {
        fcom.ajax(fcom.makeUrl('FaqCategories', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('FaqCategories', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                faqCatLangForm(t.catId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    faqCatLangForm = function (faqcatId, langId) {
        fcom.ajax(fcom.makeUrl('FaqCategories', 'langForm', [faqcatId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupLang = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('FaqCategories', 'langSetup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                faqCatLangForm(t.catId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('FaqCategories', 'deleteRecord'), {id: id}, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchFaqCategories(document.srchForm);
    };
    toggleStatus = function (e, obj, canEdit) {
        if (canEdit == 0) {
            e.preventDefault();
            return;
        }
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var faqcatId = parseInt(obj.value);
        var data = 'faqcatId=' + faqcatId;
        fcom.ajax(fcom.makeUrl('FaqCategories', 'changeStatus'), data, function (res) {
            $(obj).toggleClass("active");
            setTimeout(function () {
                reloadList();
            }, 1000);
        });
    };
})();
