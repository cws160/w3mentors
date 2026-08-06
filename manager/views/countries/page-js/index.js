/* global fcom, langLbl, e */
$(document).ready(function () {
    searchCountry(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchCountry(frm);
    };
    reloadList = function () {
        searchCountry(document.srchFormPaging);
    };
    searchCountry = function (form) {
        fcom.ajax(fcom.makeUrl('Countries', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    addCountryForm = function (id) {
        countryForm(id);
    };
    countryForm = function (id) {
        fcom.ajax(fcom.makeUrl('Countries', 'form', [id]), '', function (t) {
            $.w3mentorsmodal(t);
            fcom.updatePopupContent(t);
        });
    };
    editCountryFormNew = function (countryId) {
        editCountryForm(countryId);
    };
    editCountryForm = function (countryId) {
        fcom.ajax(fcom.makeUrl('Countries', 'form', [countryId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupCountry = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('Countries', 'setup'), data, function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(t.countryId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (countryId, langId) {
        fcom.ajax(fcom.makeUrl('Countries', 'langForm', [countryId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupLangCountry = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Countries', 'langSetup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(t.countryId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var countryId = parseInt(obj.id);
        var data = 'countryId=' + countryId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('Countries', 'changeStatus'), data, function (res) {
            searchCountry(document.srchFormPaging);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var countryId = parseInt(obj.id);
        var data = 'countryId=' + countryId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('Countries', 'changeStatus'), data, function (res) {
            searchCountry(document.srchFormPaging);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchCountry(document.srchForm);
    };
})();