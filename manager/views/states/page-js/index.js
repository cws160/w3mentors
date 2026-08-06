/* global fcom, langLbl, e */
$(document).ready(function () {
    searchStates(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchStates(frm);
    };
    reloadList = function () {
        searchStates(document.srchFormPaging);
    };
    searchStates = function (form) {
        fcom.ajax(fcom.makeUrl('States', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    form = function (id) {
        fcom.ajax(fcom.makeUrl('States', 'form', [id]), '', function (t) {
            $.w3mentorsmodal(t);
            fcom.updatePopupContent(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('States', 'setup'), data, function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.stateId, langId, false);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (statesId, langId) {
        fcom.ajax(fcom.makeUrl('States', 'langForm'), { stlang_state_id: statesId, stlang_lang_id: langId }, function (t) {
            fcom.updatePopupContent(t);
        });
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('States', 'langSetup'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.stateId, langId, false);
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
        var StatesId = parseInt(obj.id);
        var data = 'stateId=' + StatesId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('States', 'changeStatus'), data, function (res) {
            searchStates(document.srchFormPaging);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var StatesId = parseInt(obj.id);
        var data = 'stateId=' + StatesId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('States', 'changeStatus'), data, function (res) {
            searchStates(document.srchFormPaging);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchStates();
    };
})();